<style>
    th.rotate {
        /* Something you can count on */
        height: 75px;
        white-space: nowrap;
        vertical-align: inherit;
    }

    th.rotate > div {
        transform:
            /* Magic Numbers */
            translate(-1px, 23px)
                /* 45 is really 360 - 45 */
            rotate(315deg);
        width: 30px;
    }
    th.rotate > div > span {
        padding: 5px 10px;
    }
    td.intsect {
        text-align:center;
        border: 1px solid #333;
    }
    td.intsect.gray {
        background-color: #888;
    }
</style>
<h2>Жеребьёвка</h2>
<?php if (!$isApproved) { ?>
<form action="" method="post">
    <input type="hidden" name="factor" value="<?php echo dechex($randFactor); ?>" />
    <input type="submit" class="btn btn-success" value="Утвердить!"
           onclick="javascript: return prompt('Утвердить эту рассадку для следующей игры?');" />
</form>
<a href='/sortition/gennew/' class="btn btn-warning" style="float: right">Следующая рассадка</a>
<?php } else echo 'Утвержденная рассадка. '; ?>
<br>
<table class="table table-striped">
    <tr>
        <th># стола</th>
        <th>ВОСТОК</th>
        <th></th>
        <th>ЮГ</th>
        <th></th>
        <th>ЗАПАД</th>
        <th></th>
        <th>СЕВЕР</th>
        <th></th>
    </tr>
    <?php foreach ($tables as $idx => $table) { ?>
        <tr>
            <td>Стол № <?php echo $idx + 1; ?></td>
            <?php foreach ($table as $item) { ?>
            <td>
                <?php echo $aliases[$item['username']];?>
            </td>
            <td>
                <span class="badge<?php if ($item['rating'] >= 1500) { echo ' badge-success'; } else {echo ' badge-important';}; ?>"><?php echo $item['rating'];?></span>
            </td>
            <?php } ?>
        </tr>
    <?php } ?>
</table>
<hr />
<h3>Пересечения</h3>
<br>
<table cellpadding="3">
    <tr>
        <th>&nbsp;</th>
        <?php foreach ($sortition as $item) { ?>
            <th class="rotate"><div><span><?php echo $aliases[$item['username']];?></span></div></th>
        <?php } ?>
    </tr>
    <?php foreach ($sortition as $item1) { ?>
        <tr>
            <td><?php echo $aliases[$item1['username']];?></td>
            <?php foreach ($sortition as $item2) { ?>
                <?php
                    $itemKey1 = $item1['username']."+++".$item2['username'];
                    $itemKey2 = $item2['username']."+++".$item1['username'];
                    if (!empty($bestIntersection[$itemKey1]) || !empty($bestIntersection[$itemKey2])) {
                        $cnt = (empty($bestIntersection[$itemKey1]) ? 0 : $bestIntersection[$itemKey1]) +
                            (empty($bestIntersection[$itemKey2]) ? 0 : $bestIntersection[$itemKey2]);

                        $classesList = array('badge');
                        if ($cnt == 2) {
                            $classesList []= 'badge-warning';
                        } elseif ($cnt >= 3) {
                            $classesList []= 'badge-important';
                        }
                        echo '<td class="intsect"><span class="' . implode(' ', $classesList) . '">' . $cnt . '</span></td>';
                    } else {
                        if ($item1['username'] == $item2['username']) {
                            echo '<td class="intsect gray">&nbsp;</td>';
                        } else {
                            echo '<td class="intsect">0</td>';
                        }
                    }
                ?>
            <?php } ?>
        </tr>
    <?php } ?>

</table>
