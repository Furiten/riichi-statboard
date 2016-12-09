<?php

include_once "scripts/helpers/Array.php";
include_once "scripts/helpers/Yaku.php";

/**
 * Добавление онлайн-игры
 */
class AddOnlineGame extends Controller {
    /**
     * Член класса для сохранения данны о раундах, прилетевших из колбэков
     *
     * @var array
     */
    protected $_loggedRounds = [];

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
        $matches = [];
        if (preg_match($regex, $paifu, $matches)) {
            if ($matches[1] == ALLOWED_LOBBY) {
                return;
            }
        }
        throw new Exception('This replay is not from this tournament');
    }

    protected function _checkGameExpired($replayHash) {
        $regex = '#(?<year>\d{4})(?<month>\d{2})(?<day>\d{2})(?<hour>\d{2})gm#is';
        $matches = [];
        if (preg_match($regex, $replayHash, $matches)) {
            $date = mktime($matches['hour'], 0, 0, $matches['month'], $matches['day'], $matches['year']);
            if (time() - $date < 27*60*60) { // 27 часов, чтобы покрыть разницу с JST
                return;
            }
        }

        throw new Exception('Добавляемая игра сыграна более чем сутки назад. Игра не принята из-за истечения срока годности.');
    }

    /**
     * @param $link
     * @param $isReplay если добавление игры происходит в процессе перезаливки пайфу
     */
    protected function _addGame($link, $isReplay = false) {
        list($replayHash, $paifuContent) = $this->_getContent($link);
        if (!$isReplay) {
            $this->_checkGameExpired($replayHash);
        }
        // пример: http://e.mjv.jp/0/log/plainfiles.cgi?2015082718gm-0009-7994-2254c66d
        $this->_checkLobby($paifuContent);
        list($counts, $usernames) = $this->_parseRounds($paifuContent);
        $players = array_combine($usernames, $this->_parseOutcome($paifuContent));

        //////////////////////////////////////////////////////////////////////////////////

        $playerPlaces = $this->_calcPlaces($players);
        $resultScores = $this->_countResultScore($players, $playerPlaces);
        $this->_registerUsers($usernames);

        $gameId = $this->_addToDb([
            'originalLink' => $link,
            'replayHash' => $replayHash,
            'players' => $players,
            'scores' => $resultScores,
            'rounds' => $this->_loggedRounds,
            'counts' => $counts
        ]);

        $this->_updatePlayerRatings($playerPlaces, $resultScores, $gameId);
    }

    public function externalAddGame($link) { // паблик морозов
        $this->_loggedRounds = [];
        $this->_addGame($link, true);
    }

    /**
     * Основной метод контроллера
     */
    protected function _run() {
        if (empty($_POST['log'])) { // пусто - показываем форму
            $this->_showForm();
        } else {
            try {
                $this->_addGame($_POST['log']);
            } catch (Exception $e) {
                $this->_showForm($e->getMessage());
                return;
            }

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
        $uma = [1 => UMA_1PLACE, UMA_2PLACE, UMA_3PLACE, UMA_4PLACE];
        foreach ($places as $k => $v) {
            $places[$k] = $uma[$v];
        }

        $resultScores = [];
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

        return array_combine($players, [1, 2, 3, 4]);
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
        if ($currentRatings[$playerName]['games_played'] < 60) {
            $adj = 1 - $currentRatings[$playerName]['games_played'] * 0.01;
        } else {
            $adj = 0.4;
        }

        return $adj * ($resultScores[$playerName] / 20.);
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
     * @throws Exception
     * @param $data
     * @return string
     */
    protected function _addToDb($data) {
        if ($this->_alreadyAdded($data['replayHash'])) {
            throw new Exception('Упц. Эта игра уже зарегистрирована в нашей базе. Кто-то успел раньше вас? :)');
        }

        $gameInsert = "INSERT INTO game (orig_link, replay_hash, play_date, ron_count, tsumo_count, drawn_count) VALUES (
            '{$data['originalLink']}', '{$data['replayHash']}', CURRENT_TIMESTAMP(),
            {$data['counts']['ron']}, {$data['counts']['tsumo']}, {$data['counts']['draw']}
        )";
        Db::exec($gameInsert);
        $gameId = Db::connection()->lastInsertId();

        $scores = [];
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
        $game = Db::get("SELECT COUNT(*) as cnt FROM game WHERE replay_hash = '{$replayHash}'");
        $checkQuery = reset($game);
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
            $hexparts = [
                hexdec(substr($parts[3], 1, 4)),
                hexdec(substr($parts[3], 5, 4)),
                hexdec(substr($parts[3], 9, 4)),
                0
            ];

            if ($parts[0] >= base64_decode('MjAxMDA0MTExMWdt')) {
                $hexparts[3] = intval("3" . substr($parts[0], 4, 6)) % (17 * 2 - intval(substr($parts[0], 9, 1)) - 1);
            }

            $hashHead = dechex($hexparts[0] ^ $hexparts[1] ^ $t[$hexparts[3] + 0]);
            $hashTail = dechex($hexparts[1] ^ $t[$hexparts[3] + 0] ^ $hexparts[2] ^ $t[$hexparts[3] + 1]);
            $hashHead = str_repeat('0', 4 - strlen($hashHead)) . $hashHead;
            $hashTail = str_repeat('0', 4 - strlen($hashTail)) . $hashTail;
            $parts[3] = $hashHead . $hashTail;
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

        return [$logHash, $content];
    }

    /**
     * Парсим результаты из содержимого ответа
     *
     * @param $content
     * @return array
     */
    protected function _parseOutcome($content) {
        $regex = "#owari=\"([^\"]*)\"#";
        $matches = [];
        if (preg_match($regex, $content, $matches)) {
            $parts = explode(',', $matches[1]);
            return [
                $parts[0] . '00',
                $parts[2] . '00',
                $parts[4] . '00',
                $parts[6] . '00'
            ];
        }

        return false;
    }


    /**
     * Колбэк "ничья"
     */
    public function cb_roundDrawn($roundData /*$round*/) {
        $round = $roundData['round'];
        if (!empty($roundData['players_tempai'])) {
            $players = serialize($roundData['players_tempai']);
        } else {
            $players = '';
        }
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
        $usernames = [];
        $counts = [
            'ron' => 0,
            'tsumo' => 0,
            'draw' => 0
        ];

        $reader = new XMLReader();
        $reader->xml($content);
        while ($reader->read()) {
            if ($reader->nodeType != XMLReader::ELEMENT) continue;
            switch($reader->localName) {
                case 'UN':
                    if (count($usernames) == 0) {
                        $usernames = [
                            base64_encode(rawurldecode($reader->getAttribute('n0'))),
                            base64_encode(rawurldecode($reader->getAttribute('n1'))),
                            base64_encode(rawurldecode($reader->getAttribute('n2'))),
                            base64_encode(rawurldecode($reader->getAttribute('n3')))
                        ];

                        if (in_array('NoName', $usernames)) {
                            throw new Exception('В рейтинг не допускаются игры с безымянными игроками.');
                        }
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
                    $yakuList = $reader->getAttribute('yaku');
                    $yakumanList = $reader->getAttribute('yakuman');

                    $hanSum = YakuHelper::getHanSum($yakuList);
                    $yakuAndDora = YakuHelper::getLocalYaku($yakuList, $yakumanList);

                    if ($hanSum > 12 || !empty($yakumanList)) {
                        $this->cb_yakuman([
                            'round' => $currentRound,
                            'outcome' => $outcomeType,
                            'winner' => $usernames[$winner],
                            'loser' => $usernames[$loser],
                            'dealer' => $dealerWins,
                            'yaku' => $yakuAndDora['yaku']
                        ]);
                    } else {
                        $this->cb_usualWin([
                            'round' => $currentRound,
                            'outcome' => $outcomeType,
                            'winner' => $usernames[$winner],
                            'loser' => $usernames[$loser],
                            'dealer' => $dealerWins,
                            'han' => $hanSum,
                            'fu' => $fu,
                            'yaku' => $yakuAndDora['yaku'],
                            'dora' => $yakuAndDora['dora']
                        ]);
                    }

                    break;
                case 'RYUUKYOKU':
                    if ($reader->getAttribute('type')) {
                        // пересдача
                        $this->cb_roundDrawn([
                            'round' => $currentRound
                        ]);
                    } else {
                        $scores = array_filter(explode(',', $reader->getAttribute('sc')));
                        $users = [];
                        for ($i = 0; $i < count($usernames); $i++) {
                            if (empty($usernames[$i])) continue;
                            $users[$usernames[$i]] = (intval($scores[$i*2+1]) >= 0 ? 'tempai' : 'noten');
                        }
                        $this->cb_roundDrawn([
                            'round' => $currentRound,
                            'players_tempai' => $users
                        ]);
                    }
                    $counts['draw']++;
                    break;
                default:;
            }
        }

        return [$counts, $usernames];
    }
}
