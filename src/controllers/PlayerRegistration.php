<?php

class PlayerRegistration extends Controller
{
    protected function _showForm($error = '')
    {
        include "templates/PlayerRegistration.php";
    }

    protected function _run()
    {
        if (!isset($_POST['username'])) {
            $this->_showForm();
        } else {
            if ($_COOKIE['secret'] != ADMIN_COOKIE) {
                $this->_showForm("Секретное слово неправильное");
                return;
            }

            if (preg_match('#[^a-z0-9]+#is', $_POST['username'])) {
                $this->_showForm("В системном имени должны быть только латинские буквы и цифры, никаких пробелов");
                return;
            }

            $query = Db::connection()->prepare("SELECT COUNT(*) as cnt FROM players WHERE username = :uname");
            $query->bindParam(':uname', $_POST['username'], PDO::PARAM_STR);
            $query->execute();
            $count = $query->fetch(PDO::FETCH_ASSOC);
            if ($count['cnt'] != 0) {
                $this->_showForm("Такой пользователь уже есть в базе");
                return;
            }

            Db::exec("
                INSERT INTO players (username, alias, rating, games_played, places_sum)
                VALUES ('{$_POST['username']}', '{$_POST['alias']}', " . START_RATING . ", 0, 0)
            ");
            echo "Успешно зарегистрировали пользователя.";
            $this->_showForm();
        }
    }
}
