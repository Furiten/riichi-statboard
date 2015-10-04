<?php

include_once "scripts/ArrayHelpers.php";

/**
 * Добавление онлайн-игры
 */
class AddOnlineGame extends Controller {
    protected function _toLocalYaku($yakuList) {
        $tenhouYakuMap = array(
            0  => 36,
            1  => 33,
            2  => 35,
            3  => 42,
            4  => 38,
            5  => 37,
            6  => 41,
            7  => 8,
            8  => 23,
            9  => 9,
//        10 => 13, // yakuhai place wind ton
//        11 => 13, // yakuhai place wind nan
//        12 => 13, // yakuhai place wind sha
//        13 => 13, // yakuhai place wind pei
//        14 => 13, // yakuhai round wind ton
//        15 => 13, // yakuhai round wind nan
//        16 => 13, // yakuhai round wind sha
//        17 => 13, // yakuhai round wind pei
//        18 => 13, // yakuhai haku
//        19 => 13, // yakuhai hatsu
//        20 => 13, // yakuhai chun
            21 => 34,
            22 => 31,
            23 => 24,
            24 => 12,
            25 => 11,
            26 => 4,
            27 => 5,
            28 => 1,
            29 => 3,
            30 => 18,
            31 => 2,
            32 => 10,
            33 => 25,
            34 => 27,
            35 => 28,
            36 => 43,
            37 => 39,
            38 => 40,
            39 => 19,
            40 => 7,
            41 => 7, // tanki
            42 => 22,
            43 => 30,
            44 => 26,
            45 => 29,
            46 => 29, // 9-machi
            47 => 32,
            48 => 32, // 13-machi
            49 => 21,
            50 => 20,
            51 => 6
//        52 => -1, // dora
//        53 => -1, // uradora
//        54 => -1 // akadora
        );

        $yakuhaiCountMap = array(1 => '13', '14', '15', '16', '17');

        $result = array(
            'yaku' => array(),
            'dora' => 0
        );
        $yakuhaiCount = 0;
        for ($i = 0; $i < count($yakuList); $i+=2) {
            $key = $yakuList[$i];
            $value = $yakuList[$i+1];

            if ($key >= 52 && $key <= 54) {
                $result['dora'] += $value;
            } elseif ($key >= 10 && $key <= 20) {
                $yakuhaiCount++;
            } else {
                $result['yaku'] []= $tenhouYakuMap[$key];
            }
        }

        if ($yakuhaiCount > 0) {
            $result['yaku'] []= $yakuhaiCountMap[$yakuhaiCount];
        }

        return $result;
    }

    /**
     * Член класса для сохранения данны о раундах, прилетевших из колбэков
     *
     * @var array
     */
    protected $_loggedRounds = array();

    /**
     * Показать форму добавления, если есть ошибка - вывести сообщение
     *
     * @param string $error
     */
    protected function _showForm($error = '') {
		include 'templates/AddOnlineGame.php';
    }

    protected function _checkLobby($paifu) {
        $regex = "#<GO.*?lobby=\"(\d+)\"/>#is";
        $matches = array();
        if (preg_match($regex, $paifu, $matches)) {
            if ($matches[1] == ALLOWED_LOBBY) return;
        }
        throw new Exception('This replay is not from this tournament');
    }

    /**
     * Основной метод контроллера
     */
    protected function _run() {
        if (empty($_POST['log'])) { // пусто - показываем форму
            $this->_showForm();
        } else {
			try {
                list($replayHash, $paifuContent) = $this->_getContent($_POST['log']);
                // пример: http://e.mjv.jp/0/log/plainfiles.cgi?2015082718gm-0009-7994-2254c66d
                $this->_checkLobby($paifuContent);
                list($counts, $usernames) = $this->_parseRounds($paifuContent);
                $players = array_combine($usernames, $this->_parseOutcome($paifuContent));
            } catch (Exception $e) {
				$this->_showForm($e->getMessage());
				return;
			}

			//////////////////////////////////////////////////////////////////////////////////

            $playerPlaces = $this->_calcPlaces($players);
            $resultScores = $this->_countResultScore($players, $playerPlaces);
            $this->_registerUsers($usernames);

            $gameId = $this->_addToDb(array(
                'originalLink' => $_POST['log'],
                'replayHash' => $replayHash,
                'players' => $players,
                'scores' => $resultScores,
                'rounds' => $this->_loggedRounds,
                'counts' => $counts
            ));

            $this->_updatePlayerRatings($playerPlaces, $resultScores, $gameId);

            echo "<h4>Игра успешно добавлена!</h4><br>";
			echo "Идем обратно через 3 секунды... <script type='text/javascript'>window.setTimeout(function() {window.location = '/addonline/';}, 3000);</script>";
        }
    }

    /**
     * Расчет итоговых очков.
     *
     * @param $players
     * @param $places
     * @return array
     */
    protected function _countResultScore($players, $places) {
        // назначаем ранговые бонуса согласно месту
        $uma = array(1 => UMA_1PLACE, UMA_2PLACE, UMA_3PLACE, UMA_4PLACE);
        foreach ($places as $k => $v) {
            $places[$k] = $uma[$v];
        }

        $resultScores = array();
        foreach ($players as $k => $v) {
            $resultScores[$k] = (($v - START_POINTS) / DIVIDER) + $places[$k];
        }

        return $resultScores;
    }

    /**
     * Высчитываем места игроков по их очкам
     *
     * @param $playerscores
     * @return array
     */
    protected function _calcPlaces($playerscores) {
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
    protected function _updatePlayerRatings($playerPlaces, $resultScores, $gameId) {
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
    protected function _calculateRatingChange($playerName, $playerPlaces, $resultScores, $currentRatings) {
        return $resultScores[$playerName] / RESULT_DIVIDER;
    }

    /**
     * Непосредственно сохраняем результаты рейтингов в БД
     *
     * @param $ratings
     * @param $gameId
     */
    protected function _saveRatingsToDb($ratings, $gameId) {
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
     * Регистрируем юзеров, участвующих в реплее
     * @param $users
     */
    protected function _registerUsers($users) {
        foreach ($users as $user) {
            Db::exec("INSERT INTO players (username, alias, rating, games_played, places_sum)
                VALUES ('{$user}', '{$user}', 1500, 0, 0)
                ON DUPLICATE KEY UPDATE alias=VALUES(alias)");
        }
    }

    /**
     * Добавляем в БД запись об игре и всех ее раундах
     *
     * @param $data
     * @return string
     */
    protected function _addToDb($data) {
        $gameInsert = "INSERT INTO game (orig_link, replay_hash, play_date, ron_count, tsumo_count, drawn_count) VALUES (
            '{$data['originalLink']}', '{$data['replayHash']}', CURRENT_TIMESTAMP(),
            {$data['counts']['ron']}, {$data['counts']['tsumo']}, {$data['counts']['draw']}
        )";
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
        $roundsInsert = "INSERT INTO round (game_id, username, loser, tempai_list, han, fu, yakuman, dealer, round, result, yaku, dora) VALUES " . implode(', ', $rounds);
        Db::exec($roundsInsert);

        return $gameId;
    }

    /**
     * Проверяем, а не добавили ли мы уже эту игру
     *
     * @param $replayHash
     * @return bool
     */
    protected function _alreadyAdded($replayHash) {
        $checkQuery = reset(Db::get("SELECT COUNT(*) as cnt FROM game WHERE replay_hash = '{$replayHash}'"));
        return isset($checkQuery['cnt']) && $checkQuery['cnt'] > 0;
    }

    /**
     * Раскодируем тенховский хеш
     *
     * @param $log
     * @return string
     */
    protected function _decodeHash($log) {
        $t = json_decode(base64_decode("WzIyMTM2LDUyNzE5LDU1MTQ2LDQyMTA0LDU5NTkxLDQ2OTM0LDkyNDgsMjg4OTEsNDk1OTcsNTI5NzQsNjI4NDQsNDAxNSwxODMxMSw1MDczMCw0MzA1NiwxNzkzOSw2NDgzOCwzODE0NSwyNzAwOCwzOTEyOCwzNTY1Miw2MzQwNyw2NTUzNSwyMzQ3MywzNTE2NCw1NTIzMCwyNzUzNiw0Mzg2LDY0OTIwLDI5MDc1LDQyNjE3LDE3Mjk0LDE4ODY4LDIwODFd"));
        $parts = explode('-', $log);
        if (count($parts) != 4) {
            return $log;
        }

        if (ord($parts[3][0]) == 120) {
            $hexparts = array(
                hexdec(substr($parts[3], 1, 4)),
                hexdec(substr($parts[3], 5, 4)),
                hexdec(substr($parts[3], 9, 4)),
                0
            );

            if ($parts[0] >= base64_decode('MjAxMDA0MTExMWdt')) {
                $hexparts[3] = intval("3" . substr($parts[0], 4, 6)) % (17 * 2 - intval(substr($parts[0], 9, 1)) - 1);
            }
            $parts[3] = dechex($hexparts[0] ^ $hexparts[1] ^ $t[$hexparts[3] + 0]) .
                dechex($hexparts[1] ^ $t[$hexparts[3] + 0] ^ $hexparts[2] ^ $t[$hexparts[3] + 1]);

            $parts[3] = str_repeat('0', 8 - strlen($parts[3])) . $parts[3];
        }

        return join('-', $parts);
    }

    /**
     * Получаем данные лога
     *
     * @param $logUrl
     * @return array
     * @throws Exception
     */
    protected function _getContent($logUrl) {
        $queryString = parse_url($logUrl, PHP_URL_QUERY);
        parse_str($queryString, $out);
        $logHash = $this->_decodeHash($out['log']);
        if ($this->_alreadyAdded($logHash)) {
            throw new Exception('This replay is already in our DB!');
        }

        $logUrl = base64_decode("aHR0cDovL2UubWp2LmpwLzAvbG9nL3BsYWluZmlsZXMuY2dpPw==") . $logHash;
        $fallbackLogUrl = base64_decode("aHR0cDovL2UubWp2LmpwLzAvbG9nL2FyY2hpdmVkLmNnaT8=") . $logHash;

        $content = @file_get_contents($logUrl);
        if (!$content) {
            $content = @file_get_contents($fallbackLogUrl);
            if (!$content) {
                throw new Exception('Content fetch failed: format changed? Contact heilage.nsk@gmail.com for instructions');
            }
        }

        return array($logHash, $content);
    }

    /**
     * Парсим результаты из содержимого ответа
     *
     * @param $content
     * @return array
     */
    protected function _parseOutcome($content) {
        $regex = "#owari=\"([^\"]*)\"#";
        $matches = array();
        if (preg_match($regex, $content, $matches)) {
            $parts = explode(',', $matches[1]);
            return array(
                $parts[0] . '00',
                $parts[2] . '00',
                $parts[4] . '00',
                $parts[6] . '00'
            );
        }

        return false;
    }


    /**
     * Колбэк "ничья"
     */
    public function cb_roundDrawn($roundData /*$round*/) {
        $round = $roundData['round'];
        $players = serialize($roundData['players_tempai']);
        $this->_loggedRounds []= "(#GAMEID#, '', '', '{$players}', 0, 0, 0, 0, '{$round}', 'draw', '', 0)";
    }

    /**
     * Колбэк "якуман"
     */
    public function cb_yakuman($roundData /*$round, $outcome, $player, $dealer*/) {
        $round = $roundData['round'];
        $outcome = $roundData['outcome'];
        $player = $roundData['winner'];
        $loser = empty($roundData['loser']) ? '' : $roundData['loser'];
        $yaku = implode(',', $roundData['yaku']);

        if (!empty($roundData['dealer'])) {
            $dealer = '1';
        } else {
            $dealer = '0';
        }

        $this->_loggedRounds []= "(#GAMEID#, '{$player}', '{$loser}', '', 0, 0, 1, {$dealer}, '{$round}', '{$outcome}', '{$yaku}', 0)";
    }

    /**
     * Колбэк "обычный выигрыш"
     */
    public function cb_usualWin($roundData /*$round, $outcome, $player, $hanCount, $fuCount, $dealer*/) {
        $round = $roundData['round'];
        $outcome = $roundData['outcome'];
        $player = $roundData['winner'];
        $loser = empty($roundData['loser']) ? '' : $roundData['loser'];
        $yaku = implode(',', $roundData['yaku']);
        $dora = intval($roundData['dora']);

        $hanCount = $roundData['han'];
        $fuCount = empty($roundData['fu']) ? '0' : $roundData['fu'];

        if (!empty($roundData['dealer'])) {
            $dealer = '1';
        } else {
            $dealer = '0';
        }

        $this->_loggedRounds []= "(#GAMEID#, '{$player}', '{$loser}', '', {$hanCount}, {$fuCount}, 0, {$dealer}, '{$round}', '{$outcome}', '{$yaku}', {$dora})";
    }

    protected function _parseRounds($content) {
        $currentDealer = '0';
        $currentRound = 1;
        $usernames = array();
        $counts = array(
            'ron' => 0,
            'tsumo' => 0,
            'draw' => 0
        );

        $reader = new XMLReader();
        $reader->xml($content);
        while ($reader->read()) {
            if ($reader->nodeType != XMLReader::ELEMENT) continue;
            switch($reader->localName) {
                case 'UN':
                    if (count($usernames) == 0) {
                        $usernames = array(
                            rawurldecode($reader->getAttribute('n0')),
                            rawurldecode($reader->getAttribute('n1')),
                            rawurldecode($reader->getAttribute('n2')),
                            rawurldecode($reader->getAttribute('n3'))
                        );
                    }
                    break;
                case 'INIT':
                    $newDealer = $reader->getAttribute('oya');
                    if ($currentDealer != $newDealer) {
                        $currentRound ++;
                        $currentDealer = $newDealer;
                    }
                    break;
                case 'AGARI':
                    $winner = $reader->getAttribute('who');
                    $loser = $reader->getAttribute('fromWho');
                    $dealerWins = ($winner == $currentDealer ? '1' : '0');
                    $outcomeType = ($winner == $loser ? 'tsumo' : 'ron');

                    $counts[$outcomeType]++;

                    list($fu, $points) = explode(',', $reader->getAttribute('ten'));
                    $yakuList = explode(',', $reader->getAttribute('yaku'));

                    $hanSum = 0;
                    for ($i = 1; $i < count($yakuList); $i+=2) {
                        $hanSum += $yakuList[$i];
                    }
                    $yakuAndDora = $this->_toLocalYaku($yakuList);

                    if ($hanSum > 12) {
                        $this->cb_yakuman(array(
                            'round' => $currentRound,
                            'outcome' => $outcomeType,
                            'winner' => $usernames[$winner],
                            'loser' => $usernames[$loser],
                            'dealer' => $dealerWins,
                            'yaku' => $yakuAndDora['yaku']
                        ));
                    } else {
                        $this->cb_usualWin(array(
                            'round' => $currentRound,
                            'outcome' => $outcomeType,
                            'winner' => $usernames[$winner],
                            'loser' => $usernames[$loser],
                            'dealer' => $dealerWins,
                            'han' => $hanSum,
                            'fu' => $fu,
                            'yaku' => $yakuAndDora['yaku'],
                            'dora' => $yakuAndDora['dora']
                        ));
                    }

                    break;
                case 'RYUUKYOKU':
                    if ($reader->getAttribute('type')) {
                        // пересдача
                        $this->cb_roundDrawn(array());
                    } else {
                        $scores = array_filter(explode(',', $reader->getAttribute('sc')));
                        $users = array();
                        for ($i = 0; $i < count($scores); $i++) {
                            if (empty($usernames[$i])) continue;
                            $users[$usernames[$i]] = (intval($scores[$i*2+1]) >= 0 ? 'tempai' : 'noten');
                        }
                        $this->cb_roundDrawn(array(
                            'round' => $currentRound,
                            'players_tempai' => $users
                        ));
                    }
                    $counts['draw']++;
                    break;
                default:;
            }
        }

        return array($counts, $usernames);
    }
}
