<?php

class Points {
    public static function getRonPoints($han, $fu, $dealer) {
        return self::_calcPoints($han, $fu, false, $dealer);
    }

    public static function getTsumoPoints($han, $fu) {
        return self::_calcPoints($han, $fu, true, false);
    }

    protected static function _calcPoints($han, $fu, $tsumo, $dealer) {
        if ($han < 5) {
            $basePoints = $fu * pow(2, 2 + $han);
            $rounded = ceil($basePoints / 100.) * 100;
            $doubleRounded = ceil(2 * $basePoints / 100.) * 100;
            $timesFourRounded = ceil(4 * $basePoints / 100.) * 100;
            $timesSixRounded = ceil(6 * $basePoints / 100.) * 100;

            // mangan
            if ($basePoints >= 2000) {
                $rounded = 2000;
                $doubleRounded = $rounded * 2;
                $timesFourRounded = $doubleRounded * 2;
                $timesSixRounded = $doubleRounded * 3;
            }
        } else { // limits
            // yakuman
            if ($han >= 13) {
                $rounded = 8000;
            }

            // sanbaiman
            else if ($han >= 11) {
                $rounded = 6000;
            }

            // baiman
            else if ($han >= 8) {
                $rounded = 4000;
            }

            // haneman
            else if ($han >= 6) {
                $rounded = 3000;
            }

            else {
                $rounded = 2000;
            }

            $doubleRounded = $rounded * 2;
            $timesFourRounded = $doubleRounded * 2;
            $timesSixRounded = $doubleRounded * 3;
        }

        if ($tsumo) {
            return [
                'dealer' => $doubleRounded,
                'player' => $rounded
            ];
        } else {
            return $dealer ? $timesSixRounded : $timesFourRounded;
        }
    }
}
