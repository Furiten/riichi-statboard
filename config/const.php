<?php

define("RULES", 'JPML-A');

define("SORTITION_GROUPS_COUNT", 4); // количество независимых групп, внутри которых происходит 
                                     // перемешивание игроков при рассадке. При =2 получается сетка виннеров-лузеров.

define('DB_TYPE', 'mysql');
define('DB_NAME', 'statboard');
define('DB_USER', 'statboard');
define('DB_PASSWORD', 'statboard');
define('DB_HOST', 'localhost');

define('ADMIN_PASSWORD', 'hjpjdstckjybrb');
define('ADMIN_COOKIE', 'kldfmewmd9vbeiogbjsdvjepklsdmnvmn');

define('PARSER_LOG', false);
define('KIRIAGE_MANGAN', false);

switch(RULES) {
    case 'EMA2016':
        define('IS_ONLINE', false);
        define('START_RATING', '0');
        define('RIICHI_GO_TO_WINNER', true);

        // SIMPLE - простое сложение,
        // AVERAGE_SKILL - с учетом скилла игроков за столом
        define('RATING_FORMULA', 'SIMPLE');

        define('CHOMBO_PAYMENTS', false); // only rating penalty

        define("CHOMBO_PENALTY", 20000);
        define("UMA_1PLACE", 15000);
        define("UMA_2PLACE", 5000);
        define("UMA_3PLACE", -5000);
        define("UMA_4PLACE", -15000);
        define("START_POINTS", 30000);

        define("DIVIDER", 1); // делитель для очков; на tenhou равен 1000, на ron2 равен 100 -
        // соответственно делителю должны быть отредактированы ока, ума, чомбо пенальти

        define("RESULT_DIVIDER", 1.); // дополнительный делитель для вывода результатов на экран
    break;
    case 'JPML-A':
        define('IS_ONLINE', false);
        define('START_RATING', '1500');
        define('RIICHI_GO_TO_WINNER', false);

        // SIMPLE - простое сложение,
        // AVERAGE_SKILL - с учетом скилла игроков за столом
        define('RATING_FORMULA', 'SIMPLE');

        define('CHOMBO_PAYMENTS', true); // score + rating penalty

        define("CHOMBO_PENALTY", 200);
        define("UMA_1PLACE", 150);
        define("UMA_2PLACE", 50);
        define("UMA_3PLACE", -50);
        define("UMA_4PLACE", -150);
        define("START_POINTS", 30000);

        define("DIVIDER", 100); // делитель для очков; на tenhou равен 1000, на ron2 равен 100 -
        // соответственно делителю должны быть отредактированы ока, ума, чомбо пенальти

        define("RESULT_DIVIDER", 10.); // дополнительный делитель для вывода результатов на экран
        break;
    case 'TENHOUNET':
        define('IS_ONLINE', true);
        define('ALLOWED_LOBBY', '7994');
        define('START_RATING', '1500');
        define('RIICHI_GO_TO_WINNER', false);

        // SIMPLE - простое сложение,
        // AVERAGE_SKILL - с учетом скилла игроков за столом
        define('RATING_FORMULA', 'SIMPLE');

        define('CHOMBO_PAYMENTS', true); // score + rating penalty

        define("UMA_1PLACE", 15);
        define("UMA_2PLACE", 5);
        define("UMA_3PLACE", -5);
        define("UMA_4PLACE", -15);
        define("START_POINTS", 25000);

        define("DIVIDER", 1000); // делитель для очков; на tenhou равен 1000, на ron2 равен 100 -
        // соответственно делителю должны быть отредактированы ока, ума, чомбо пенальти

        define("RESULT_DIVIDER", 1.); // дополнительный делитель для вывода результатов на экран
        break;
}
