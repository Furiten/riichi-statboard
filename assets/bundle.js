function plotRating(points, games, currentUser, playersMap) {
    var ticks = [];
    for (var idx = 0; idx < points.length; idx++) {
        ticks.push(idx);
    }

    $.jqplot(
        'chart_rating',
        [points],
        {
            axes: {
                xaxis: {
                    // label:'Сыграно игр',
                    ticks: ticks,
                    tickInterval: 1,
                    tickOptions: {
                        formatString: '%d'
                    }
                },
                yaxis: {
                    label: 'Рейтинг'
                }
            },
            highlighter: {
                show: true,
                sizeAdjust: 7,
                tooltipContentEditor: function (str, seriesIndex, pointIndex) {
                    var g = games[pointIndex - 1];
                    var players = [];
                    var outcome = '';
                    var own = '';

                    players.push('<table class="table table-condensed table-bordered table-plot-rating">');
                    for (var i = 0; i < 4; i++) {
                        outcome = g[i].rating_delta < 0 ? 'important' : 'success';
                        own = g[i].player_id == currentUser ? 'own' : '';
                        players.push(
                            '<tr class="' + own + '">' +
                            '<td><b>' + playersMap[g[i].player_id].display_name + '</b>: ' +
                            '</td><td>' +
                            '<span class="badge badge-' + outcome + '">' + g[i].rating_delta + '</span>' +
                            '</td></tr>'
                        );
                    }
                    players.push('</table>');
                    return players.join('');
                }
            },
            cursor: {
                show: false
            },
            seriesDefaults: {
                rendererOptions: {
                    smooth: true
                }
            }
        }
    );
}

function plotHands(handValueStats, yakuStats) {
    $.jqplot('chart_hands', [handValueStats], {
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

    $.jqplot('chart_yaku', [yakuStats], {
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
}
