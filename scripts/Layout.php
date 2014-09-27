<?php

class Layout {
	protected static $_disabled = false;

	public static function init()
	{
		ob_start();
	}

	public static function show()
	{
		$content = ob_get_clean();
		header ("Content-type: text/html; charset=utf-8");
		if (!self::$_disabled) {
        	include "templates/Layout.php";
		} else {
			echo $content;
		}
	}

	public static function disable()
	{
		self::$_disabled = true;
	}
}

