# update composer
update-composer:
	cd composer && curl -sS https://getcomposer.org/installer | php;

# update dependancies
update-depends:
	cd composer && php composer.phar install;
