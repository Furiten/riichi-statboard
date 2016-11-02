<?php

include_once __DIR__ . "/../helpers/Array.php";

class LastGames extends Controller
{
    protected function _run()
    {
        $limit = 10;
        $offset = 0;
        $currentPage = 1;

        if (isset($_GET['page']) && is_numeric($_GET['page'])) {
            $currentPage = (int)$_GET['page'];
            $offset = ($currentPage - 1) * $limit;
        }

        $gamesData = $this->_api->execute('getLastGames', [TOURNAMENT_ID, $limit, $offset]);
        include __DIR__ . '/../../templates/LastGames.php';
    }
}
