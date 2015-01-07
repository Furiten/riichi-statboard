<?php

class Logs extends Controller {
    const TABLES_COUNT = 9; // change this if needed

    protected function _run()
    {
        mb_internal_encoding('UTF-8');
        $users = db::get("SELECT username, alias FROM players ORDER BY alias");
        $aliases = array();
        foreach ($users as $v) {
            $aliases[$v['username']] = $v['alias'];
        }

        $roundResult = db::get("
			SELECT round.id, game_id, username, loser, result, han, fu, dealer, round, tempai_list, group_concat(yaku.title) as yaku_list, dora
			FROM round
			LEFT JOIN yaku ON FIND_IN_SET(yaku.id, round.yaku)
			GROUP BY round.id
		");

        $gameResultTmp = db::get("
			SELECT game_id, username, score, result_score FROM result_score ORDER BY game_id, place
		");

        $gameResult = array();
        foreach ($gameResultTmp as $v) {
            $gameResult[$v['game_id']] []= $v;
        }

        //////////////////////
        // Making results array ...
        $results = array();
        $lastGameId = 0;
        $counter = 0;

        foreach ($roundResult as $v) {
            if ($lastGameId == 0 || $lastGameId != $v['game_id']) {
                $lastGameId = $v['game_id'];
                $counter ++;
            }
            $v['tempai_list'] = @unserialize($v['tempai_list']);
            $results[ceil($counter / self::TABLES_COUNT)][$counter % self::TABLES_COUNT]['rounds'] []= $v;
            $results[ceil($counter / self::TABLES_COUNT)][$counter % self::TABLES_COUNT]['results'] = $gameResult[$v['game_id']];
        }

        include 'templates/Logs.php';
    }
}
