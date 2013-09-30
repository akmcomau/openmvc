# update composer
update-composer:
	cd composer && curl -sS https://getcomposer.org/installer | php;

# update dependancies
update-depends:
	cd composer && php composer.phar install && php composer.phar update;

# update dependancies
create-database:
	./bin/create_database.php;

# update dependancies
create-database-data:
	./bin/create_database_data.php;
