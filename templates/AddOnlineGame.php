<div class="row">
	<div class="span9">
<h2>Добавить онлайн-игру</h2><br>
<?php if(!empty($error)) { ?>
<div class="alert alert-error"><?php echo $error; ?></div>
<?php } ?>
<form action="" method="POST" id="addform">
    <input type="text" name="log" value="<?php if (!empty($_POST['content'])) echo $_POST['content']; ?>">
    <div class="row">
        <div class="span10">
            <input type="submit" value="Добавить" class="btn btn-primary btn-large">
        </div>
    </div>

</form>
</div>