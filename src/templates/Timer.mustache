<?php if ($getNewUsersData): ?>
	<?php $counter = 1; foreach ($usersData as $item) { ?>
    <div style="display: none;">
		<?php /*<td style="width: 100px"># <?php echo $counter++; ?></td> */ ?>
        <div style="height: 30px; display: table-row;">
            <div style="width: 300px; display: table-cell"><?php echo $aliases[$item['username']];?></div>
            <div style="width: 200px; display: table-cell"><span class="<?php if ($item['rating'] >= 1500) { echo 'success'; } else {echo 'fail';}; ?>"><?php echo $item['rating'];?></span></div>
        </div>
    </div>
	<?php } ?>
<?php else: ?>

<style type="text/css">
    .players {
        width: 100%;
    }

    .players div {
        font-size: 45px;
        padding: 12px;
    }

    .players > div {
        position: absolute;
    }

    span.success {
        color: #393;
    }

    span.fail {
        color: #c44;
    }

    .navbar-inner {
        display: none;
    }

    .timer {
        font-size: 300px;
        font-family: monospace;
        font-weight: bold;
        margin-top: 200px;
        text-align: center;
    }
</style>

<script type="text/javascript">
    Array.prototype.shuffle = function() {
        var i = this.length, j, tempi, tempj;
        if ( i == 0 ) return false;
        while ( --i ) {
            j       = Math.floor( Math.random() * ( i + 1 ) );
            tempi   = this[i];
            tempj   = this[j];
            this[i] = tempj;
            this[j] = tempi;
        }
        return this;
    };

    $(function() {
        var table = $('#table-players');
        var rowsCount = $('#table-players > div').length;
        var orderArray = [];
        for (var i = 0; i < rowsCount; i ++) {
            orderArray.push(i);
        }

        orderArray = orderArray.shuffle();

        var counter = 0;
        var tabletimer = window.setInterval(function() {
            table.children('div:visible').animate({
                opacity: 0,
                marginTop: -30
            }, 300);
            var randowRow = orderArray[(counter++) % rowsCount];
            table.children('div:eq(' + randowRow + ')').css({opacity: 0, paddingTop: 30}).show().animate({
                opacity: 1,
                paddingTop: 0
            }, 300);

			// request new content every minute
			if (counter % 12 == 0) {
				$('#table-players').load('/timer.php?newData=1', function() {
					// update it all...
                    rowsCount = $('#table-players > div').length;
                    orderArray = [];
                    for (var i = 0; i < rowsCount; i ++) {
                        orderArray.push(i);
                    }

                    orderArray = orderArray.shuffle();
				});
			}
        }, 5000);


        var snd5min = new Audio("/assets/5min.wav"); // buffers automatically when created
        var sndend = new Audio("/assets/endgame.wav"); // buffers automatically when created

        var initialTime = '<?php if (!empty($_GET['init'])) { echo $_GET['init']; } else echo '45:00'; ?>'.split(':');
        var minutes = parseInt(initialTime[0]);
        var seconds = parseInt(initialTime[1]) / 10;
        var timer = window.setInterval(function() {
            if (seconds == 0) {
                minutes --;
                seconds = 6;
            }

            seconds--;
            var addedZero = '';
            if (seconds < 1) {
                addedZero = '0';
            }

            if (minutes < 5) {
                $('#time').css({color: '#f55'});
                if (snd5min) {
                    snd5min.play();
                    snd5min = false; // нам надо проиграть звук только один раз
                }
            }

            if (minutes < 0) {
                window.clearInterval(timer);
                $('#time').html('СТОП!');
                if (sndend) {
                    sndend.play();
                    sndend = false; // нам надо проиграть звук только один раз
                }
            } else {
                $('#time').html(minutes + ':' + addedZero + seconds * 10);
            }
        }, 10000);
    });
</script>

<div class="players" id="table-players" style="margin-left: 300px; margin-right: 300px">
    <?php $counter = 1; foreach ($usersData as $item) { ?>
    <div style="display: none;">
        <?php /*<td style="width: 100px"># <?php echo $counter++; ?></td> */ ?>
        <div style="height: 30px; display: table-row;">
            <div style="width: 300px; display: table-cell"><?php echo $aliases[$item['username']];?></div>
            <div style="width: 200px; display: table-cell"><span class="<?php if ($item['rating'] >= 1500) { echo 'success'; } else {echo 'fail';}; ?>"><?php echo $item['rating'];?></span></div>
        </div>
    </div>
    <?php } ?>
</div>

<div class="timer">
    <span id="time"><?php if (!empty($_GET['init'])) { echo $_GET['init']; } else echo '45:00'; ?></span>
</div>


<?php endif; ?>