<?php

require_once __DIR__ . '/../helpers/YakuMap.php';

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
            foreach ($data['rating_history'] as $rating) {
                $graphData []= [$i++, floor($rating)];
                $integralData []= $rating;
            }

            $handValueStats = [];
            $yakumanCount = 0;
            foreach ($data['hands_value_summary'] as $han => $count) {
                if ($han > 0) {
                    $handValueStats []= [(string)$han, $count];
                } else {
                    $yakumanCount += $count;
                }
            }
            if ($yakumanCount > 0) {
                $handValueStats [] = ['★', $yakumanCount];
            }

            $yakuStats = [];
            foreach ($data['yaku_summary'] as $yaku => $count) {
                $yakuStats []= [$count, Yaku::getMap()[$yaku]];
            }

            return [
                'playerData' => $playerData,
                'data' => empty($data['score_history']) ? null : [
                    'currentPlayer' => $currentUser,

                    'playersMap'     => json_encode($usersMap),
                    'points'         => json_encode($graphData),
                    'games'          => json_encode($data['score_history']),
                    'handValueStats' => json_encode($handValueStats),
                    'yakuStats'      => json_encode($yakuStats),

                    'integralRating' => $this->_integral($integralData),

                    'ronCount'          => $data['win_summary']['ron'],
                    'tsumoCount'        => $data['win_summary']['tsumo'],
                    'winCount'          => $data['win_summary']['ron'] + $data['win_summary']['tsumo'],
                    'feedCount'         => $data['win_summary']['feed'],
                    'feedUnderRiichi'   => $data['riichi_summary']['feed_under_riichi'],
                    'tsumoFeedCount'    => $data['win_summary']['tsumofeed'],
                    'chomboCount'       => $data['win_summary']['chombo'],
                    'riichiWon'         => $data['riichi_summary']['riichi_won'],
                    'riichiLost'        => $data['riichi_summary']['riichi_lost'],
                    'riichiTotal'       => $data['riichi_summary']['riichi_won'] + $data['riichi_summary']['riichi_lost'],


                    'ronCountPercent'        => round($data['win_summary']['ron']
                        * 100. / $data['total_played_rounds'], 2),
                    'tsumoCountPercent'      => round($data['win_summary']['tsumo']
                        * 100. / $data['total_played_rounds'], 2),
                    'winCountPercent'        => round(($data['win_summary']['ron'] + $data['win_summary']['tsumo'])
                        * 100. / $data['total_played_rounds'], 2),
                    'feedCountPercent'       => round($data['win_summary']['feed']
                        * 100. / $data['total_played_rounds'], 2),
                    'feedUnderRiichiPercent' => round($data['riichi_summary']['feed_under_riichi']
                        * 100. / $data['total_played_rounds'], 2),
                    'tsumoFeedCountPercent'  => round($data['win_summary']['tsumofeed']
                        * 100. / $data['total_played_rounds'], 2),
                    'chomboCountPercent'     => round($data['win_summary']['chombo']
                        * 100. / $data['total_played_rounds'], 2),

                    'riichiWonPercent'   => round($data['riichi_summary']['riichi_won'] * 100.
                        / ($data['riichi_summary']['riichi_won'] + $data['riichi_summary']['riichi_lost']), 2),
                    'riichiLostPercent'  => round($data['riichi_summary']['riichi_lost'] * 100.
                        / ($data['riichi_summary']['riichi_won'] + $data['riichi_summary']['riichi_lost']), 2),
                    'riichiTotalPercent' => round(($data['riichi_summary']['riichi_won'] + $data['riichi_summary']['riichi_lost'])
                        * 100. / $data['total_played_rounds'], 2),

                    'place1' => round($data['places_summary'][1] * 100. / array_sum($data['places_summary']), 2),
                    'place2' => round($data['places_summary'][2] * 100. / array_sum($data['places_summary']), 2),
                    'place3' => round($data['places_summary'][3] * 100. / array_sum($data['places_summary']), 2),
                    'place4' => round($data['places_summary'][4] * 100. / array_sum($data['places_summary']), 2),
                ],
                'error' => null
            ];
        } catch (Exception $e) {
            return [
                'data' => null,
                'error' => $e->getMessage()
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
