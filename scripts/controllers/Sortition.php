<?php

require_once 'scripts/ArrayHelpers.php';

class Sortition extends Controller {
    protected function _calculateFactor($playersMap, $playData)
    {
        $factor = 0;
        $players = array_chunk($playersMap, 4);
        $data = ArrayHelpers::elm2Key($playData, 'game_id', true);
        foreach ($players as $table) {
            foreach ($data as $game) {
                $gameTmp = ArrayHelpers::elm2Key($game, 'username');
                if (
                    (!empty($gameTmp[$table[0]['username']]) && !empty($gameTmp[$table[1]['username']])) ||
                    (!empty($gameTmp[$table[0]['username']]) && !empty($gameTmp[$table[2]['username']])) ||
                    (!empty($gameTmp[$table[0]['username']]) && !empty($gameTmp[$table[3]['username']])) ||
                    (!empty($gameTmp[$table[1]['username']]) && !empty($gameTmp[$table[2]['username']])) ||
                    (!empty($gameTmp[$table[1]['username']]) && !empty($gameTmp[$table[3]['username']])) ||
                    (!empty($gameTmp[$table[2]['username']]) && !empty($gameTmp[$table[3]['username']]))
                ) {
                    $factor ++;
                }
            }
        }

        return $factor;
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
            $aliases[$v['username']] = $v['alias'];
        }

        $usersData = db::get("SELECT * FROM players ORDER BY rating DESC, place_avg ASC");
        $winners = array_slice($usersData, 0, count($usersData) / 2);
        $losers = array_slice($usersData, count($usersData) / 2);

        $playData = db::get("SELECT game_id, username, rating FROM rating_history");

        $maxIterations = 1000;
        $bestWinnersMap = array();
        $bestLosersMap = array();
        $factor = 100500;
        $randFactor = hexdec($this->_path['seed']);

        for ($i = 0; $i < $maxIterations; $i++) {
            srand($randFactor += 5);
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

        include "templates/Sortition.php";
    }
}