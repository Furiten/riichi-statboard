<?php

require_once 'scripts/Controller.php';

class Mainpage extends Controller {
    protected function _run() {
        include 'templates/Mainpage.php';
    }
}