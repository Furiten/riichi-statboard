<?php

include_once 'scripts/helpers/Yaku.php';

class Graphs extends Controller {
    protected function _run()
    {
        $integralData = [];
        $gamesData = [];
        $placesData = [];
        $integralRating = 0;
        $error = '';
        $gamesCount = 0;

		$users = Db::get("SELECT username, alias FROM players");
		$aliases = [];
		foreach ($users as $v) {
			$aliases[$v['username']] = IS_ONLINE ? base64_decode($v['alias']) : $v['alias'];
		}

        if (!empty($_GET['user'])) {
            $user = IS_ONLINE ? base64_encode(rawurldecode($_GET['user'])) : rawurldecode($_GET['user']);
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
                $roundsData = $this->_getRoundsData($user);

                $graphData = [0 => [0, 1500]];
                $i = 1;
                foreach ($ratingResults as $row) {
                    $graphData []= [$i++, floor($row['rating'])];
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
        $games = [];
        foreach ($gamesResults as $row) {
            if (empty($games[$row['game_id']])) {
                $games[$row['game_id']] = [];
            }
            $games[$row['game_id']] []= $row;
        }

        ksort($games);
        return array_values($games);
    }

    protected function _getRoundsData($user) {
        $roundsData = Db::get("
            SELECT round.username, loser, riichi
            FROM round
            JOIN result_score ON ( result_score.game_id = round.game_id )
            WHERE result_score.username = '{$user}'
        ");

        $furikomiCount = 0;
        $furikomiAtRiichi = 0;
        $riichiCount = 0;
        $riichiWon = 0;
        $riichiLost = 0;

        foreach ($roundsData as $round) {
            $riichi = [];
            if (!empty($round['riichi'])) {
                $riichi = @unserialize($round['riichi']);
            }

            if (in_array($user, $riichi)) {
                $riichiCount ++;
            }

            if ($round['loser'] == $user) {
                $furikomiCount ++;
                if (in_array($user, $riichi)) {
                    $furikomiAtRiichi ++;
                    $riichiLost ++;
                }
            } else if ($round['username'] == $user) {
                if (in_array($user, $riichi)) {
                    $riichiWon ++;
                }
            } else {
                if (in_array($user, $riichi)) {
                    $riichiLost ++;
                }
            }
        }

        return [
            'furikomi_total' => $furikomiCount,
            'furikomi_riichi' => $furikomiAtRiichi,
            'rounds_played' => count($roundsData),
            'riichi_bets' => $riichiCount,
            'riichi_won' => $riichiWon,
            'riichi_lost' => $riichiLost
        ];
    }

    protected function _getPlacesData($gamesResults, $username)
    {
        $places = [1 => 0, 2 => 0, 3 => 0, 4 => 0];
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

        return [
            'places' => $places,
            'games_count' => $gamesCount
        ];
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
        $hands = [
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
            '★' => 0
        ];
        $ronCount = 0;
        $tsumoCount = 0;
        $chomboCount = 0;

        $yaku = [];

        foreach ($roundsData as $round) {
            // count yaku
            $yakuSplit = explode(',', $round['yaku']);
            array_map(function($el) use (&$yaku) {
                $key = YakuHelper::getString($el);
                if (!isset($yaku[$key])) {
                    $yaku[$key] = 0;
                }
                $yaku[$key] ++;
            }, $yakuSplit);

            // count outcomes
            if ($round['yakuman']) {
                $yakumanCount ++;
                continue;
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

        $hands['★'] = $yakumanCount;

        return [
            'rounds_won' => $roundsWon,
            'ron' => $ronCount,
            'tsumo' => $tsumoCount,
            'chombo' => $chomboCount,
            'hands' => $hands,
            'yaku' => $yaku
        ];
    }
}
