<?php

require_once 'scripts/ArrayHelpers.php';

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
        if (empty($this->_path['seed'])) {
            header('Location: /sortition/' . substr(md5(microtime(true)), 3, 8) . '/');
            return false;
        }

        return true;
    }

    protected function _run()
    {
        $users = db::get("SELECT username, alias FROM players");
        $aliases = array();
        foreach ($users as $v) {
            $aliases[$v['username']] = base64_decode($v['alias']);
        }

        $randFactor = hexdec($this->_path['seed']);
        $cachedSortition = db::get("SELECT * FROM sortition_cache WHERE hash = '{$randFactor}'");
        if (count($cachedSortition) == 0) {
            $usersData = db::get("SELECT * FROM players ORDER BY rating DESC, place_avg ASC");
            $winners = array_slice($usersData, 0, count($usersData) / 2);
            $losers = array_slice($usersData, count($usersData) / 2);

            $playData = db::get("SELECT game_id, username, rating FROM rating_history");

            $maxIterations = 3000;
            $bestWinnersMap = array();
            $bestLosersMap = array();
            $factor = 100500;

            for ($i = 0; $i < $maxIterations; $i++) {
                srand($randFactor + $i*5);
                shuffle($winners);
                shuffle($losers);

                $newFactor = $this->_calculateFactor(array_merge($winners, $losers), $playData);
                if ($newFactor < $factor) {
                    $factor = $newFactor;
                    $bestLosersMap = $losers;
                    $bestWinnersMap = $winners;
                }
            }

            $sortition = array_values(array_merge($bestWinnersMap, $bestLosersMap));
            $bestIntersection = $this->_calcIntersection($playData, $sortition);

            // store to cache
            $cacheData = base64_encode(serialize(array($sortition, $bestIntersection, $usersData)));
            db::exec("INSERT INTO sortition_cache(hash, data) VALUES ('{$randFactor}', '{$cacheData}')");
        } else {
            list($sortition, $bestIntersection, $usersData) = unserialize(base64_decode($cachedSortition[0]['data']));
        }

        include "templates/Sortition.php";
    }
}