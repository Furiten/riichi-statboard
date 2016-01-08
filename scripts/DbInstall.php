<?php

require_once 'scripts/base/Db.php';

switch (DB_TYPE) {
    case 'mysql':
        installMysql();
        break;
}

function installMysql() {
    $tables = array(
        'game' => "
        CREATE TABLE IF NOT EXISTS `game` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `replay_hash` varchar(255),
            `orig_link` text,
            `play_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `ron_count` int(11) NOT NULL DEFAULT '0',
            `tsumo_count` int(11) NOT NULL DEFAULT '0',
            `drawn_count` int(11) NOT NULL DEFAULT '0',
            PRIMARY KEY (`id`),
            KEY `replay_hash` (`replay_hash`)
        ) ENGINE=MyISAM  DEFAULT CHARSET=utf8;",
        'players' => "
        CREATE TABLE IF NOT EXISTS `players` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `username` varchar(255) NOT NULL,
            `alias` varchar(255) NOT NULL,
            `rating` float NOT NULL DEFAULT '0',
            `games_played` int(11) NOT NULL DEFAULT '0',
            `places_sum` int(11) NOT NULL DEFAULT '0',
            `place_avg` float NOT NULL DEFAULT '0',
            PRIMARY KEY (`id`),
            UNIQUE KEY `username` (`username`),
            KEY `alias` (`alias`)
        ) ENGINE=MyISAM  DEFAULT CHARSET=utf8;",
        'rating_history' => "
        CREATE TABLE IF NOT EXISTS `rating_history` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `username` varchar(255) COLLATE utf8_bin NOT NULL,
            `game_id` int(11) NOT NULL,
            `rating` float NOT NULL,
            PRIMARY KEY (`id`),
            KEY `username` (`username`)
        ) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_bin;",
        'result_score' => "
        CREATE TABLE IF NOT EXISTS `result_score` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `game_id` int(11) NOT NULL,
            `username` varchar(255) NOT NULL DEFAULT '',
            `score` int(11) NOT NULL DEFAULT '0',
            `result_score` float NOT NULL DEFAULT '0',
            `place` int(11) NOT NULL DEFAULT '0',
            PRIMARY KEY (`id`),
            KEY `game_id` (`game_id`),
            KEY `username` (`username`)
        ) ENGINE=MyISAM  DEFAULT CHARSET=utf8;",
        'round' => "
        CREATE TABLE IF NOT EXISTS `round` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `game_id` int(11) NOT NULL,
            `username` varchar(255) NOT NULL,
            `han` int(11) NOT NULL DEFAULT '0',
            `fu` int(11) NOT NULL DEFAULT '0',
            `yakuman` tinyint(4) NOT NULL DEFAULT '0',
            `dealer` tinyint(4) NOT NULL DEFAULT '0',
            `round` tinyint(4) NOT NULL,
            `result` enum('ron','tsumo','draw','chombo') NOT NULL,
            `loser` varchar(255) NOT NULL,
            `tempai_list` varchar(255) NOT NULL DEFAULT '',
            `yaku` varchar(255) NOT NULL,
            `dora` int(11) NOT NULL DEFAULT '0',
            PRIMARY KEY (`id`),
            KEY `game_id` (`game_id`),
            KEY `username` (`username`),
            KEY `han` (`han`,`fu`),
            KEY `yakuman` (`yakuman`),
            KEY `loser` (`loser`),
            KEY `yaku` (`yaku`),
            KEY `dora` (`dora`)
        ) ENGINE=MyISAM  DEFAULT CHARSET=utf8;",
        'yaku' => "
        CREATE TABLE IF NOT EXISTS `yaku` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `title` varchar(255) NOT NULL,
            PRIMARY KEY (`id`),
            KEY `title` (`title`)
        ) ENGINE=MyISAM  DEFAULT CHARSET=utf8;",
        'sortition_cache' => "
        CREATE TABLE IF NOT EXISTS `sortition_cache` (
            `hash` varchar(24) NOT NULL,
            `data` TEXT NOT NULL,
            `is_confirmed` tinyint(4) NOT NULL DEFAULT '0',
            PRIMARY KEY (`hash`)
        ) ENGINE=MyISAM  DEFAULT CHARSET=utf8;",
        'tables' => "
        CREATE TABLE IF NOT EXISTS `tables` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `player_num` int(11) NOT NULL,
            `username` varchar(255) NOT NULL,
            PRIMARY KEY (`id`),
            KEY `username` (`username`)
        ) ENGINE=MyISAM  DEFAULT CHARSET=utf8;"
    );

    $yakuData = "
        INSERT INTO `yaku` (`id`, `title`) VALUES
            (34, 'Дабл риичи'),
            (19, 'Дайсанген'),
            (21, 'Дайсууши'),
            (25, 'Джунчан'),
            (9, 'Иипейко'),
            (35, 'Иппацу'),
            (12, 'Иццу'),
            (32, 'Кокушимусо'),
            (36, 'Мендзен цумо'),
            (8, 'Пин-фу'),
            (43, 'Ренхо'),
            (33, 'Риичи'),
            (38, 'Риншан кайхо'),
            (30, 'Рюисо'),
            (10, 'Рянпейко'),
            (3, 'Сананко'),
            (5, 'Санканцу'),
            (11, 'Саншоку'),
            (4, 'Саншоку доко'),
            (7, 'Сууанко'),
            (6, 'Сууканцу'),
            (23, 'Тан-яо'),
            (39, 'Тенхо'),
            (1, 'Тойтой'),
            (37, 'Хайтей'),
            (27, 'Хоницу'),
            (2, 'Хонрото'),
            (41, 'Хотей'),
            (22, 'Цууисо'),
            (42, 'Чанкан'),
            (24, 'Чанта'),
            (31, 'Чиитойцу'),
            (28, 'Чиницу'),
            (26, 'Чинрото'),
            (40, 'Чихо'),
            (29, 'Чууренпото'),
            (18, 'Шосанген'),
            (20, 'Шосууши'),
            (13, 'Якухай 1'),
            (14, 'Якухай 2'),
            (15, 'Якухай 3'),
            (16, 'Якухай 4'),
            (17, 'Якухай 5');
    ";

    try {
        foreach ($tables as $table => $sql) {
            Db::exec($sql);
        }
        Db::exec($yakuData);
    } catch (Exception $e) {
        echo "Couldn't install database." . PHP_EOL . $e->getMessage();
    }
}
