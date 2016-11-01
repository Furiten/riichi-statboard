<?php

class Graphs extends Controller
{
    protected function _run()
    {
        try {
            $currentUser = intval($_GET['user']);
            $data = $this->_api->execute('getPlayerStats', [$currentUser, TOURNAMENT_ID]);

            $usersMap = [];
            foreach ($data['players_info'] as $player) {
                $usersMap[$player['id']] = $player;
            }

            $integralData = [];
            $graphData = [];
            $i = 0;
            foreach ($data['rating_history'] as $row) {
                $graphData []= [$i++, floor($row['rating'])];
                $integralData []= $row['rating'];
            }

            $integralRating = $this->_integral($integralData);
        } catch (Exception $e) {
            $error = "Нет данных по указанному пользователю";
        }

        include "templates/Graphs.php";
    }

    protected function _integral($integralData)
    {
        $integralResult = 0;
        $dataCount = count($integralData);
        for ($i = 1; $i < $dataCount; $i++) {
            $integralResult += (
                ($integralData[$i-1] - 1500) +
                ($integralData[$i] - 1500)
            ) / 2.;
        }
        return $integralResult;
    }
}
