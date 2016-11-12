<?php

include_once __DIR__ . "/../helpers/Array.php";
include_once __DIR__ . "/../helpers/YakuMap.php";

class LastGames extends Controller
{
    protected $_mainTemplate = 'LastGames';

    protected function _run()
    {
        $limit = 10;
        $offset = 0;
        $currentPage = 1;

        if (isset($_GET['page']) && is_numeric($_GET['page'])) {
            $currentPage = max(1, (int)$_GET['page']);
            $offset = ($currentPage - 1) * $limit;
        }

        $gamesData = $this->_api->execute('getLastGames', [TOURNAMENT_ID, $limit, $offset]);

        return [
            'noGames' => empty($gamesData['games']) && $currentPage == 1,
            'games' => $this->_makeGamesData($gamesData),
            'nextPage' => $currentPage + 1,
            'prevPage' => $currentPage == 1 ? 1 : $currentPage - 1
        ];
    }

    protected function _makeGamesData(&$gamesData)
    {
        $result = [];
        foreach ($gamesData['games'] as $gameId => $game) {
            $players = array_map(
                function ($finalScore, $playerId) use (&$gamesData) {
                    return [
                        'display_name' => $gamesData['players'][$playerId]['display_name'],
                        'score' => $finalScore['score'],
                        'label' => ($finalScore['rating_delta'] > 0
                            ? 'success'
                            : ($finalScore['rating_delta'] < 0
                                ? 'important'
                                : 'info'
                            )
                        ),
                        'rating_delta' => ($finalScore['rating_delta'] > 0 ? '+' : '') . $finalScore['rating_delta']
                    ];
                },
                array_values($game['final_results']),
                array_keys($game['final_results'])
            );

            // Some client-side stats
            $bestHan = 0;
            $bestFu = 0;
            $bestHandPlayers = [];
            $chomboCount = 0;
            $ronWins = 0;
            $doubleronWins = 0; // TODO
            $tripleronWins = 0; // TODO
            $tsumoWins = 0;
            $draws = 0;
            $firstYakuman = true;

            foreach ($game['rounds'] as $round) {
                switch ($round['outcome']) {
                    case 'chombo':
                        $chomboCount++;
                        break;
                    case 'ron':
                        $ronWins++;
                        break;
                    case 'tsumo':
                        $tsumoWins++;
                        break;
                    case 'draw':
                        $draws++;
                        break;
                    case 'abort':
                        $draws++;
                        break;
                }

                if (empty($gamesData['players'][$round['winner_id']]['display_name'])) {
                    continue;
                }

                $winner = $gamesData['players'][$round['winner_id']]['display_name'];

                if ($round['han'] < 0) { // yakuman
                    $bestHan = $bestFu = 200;
                    if ($firstYakuman) {
                        $bestHandPlayers = [];
                        $firstYakuman = false;
                    }
                    array_push($bestHandPlayers, $winner);
                }

                if (($round['han'] > $bestHan) || ($round['han'] == $bestHan && $round['fu'] > $bestFu)) {
                    $bestHan = $round['han'];
                    $bestFu = $round['fu'];
                    $bestHandPlayers = [];
                    array_push($bestHandPlayers, $winner);
                }

                if ($round['han'] == $bestHan && $round['fu'] == $bestFu) {
                    if (!in_array($winner, $bestHandPlayers)) {
                        array_push($bestHandPlayers, $winner);
                    }
                }
            }

            $result [] = [
                'index' => $gameId,
                'playDate' => $game['date'],
                'players' => $players,
                'replayLink' => $game['replay_link'],
                'bestHandPlayers' => implode(', ', $bestHandPlayers),
                'bestHandCost' => ($bestHan == 200
                    ? 'якуман'
                    : ($bestHan > 4
                        ? $bestHan . ' хан'
                        : $bestHan . ' хан, ' . $bestFu . ' фу'
                    )
                ),
                'ronWins' => $ronWins,
                'tsumoWins' => $tsumoWins,
                'draws' => $draws,
                'chombo' => $chomboCount,
                'logItems' => $this->_makeLog($game, $gamesData['players'])
            ];
        }

        return $result;
    }

    protected function _makeLog($game, &$playersData)
    {
        $roundWind = '東';
        $roundIndex = $game['round_index'];

        if ($game['round'] > 4) {
            $roundWind = '南';
            $roundIndex = ($game['round_index'] - 4);
        }

        if ($game['round'] > 8) {
            $roundWind = '西';
            $roundIndex = ($game['round_index'] - 8);
        }

        if ($game['round'] > 12) {
            $roundWind = '北';
            $roundIndex = ($game['round_index'] - 12);
        }

        $yakuList = implode(', ',
            array_map(
                function($yaku) {
                    return Yaku::getMap()[$yaku];
                },
                explode(',', $game['yaku'])
            )
        );

        $tempaiList = null;
        if (!empty($game['tempai'])) {
            $tempaiList = array_map(
                function ($el) use (&$playersData) {
                    return $playersData[$el]['display_name'];
                },
                explode(',', $game['tempai'])
            );
            $tempaiList = implode(', ', $tempaiList);
        }

        return [
            'roundWind' => $roundWind,
            'roundIndex' => $roundIndex,
            'roundTypeRon' => $game['outcome'] == 'ron',
            'roundTypeTsumo' => $game['outcome'] == 'tsumo',
            'roundTypeDraw' => $game['outcome'] == 'draw',
            'roundTypeAbort' => $game['outcome'] == 'abort',
            'roundTypeChombo' => $game['outcome'] == 'chombo',

            'winnerName' => $playersData[$game['winner_id']],
            'loserName' => $playersData[$game['loser_id']],
            'yakuList' => $yakuList,
            'doras' => $game['dora'],
            'han' => $game['han'],
            'fu' => $game['fu'],
            'yakuman' => $game['han'] < 0,
            'tempaiPlayers' => $tempaiList,
        ];
    }
}
