<?php foreach ($results as $game => $gameData): ?>
    <hr />
    <h2>Игра №<?php echo $game; ?></h2>
    <hr />
    <?php foreach ($gameData as $table => $tableData): ?>
        <h3>Стол №<?php if ($table) echo $table; else echo self::TABLES_COUNT; ?></h3>
        <h4>Участники: <?php $players = array_map(function($el) use($aliases) { return $aliases[$el['username']]; }, $tableData['results']); echo implode(', ', $players); ?></h4>
        <?php foreach ($tableData['rounds'] as $round): ?>
            <div>
                <?php
                if ($round['round'] <= 4) {
                    echo '東' . $round['round'];
                } else {
                    echo '南' . ($round['round'] - 4);
                }
                ?>:
                <?php
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

                        $round['yaku_list'] = str_replace(',', ', ', $round['yaku_list']);
                        echo "<b>{$aliases[$round['username']]}</b> - {$round['yaku_list']}{$dora} (<b>{$aliases[$round['loser']]}</b>), {$round['han']} хан{$fu}{$dealer}";
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

                        $round['yaku_list'] = str_replace(',', ', ', $round['yaku_list']);
                        echo "<b>{$aliases[$round['username']]}</b> - {$round['yaku_list']}{$dora} (цумо), {$round['han']} хан{$fu}{$dealer}";
                        break;
                    case 'draw':
                        $tempaiList = array();
                        foreach ($round['tempai_list'] as $name => $r) {
                            if ($r == 'tempai') {
                                $tempaiList []= $aliases[$name];
                            }
                        }
                        $tempaiList = implode(', ', $tempaiList);
                        echo "Ничья (темпай: {$tempaiList})";
                        break;
                    case 'chombo':
                        if ($round['dealer']) {
                            $dealer = ' (дилерское)';
                        } else $dealer = '';

                        echo "Чомбо: {$aliases[$round['username']]}{$dealer}";
                        break;
                    default:;
                }
                ?>
            </div>
        <?php endforeach; ?>
        <h4>Итоги игры</h4>
        <ul>
            <?php foreach ($tableData['results'] as $res) {
                if ($res['result_score'] > 0) {
                    $res['result_score'] = '+' . $res['result_score'];
                }
                echo "<li>" . $aliases[$res['username']] . ": {$res['score']} ({$res['result_score']})</li>";
            }
            ?></ul>
    <?php endforeach; ?>
<?php endforeach; ?>