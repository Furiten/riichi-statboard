<?php

class Graphs extends Controller {
    protected function _run()
    {
        $integralData = array();
        $gamesData = array();
        $placesData = array();
        $integralRating = 0;
        $error = '';
        $gamesCount = 0;

		$users = Db::get("SELECT username, alias FROM players");
		$aliases = array();
		foreach ($users as $v) {
			$aliases[$v['username']] = base64_decode($v['alias']);
		}

        if (!empty($_GET['user'])) {
            $user = base64_encode(rawurldecode($_GET['user']));
        } else {
            $user = '';
        }

        $ratingResults = Db::get("SELECT rating, game_id FROM rating_history WHERE username='{$user}' ORDER BY game_id");

		if (empty($ratingResults) && !empty($user)) {
			$userData = Db::get("SELECT username FROM players WHERE alias = '{$user}'");
			if (empty($userData)) {
				$ratingResults = false;
			} else { // второй шанс по алиасу
				$user = $userData[0]['username'];
				$ratingResults = Db::get("SELECT rating, game_id FROM rating_history WHERE username='{$user}' ORDER BY game_id");
			}
		}

        if (empty($ratingResults) && !empty($user)) { // все еще пусто?
            $error = "Нет такого пользователя в базе";
        } else {
            if (!empty($user)) {
                $gameResults = $this->_getGameResults($ratingResults);
                $gamesData = $this->_getGamesData($gameResults);

                $data = $this->_getPlacesData($gameResults, $user);
                $placesData = $data['places'];
                $gamesCount = $data['games_count'];

                $handsData = $this->_getHandsData($user);

                $graphData = array(0 => array(0, 1500));
                $i = 1;
                foreach ($ratingResults as $row) {
                    $graphData []= array($i++, floor($row['rating']));
                    $integralData []= $row['rating'];
                }
            }
        }

        $integralRating = $this->_integral($integralData);
        include "templates/Graphs.php";
    }

    protected function _getGameResults($ratingResults)
    {
        $gameIds = array_map(function($el) {return $el['game_id'];}, $ratingResults);
        $gameIds = implode(',', $gameIds);
        return Db::get("SELECT * FROM result_score WHERE game_id IN({$gameIds})");
    }

    protected function _getGamesData($gamesResults)
    {
        $games = array();
        foreach ($gamesResults as $row) {
            if (empty($games[$row['game_id']])) {
                $games[$row['game_id']] = array();
            }
            $games[$row['game_id']] []= $row;
        }

        ksort($games);
        return array_values($games);
    }

    protected function _getPlacesData($gamesResults, $username)
    {
        $places = array(1 => 0, 2 => 0, 3 => 0, 4 => 0);
        $gamesCount = 0;

        foreach ($gamesResults as $row) {
            if ($row['username'] == $username) {
                $places[(int)$row['place']] ++;
                $gamesCount ++;
            }
        }

        foreach ($places as $k => $v) {
            $places[$k] = 100. * floatval($v) / $gamesCount;
        }

        return array(
            'places' => $places,
            'games_count' => $gamesCount
        );
    }

    protected function _integral($integralData)
    {
        $integralResult = 0;
        $dataCount = count($integralData);
        for($i = 1; $i < $dataCount; $i++) {
            $integralResult += (
                ($integralData[$i-1] - 1500) +
                ($integralData[$i] - 1500)
            ) / 2.;
        }
        return $integralResult;
    }

    protected function _getHandsData($user)
    {
        $roundsData = Db::get("SELECT * FROM round WHERE username = '{$user}'");
        $roundsWon = count($roundsData);
        $yakumanCount = 0;
        $hands = array(
            1 => 0,
            2 => 0,
            3 => 0,
            4 => 0,
            5 => 0,
            6 => 0,
            7 => 0,
            8 => 0,
            9 => 0,
            10 => 0,
            11 => 0,
            12 => 0,
            '13+' => 0
        );
        $ronCount = 0;
        $tsumoCount = 0;
        $chomboCount = 0;
        foreach ($roundsData as $round) {
            if ($round['yakuman']) {
                $yakumanCount ++;
            }

            if ($round['result'] == 'ron') {
                $ronCount ++;
            }

            if ($round['result'] == 'tsumo') {
                $tsumoCount ++;
            }

            if ($round['result'] == 'chombo') {
                $chomboCount ++;
		continue;
            }

            $hands[$round['han']] ++;
        }

        $hands['13+'] = $yakumanCount;

        return array(
            'rounds_won' => $roundsWon,
            'ron' => $ronCount,
            'tsumo' => $tsumoCount,
            'chombo' => $chomboCount,
            'hands' => $hands
        );
    }
}
