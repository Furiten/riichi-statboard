<?php

require_once 'scripts/Db.php';
require_once 'scripts/controllers/AddOnlineGame.php';

$controllerInstance = new AddOnlineGame('', array());
$links = Db::get("SELECT orig_link FROM game");
Db::exec('TRUNCATE TABLE game');
Db::exec('TRUNCATE TABLE players');
Db::exec('TRUNCATE TABLE rating_hsitory');
Db::exec('TRUNCATE TABLE result_score');
Db::exec('TRUNCATE TABLE round');
Db::exec('TRUNCATE TABLE sortition_cache');
 
try {
    foreach ($links as $link) {
        $controllerInstance->externalAddGame($link['orig_link']);
        sleep(1);
    }
} catch (Exception $e) {
    echo "Couldn't replay ratings sequence: " . PHP_EOL . $e->getMessage();
}

