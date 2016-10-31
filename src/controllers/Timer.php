<?php

class Timer extends Controller
{
    protected function _run()
    {
        $getNewUsersData = !empty($_GET['newData']);
        if ($getNewUsersData) {
            layout::disable();
        }

        $usersData = Db::get("SELECT * FROM players ORDER BY rating DESC, place_avg ASC");

        $users = Db::get("SELECT username, alias FROM players");
        $aliases = [];
        foreach ($users as $v) {
            $aliases[$v['username']] = $v['alias'];
        }

        include 'templates/Timer.php';
    }
}
