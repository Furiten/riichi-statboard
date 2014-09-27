<?php

class PlayersStat extends Controller {
    protected function _run()
    {
		$users = Db::get("SELECT username, alias FROM players");
		$aliases = array();
		foreach ($users as $v) {
			$aliases[$v['username']] = $v['alias'];
		}

        if (!isset($_GET['sort'])) {
            $_GET['sort'] = '';
        }
        switch ($_GET['sort']) {
            case 'avg':
                $usersData = Db::get("SELECT * FROM players ORDER BY place_avg ASC, rating DESC");
                break;
            case 'rating':
            default:
                $usersData = Db::get("SELECT * FROM players ORDER BY rating DESC, place_avg ASC");
        }

        include "templates/PlayersStat.php";
    }
}
