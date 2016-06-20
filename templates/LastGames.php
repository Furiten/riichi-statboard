<h2>Последние игры</h2>
<br>
<?php

if (empty($gamesData) && $currentPage == 1) {
    echo "Ни одной игры еще не сыграно";
    return;
}

//$gamesCounter = 1;
echo "
    <table class='table table-striped table-condensed'>
    <tr>
        <th>#</th>
        <th>Время регистрации</th>
        <th>Игроки</th>
        <th>Всякое разное</th>
    </tr>";
$gamesCounter = $offset + 1;
foreach ($gamesData as $game) {
    // игроки и очки
    $players = "<table class='table table-'>\n";
    foreach ($scoresData as $score) {
        if ($game['id'] != $score['game_id']) {
            continue;
        }
        if ($score['result_score'] > 0) {
            $plus = '+';
        } else {
            $plus = '';
        }

        if ($score['result_score'] > 0) {
            $label = 'badge-success';
        } elseif ($score['result_score'] < 0) {
            $label = 'badge-important';
        } else {
            $label = 'badge-info';
        }

        $players .= "<tr>
            <td style='background: transparent; width: 50%;'><span class='icon-user'></span>&nbsp;<b>" . $aliases[$score['username']] . "</b></td>
            <td style='background: transparent; width: 25%;'>{$score['score']}</td>
            <td style='background: transparent; width: 25%;'><span class='badge {$label}'>{$plus}{$score['result_score']}</span></td>
        </tr>\n";
    }
    $players .= "</table>";

    // лучшая рука
    $bestHan = 0;
    $bestFu = 0;
    $player = '';
    $yakuman = 0;
    $chomboCount = 0;
    $bestHandPlayers = [];
    $firstYakuman = true;

    foreach ($roundsData as $round) {
        if ($game['id'] != $round['game_id']) {
            continue;
        }

		if ($round['result'] == 'chombo') {
            $chomboCount++;
        }

        $player = $aliases[$round['username']];

        if ($round['yakuman']) {
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

    $ronWins = $game['ron_count'] . ' ' . plural($game['ron_count'], 'победа', 'победы', 'побед');
    $doubleronWins = $game['doubleron_count'] . ' ' . plural($game['doubleron_count'], 'победа', 'победы', 'побед');
    $tripleronWins = $game['tripleron_count'] . ' ' . plural($game['tripleron_count'], 'победа', 'победы', 'побед');
    $tsumoWins = $game['tsumo_count'] . ' ' . plural($game['tsumo_count'], 'победа', 'победы', 'побед');
    $draws = $game['drawn_count'] . ' ' . plural($game['drawn_count'], 'ничья/пересдача', 'ничьи/пересдачи', 'ничьих/пересдач');

    $chombosLi = '';
    if ($chomboCount > 0) {
        $chomboCount .= ' ' . plural($chomboCount, 'штраф чомбо', 'штрафа чомбо', 'штрафов чомбо');
        $chombosLi = "<li>В игре было {$chomboCount}</li>";
    }

    $fullLog = '';
    foreach ($roundsData as $round) {
        if ($game['id'] != $round['game_id']) {
            continue;
        }

        $fullLog .= '<div>';
        if ($round['round'] <= 4) {
            $fullLog .= '東' . $round['round'];
        } else if ($round['round'] <= 8) {
            $fullLog .= '南' . ($round['round'] - 4);
        } else if ($round['round'] <= 12) {
            $fullLog .= '西' . ($round['round'] - 8);
        } else if ($round['round'] <= 16) {
            $fullLog .= '北' . ($round['round'] - 12);
        }

        $fullLog .= ': ';

        switch ($round['result']) {
            case 'ron':
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
                    $fullLog .= "<b>{$aliases[$round['username']]}</b> - {$yakuList} (<b>{$aliases[$round['loser']]}</b>), якуман! {$dealer}";
                } else {
                    $fullLog .= "<b>{$aliases[$round['username']]}</b> - {$yakuList}{$dora} (<b>{$aliases[$round['loser']]}</b>), {$round['han']} хан{$fu}{$dealer}";
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
                    $fullLog .= "<b>{$aliases[$round['username']]}</b> - {$yakuList} (цумо), якуман! {$dealer}";
                } else {
                    $fullLog .= "<b>{$aliases[$round['username']]}</b> - {$yakuList}{$dora} (цумо), {$round['han']} хан{$fu}{$dealer}";
                }
                break;
            case 'draw':
                $tempaiList = [];
                if ($round['tempai_list']) {
                    // ничья
                    $round['tempai_list'] = @unserialize($round['tempai_list']);
                    foreach ($round['tempai_list'] as $name => $r) {
                        if ($r == 'tempai') {
                            $tempaiList []= $aliases[$name];
                        }
                    }
                    $tempaiList = implode(', ', $tempaiList);
                    $fullLog .= "Ничья (темпай: {$tempaiList})";
                } else {
                    // пересдача
                    $fullLog .= "Пересдача";
                }
                break;
            case 'chombo':
                if ($round['dealer']) {
                    $dealer = ' (дилерское)';
                } else $dealer = '';

                $fullLog .= "Чомбо: {$aliases[$round['username']]}{$dealer}";
                break;
            default:;
        }
        $fullLog .= "</div>";
    }

    echo "<tr>
        <td>{$gamesCounter}</td>
        <td>{$game['play_date']}</td>
        <td>{$players}</td>
        <td>
            <ul>
                " . (IS_ONLINE ? "<li><a href='{$game['orig_link']}' target='_blank'>Посмотреть реплей</a></li>" : "") . "
                <li>Лучшая рука собрана " . plural(count($bestHandPlayers), 'игроком', 'игроками', 'игроками') . " <b>" . join(', ', $bestHandPlayers) . "</b> - {$cost}</li>
                <li>В игре было {$ronWins} по рон и {$tsumoWins} по цумо</li>
                ". ($game['doubleron_count'] ? "<li>Кроме того, {$doubleronWins} по дабл-рон!</li>" : "")."
                ". ($game['tripleron_count'] ? "<li>Кроме того, {$tripleronWins} по трипл-рон!</li>" : "")."
                <li>В игре было {$draws}</li>
                <li>Полный лог игры:</li>
                {$chombosLi}
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
