<?php

return array(
    '/'               => 'Mainpage',
    '/login/'         => 'AdminLogin',
    '/last/.*'        => 'LastGames',
    '/add/'           => 'AddGame',
    '/graphs/.*'      => 'Graphs',
    '/nominations/'   => 'Nominations',
    '/reg/'           => 'PlayerRegistration',
    '/stat/.*'        => 'PlayersStat',
    '/timer/.*'       => 'Timer',
    '/sortition/'     => 'Sortition',

    '/favicon.ico'    => 'Mainpage' // костылёк ^_^
);
