<?php

include_once "scripts/helpers/Array.php";
include_once "scripts/helpers/Yaku.php";

class LastGames extends Controller
{
    protected function _run()
    {
        $users = Db::get("SELECT username, alias FROM players");
        $aliases = [];
        foreach ($users as $v) {
            $aliases[$v['username']] = IS_ONLINE ? base64_decode($v['alias']) : $v['alias'];
        }

        $limit = 10;
        $offset = 0;
        $currentPage = 1;
        if (isset($_GET['page']) && is_numeric($_GET['page'])) {
            $currentPage = (int)$_GET['page'];
            $offset = ($currentPage - 1) * $limit;
        }

        $todaysGames = "SELECT * FROM game ORDER BY play_date DESC LIMIT {$offset}, {$limit}";
        $gamesData = Db::get($todaysGames);

        if (empty($gamesData)) {
            include 'templates/LastGames.php';
            return;
        }

        $gameIds = array_map(function ($el) {
            return $el['id'];
        }, $gamesData);
        $gameIds = implode(',', $gameIds);

        $resultScores = "SELECT * FROM result_score WHERE game_id IN({$gameIds})";
        $scoresData = Db::get($resultScores);
        $scoresData = ArrayHelpers::elm2Key($scoresData, 'id');

        $rounds = "SELECT * FROM round WHERE game_id IN({$gameIds})";
        $roundsData = Db::get($rounds);
        foreach ($roundsData as $id => $data) {
            $roundsData[$id]['yaku'] = YakuHelper::toStringList($data['yaku']);
        }

        $roundsData = ArrayHelpers::elm2Key($roundsData, 'id');

        include 'templates/LastGames.php';
    }
}
