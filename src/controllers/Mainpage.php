<?php

require_once 'src/base/Controller.php';

class Mainpage extends Controller
{
    protected function _run()
    {
        include 'templates/Mainpage.php';
    }
}
