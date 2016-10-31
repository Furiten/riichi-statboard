<?php
error_reporting(8191);
ini_set('display_errors', 'on');

require_once 'src/base/Db.php';
require_once 'src/base/Controller.php';
require_once 'src/controllers/AddOnlineGame.php';

$controllerInstance = new AddOnlineGame('', []);
$links = Db::get("SELECT orig_link FROM game GROUP BY replay_hash");

Db::exec("TRUNCATE TABLE game");
Db::exec("TRUNCATE TABLE players");
Db::exec("TRUNCATE TABLE rating_history");
Db::exec("TRUNCATE TABLE result_score");
Db::exec("TRUNCATE TABLE round");
Db::exec("TRUNCATE TABLE sortition_cache");

try {
    foreach ($links as $link) {
        $controllerInstance->externalAddGame($link['orig_link']);
        sleep(1);
    }
} catch (Exception $e) {
    echo "Couldn't replay ratings sequence: " . PHP_EOL . $e->getMessage();
}
