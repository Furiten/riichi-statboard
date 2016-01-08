<div class="row">
	<div class="span9">
<h2>Добавить игру</h2><br>
Формат:<br>
<pre>
[player][:][(-)?\d{,5}] [player][:][(-)?\d{,5}] [player][:][(-)?\d{,5}] [player][:][(-)?\d{,5}]
ron [player] from [player] [5-12]han riichi [player] [player]
ron [player] from [player] [5-12]han
ron [player] from [player] [1-4]han \d{2,3}fu
ron [player] from [player] yakuman
tsumo [player] [5-12]han riichi [player] [player]
tsumo [player] [5-12]han
tsumo [player] [1-4]han \d{2,3}fu
tsumo [player] yakuman
draw tempai nobody
draw tempai [player] riichi [player] [player]
draw tempai [player] [player]
draw tempai [player] [player] [player]
draw tempai all
chombo [player]
</pre>
Пример:<br>
<pre>
heilage:12300 Chaos:32000 Frontier:-2000 Manabi:30000
ron heilage from Chaos 2han 30fu riichi Frontier
ron Chaos from Frontier 2han 40fu
tsumo Frontier yakuman riichi heilage Chaos
draw tempai nobody riichi Manabi
tsumo heilage 5han
ron heilage from Chaos yakuman
итд
Ренчаны и хонба высчитываются автоматически. Также производится проверка начисленных очков и выводится предупреждение в случае несоответствия.
</pre>
<?php if(!empty($error)) { ?>
<div class="alert alert-error"><?php echo $error; ?></div>
<?php } ?>
<form action="" method="POST" id="addform">
    <textarea name="content" style="width:100%; height: 300px"><?php if (!empty($_POST['content'])) echo $_POST['content']; ?></textarea>
    <div class="row">
        <div class="span10">
            <input type="submit" value="Добавить" class="btn btn-primary btn-large">
        </div>
    </div>

</form>
<?php /*<ul style="padding-left:30px" id="errors" class="alert alert-error"></ul>*/ ?>
    </div>
	<div class="span3">
		<h5>Алиасы</h5>
        <table border=0 class='table table-condensed'>
		<?php
			foreach ($aliases as $user => $alias) {
				echo "<tr><td>{$alias}</td><td> = </td><td>{$user}</td></tr>" . PHP_EOL;
			}
		?>
		</table>
	</div>
</div>