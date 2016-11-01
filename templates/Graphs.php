<h2>Графики рейтинга</h2>
<br>
<?php if(!empty($error)) { ?>
<div class="alert alert-error"><?php echo $error; ?></div>
<?php } ?>

<?php if (!empty($data)) : ?>
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

        var playersMap = <?php echo json_encode($usersMap); ?>;
        var points = <?php echo json_encode($graphData); ?>;
        var games = <?php echo json_encode($data['score_history']); ?>;
        var user = '<?php echo $currentUser; ?>';
        var plot_rating = $.jqplot('chart_rating', [points], {
            axes:{
                xaxis:{
                    // label:'Сыграно игр',
                    ticks: <?php echo json_encode(array_keys($graphData)); ?>,
                    tickInterval: 1,
                    tickOptions: {
                        formatString: '%d'
                    }
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
                        if (g[i].rating_delta < 0) {
                            outcome = 'important';
                        } else {
                            outcome = 'success';
                        }
                        if (g[i].player_id == user) {
                            own = 'own';
                        } else {
                            own = '';
                        }
                        players.push(
                            '<tr class=" ' + own + '">' +
                            '<td><b>' + playersMap[g[i].player_id].display_name + '</b>: ' +
                            '</td><td>' +
                            '<span class="badge badge-' + outcome + '">' + g[i].rating_delta + '</span>' +
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
                    $output = [];
                    foreach ($data['hands_value_summary'] as $han => $count) {
                        $output []= "['{$han}', {$count}]";
                    }
                    echo implode(", \n", $output);
                ?>
            ];

            var yaku_data = [
                <?php
                    $output = [];
                    foreach ($data['yaku'] as $yaku => $count) {
                        $output []= "[$count, '" . Yaku::getMap()[$yaku] . "']";
                    }
                    echo implode(", \n", $output);
                ?>
            ];

            var plot_hands = $.jqplot('chart_hands', [han_data], {
                title: 'Ценность собранных рук',
                series:[{renderer:$.jqplot.BarRenderer}],
                axesDefaults: {
                    tickOptions: {
                        fontSize: '12pt'
                    }
                },
                axes: {
                    xaxis: {
                        label: 'Хан',
                        renderer: $.jqplot.CategoryAxisRenderer
                    }
                }
            });

            var plot_yaku = $.jqplot('chart_yaku', [yaku_data], {
                height: 400,
                title: 'Собранные яку (за все время)',
                series:[{
                    renderer: $.jqplot.BarRenderer,
                    rendererOptions: {
                        barWidth: 7,
                        shadowOffset: 1,
                        barDirection: 'horizontal'
                    }
                }],
                axesDefaults: {
                    tickOptions: {
                        fontSize: '12pt'
                    }
                },
                axes: {
                    yaxis: {
                        renderer: $.jqplot.CategoryAxisRenderer
                    },
                    xaxis: {
                        min: 0,
                        tickInterval: 1,
                        tickOptions: {
                            formatString: '%d'
                        }
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
            <tr><td>Сыграно игр</td><td>
                    <b><?php echo $data['total_played_games']; ?></b></td></tr>
            <?php
                    /* <tr><td>Сыграно раздач</td><td>
                    <b><?php echo $data; ?></b></td></tr> */ // TODO
                    //
            ?>
            <tr><td>Выиграно раздач</td><td>
                    <b><?php echo $data['win_summary']['ron'] + $data['win_summary']['tsumo']; ?></b> &nbsp;
                    <?php /* TODO: % of rounds */ ?></td></tr>
            <tr><td>Интегральный рейтинг</td><td>
                    <?php echo $integralRating; ?></td></tr>
            <tr><td colspan="2" style="padding-left: 20px"><b>По исходам раздач:</b></td></tr>
            <tr><td>Выигрышей по рон</td><td>
                    <b><?php echo $data['win_summary']['ron']; ?></b> &nbsp;
                    <?php /* TODO: % of rounds */ ?></td></tr>
            <tr><td>Выигрышей по цумо</td><td>
                    <b><?php echo $data['win_summary']['tsumo']; ?></b> &nbsp;
                    <?php /* TODO: % of rounds */ ?></td></tr>
            <tr><td>Набросов в рон</td><td>
                    <b><?php echo $data['win_summary']['feed']; ?></b> &nbsp;
                    <?php /* TODO: % of rounds */ ?></td></tr>
            <?php /* TODO
                    <tr><td>- в том числе из-за риичи</td><td>
                    <b><?php echo $roundsData['furikomi_riichi']; ?></b>
                    (<?php echo sprintf('%.2f', 100. * $roundsData['furikomi_riichi'] / $roundsData['rounds_played']); ?>%)</td></tr>
            */ ?>
            <tr><td>Проигрышей по цумо</td><td>
                    <b><?php echo $data['win_summary']['tsumofeed']; ?></b> &nbsp;
                    <?php /* TODO: % of rounds */ ?></td></tr>
            <tr><td>Штрафов чомбо</td><td>
                    <b><?php echo $data['win_summary']['chombo']; ?></b> &nbsp;
                    <?php /* TODO: % of rounds */ ?></td></tr>
            <?php /* <tr><td>Ставок риичи</td><td>
                    <b><?php echo $roundsData['riichi_bets']; ?></b> &nbsp;
                    (<?php echo sprintf('%.2f', 100. * $roundsData['riichi_bets'] / $roundsData['rounds_played']); ?>%)</td></tr>
            <tr><td>- из них выигравших</td><td>
                    <b><?php echo $roundsData['riichi_won']; ?></b> &nbsp;
                    (<?php echo sprintf('%.2f', 100. * $roundsData['riichi_won'] / $roundsData['rounds_played']); ?>%)</td></tr>
            <tr><td>- из них потерянных</td><td>
                    <b><?php echo $roundsData['riichi_lost']; ?></b> &nbsp;
                    (<?php echo sprintf('%.2f', 100. * $roundsData['riichi_lost'] / $roundsData['rounds_played']); ?>%)</td></tr>*/
            // TODO
            ?>

            <tr><td colspan="2" style="padding-left: 20px"><b>По занятым местам:</b></td></tr>
            <tr><td>1 место</td><td>
                    <?php echo sprintf('%.2f', $data['places_summary'][1] / (float) array_sum($data['places_summary'])); ?> %</td></tr>
            <tr><td>2 место</td><td>
                    <?php echo sprintf('%.2f', $data['places_summary'][2] / (float) array_sum($data['places_summary'])); ?> %</td></tr>
            <tr><td>3 место</td><td>
                    <?php echo sprintf('%.2f', $data['places_summary'][3] / (float) array_sum($data['places_summary'])); ?> %</td></tr>
            <tr><td>4 место</td><td>
                    <?php echo sprintf('%.2f', $data['places_summary'][4] / (float) array_sum($data['places_summary'])); ?> %</td></tr>
        </table>
    </div>
    <div class="span8">
        <div id='chart_hands'></div>
        <hr />
        <div id='chart_yaku'></div>
    </div>
</div>

<?php endif; ?>
