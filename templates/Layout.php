<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Riichi statboard</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="">
    <meta name="author" content="Oleg Klimenko @ Novosibirsk Mahjong Club">

    <link href="/assets/css/bootstrap.css" rel="stylesheet">
    <script src="/assets/js/jquery.js"></script>
    <script src="/assets/js/bootstrap.js"></script>
    <script type="text/javascript" src="/assets/js/jquery.jqplot.min.js"></script>
    <script type="text/javascript" src="/assets/js/jqplot.categoryAxisRenderer.min.js"></script>
    <script type="text/javascript" src="/assets/js/jqplot.barRenderer.min.js"></script>
    <script type="text/javascript" src="/assets/js/jqplot.highlighter.js"></script>
    <script type="text/javascript" src="/assets/js/jqplot.cursor.js"></script>
    <link rel="stylesheet" type="text/css" href="/assets/css/jquery.jqplot.min.css" />
    <link href="/assets/css/bootstrap-responsive.css" rel="stylesheet">
    <style>
        body {
            padding-top: 60px; /* 60px to make the container go all the way to the bottom of the topbar */
        }
    </style>

    <!--[if lt IE 9]>
    <script src="http://html5shim.googlecode.com/svn/trunk/html5.js"></script>
    <![endif]-->

    <link rel="shortcut icon" href="/assets/ico/favicon.ico">
    <link rel="apple-touch-icon-precomposed" sizes="144x144" href="/assets/ico/apple-touch-icon-144-precomposed.png">
    <link rel="apple-touch-icon-precomposed" sizes="114x114" href="/assets/ico/apple-touch-icon-114-precomposed.png">
    <link rel="apple-touch-icon-precomposed" sizes="72x72" href="/assets/ico/apple-touch-icon-72-precomposed.png">
    <link rel="apple-touch-icon-precomposed" href="/assets/ico/apple-touch-icon-57-precomposed.png">

    <link rel="stylesheet" type="text/css" href="/assets/styles.css">
</head>

<body>
<div class="navbar navbar-fixed-top">
    <div class="navbar-inner">
        <div class="container">
            <a class="btn btn-navbar" data-toggle="collapse" data-target=".nav-collapse">
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
            </a>
            <div class="nav-collapse">
                <ul class="nav">
                    <li><a href="/last/">Последние игры</a></li>
                    <li><a href="/stat/">Рейтинг</a></li>
                    <li><a href="/graphs/">Графики</a></li>
                    <li><a href="/nominations/">Номинации</a></li>
                    <?php if (IS_ONLINE) { ?>
                    <li><a href="/addonline/">+Онлайн-игра&nbsp;&nbsp;<span style="opacity: 0.5" class="icon-lock icon-white"></span></a></li>
                    <?php } else { ?>
                    <li><a href="/add/">+Игра&nbsp;&nbsp;<span style="opacity: 0.5" class="icon-lock icon-white"></span></a></li>
                    <?php } ?>
                    <li><a href="/reg/">+Игрок&nbsp;&nbsp;<span style="opacity: 0.5" class="icon-lock icon-white"></span></a></li>
                    <li><a href="/login/">Вход&nbsp;&nbsp;<span style="opacity: 0.5" class="icon-lock icon-white"></span></a></li>
                    <li><a href="/timer/" target="_blank">Таймер</a></li>
                    <li><a href="/sortition/gennew/" target="_blank"><span style="opacity: 0.5" class="icon-lock icon-white">Рассадка</a></li>
                </ul>
            </div>
        </div>
    </div>
</div>
<div class="container">
    <?php echo $content; ?>
</div>

</body>
</html>
