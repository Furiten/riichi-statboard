<?php

class Nominations extends Controller {
    protected function _run()
    {
		// 1) крокодил
		$allUsers = Db::get("SELECT players.username, count(DISTINCT round.id) as cnt FROM players LEFT JOIN round ON round.username = players.username GROUP BY players.username HAVING cnt > 0");
		$winners = Db::get("SELECT username, COUNT( * ) AS cnt FROM  `round` WHERE username !=  '' GROUP BY username ORDER BY cnt ASC");
		$losers = Db::get("SELECT loser, COUNT( * ) AS cnt FROM  `round` WHERE loser !=  '' GROUP BY loser ORDER BY cnt ASC");
		$tempai = Db::get("SELECT tempai_list FROM `round` WHERE tempai_list != ''");

		$winCounts = array();
		foreach ($allUsers as $u) {
			$winCounts[$u['username']] = 0;
		}

		foreach ($winners as $u) {
			$winCounts[$u['username']] += $u['cnt'];
		}

		foreach ($losers as $u) {
			$winCounts[$u['loser']] += $u['cnt'];
		}

		foreach ($tempai as $record) {
			$data = unserialize($record);
			if ($data) {
				$tempaiCount = 0;
				foreach ($data as $u) {
					if ($u == 'tempai') {
						$tempaiCount ++;
					}
				}

				if ($tempaiCount == 0 || $tempaiCount == 4) {
					// все темпай или все нотен - не считаем
					continue;
				} else {
					// есть темпай или нотен - всем четверым по одной победе-поражению
					foreach ($data as $u => $s) {
						$winCounts[$u] ++;
					}
				}
			}
		}

		asort($winCounts);

		$krokodil = reset(array_keys($winCounts));
		$krokodilCount = reset(array_values($winCounts));

		// 2) орёл
		$bigHits = Db::get("
			SELECT * FROM `round`
			LEFT JOIN result_score ON result_score.game_id = round.game_id AND result_score.username = round.loser
			WHERE round.result = 'ron' AND result_score.score > 0
			ORDER BY yakuman DESC, han DESC, fu DESC
		");
		$first = reset($bigHits);
		$orel = $first['loser'];
		$orelHit = 0;
		if ($first['yakuman']) {
			$orelHit = 'yakuman';
		} elseif ($first['han'] >= 5) {
			$orelHit = $first['han'];
		} else {
			$orelHit = $first['han'] . '/' . $first['fu'];
		}
		$orelLastScore = $first['score'];

        include "templates/Nominations.php";
    }
}
