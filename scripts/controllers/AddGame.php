<?php

include_once "scripts/Parser.php";

/**
 * Добавление игры
 */
class AddGame extends Controller {
    /**
     * Член класса для сохранения данны о раундах, прилетевших из колбэков
     *
     * @var array
     */
    protected $_loggedRounds = array();

    /**
     * Данные о штрафах чомбо по каждому игроку
     *
     * @var array
     */
    protected $_chomboPenalties = array();

    /**
     * Показать форму добавления, если есть ошибка - вывести сообщение
     *
     * @param string $error
     */
    protected function _showForm($error = '')
    {
		$users = Db::get("SELECT username, alias FROM players ORDER BY alias");
		$aliases = array();
		foreach ($users as $v) {
			$aliases[$v['username']] = $v['alias'];
		}

        include 'templates/AddGame.php';
    }

    /**
     * Основной метод контроллера
     */
    protected function _run()
    {
        if (empty($_POST['content'])) { // пусто - показываем форму
            $this->_showForm();
        } else {
            // иначе пытаемся сохранить игру в базу
            if ($_COOKIE['secret'] != 'kldfmewmd9vbeiogbjsdvjepklsdmnvmn') {
                $this->_showForm("Секретное слово неправильное");
                return;
            }

			try {
				$parser = new Parser(
					array($this, 'cb_usualWin'),
					array($this, 'cb_yakuman'),
					array($this, 'cb_roundDrawn'),
					array($this, 'cb_chombo'),
					$this->_getRegisteredUsersList()
				);

                $this->_loggedRounds = array();
				$results = $parser->parse($_POST['content']);
				$players = $results['scores'];
				$counts = $results['counts'];
			} catch (Exception $e) {
				$this->_showForm($e->getMessage());
				return;
			}

			//////////////////////////////////////////////////////////////////////////////////

            $playerPlaces = $this->_calcPlaces($players);
            $resultScores = $this->_countResultScore($players, $playerPlaces);

            $gameId = $this->_addToDb(array(
                'players' => $players,
                'scores' => $resultScores,
                'rounds' => $this->_loggedRounds,
                'counts' => $counts
            ));

            $this->_updatePlayerRatings($playerPlaces, $resultScores, $gameId);

            echo "<h4>Игра успешно добавлена!</h4><br>";
			echo "Идем обратно через 3 секунды... <script type='text/javascript'>window.setTimeout(function() {window.location = 'add.php';}, 3000);</script>";
        }
    }

    /**
     * Получение полного списка зарегистрированных юзеров
     */
	protected function _getRegisteredUsersList()
	{
        $result = Db::get("SELECT username, alias FROM `players`");
        $regged = array();
        foreach ($result as $row) {
            $regged[$row['username']] = $row['alias'];
        }

        return $regged;
	}

    /**
     * Колбэк "ничья"
     */
    public function cb_roundDrawn($roundData /*$round*/)
    {
        $round = $roundData['round'];
        $players = serialize($roundData['players_tempai']);
        $this->_loggedRounds []= "(#GAMEID#, '', '', '{$players}', 0, 0, 0, 0, '{$round}', 'draw')";
    }

    /**
     * Колбэк "якуман"
     */
    public function cb_yakuman($roundData /*$round, $outcome, $player, $dealer*/)
    {
        $round = $roundData['round'];
        $outcome = $roundData['outcome'];
        $player = $roundData['winner'];
        $loser = empty($roundData['loser']) ? '' : $roundData['loser'];

        if (!empty($roundData['dealer'])) {
            $dealer = '1';
        } else {
            $dealer = '0';
        }

        $this->_loggedRounds []= "(#GAMEID#, '{$player}', '{$loser}', '', 0, 0, 1, {$dealer}, '{$round}', '{$outcome}')";
    }

    /**
     * Колбэк "чомбо"
     */
    public function cb_chombo($roundData /*$round, $outcome, $player, $dealer*/)
    {
        $round = $roundData['round'];
        $outcome = $roundData['outcome'];
        $player = $roundData['loser'];

        if (!empty($roundData['dealer'])) {
            $dealer = '1';
        } else {
            $dealer = '0';
        }

        $this->_loggedRounds []= "(#GAMEID#, '{$player}', '', '', 0, 0, 0, {$dealer}, '{$round}', '{$outcome}')";
		if (empty($this->_chomboPenalties[$player])) {
			$this->_chomboPenalties[$player] = -CHOMBO_PENALTY;
		} else {
			$this->_chomboPenalties[$player] -= CHOMBO_PENALTY;
		}
    }

    /**
     * Колбэк "обычный выигрыш"
     */
    public function cb_usualWin($roundData /*$round, $outcome, $player, $hanCount, $fuCount, $dealer*/)
    {
        $round = $roundData['round'];
        $outcome = $roundData['outcome'];
        $player = $roundData['winner'];
		$loser = empty($roundData['loser']) ? '' : $roundData['loser'];

        $hanCount = $roundData['han'];
        $fuCount = empty($roundData['fu']) ? '0' : $roundData['fu'];

        if (!empty($roundData['dealer'])) {
            $dealer = '1';
        } else {
            $dealer = '0';
        }

        $this->_loggedRounds []= "(#GAMEID#, '{$player}', '{$loser}', '', {$hanCount}, {$fuCount}, 0, {$dealer}, '{$round}', '{$outcome}')";
    }

    /**
     * Расчет итоговых очков.
     *
     * @param $players
     * @param $places
     * @return array
     */
    protected function _countResultScore($players, $places)
    {
        // назначаем ранговые бонуса согласно месту
        $uma = array(1 => UMA_1PLACE, UMA_2PLACE, UMA_3PLACE, UMA_4PLACE);
        foreach ($places as $k => $v) {
            $places[$k] = $uma[$v];
        }

        $resultScores = array();
        foreach ($players as $k => $v) {
            $resultScores[$k] = (($v - START_POINTS) / DIVIDER) + $places[$k];
			if (!empty($this->_chomboPenalties[$k])) {
				$resultScores[$k] += $this->_chomboPenalties[$k];
			}
        }

        return $resultScores;
    }

    /**
     * Высчитываем места игроков по их очкам
     *
     * @param $playerscores
     * @return array
     */
    protected function _calcPlaces($playerscores)
    {
        arsort($playerscores);
        $players = array_keys($playerscores);
        $scores = array_values($playerscores);

        // если есть равные очки, полагаемся на корейский рандом для распределения мест
        for ($i = 0; $i < 4; $i++) {
            for ($j = 0; $j < 4; $j++) {
                if ($i == $j) {
                    continue;
                }
                if ($scores[$i] == $scores[$j] && mt_rand(0, 1)) {
                    $tmp = $players[$i];
                    $players[$i] = $players[$j];
                    $players[$j] = $tmp;
                }
            }
        }

        return array_combine($players, array(1, 2, 3, 4));
    }

    /**
     * Обновление рейтингов игроков
     *
     * @param $playerPlaces
     * @param $resultScores
     * @param $gameId
     */
    protected function _updatePlayerRatings($playerPlaces, $resultScores, $gameId)
    {
        $playerNames = implode("', '", array_keys($resultScores));

        $currentRatings = Db::get("SELECT * FROM `players` WHERE username IN('{$playerNames}')");
        $currentRatings = ArrayHelpers::elm2Key($currentRatings, 'username');

        // заполняем дефолтным рейтингом новичков, а неновичкам - добавляем значения
        foreach (array_keys($resultScores) as $player) {
            $currentRatings[$player]['rating'] += $this->_calculateRatingChange($player, $playerPlaces, $resultScores, $currentRatings);
            $currentRatings[$player]['games_played'] ++;
            $currentRatings[$player]['places_sum'] += $playerPlaces[$player];
        }

        $this->_saveRatingsToDb($currentRatings, $gameId);
    }

    // турнир: все линейно, ничего не делаем
    protected function _calculateRatingChange($playerName, $playerPlaces, $resultScores, $currentRatings)
    {
        return $resultScores[$playerName] / RESULT_DIVIDER;
    }

    /**
     * Непосредственно сохраняем результаты рейтингов в БД
     *
     * @param $ratings
     * @param $gameId
     */
    protected function _saveRatingsToDb($ratings, $gameId)
    {
        foreach ($ratings as $playerRating) {
            $avg = ((double)$playerRating['places_sum']) / ((double)$playerRating['games_played']);
            $query = "INSERT INTO players (username, rating, games_played, places_sum, place_avg)
                VALUES ('{$playerRating['username']}', {$playerRating['rating']}, {$playerRating['games_played']}, {$playerRating['places_sum']}, {$avg})
                ON DUPLICATE KEY UPDATE rating=VALUES(rating), games_played=VALUES(games_played), places_sum=VALUES(places_sum), place_avg=VALUES(place_avg)";
            Db::exec($query);

            // adding entry to rating history
            $query = "
                INSERT INTO rating_history (username, game_id, rating) 
                VALUES ('{$playerRating['username']}', {$gameId}, {$playerRating['rating']})
            ";

            Db::exec($query);
        }
    }

    /**
     * Добавляем в БД запись об игре и всех ее раундах
     *
     * @param $data
     * @return string
     */
    protected function _addToDb($data)
    {
        $gameInsert = "INSERT INTO game (play_date, ron_count, tsumo_count, drawn_count) VALUES (CURRENT_TIMESTAMP(), {$data['counts']['ron']}, {$data['counts']['tsumo']}, {$data['counts']['draw']})";
        Db::exec($gameInsert);
        $gameId = Db::connection()->lastInsertId();

        $scores = array();
        // sort by score
        arsort($data['players']);
        $index = 1;
        foreach ($data['players'] as $name => $score) {
            $scores []= "({$gameId}, '{$name}', '{$score}', '{$data['scores'][$name]}', " . ($index++) . ")";
        }

        $scoreInsert = "INSERT INTO result_score (game_id, username, score, result_score, place) VALUES " . implode(', ', $scores);
        Db::exec($scoreInsert);

        $rounds = array_map(function($el) use($gameId) {return str_replace("#GAMEID#", $gameId, $el);}, $data['rounds']);
        $roundsInsert = "INSERT INTO round (game_id, username, loser, tempai_list, han, fu, yakuman, dealer, round, result) VALUES " . implode(', ', $rounds);
        Db::exec($roundsInsert);

        return $gameId;
    }
}
