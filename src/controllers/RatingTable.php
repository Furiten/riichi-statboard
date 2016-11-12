<?php

require_once __DIR__ . '/../helpers/Array.php';

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
            $players = $this->_api->execute('getAllPlayers', [TOURNAMENT_ID]);
            $players = ArrayHelpers::elm2Key($players, 'id');

            $data = $this->_api->execute('getRatingTable', [TOURNAMENT_ID, $orderBy, $order]);

            array_map(function($el) use (&$players) {
                // remove from common list - user exists in history
                unset($players[$el['id']]);
            }, $data);

            // Merge players who didn't finish yet into rating table
            $data = array_merge($data, array_map(function($el) {
                return array_merge($el, [
                    'rating'        => '0',
                    'winner_zone'   => true,
                    'avg_place'     => '0',
                    'games_played'  => '0'
                ]);
            }, array_values($players)));

            // Assign indexes for table view
            $ctr = 1;
            $data = array_map(function($el) use (&$ctr, &$players) {
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
