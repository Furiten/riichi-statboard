<?php

class PlayerRegistration extends Controller
{
    protected $_mainTemplate = 'PlayerRegistration';

    protected function _run()
    {
        $errorMsg = '';
        $ident = '';
        $displayName = '';

        if (!empty($_POST['ident'])) {
            $ident = $_POST['ident'];
            $displayName = $_POST['display_name'];

            if ($_COOKIE['secret'] != ADMIN_COOKIE) {
                $errorMsg = "Секретное слово неправильное";
            } else if (preg_match('#[^a-z0-9]+#is', $_POST['ident'])) {
                $errorMsg = "В системном имени должны быть только латинские буквы и цифры, никаких пробелов";
            } else {
                try {
                    $playerId = $this->_api->execute('addPlayer', [
                        $_POST['ident'], $_POST['ident'], $_POST['display_name'], null
                    ]);
                    $this->_api->execute('registerPlayer', [TOURNAMENT_ID, $playerId]);
                } catch (Exception $e) {
                    $errorMsg = $e->getMessage();
                };
            }
        }

        return [
            'error' => $errorMsg,
            'ident' => $ident,
            'display_name' => $displayName
        ];
    }
}
