<?php

return [
    '/'               => 'Mainpage',
    '/login/'         => 'AdminLogin',
    '/last/.*'        => 'LastGames',
    '/add/'           => 'AddGame',
    '/addonline/'     => 'AddOnlineGame',
    '/graphs/.*'      => 'Graphs',
    '/reg/'           => 'PlayerRegistration',
    '/stat/.*'        => 'RatingTable',
    '/timer/.*'       => 'Timer',
    '/sortition/gennew/'     => 'Sortition',
    '/sortition/(?<seed>[0-9a-f]+)/' => 'Sortition',

    '/favicon.ico'    => 'Mainpage' // костылёк ^_^
];
