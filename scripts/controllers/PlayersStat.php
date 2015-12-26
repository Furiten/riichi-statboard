<?php

class PlayersStat extends Controller {
    protected function _run()
    {
        $users = Db::get("SELECT username, alias FROM players");
        $aliases = array();
        foreach ($users as $v) {
            $aliases[$v['username']] = IS_ONLINE ? base64_decode($v['alias']) : $v['alias'];
        }

        if (!isset($_GET['sort'])) {
            $_GET['sort'] = '';
        }

        $query = "SELECT players.*, STD(result_score.place) AS stddev
            FROM players
            LEFT JOIN result_score ON (players.username = result_score.username)
            GROUP BY result_score.username
        ";

        switch ($_GET['sort']) {
            case 'avg':
                $query .= "ORDER BY place_avg ASC, rating DESC";
                break;
            case 'rating':
            default:
                $query .= "ORDER BY rating DESC, place_avg ASC";
        }

        $usersData = Db::get($query);
        include "templates/PlayersStat.php";
    }
}

