<h2>Админство</h2>
<br>
<?php if(!empty($error)) { ?>
<div class="alert alert-error"><?php echo $error; ?></div>
<?php } ?>

<?php if(!empty($loggedIn)) { ?>
<div class="alert alert-error">Уже авторизованы!</div>
<?php } ?>
<form action="" method="post" class="well form-search">
    <input type="password" class="input-medium search-query" name="secret" placeholder='secret'>
    <button type="submit" class="btn">Авторизация</button>
</form>