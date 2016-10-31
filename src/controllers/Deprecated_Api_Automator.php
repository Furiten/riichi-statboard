<?php

/**
 * TODO: replace this very basic automation support with nice api implementation!
 *
 * Class Api_Automator
 */

class Api_Automator
{
    /**
     * TODO: унести в апи?
     *
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

}
