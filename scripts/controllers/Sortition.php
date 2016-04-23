<?php
require_once 'scripts/helpers/Array.php';

class Sortition extends Controller {
    protected $_possibleIntersections = array(
        array(0, 1),
        array(0, 2),
        array(0, 3),
        array(1, 2),
        array(1, 3),
        array(2, 3)
    );

    protected function _calculateFactor($playersMap, $playData)
    {
        $factor = 0;
        $players = array_chunk($playersMap, 4);
        $data = ArrayHelpers::elm2Key($playData, 'game_id', true);
        foreach ($players as $table) {
            foreach ($data as $game) {
                $gameTmp = ArrayHelpers::elm2Key($game, 'username');
                foreach ($this->_possibleIntersections as $intersection) {
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

    protected function _calcIntersection($playData, $sortition) {
        $intersectionData = array();

        $data = ArrayHelpers::elm2Key($playData, 'game_id', true);
        $data = array_merge($data, array_chunk($sortition, 4));

        foreach ($data as $game) {
            foreach ($this->_possibleIntersections as $intersection) {
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

    protected function _beforeRun() {
        if (!empty($_POST['factor'])) {
            $cachedSortition = db::get("SELECT * FROM sortition_cache WHERE hash = '" . hexdec($_POST['factor']) . "'");
            if (count($cachedSortition) >= 0) {
                $sortition = unserialize(base64_decode($cachedSortition[0]['data']));

                $tablesData = [];
                foreach ($sortition[0] as $table) {
                    $tablesData = array_merge($tablesData, [
                        ['username' => $table[0]['username'], 'player_num' => 0],
                        ['username' => $table[1]['username'], 'player_num' => 1],
                        ['username' => $table[2]['username'], 'player_num' => 2],
                        ['username' => $table[3]['username'], 'player_num' => 3],
                    ]);
                }
                $query = "INSERT INTO tables (username, player_num) VALUES " . implode(', ', array_map(function($item) {
                    return "('{$item['username']}', {$item['player_num']})";
                }, $tablesData));
                db::exec($query);

                db::exec("UPDATE sortition_cache SET is_confirmed=1 WHERE hash = '" . hexdec($_POST['factor']) . "'");
                echo "Рассадка успешно подтверждена!";
                return false;
            }

            return true;
        }

        if (empty($this->_path['seed'])) {
            header('Location: /sortition/' . substr(md5(microtime(true)), 3, 8) . '/');
            return false;
        }

        return true;
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
    protected function _calсSubSums($player1, $player2, $player3, $player4, $prevData) {
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

    /**
     * @param $tablePlayers [{username -> #name}, {username -> #name}, {username -> #name}, {username -> #name}]
     * @param $previousPlacements [{#name -> [[player_num -> 1], [player_num -> 2], [player_num -> 0]]}, ...]
     * @return array|null
     */
    protected function _calcPlacement($tablePlayers, $previousPlacements) {
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
            $newResult = $this->_calсSubSums(
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

    protected function _run()
    {
        $users = db::get("SELECT username, alias FROM players");
        $aliases = array();
        foreach ($users as $v) {
            $aliases[$v['username']] = IS_ONLINE ? base64_decode($v['alias']) : $v['alias'];
        }

        $randFactor = hexdec($this->_path['seed']);
        $cachedSortition = db::get("SELECT * FROM sortition_cache WHERE hash = '{$randFactor}'");
        if (count($cachedSortition) == 0) {
            if ($_COOKIE['secret'] != ADMIN_COOKIE) {
                echo "Секретное слово неправильное";
                return;
            }

            $usersData = db::get("SELECT * FROM players ORDER BY rating DESC, place_avg ASC");
            $groups = array_chunk($usersData, ceil(count($usersData) / SORTITION_GROUPS_COUNT), true);

            $playData = db::get("SELECT game_id, username, rating FROM rating_history");
            $previousPlacements = db::get("SELECT * FROM tables");
            $previousPlacements = ArrayHelpers::elm2Key($previousPlacements, 'username', true);

            $maxIterations = 3000;
            $bestGroupsMap = array();
            $factor = 100500;

            for ($i = 0; $i < $maxIterations; $i++) {
                srand($randFactor + $i*5);
                foreach ($groups as $k => $v) {
                    shuffle($groups[$k]);
                }

                $newFactor = $this->_calculateFactor(array_reduce($groups, function($acc, $el) {
                    return array_merge($acc, $el);
                }, []) , $playData);
                if ($newFactor < $factor) {
                    $factor = $newFactor;
                    $bestGroupsMap = $groups;
                }
            }

            $sortition = array_values(array_reduce($bestGroupsMap, function($acc, $el) {
                return array_merge($acc, $el);
            }, []));
            $bestIntersection = $this->_calcIntersection($playData, $sortition);
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
                $tables[$k] = $this->_calcPlacement($v, $previousPlacements);
            }

            // store to cache
            $cacheData = base64_encode(serialize(array($tables, $sortition, $bestIntersection, $bestIntersectionSets, $usersData)));
            db::exec("INSERT INTO sortition_cache(hash, data) VALUES ('{$randFactor}', '{$cacheData}')");
        } else {
            $isApproved = !!$cachedSortition[0]['is_confirmed'];
            list($tables, $sortition, $bestIntersection, $bestIntersectionSets, $usersData) = unserialize(base64_decode($cachedSortition[0]['data']));
        }

        include "templates/Sortition.php";
    }
}
