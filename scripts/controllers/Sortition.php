<?php
require_once 'scripts/helpers/Array.php';
require_once 'scripts/helpers/Sortition.php';

class Sortition extends Controller {
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

    protected function _run()
    {
        $users = db::get("SELECT username, alias FROM players");
        $aliases = [];
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
            $playData = db::get("SELECT game_id, username, rating FROM rating_history");
            $previousPlacements = db::get("SELECT * FROM tables");
            $previousPlacements = ArrayHelpers::elm2Key($previousPlacements, 'username', true);

            list($tables, $sortition, $bestIntersection, $bestIntersectionSets) =
                SortitionHelper::generate($randFactor, $usersData, $playData, $previousPlacements, SORTITION_GROUPS_COUNT);

            // store to cache
            $cacheData = base64_encode(serialize([$tables, $sortition, $bestIntersection, $bestIntersectionSets, $usersData]));
            db::exec("INSERT INTO sortition_cache(hash, data) VALUES ('{$randFactor}', '{$cacheData}')");
        } else {
            $isApproved = !!$cachedSortition[0]['is_confirmed'];
            list($tables, $sortition, $bestIntersection, $bestIntersectionSets, $usersData) = unserialize(base64_decode($cachedSortition[0]['data']));
        }

        include "templates/Sortition.php";
    }

    protected function _generateAndApprove()
    {
        $randFactor = substr(md5(microtime(true)), 3, 8);

        $botsNames = [
            base64_encode('Alfa-Tom'),
            base64_encode('Beta-Zef'),
            base64_encode('Gamma-Ke'),
            base64_encode('Delta-Se')
        ];

        // Generate
        $usersData = db::get("SELECT * FROM players WHERE username NOT IN('{$botsNames[0]}', '{$botsNames[1]}', '{$botsNames[2]}', '{$botsNames[3]}')
                              ORDER BY games_played DESC, rating DESC, place_avg ASC");
        while (count($usersData) % 4 != 0) { // округляем до стола
            array_pop($usersData);
        }

        $playData = db::get("SELECT game_id, username, rating FROM rating_history");
        $previousPlacements = db::get("SELECT * FROM tables");
        $previousPlacements = ArrayHelpers::elm2Key($previousPlacements, 'username', true);

        list($tables, $sortition, $bestIntersection, $bestIntersectionSets) =
            SortitionHelper::generate($randFactor, $usersData, $playData, $previousPlacements, SORTITION_GROUPS_COUNT);

        // store to cache
        $cacheData = base64_encode(serialize([$tables, $sortition, $bestIntersection, $bestIntersectionSets, $usersData]));
        db::exec("INSERT INTO sortition_cache(hash, data) VALUES ('{$randFactor}', '{$cacheData}')");

        // Approve
        $tablesData = [];
        foreach ($tables as $table) {
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

        db::exec("UPDATE sortition_cache SET is_confirmed=1 WHERE hash = '" . $randFactor . "'");

        return $randFactor;
    }

    public function _genSort() // паблик морозов
    {
        return $this->_generateAndApprove();
    }

    public function _getSort($seed)
    {
        $cachedSortition = db::get("SELECT * FROM sortition_cache WHERE hash = '" . $seed . "'");
        if (count($cachedSortition) >= 0) {
            $sortition = unserialize(base64_decode($cachedSortition[0]['data']));
            return $sortition[0]; // tables
        }

        return null;
    }
}
