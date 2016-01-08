<?php

require_once 'scripts/helpers/Points.php';
require_once 'config/const.php';

class PointsCalc {
    protected $_points;
    protected $_pointsDiff = [];

    public function setPlayersList($players) {
        $this->_points = array_combine($players, [START_POINTS, START_POINTS, START_POINTS, START_POINTS]);
    }

    protected function _applyLostRiichi($riichiList, $winner) {
        $diffItem = [];
        foreach ($riichiList as $player) {
            if ($player != $winner) {
                $this->_points[$player] -= 1000;
                $diffItem []= ['player' => $player, 'value' => 1000, 'reason' => 'lostRiichi'];
            }
        }

        return $diffItem;
    }

    /**
     * @param $han
     * @param $fu
     * @param $winner
     * @param $loser
     * @param $honba
     * @param $riichiList
     * @param $totalRiichiCount включает в себя как текущие риичи на столе, так и риичи на кону
     * @param $currentDealer
     * @param bool $yakuman
     * @throws Exception
     */
    public function registerRon($han, $fu, $winner, $loser, $honba, $riichiList, $totalRiichiCount, $currentDealer, $yakuman = false) {
        if (!$this->_points) throw new Exception('setPlayersList must be called before any register method');
        $basicPoints = Points::getRonPoints($yakuman ? 13 : $han, $fu, $currentDealer == $winner);
        $lostPoints = $basicPoints + $honba * 300;
        $wonPoints = $lostPoints + $totalRiichiCount * 1000;
        $diffItem = [];

        $this->_points[$winner] += $wonPoints;
        $diffItem []= ['player' => $winner, 'value' => $wonPoints, 'type' => 'wonByRon'];
        $this->_points[$loser] -= $lostPoints;
        $diffItem []= ['player' => $loser, 'value' => $lostPoints, 'type' => 'lostByRon'];

        $this->_pointsDiff []= array_merge($diffItem, $this->_applyLostRiichi($riichiList, $winner));
    }

    /**
     * @param $han
     * @param $fu
     * @param $winner
     * @param $honba
     * @param $riichiList
     * @param $totalRiichiCount включает в себя как текущие риичи на столе, так и риичи на кону
     * @param $currentDealer
     * @param bool $yakuman
     * @throws Exception
     */
    public function registerTsumo($han, $fu, $winner, $honba, $riichiList, $totalRiichiCount, $currentDealer, $yakuman = false) {
        if (!$this->_points) throw new Exception('setPlayersList must be called before any register method');
        $basicPoints = Points::getTsumoPoints($yakuman ? 13 : $han, $fu);
        $diffItem = [];

        if ($winner == $currentDealer) {
            $lostPointsForSingleUser = $basicPoints['dealer'] + $honba * 100;
            foreach ($this->_points as $user => $points) {
                if ($user == $winner) continue;
                $this->_points[$user] -= $lostPointsForSingleUser;
                $diffItem []= ['player' => $user, 'value' => $lostPointsForSingleUser, 'type' => 'lostByTsumo'];
            }

            $wonPoints = $lostPointsForSingleUser * 3 + $totalRiichiCount * 1000;
            $this->_points[$winner] += $wonPoints;
            $diffItem []= ['player' => $winner, 'value' => $wonPoints, 'type' => 'wonByTsumo'];
        } else {
            foreach ($this->_points as $user => $points) {
                if ($user == $winner) continue;
                if ($user == $currentDealer) {
                    $lostPointsForSingleUser = $basicPoints['dealer'] + $honba * 100;
                } else {
                    $lostPointsForSingleUser = $basicPoints['player'] + $honba * 100;
                }
                $this->_points[$user] -= $lostPointsForSingleUser;
                $diffItem []= ['player' => $user, 'value' => $lostPointsForSingleUser, 'type' => 'lostByTsumo'];
            }

            $wonPoints = $basicPoints['dealer'] + $basicPoints['player'] * 2 + $totalRiichiCount * 1000 + $honba * 300;
            $this->_points[$winner] += $wonPoints;
            $diffItem []= ['player' => $winner, 'value' => $wonPoints, 'type' => 'wonByTsumo'];
        }

        $this->_pointsDiff []= array_merge($diffItem, $this->_applyLostRiichi($riichiList, $winner));
    }

    public function registerDraw($tempaiList, $riichiList) {
        if (!$this->_points) throw new Exception('setPlayersList must be called before any register method');
        if (count($tempaiList) > 4) return;
        $tempaiCount = array_reduce($tempaiList, function($carry, $item) {
            return $carry + ($item == 'tempai' ? 1 : 0);
        }, 0);

        if ($tempaiCount == 0 || $tempaiCount == 4) {
            $this->_pointsDiff []= ['type' => 'ultimate ' . ($tempaiCount == 0 ? 'noten' : 'tempai')];
            return;
        }

        $diffItem = [];
        $payments = [
            1 => 3000,
            2 => 1500,
            3 => 1000
        ];

        foreach ($tempaiList as $player => $status) {
            if ($status == 'tempai') {
                $this->_points[$player] += $payments[$tempaiCount];
                $diffItem []= ['player' => $player, 'value' => $payments[$tempaiCount], 'type' => 'wonByDraw'];
            } else {
                $this->_points[$player] -= $payments[4 - $tempaiCount];
                $diffItem []= ['player' => $player, 'value' => $payments[4 - $tempaiCount], 'type' => 'lostByDraw'];
            }
        }

        $this->_pointsDiff []= array_merge($diffItem, $this->_applyLostRiichi($riichiList, null));
    }

    public function registerChombo($loser, $currentDealer) {
        if (!$this->_points) throw new Exception('setPlayersList must be called before any register method');
        $basicPoints = Points::getTsumoPoints(5, 20);
        if ($loser == $currentDealer) {
            $wonPointsForSingleUser = $basicPoints['dealer'];
            foreach ($this->_points as $user => $points) {
                if ($user == $loser) continue;
                $this->_points[$user] += $wonPointsForSingleUser;
                $diffItem []= ['player' => $user, 'value' => $wonPointsForSingleUser, 'type' => 'gainedByChombo'];
            }

            $lostPoints = $wonPointsForSingleUser * 3;
            $this->_points[$loser] -= $lostPoints;
            $diffItem []= ['player' => $loser, 'value' => $lostPoints, 'type' => 'lostByChombo'];
        } else {
            foreach ($this->_points as $user => $points) {
                if ($user == $loser) continue;
                if ($user == $currentDealer) {
                    $wonPointsForSingleUser = $basicPoints['dealer'];
                } else {
                    $wonPointsForSingleUser = $basicPoints['player'];
                }
                $this->_points[$user] += $wonPointsForSingleUser;
                $diffItem []= ['player' => $user, 'value' => $wonPointsForSingleUser, 'type' => 'gainedByChombo'];
            }

            $lostPoints = $basicPoints['dealer'] + $basicPoints['player'] * 2;
            $this->_points[$loser] -= $lostPoints;
            $diffItem []= ['player' => $loser, 'value' => $lostPoints, 'type' => 'lostByChombo'];
        }
    }

    public function getResultPoints() {
        return $this->_points;
    }

    public function getLog() {
        return $this->_pointsDiff;
    }
}
