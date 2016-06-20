<?php

return [
    '/'               => 'Mainpage',
    '/login/'         => 'AdminLogin',
    '/last/.*'        => 'LastGames',
    '/add/'           => 'AddGame',
    '/addonline/'     => 'AddOnlineGame',
    '/graphs/.*'      => 'Graphs',
    '/nominations/'   => 'Nominations',
    '/reg/'           => 'PlayerRegistration',
    '/stat/.*'        => 'PlayersStat',
    '/timer/.*'       => 'Timer',
    '/sortition/gennew/'     => 'Sortition',
    '/sortition/(?<seed>[0-9a-f]+)/' => 'Sortition',

    '/api/1.0/(?<method>[a-zA-Z]+)/'       => 'Api_Automator',

    '/favicon.ico'    => 'Mainpage' // костылёк ^_^
];
