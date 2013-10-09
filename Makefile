# update composer
update-composer:
	cd composer && curl -sS https://getcomposer.org/installer | php;

# update dependancies
update-depends:
	rm -f composer/vendor/ckeditor/ckeditor/plugins/mathjax
	rm -f composer/vendor/ckeditor/ckeditor/plugins/onchange

	cd composer && php composer.phar install && php composer.phar update;

	cd composer/vendor/ckeditor/ckeditor/plugins && ln -s ${PWD}/core/themes/default/ck_mathjax ./mathjax
	cd composer/vendor/ckeditor/ckeditor/plugins && ln -s ${PWD}/composer/vendor/ckeditor/onchange ./onchange

# update dependancies
create-database:
	./bin/create_database.php;
	./bin/create_database_data.php;
