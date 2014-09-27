<div class="row">
	<div class="span9">
<h2>Добавить игру</h2><br>
Формат:<br>
<pre>
[player][:][(-)?\d{,5}] [player][:][(-)?\d{,5}] [player][:][(-)?\d{,5}] [player][:][(-)?\d{,5}]
[1-4] ron [player] from [player] [5-12]han
[1-4] ron [player] from [player] [5-12]han dealer
[1-4] ron [player] from [player] [1-4]han \d{2,3}fu
[1-4] ron [player] from [player] [1-4]han \d{2,3}fu dealer
[1-4] ron [player] from [player] yakuman
[1-4] ron [player] from [player] yakuman dealer
[1-4] tsumo [player] [5-12]han
[1-4] tsumo [player] [5-12]han dealer
[1-4] tsumo [player] [1-4]han \d{2,3}fu
[1-4] tsumo [player] [1-4]han \d{2,3}fu dealer
[1-4] tsumo [player] yakuman
[1-4] tsumo [player] yakuman dealer
[1-4] draw tempai nobody
[1-4] draw tempai [player]
[1-4] draw tempai [player] [player]
[1-4] draw tempai [player] [player] [player]
[1-4] draw tempai all
[1-4] chombo [player]
[1-4] chombo [player] dealer
</pre>
Пример:<br>
<pre>
heilage:12300 Chaos:32000 Frontier:-2000 Manabi:30000
1 ron heilage 2han 30fu
2 ron Chaos 2han 40fu dealer
3 tsumo Frontier yakuman
4 draw
4 tsumo heilage 5han
4 ron heilage yakuman dealer
итд
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