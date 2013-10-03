# update composer
update-composer:
	cd composer && curl -sS https://getcomposer.org/installer | php;

# update dependancies
update-depends:
	rm -f composer/vendor/ckeditor/ckeditor/plugins/pbckcode
	cd composer && php composer.phar install && php composer.phar update;
	cd composer/vendor/ckeditor/ckeditor/plugins && ln -s ../../PBCKCode/ ./pbckcode

# update dependancies
create-database:
	./bin/create_database.php;
	./bin/create_database_data.php;
