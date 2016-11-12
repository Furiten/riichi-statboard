<?php

class Graphs extends Controller
{
    protected $_mainTemplate = 'Graphs';
    protected function _run()
    {
        try {
            $currentUser = intval($_GET['user']);
            $playerData = $this->_api->execute('getPlayer', [$currentUser]);
            $data = $this->_api->execute('getPlayerStats', [$currentUser, TOURNAMENT_ID]);

            $usersMap = [];
            foreach ($data['players_info'] as $player) {
                $usersMap[$player['id']] = $player;
            }

            $integralData = [];
            $graphData = [];
            $i = 0;
            foreach ($data['rating_history'] as $row) {
                $graphData []= [$i++, floor($row['rating'])];
                $integralData []= $row['rating'];
            }

            $handValueStats = [];
            foreach ($data['hands_value_summary'] as $han => $count) {
                $handValueStats []= [(string)$han, $count];
            }

            $yakuStats = [];
            foreach ($data['yaku'] as $yaku => $count) {
                $yakuStats []= [$count, Yaku::getMap()[$yaku]];
            }

            return [
                'playerData' => $playerData,
                'data' => empty($data['score_history']) ? null : [
                    'currentPlayer' => $currentUser,
                    'playersMap' => json_encode($usersMap),
                    'points' => json_encode($graphData),
                    'games' => json_encode($data['score_history']),
                    'handValueStats' => json_encode($handValueStats),
                    'yakuStats' => json_encode($yakuStats),
                    'integralRating' => $this->_integral($integralData),
                    'ronCount' => $data['win_summary']['ron'],
                    'ronCountPercent' => $data['win_summary']['ron'] * 100. / $data['total_played_rounds'],
                    'tsumoCount' => $data['win_summary']['tsumo'],
                    'tsumoCountPercent' => $data['win_summary']['tsumo'] * 100. / $data['total_played_rounds'],
                    'winCount' => $data['win_summary']['ron'] + $data['win_summary']['tsumo'],
                    'winCountPercent' => ($data['win_summary']['ron'] + $data['win_summary']['tsumo'])
                        * 100. / $data['total_played_rounds'],
                    'feedCount' => $data['win_summary']['feed'],
                    'feedCountPercent' => $data['win_summary']['feed'] * 100. / $data['total_played_rounds'],
                    'feedUnderRiichi' => $data['riichi_summary']['feed_under_riichi'],
                    'feedUnderRiichiPercent' => $data['riichi_summary']['feed_under_riichi']
                        * 100. / $data['total_played_rounds'],
                    'tsumoFeedCount' => $data['win_summary']['tsumofeed'],
                    'tsumoFeedCountPercent' => $data['win_summary']['tsumofeed'] * 100. / $data['total_played_rounds'],
                    'chomboCount' => $data['win_summary']['chombo'],
                    'chomboCountPercent' => $data['win_summary']['chombo'] * 100. / $data['total_played_rounds'],
                    'riichiWon' => $data['riichi_summary']['riichi_won'],
                    'riichiLost' => $data['riichi_summary']['riichi_lost'],
                    'riichiTotal' => $data['riichi_summary']['riichi_won'] + $data['riichi_summary']['riichi_lost'],
                    'riichiWonPercent' => $data['riichi_summary']['riichi_won'] * 100.
                        / ($data['riichi_summary']['riichi_won'] + $data['riichi_summary']['riichi_lost']),
                    'riichiLostPercent' => $data['riichi_summary']['riichi_lost'] * 100.
                        / ($data['riichi_summary']['riichi_won'] + $data['riichi_summary']['riichi_lost']),
                    'riichiTotalPercent' => ($data['riichi_summary']['riichi_won'] + $data['riichi_summary']['riichi_lost'])
                        * 100. / $data['total_played_rounds'],
                    'place1' => $data['places_summary'][1] * 100. / array_sum($data['places_summary']),
                    'place2' => $data['places_summary'][2] * 100. / array_sum($data['places_summary']),
                    'place3' => $data['places_summary'][3] * 100. / array_sum($data['places_summary']),
                    'place4' => $data['places_summary'][4] * 100. / array_sum($data['places_summary']),
                ],
                'error' => null
            ];
        } catch (Exception $e) {
            return [
                'data' => null,
                'error' => "Нет данных по указанному пользователю"
            ];
        }
    }

    protected function _integral($integralData)
    {
        $integralResult = 0;
        $dataCount = count($integralData);
        for ($i = 1; $i < $dataCount; $i++) {
            $integralResult += (
                ($integralData[$i-1] - 1500) +
                ($integralData[$i] - 1500)
            ) / 2.;
        }
        return $integralResult;
    }
}
