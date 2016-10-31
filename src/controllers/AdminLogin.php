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

class AdminLogin extends Controller
{
    protected function _showForm($error = '')
    {
        $loggedIn = ($_COOKIE['secret'] == ADMIN_COOKIE);
        include __DIR__ . "/../../templates/AdminLogin.php";
    }

    protected function _run()
    {
        if (!isset($_POST['secret'])) {
            $this->_showForm();
        } else {
            if ($_POST['secret'] != ADMIN_PASSWORD) {
                $this->_showForm("Wrong password!");
                return;
            }

            setcookie('secret', ADMIN_COOKIE, time() + 3600, '/');
            header('Location: ' . $this->_url);
        }
    }
}
