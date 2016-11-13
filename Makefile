SQLITE_FILE ?= data/db.sqlite

hooks:
	cp -pnrf bin/hooks/* .git/hooks
	chmod a+x .git/hooks/*

deps: hooks
	php bin/composer.phar install

lint:
	php vendor/bin/phpcs --config-set default_standard PSR2
	php vendor/bin/phpcs --config-set show_warnings 0
	php vendor/bin/phpcs src tests

unit:
	php bin/unit.php

check: lint unit

autofix:
	php vendor/bin/phpcbf --config-set default_standard PSR2
	php vendor/bin/phpcbf --config-set show_warnings 0
	php vendor/bin/phpcbf src tests
