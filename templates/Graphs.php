<h2>Графики рейтинга</h2>
<br>
<?php if(!empty($error)) { ?>
<div class="alert alert-error"><?php echo $error; ?></div>
<?php } ?>
<form action="" method="get" class="well form-search">
    <input placeholder="Игрок" type="text" name="user"<?php if(!empty($_GET['user'])) { echo " value='" . $_GET['user'] . "'"; } ?>>
    <input type="submit" value="Посмотреть" class="btn">
</form>
<?php if(!empty($_GET['user']) && !empty($graphData)) : ?>
<div id='chart_rating'></div>
<style type="text/css">
    .own {
        background-color: #ffff00 !important;
    }
    .jqplot-highlighter-tooltip {
        border: 1px solid #555;
        -webkit-box-shadow: 4px 4px 24px 1px rgba(0, 0, 0, 0.7);
        box-shadow: 4px 4px 24px 1px rgba(0, 0, 0, 0.7);
    }
</style>
<script type="text/javascript">
    $(document).ready(function(){
        ////// rating plot
        var points = <?php echo str_replace(array_keys($aliases), array_values($aliases), json_encode($graphData)); ?>;
        var games = <?php echo str_replace(array_keys($aliases), array_values($aliases), json_encode($gamesData)); ?>;
        var user = '<?php echo str_replace(array_keys($aliases), array_values($aliases), $user); ?>';
        var plot_rating = $.jqplot('chart_rating', [points], {
            axes:{
                xaxis:{
                    //label:'Сыграно игр',
                    ticks: <?php echo json_encode(array_keys($graphData)); ?>
                },
                yaxis:{
                    label:'Рейтинг'
                }
            },
            highlighter: {
                show: true,
                sizeAdjust: 7,
                tooltipContentEditor: function(str, seriesIndex, pointIndex) {
                    var g = games[pointIndex-1];
                    var players = [];
                    var outcome = '';
                    players.push('<table style="background-color:#fff; padding-bottom: 0; margin-bottom: 0" class="table table-condensed table-bordered">');
                    for (var i = 0; i < 4; i++) {
                        if (g[i].result_score < 0) {
                            outcome = 'important';
                        } else {
                            outcome = 'success';
                        }
                        if (g[i].username == user) {
                            own = 'own';
                        } else {
                            own = '';
                        }
                        players.push(
                            '<tr class=" ' + own + '">' +
                            '<td><b>' + g[i].username + '</b>: ' +
                            '</td><td>' +
                            '<span class="badge badge-' + outcome + '">' + g[i].result_score + '</span>' +
                            '</td></tr>');
                    }
                    players.push('</table>');
                    return players.join('');
                }
            },
            cursor: {
                show: false
            },
            seriesDefaults:{
                rendererOptions: {
                    smooth: true
                }
            }
        });

        ////// hands plot

        $(document).ready(function(){
            var han_data = [
                <?php
                    $output = array();
                    foreach ($handsData['hands'] as $han => $count) {
                        $output []= "['{$han}', {$count}]";
                    }
                    echo implode(", \n", $output);
                ?>
            ];

            var plot_hands = $.jqplot('chart_hands', [han_data], {
                title: 'Ценность собранных рук',
                series:[{renderer:$.jqplot.BarRenderer}],
                axesDefaults: {
                    tickOptions: {
                        fontSize: '10pt'
                    }
                },
                axes: {
                    xaxis: {
                        label: 'Хан',
                        renderer: $.jqplot.CategoryAxisRenderer
                    }
                }
            });
        });

    });
</script>
<hr>

<div class="row">
    <div class="span4">
        <table class="table table-striped table-condensed">
            <tr><td colspan="2" style="padding-left: 20px"><b>Общая статистика:</b></td></tr>
            <tr><td>Сыграно игр</td><td><?php echo $gamesCount; ?></td></tr>
            <tr><td>Выиграно раздач</td><td><?php echo $handsData['rounds_won'] - $handsData['chombo']; ?></td></tr>
            <tr><td>Интегральный рейтинг</td><td><?php echo $integralRating; ?></td></tr>
            <tr><td colspan="2" style="padding-left: 20px"><b>По исходам раздач:</b></td></tr>
            <tr><td>Выигрышей по рон</td><td><b><?php echo $handsData['ron']; ?></b> &nbsp; (<?php echo sprintf('%.2f', 100. * $handsData['ron'] / ($handsData['rounds_won'] ? $handsData['rounds_won'] : 1)); ?>%)</td></tr>
            <tr><td>Выигрышей по цумо</td><td><b><?php echo $handsData['tsumo']; ?></b> &nbsp; (<?php echo sprintf('%.2f', 100. * $handsData['tsumo'] / ($handsData['rounds_won'] ? $handsData['rounds_won'] : 1)); ?>%)</td></tr>
            <tr><td>Штрафов чомбо</td><td><b><?php echo $handsData['chombo']; ?></b> &nbsp; (<?php echo sprintf('%.2f', 100. * $handsData['chombo'] / ($handsData['rounds_won'] ? $handsData['rounds_won'] : 1)); ?>%)</td></tr>
            <tr><td colspan="2" style="padding-left: 20px"><b>По занятым местам:</b></td></tr>
            <tr><td>1 место</td><td><?php echo sprintf('%.2f', $placesData[1]); ?> %</td></tr>
            <tr><td>2 место</td><td><?php echo sprintf('%.2f', $placesData[2]); ?> %</td></tr>
            <tr><td>3 место</td><td><?php echo sprintf('%.2f', $placesData[3]); ?> %</td></tr>
            <tr><td>4 место</td><td><?php echo sprintf('%.2f', $placesData[4]); ?> %</td></tr>
        </table>
    </div>
    <div class="span8">
        <div id='chart_hands'></div>
    </div>
</div>

<?php endif; ?>