<?php

return array(
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
    '/sortition/'     => 'Sortition',
    '/sortition/(?<seed>[0-9a-f]+)/' => 'Sortition',
    '/logs/'          => 'Logs',

    '/favicon.ico'    => 'Mainpage' // костылёк ^_^
);
