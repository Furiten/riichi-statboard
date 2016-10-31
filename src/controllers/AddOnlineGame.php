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
 * Добавление онлайн-игры
 */
class AddOnlineGame extends Controller
{
    /**
     * Показать форму добавления, если есть ошибка - вывести сообщение
     *
     * @param string $error
     */
    protected function _showForm($error = '')
    {
        include __DIR__ . '/../../templates/AddOnlineGame.php';
    }

    // TODO
//    protected function _checkGameExpired($replayHash)
//    {
//        $regex = '#(?<year>\d{4})(?<month>\d{2})(?<day>\d{2})(?<hour>\d{2})gm#is';
//        $matches = [];
//        if (preg_match($regex, $replayHash, $matches)) {
//            $date = mktime($matches['hour'], 0, 0, $matches['month'], $matches['day'], $matches['year']);
//            if (time() - $date < 27*60*60) { // 27 часов, чтобы покрыть разницу с JST
//                return;
//            }
//        }
//
//        throw new Exception('Добавляемая игра сыграна более чем сутки назад. Игра не принята из-за истечения срока годности.');
//    }

    // TODO: авторегистрация юзеров в онлайне

    /**
     * Основной метод контроллера
     */
    protected function _run()
    {
        if (empty($_POST['log'])) { // пусто - показываем форму
            $this->_showForm();
        } else {
            try {
                $this->_api->execute('addOnlineReplay', [TOURNAMENT_ID, $_POST['log']]);
            } catch (Exception $e) {
                $this->_showForm($e->getMessage());
                return;
            }

            echo "<h4>Игра успешно добавлена!</h4><br>";
            echo "Идем обратно через 3 секунды... <script type='text/javascript'>window.setTimeout(function() {window.location = '/addonline/';}, 3000);</script>";
        }
    }
}
