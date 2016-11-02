<h2>Последние игры</h2>
<br>
<?php

if (empty($gamesData) && $currentPage == 1) {
    echo "Ни одной игры еще не сыграно";
    return;
}

echo "
    <table class='table table-striped table-condensed'>
    <tr>
        <th>#</th>
        <th>Время регистрации</th>
        <th>Игроки</th>
        <th>Всякое разное</th>
    </tr>";

$gamesCounter = $offset + 1;
foreach ($gamesData['games'] as $game) {
    // игроки и очки
    $players = "<table class='table table-'>\n";
    foreach ($game['final_results'] as $playerId => $results) {
        $plus = '';
        if ($results['rating_delta'] > 0) {
            $plus = '+';
            $label = 'badge-success';
        } elseif ($results['rating_delta'] < 0) {
            $label = 'badge-important';
        } else {
            $label = 'badge-info';
        }

        $players .= "<tr>
            <td style='background: transparent; width: 50%;'><span class='icon-user'></span>&nbsp;<b>" . $gamesData['players'][$playerId]['display_name'] . "</b></td>
            <td style='background: transparent; width: 25%;'>{$results['score']}</td>
            <td style='background: transparent; width: 25%;'><span class='badge {$label}'>{$plus}{$results['rating_delta']}</span></td>
        </tr>\n";
    }
    $players .= "</table>";

    // лучшая рука
    $bestHan = 0;
    $bestFu = 0;
    $player = '';
    $yakuman = 0;
    $bestHandPlayers = [];
    $firstYakuman = true;

    $chomboCount = 0;
    $ronWins = 0;
    $doubleronWins = 0; // TODO
    $tripleronWins = 0; // TODO
    $tsumoWins = 0;
    $draws = 0;


    foreach ($game['rounds'] as $round) {
        switch($round['outcome']) {
            case 'chombo': $chomboCount++; break;
            case 'ron': $ronWins++; break;
            case 'tsumo': $tsumoWins++; break;
            case 'draw': $draws++; break;
            case 'abort': $draws++; break;
        }

        $player = $gamesData['players'][$round['winner_id']]['display_name'];

        if ($round['han'] < 0) { // yakuman
            $bestHan = $bestFu = 200;
            $yakuman = 1;
            if ($firstYakuman) {
                $bestHandPlayers = [];
                $firstYakuman = false;
            }
            array_push($bestHandPlayers, $player);
        }

        if ($round['han'] > $bestHan) {
            $bestHan = $round['han'];
            $bestFu = $round['fu'];
            $bestHandPlayers = [];
            array_push($bestHandPlayers, $player);
        }

        if ($round['han'] == $bestHan && $round['fu'] > $bestFu) {
            $bestFu = $round['fu'];
            $bestHandPlayers = [];
            array_push($bestHandPlayers, $player);
        }

        if ($round['han'] == $bestHan && $round['fu'] == $bestFu) {
            if (!in_array($player, $bestHandPlayers)) {
                array_push($bestHandPlayers, $player);
            }
        }
    }

    if ($bestHan >= 5) {
        $cost = $bestHan . ' хан';
    } else {
        $cost = $bestHan . ' хан, ' . $bestFu . ' фу';
    }

    if ($yakuman) {
        $cost = 'якуман!';
    }

    $chombosLi = '';
    if ($chomboCount > 0) {
        $chomboCount .= ' ' . plural($chomboCount, 'штраф чомбо', 'штрафа чомбо', 'штрафов чомбо');
        $chombosLi = "<li>В игре было {$chomboCount}</li>";
    }

    $fullLog = '';
    foreach ($game['rounds'] as $round) {
        $fullLog .= '<div>';
        if ($round['round_index'] <= 4) {
            $fullLog .= '東' . $round['round_index'];
        } else if ($round['round'] <= 8) {
            $fullLog .= '南' . ($round['round_index'] - 4);
        } else if ($round['round'] <= 12) {
            $fullLog .= '西' . ($round['round_index'] - 8);
        } else if ($round['round'] <= 16) {
            $fullLog .= '北' . ($round['round_index'] - 12);
        }

        $fullLog .= ': ';

        switch ($round['outcome']) {
            case 'ron':
                if ($round['dora'] > 0) {
                    $dora = ', дора ' . $round['dora'];
                } else $dora = '';

                if ($round['han'] < 5) {
                    $fu = ', ' . $round['fu'] . ' фу';
                } else $fu = '';

                $yakuList = implode(', ',
                    array_map(
                        function($yaku) {
                            return Yaku::getMap()[$yaku];
                        },
                        explode(',', $round['yaku'])
                    )
                );

                if (!empty($round['yakuman'])) {
                    $fullLog .= "<b>{$gamesData['players'][$round['winner_id']]['display_name']}</b>" .
                                " - {$yakuList} (<b>{$gamesData['players'][$round['loser_id']]['display_name']}</b>), якуман!";
                } else {
                    $fullLog .= "<b>{$gamesData['players'][$round['winner_id']]['display_name']}</b>" .
                                " - {$yakuList}{$dora} (<b>{$gamesData['players'][$round['loser_id']]['display_name']}</b>)," .
                                " {$round['han']} хан{$fu}";
                }
                break;
            case 'tsumo':
                if ($round['dora'] > 0) {
                    $dora = ', дора ' . $round['dora'];
                } else $dora = '';

                if ($round['han'] < 5) {
                    $fu = ', ' . $round['fu'] . ' фу';
                } else $fu = '';

                if ($round['dealer']) {
                    $dealer = ' (дилерское)';
                } else $dealer = '';

                $yakuList = implode(', ', $round['yaku']);
                if (!empty($round['yakuman'])) {
                    $fullLog .= "<b>{$gamesData['players'][$round['winner_id']]['display_name']}</b>" .
                        " - {$yakuList} (цумо}</b>), якуман!";
                } else {
                    $fullLog .= "<b>{$gamesData['players'][$round['winner_id']]['display_name']}</b>" .
                        " - {$yakuList}{$dora} (цумо), {$round['han']} хан{$fu}";
                }
                break;
            case 'draw':
                // ничья
                $tempaiList = array_map(function($el) use(&$gamesData) {
                    return $gamesData['players'][$el]['display_name'];
                }, explode(',', $round['tempai']));
                $tempaiList = implode(', ', $tempaiList);
                $fullLog .= "Ничья (темпай: {$tempaiList})";
                break;
            case 'abort':
                $fullLog .= "Пересдача";
                break;
            case 'chombo':
                $fullLog .= "Чомбо: {$gamesData['players'][$round['loser_id']]['display_name']}";
                break;
            default:;
        }
        $fullLog .= "</div>";
    }

    $ronWins .= plural($ronWins, 'победа', 'победы', 'побед');
    $doubleronWins .= plural($doubleronWins, 'победа', 'победы', 'побед');
    $tripleronWins .= plural($tripleronWins, 'победа', 'победы', 'побед');
    $tsumoWins .= plural($tripleronWins, 'победа', 'победы', 'побед');
    $draws .= plural($draws, 'ничья/пересдача', 'ничьи/пересдачи', 'ничьих/пересдач');

    echo "<tr>
        <td>{$gamesCounter}</td>
        <td>{$game['play_date']}</td>
        <td>{$players}</td>
        <td>
            <ul>
                " . (empty($game['replay_link']) ? "" : "<li><a href='{$game['orig_link']}' target='_blank'>Посмотреть реплей</a></li>") . "
                <li>Лучшая рука собрана " . plural(count($bestHandPlayers), 'игроком', 'игроками', 'игроками') . " <b>" . join(', ', $bestHandPlayers) . "</b> - {$cost}</li>
                <li>В игре было {$ronWins} по рон и {$tsumoWins} по цумо</li>
                ". ($doubleronWins ? "<li>Кроме того, {$doubleronWins} по дабл-рон!</li>" : "")."
                ". ($tripleronWins ? "<li>Кроме того, {$tripleronWins} по трипл-рон!</li>" : "")."
                <li>В игре было {$draws}</li>
                {$chombosLi}
                <li>Полный лог игры:</li>
                <div class='fullLog'>
                    {$fullLog}
                </div>
            </ul>
        </td>
    </tr>";
    $gamesCounter ++;
}

function plural($count, $form1, $form2, $form3)
{
    if ($count >= 11 && $count <= 14) {
        return $form3;
    }

    if ($count % 10 == 1) {
        return $form1;
    }

    if ($count % 10 >= 2 && $count % 10 <= 4) {
        return $form2;
    }

    return $form3;
}

if (empty($currentPage)) {
	$currentPage = 1;
}

if ($currentPage == 1) {
	$prevPage = 1;
} else {
	$prevPage = $currentPage - 1;
}

$nextPage = $currentPage + 1;

$paginator = "<div class='pagination'><ul>
<li><a href='?page={$prevPage}'>Назад</a></li>
<li><a href='?page={$nextPage}'>Вперед</a></li>
</ul></div>";

echo "<tr><td colspan=4>{$paginator}</td></tr>";
echo "</table>";
