<?php
/*  Riichi mahjong stat GUI
 *  Copyright (C) 2016  o.klimenko aka ctizen
 *
 *  This program is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * Добавление игры
 */
class AddGame extends Controller
{
    protected $_mainTemplate = 'AddGame';

    /**
     * Основной метод контроллера
     */
    protected function _run()
    {
        $players = [];
        $errorMsg = '';
        $successfullyAdded = false;

        try {
            $players = $this->_api->execute('getAllPlayers', [TOURNAMENT_ID]);
            if (!empty($_POST['content'])) {
                // пытаемся сохранить игру в базу
                if (empty($_COOKIE['secret']) || $_COOKIE['secret'] != ADMIN_COOKIE) {
                    $errorMsg = "Секретное слово неправильное";
                } else {
                    $this->_api->execute('addTextLog', [TOURNAMENT_ID, $_POST['content']]);
                    $successfullyAdded = true;
                }
            }
        } catch (Exception $e) {
            $errorMsg = $e->getMessage();
        }

        return [
            'players'           => $players,
            'error'             => $errorMsg,
            'text'              => empty($_POST['content']) ? '' : $_POST['content'],
            'successfullyAdded' => $successfullyAdded
        ];
    }
}
