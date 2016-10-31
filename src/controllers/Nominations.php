<?php

include_once "src/components/NominationsBuilder.php";

class Nominations extends Controller
{

    protected function _run()
    {
        $gamesCount = Db::get("SELECT COUNT(*) as cnt FROM game;");
        $gamesCount = reset($gamesCount)['cnt'];
        if ($gamesCount < 4) {
            $nominations = null;
        } else {
            $data = Db::get("
                SELECT round.game_id, round.round, round.username as winner, round.loser, round.han, round.fu, 
                    round.yakuman, round.result, round.tempai_list, result_score.score as last_scores
                FROM `round`
                LEFT JOIN result_score ON result_score.game_id = round.game_id;
            ");

            $nominations = new NominationsBuilder();
            $nominations = $nominations->buildNominations($data);

            $users = $data = Db::get("SELECT username, alias from players;");
            $aliases = [];
            foreach ($users as $v) {
                $aliases[$v['username']] = $v['alias'];
            }

            foreach ($nominations as $key => $value) {
                $nomination = $nominations[$key];
                if ($nomination) {
                    $nominations[$key]['alias'] = $aliases[$nomination['name']];
                }
            }
        }

        include "templates/Nominations.php";
    }
}
