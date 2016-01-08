<?php
/**
 * Main entry point
 */

require_once 'scripts/base/Controller.php';
$controller = Controller::makeInstance($_SERVER['REQUEST_URI']);
$controller->run();