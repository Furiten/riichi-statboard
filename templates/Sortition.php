<h2>Жеребьёвка</h2>
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
    <?php foreach ($sortition as $idx => $item) { ?>
        <?php if ($idx % 4 == 0) { ?><tr><td>Стол № <?php echo ($idx / 4) + 1; ?></td><?php } ?>
        <td>
            <?php echo $aliases[$item['username']];?>
        </td>
        <td>
            <span class="badge<?php if ($item['rating'] >= 1500) { echo ' badge-success'; } else {echo ' badge-important';}; ?>"><?php echo $item['rating'];?></span>
        </td>
        <?php if ($idx % 4 == 3) { ?></tr><?php } ?>
    <?php } ?>
</table>