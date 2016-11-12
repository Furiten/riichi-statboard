<?php

class RatingTable extends Controller
{
    protected $_mainTemplate = 'RatingTable';

    protected function _run()
    {
        $errMsg = '';
        $data = null;

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

        try {
            $data = $this->_api->execute('getRatingTable', [TOURNAMENT_ID, $orderBy, $order]);

            $ctr = 1;
            $data = array_map(function($el) use (&$ctr) {
                $el['_index'] = $ctr++;
                return $el;
            }, $data);
        } catch (Exception $e) {
            $errMsg = $e->getMessage();
        }

        return [
            'error'             => $errMsg,
            'data'              => $data,

            'orderDesc'         => $order == 'desc',

            'orderByRating'     => $orderBy == 'rating',
            'orderByAvgPlace'   => $orderBy == 'avg_place',
            'orderByName'       => $orderBy == 'name',
        ];
    }
}
