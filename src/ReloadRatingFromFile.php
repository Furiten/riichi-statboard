<?php
error_reporting(8191);
ini_set('display_errors', 'on');

require_once 'config/const.php';
require_once 'scripts/base/Db.php';
require_once 'scripts/base/Controller.php';
require_once 'scripts/controllers/AddGame.php';

$controllerInstance = new AddGame('', []);

Db::exec("TRUNCATE TABLE game");
Db::exec("UPDATE players SET rating = " . START_RATING . ", games_played = 0, places_sum = 0, place_avg = 0");
Db::exec("TRUNCATE TABLE rating_history");
Db::exec("TRUNCATE TABLE result_score");
Db::exec("TRUNCATE TABLE round");
Db::exec("TRUNCATE TABLE sortition_cache");

try {
    $contents = file_get_contents($argv[1]);
    $contents = preg_split("#\n{2,}#is", $contents);

    foreach ($contents as $piece) {
        $controllerInstance->externalAddGame($piece);
    }
} catch (Exception $e) {
    echo "Couldn't replay ratings sequence: " . PHP_EOL . $e->getMessage();
}
