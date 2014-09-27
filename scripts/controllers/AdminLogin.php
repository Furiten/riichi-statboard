<?php

class AdminLogin extends Controller {
    protected function _showForm($error = '')
    {
		$loggedIn = ($_COOKIE['secret'] == ADMIN_COOKIE);
        include "templates/AdminLogin.php";
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
