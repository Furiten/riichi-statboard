<h2>Номинации</h2>

<?php if (!$nominations) { ?>
    <p>Недостаточно данных. Подождите пока будут добавлены 4 игры.</p>
<?php } else { ?>

    <?php if ($nominations['survived']) {
        $nomination = $nominations['survived'];
    ?>
        <hr>

        <h3>В номинации "Жив, цел, орёл"</h3>
        <p>
            Побеждает игрок <a href="/graphs/?user=<?php echo $nomination['name']; ?>"><?php echo $nomination['alias']; ?></a>,
            которому удалось остаться в живых к концу игры
            с <?php echo $nomination['lastScore']; ?> очками, получив прямой удар по рон
            в размере <?php if ($nomination['hit'] == 'yakuman') {
                echo 'якумана';
            } else {
                echo $nomination['hit'] . ' хан';
            } ?>.
        </p>
    <?php } ?>

    <?php if ($nominations['stranger']) {
        $nomination = $nominations['stranger'];
    ?>
        <hr>

        <h3>В номинации "Мимокрокодил"</h3>
        <p>
            Побеждает игрок <a href="/graphs/?user=<?php echo $nomination['name']; ?>"><?php echo $nomination['alias']; ?></a>
            с <?php echo $nomination['wins']; ?>/<?php echo $nomination['loses']; ?> случаями выигрышей/выплат.</p>
        <p>При среднем значении <?php echo $nomination['averageWins']; ?>/<?php echo $nomination['averageLoses']; ?>.</p>
    <?php } ?>

<?php } ?>