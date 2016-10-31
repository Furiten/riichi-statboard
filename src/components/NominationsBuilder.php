<?php

class NominationsBuilder
{

    /**
     * @param $data
     * Пример данных:
     * $data = [
     * [
     *  'game_id' => 1, 'round' => 1, 'winner' => 'akagi', 'loser' => 'totoro', 'han' => 1,
     *  'fu' => 30, 'yakuman' => 0, 'result' => 'ron', 'last_scores' => 38300, 'tempai_list' => ''
     * ]
     * ];
     * @return array
     */
    public static function buildNominations($data)
    {
        $survived = self::_buildSurvivedNomination($data);
        $stranger = self::_buildStrangerNomination($data);
        return ['survived' => $survived, 'stranger' => $stranger];
    }

    /**
     * "Мимокрокодил"
     * Игрок который меньше всего взаимодействовал с другими игроками.
     * Т.е. игрок с наименьшим суммарным числом побед и набросов.
     * @param $data
     * @return array|null
     */
    protected static function _buildStrangerNomination($data)
    {
        $users = array_unique(array_merge(array_column($data, 'winner'), array_column($data, 'loser')));

        $strangers = [];
        foreach ($users as $user) {
            $strangers[$user] = ['winCount' => 0, 'loseCount' => 0, 'name' => $user];
        }

        foreach ($data as $item) {
            if ($item['tempai_list'] != '') {
                  // пока отключим

//                $tempai = unserialize($item['tempai_list']);
//                if ($tempai) {
//                    $tempaiCount = count(array_filter($tempai, function($v) {
//                        return $v == 'tempai';
//                    }));
//
//                    if ($tempaiCount != 0 && $tempaiCount != 4) {
//                        foreach ($tempai as $k => $v) {
//                            if ($v == 'tempai') {
//                                $strangers[$k]['winCount'] += 1;
//                            } else {
//                                $strangers[$k]['loseCount'] += 1;
//                            }
//                        }
//                    }
//                }
            } else {
                $strangers[$item['winner']]['winCount'] += 1;
                $strangers[$item['loser']]['loseCount'] += 1;
            }
        }

        foreach ($strangers as $key => $value) {
            $stranger = $strangers[$key];
            $strangers[$key]['total'] = $stranger['winCount'] + $stranger['loseCount'];
        }

        # prepare sort column
        $total = [];
        foreach ($strangers as $key => $value) {
            $total[$key] = $value['total'];
        }

        array_multisort($total, SORT_ASC, $strangers);
        $stranger = reset($strangers);

        $totalWins = array_sum(array_column($strangers, 'winCount'));
        $totalLoses = array_sum(array_column($strangers, 'loseCount'));

        $averageWins = round($totalWins / count($users));
        $averageLoses = round($totalLoses / count($users));

        return [
            'name' => $stranger['name'],
            'wins' => $stranger['winCount'],
            'loses' => $stranger['loseCount'],
            'averageWins' => $averageWins,
            'averageLoses' => $averageLoses,
        ];
    }

    /**
     * "Жив, цел, орёл"
     * за выживание после мощного удара
     * @param $data
     * @return array|null
     */
    protected static function _buildSurvivedNomination($data)
    {
        # prepare a list of sort columns and their data to pass to array_multisort
        $sort = array();
        foreach ($data as $k => $v) {
            $sort['yakuman'][$k] = $v['yakuman'];
            $sort['han'][$k] = $v['han'];
            $sort['fu'][$k] = $v['fu'];
            $sort['last_scores'][$k] = $v['last_scores'];
        }

        array_multisort(
            $sort['yakuman'],
            SORT_DESC,
            $sort['han'],
            SORT_DESC,
            $sort['fu'],
            SORT_DESC,
            $sort['last_scores'],
            SORT_DESC,
            $data
        );

        # let's remove users that didn't survive after hit
        # and leave only ron records
        foreach ($data as $key => $element) {
            if ($data[$key]['last_scores'] < 0 or $data[$key]['result'] != 'ron') {
                unset($data[$key]);
            }
        }

        if (count($data) == 0) {
            return null;
        }

        $first = reset($data);
        $survived = $first['loser'];

        if ($first['yakuman']) {
            $survivalHit = 'yakuman';
        } elseif ($first['han'] >= 5) {
            $survivalHit = $first['han'];
        } else {
            $survivalHit = $first['han'] . '/' . $first['fu'];
        }
        $survivalLastScore = $first['last_scores'];

        return ['name' => $survived, 'hit' => $survivalHit, 'lastScore' => $survivalLastScore];
    }
}
