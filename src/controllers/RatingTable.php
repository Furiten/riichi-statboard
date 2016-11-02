<?php

class PlayersStat extends Controller
{
    protected function _run()
    {
        if (!isset($_GET['sort'])) {
            $_GET['sort'] = '';
        }

        $order = empty($_GET['order']) ? '' : $_GET['order'];
        if ($order != 'asc' && $order != 'desc') {
            $order = '';
        }

        switch ($_GET['sort']) {
            case 'rating':
                $orderBy = $_GET['sort'];
                if (empty($_GET['order'])) {
                    $order = 'desc';
                }
                break;
            case 'avg_place':
                $orderBy = $_GET['sort'];
                if (empty($_GET['order'])) {
                    $order = 'asc';
                }
                break;
            case 'name':
                $orderBy = $_GET['sort'];
                if (empty($_GET['order'])) {
                    $order = 'asc';
                }
                break;
            default:;
                $orderBy = 'rating';
                if (empty($_GET['order'])) {
                    $order = 'desc';
                }
        }

        $data = $this->_api->execute('getRatingTable', [TOURNAMENT_ID, $orderBy, $order]);
        include "templates/PlayersStat.php";
    }
}
