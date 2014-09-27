<h2>Регистрация игрока</h2>
<br>
<?php if(!empty($error)) { ?>
<div class="alert alert-error"><?php echo $error; ?></div>
<?php } ?>
<form action="" method="post" class="well form-search">
    <input type="text" class="input-medium search-query" name="username" placeholder='Системное имя'<?php if (isset($_POST['username'])) { echo " value='" . $_POST['username'] . "'"; } ?>>
    <input type="text" class="input-medium search-query" name="alias" placeholder='Реальный ник'<?php if (isset($_POST['alias'])) { echo " value='" . $_POST['alias'] . "'"; } ?>>
    <button type="submit" class="btn">Зарегистрировать</button>
</form>