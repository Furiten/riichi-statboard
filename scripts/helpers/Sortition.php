<?php

class SortitionHelper {
    protected static $_possibleIntersections = [
        [0, 1],
        [0, 2],
        [0, 3],
        [1, 2],
        [1, 3],
        [2, 3]
    ];

    public static function generate($randFactor, $userList, $ratingsData, $previousPlacements) {
        $maxIterations = 3000;
        $bestGroupsMap = [];
        $factor = 100500;
        $groups = array_chunk($userList, ceil(count($userList) / SORTITION_GROUPS_COUNT), true);

        for ($i = 0; $i < $maxIterations; $i++) {
            srand($randFactor + $i*5);
            foreach ($groups as $k => $v) {
                shuffle($groups[$k]);
            }

            $newFactor = self::_calculateFactor(array_reduce($groups, function($acc, $el) {
                return array_merge($acc, $el);
            }, []) , $ratingsData);
            if ($newFactor < $factor) {
                $factor = $newFactor;
                $bestGroupsMap = $groups;
            }
        }

        $sortition = array_values(array_reduce($bestGroupsMap, function($acc, $el) {
            return array_merge($acc, $el);
        }, []));
        $bestIntersection = self::_calcIntersection($ratingsData, $sortition);
        $bestIntersectionSets = array_reduce($bestIntersection, function($acc, $el) {
            if (empty($acc[$el])) {
                $acc[$el] = 0;
            }
            $acc[$el]++;
            return $acc;
        }, []);
        unset($bestIntersectionSets[1]);

        $tables = array_chunk($sortition, 4);
        foreach ($tables as $k => $v) {
            $tables[$k] = self::_calcPlacement($v, $previousPlacements);
        }

        return [$tables, $sortition, $bestIntersection, $bestIntersectionSets];
    }

    /**
     * @param $tablePlayers [{username -> #name}, {username -> #name}, {username -> #name}, {username -> #name}]
     * @param $previousPlacements [{#name -> [[player_num -> 1], [player_num -> 2], [player_num -> 0]]}, ...]
     * @return array|null
     */
    protected static function _calcPlacement($tablePlayers, $previousPlacements) {
        $possiblePlacements = [
            '0123', '1023', '2013', '3012',
            '0132', '1032', '2031', '3021',
            '0213', '1203', '2103', '3102',
            '0231', '1230', '2130', '3120',
            '0312', '1302', '2301', '3201',
            '0321', '1320', '2310', '3210',
        ];

        $bestResult = 10005000;
        $bestPlacement = null;
        foreach ($possiblePlacements as $placement) {
            $newResult = self::_calсSubSums(
                $tablePlayers[$placement[0]]['username'],
                $tablePlayers[$placement[1]]['username'],
                $tablePlayers[$placement[2]]['username'],
                $tablePlayers[$placement[3]]['username'],
                $previousPlacements
            );

            if ($newResult < $bestResult) {
                $bestResult = $newResult;
                $bestPlacement = [
                    [
                        'username' => $tablePlayers[$placement[0]]['username'],
                        'rating' => $tablePlayers[$placement[0]]['rating']
                    ],
                    [
                        'username' => $tablePlayers[$placement[1]]['username'],
                        'rating' => $tablePlayers[$placement[1]]['rating']
                    ],
                    [
                        'username' => $tablePlayers[$placement[2]]['username'],
                        'rating' => $tablePlayers[$placement[2]]['rating']
                    ],
                    [
                        'username' => $tablePlayers[$placement[3]]['username'],
                        'rating' => $tablePlayers[$placement[3]]['rating']
                    ]
                ];
            }
        }

        return $bestPlacement;
    }

    /**
     * Подсчет суммы вычетов по местам исходя из предлагаемой рассадки
     * Чем меньше сумма вычетов, тем равномернее распределение.
     *
     * @param $player1
     * @param $player2
     * @param $player3
     * @param $player4
     * @param $prevData
     * @return float|int
     */
    protected static function _calсSubSums($player1, $player2, $player3, $player4, $prevData) {
        $totalsum = 0;
        foreach ([$player1, $player2, $player3, $player4] as $idx => $player) {
            $playerData = $prevData[$player];
            $buckets = [0 => 0, 1 => 0, 2 => 0, 3 => 0];
            $buckets[$idx] ++;

            foreach ($playerData as $item) {
                $buckets[$item['player_num']] ++;
            }

            $totalsum += (
                abs($buckets[0] - $buckets[1]) +
                abs($buckets[0] - $buckets[2]) +
                abs($buckets[0] - $buckets[3]) +
                abs($buckets[1] - $buckets[2]) +
                abs($buckets[1] - $buckets[3]) +
                abs($buckets[2] - $buckets[3])
            );
        }

        return $totalsum;
    }

    protected static function _calcIntersection($ratingsData, $sortition) {
        $intersectionData = [];

        $data = ArrayHelpers::elm2Key($ratingsData, 'game_id', true);
        $data = array_merge($data, array_chunk($sortition, 4));

        foreach ($data as $game) {
            foreach (self::$_possibleIntersections as $intersection) {
                // fill intersection data
                $intKey = $game[$intersection[0]]['username'].'+++'.$game[$intersection[1]]['username'];
                if (empty($intersectionData[$intKey])) {
                    $intersectionData[$intKey] = 1;
                } else {
                    $intersectionData[$intKey]++;
                }
            }
        }

        return $intersectionData;
    }

    protected static function _calculateFactor($playersMap, $ratingsData)
    {
        $factor = 0;
        $players = array_chunk($playersMap, 4);
        $data = ArrayHelpers::elm2Key($ratingsData, 'game_id', true);
        foreach ($players as $table) {
            foreach ($data as $game) {
                $gameTmp = ArrayHelpers::elm2Key($game, 'username');
                foreach (self::$_possibleIntersections as $intersection) {
                    // increase factor and intersection data if needed
                    if (
                        !empty($gameTmp[$table[$intersection[0]]['username']]) &&
                        !empty($gameTmp[$table[$intersection[1]]['username']])
                    ) {
                        $factor ++;
                    }
                }
            }
        }

        return $factor;
    }
}
