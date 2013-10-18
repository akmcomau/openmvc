# update composer
update-composer:
	cd composer && curl -sS https://getcomposer.org/installer | php && php composer.phar install;

# update dependancies
update-depends:
	rm -f composer/vendor/ckeditor/ckeditor/plugins/mathjax
	rm -f composer/vendor/ckeditor/ckeditor/plugins/onchange

	cd composer && php composer.phar update;

	cd composer/vendor/ckeditor/ckeditor/plugins && ln -s ../../../../../core/themes/default/packages/ck_mathjax ./mathjax
	cd composer/vendor/ckeditor/ckeditor/plugins && ln -s ../../../../../composer/vendor/ckeditor/onchange ./onchange

# build dependancies
build-depends:
	cd composer/vendor/jquery/jquery && npm install && grunt
	cd composer/vendor/jquery/jquery-ui && npm install && grunt && grunt build

# install npm
install-npm-depends:
	cd composer/vendor/jquery/jquery && sudo npm install -g grunt-cli
	cd composer/vendor/jquery/jquery-ui && sudo npm install -g grunt-cli

# update dependancies
create-database:
	./bin/create_database.php;
	./bin/create_database_data.php;
