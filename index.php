<?php
/**
 * Main entry point
 */

require_once 'scripts/Controller.php';
$controller = Controller::makeInstance($_SERVER['REQUEST_URI']);
$controller->run();