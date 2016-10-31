<?php

require_once 'scripts/base/Controller.php';

class Mainpage extends Controller
{
    protected function _run()
    {
        include 'templates/Mainpage.php';
    }
}
