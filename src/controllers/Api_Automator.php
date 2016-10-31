<?php

/**
 * TODO: replace this very basic automation support with nice api implementation!
 *
 * Class Api_Automator
 */

class Api_Automator extends Controller
{
    protected function _run()
    {
        layout::disable();
        header('Content-Type: application/json');
        $method = '_' . $this->_path['method'];
        if (is_callable([$this, $method])) {
            $data = $this->$method(json_decode(file_get_contents('php://input'), true));
            if (json_last_error()) {
                $data = ["code" => 400, "message" => json_last_error_msg()];
            }
        } else {
            $data = ["code" => 404, "message" => "Method not found"];
        }

        echo json_encode($data);
    }

    protected function _generateSortition()
    {
        require_once 'src/base/Controller.php';
        require_once 'src/controllers/Sortition.php';
        $controllerInstance = new Sortition('', []);
        $result = $controllerInstance->_genSort();
        if ($result) {
            return ['code' => 200, 'data' => ['seed' => $result]];
        }
        return ['code' => 500, 'message' => 'Failed to generate sortition'];
    }

    /**
     * Получить рассадку по ключу
     * Входные данные: payload => [seed => '...']
     */
    protected function _getSortition($data)
    {
        require_once 'src/base/Controller.php';
        require_once 'src/controllers/Sortition.php';
        $controllerInstance = new Sortition('', []);
        return ['code' => 200, 'data' => $controllerInstance->_getSort($data['seed'])];
    }

    /**
     * Начать новый ханчан
     * Входные данные: payload => [
     *      lobby_private_key => '...',
     *      player1 => '...',
     *      player2 => '...',
     *      player3 => '...',
     *      player4 => '...',
     * ]
     */
    protected function _startNewMatch($data)
    {
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => 'http://tenhou.net/cs/edit/start.cgi',
            CURLOPT_HEADER => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'Origin: http://tenhou.net',
                'Accept-Encoding: gzip, deflate',
                'Upgrade-Insecure-Requests: 1',
                'X-Compress: null',
                'User-Agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10_9_5) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/51.0.2704.84 Safari/537.36',
                'Content-Type: application/x-www-form-urlencoded',
                'Cache-Control: max-age=0',
                'Referer: http://tenhou.net/cs/edit/?C' . $data['lobby_private_key'],
                'Connection: keep-alive'
            ],
            CURLOPT_POSTFIELDS => "L=C{$data['lobby_private_key']}&R2=000B&RND=default&WG=1&M={$data['player1']}%0D%0A{$data['player2']}%0D%0A{$data['player3']}%0D%0A{$data['player4']}"
        ]);

        $out = curl_exec($curl);
        list($headers, $response) = explode("\r\n\r\n", $out, 2);
        $matches = [];
        if (preg_match('#tenhou\.net/cs/edit/done\.html\?C\d+\&MEMBER%20NOT%20FOUND(\S+)#is', $headers, $matches)) {
            $userlist = array_values(array_filter(preg_split('#[\n\r]+#is', rawurldecode($matches[1]))));
            return [
                'code' => '417',
                'message' => 'Users not found',
                'absentUsers' => $userlist
            ];
        }

        return ['code' => 200];
    }

    /**
     * Зарегистрировать игру
     * Входные данные: payload => [replay_link => '...']
     *
     * На выхлопе - данные о том, кто какое место занял с какими очками
     */
    protected function _registerReplay($data)
    {
        require_once 'src/base/Controller.php';
        require_once 'src/controllers/AddOnlineGame.php';
        $controllerInstance = new AddOnlineGame('', []);
        try {
            return ['code' => 200, 'data' => $controllerInstance->externalAddGame($data['replay_link'], false)];
        } catch (Exception $e) {
            return ["code" => 400, "message" => $e->getMessage()];
        }
    }

    /**
     * Получить четверку лидеров
     * Входных данных нет
     *
     * @return array
     */
    protected function _getLeaders()
    {
        $usersData = Db::get("SELECT players.username
            FROM players
            ORDER BY games_played DESC, rating DESC, place_avg ASC
            LIMIT 4
        ");

        $users = [];
        foreach ($usersData as $k => $v) {
            $users []= IS_ONLINE ? base64_decode($v['username']) : $v['username'];
        }

        return ['code' => 200, 'data' => $users];
    }
}
