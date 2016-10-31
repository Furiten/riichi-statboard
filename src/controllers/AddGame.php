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
    /**
     * Член класса для сохранения данны о раундах, прилетевших из колбэков
     *
     * @var array
     */
    protected $_loggedRounds = [];

    /**
     * Данные о штрафах чомбо по каждому игроку
     *
     * @var array
     */
    protected $_chomboPenalties = [];

    /**
     * Показать форму добавления, если есть ошибка - вывести сообщение
     *
     * @param string $error
     */
    protected function _showForm($error = '')
    {
        try {
            $players = $this->_getRegisteredUsersList();
        } catch (Exception $e) {
            $players = [];
            $error = $e->getMessage();
        }

        include __DIR__ . '/../../templates/AddGame.php';
    }

    /**
     * Основной метод контроллера
     */
    protected function _run()
    {
        if (empty($_POST['content'])) { // пусто - показываем форму
            $this->_showForm();
        } else {
            // иначе пытаемся сохранить игру в базу
            if ($_COOKIE['secret'] != ADMIN_COOKIE) {
                $this->_showForm("Секретное слово неправильное");
                return;
            }

            try {
                $this->_api->execute('addTextLog', [TOURNAMENT_ID, $_POST['content']]);
            } catch (Exception $e) {
                $this->_showForm($e->getMessage());
                return;
            }

            echo "<h4>Игра успешно добавлена!</h4><br>";
            echo "Идем обратно через 3 секунды... <script type='text/javascript'>window.setTimeout(function() {window.location = '/add/';}, 3000);</script>";
        }
    }

    /**
     * Получение полного списка зарегистрированных юзеров
     * @throws
     */
    protected function _getRegisteredUsersList()
    {
        $players = $this->_api->execute('getAllPlayers', [TOURNAMENT_ID]);
        $aliases = [];
        foreach ($players as $v) {
            $aliases[$v['username']] = $v['alias'];
        }

        return $aliases;
    }
}
