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

# update composer
site-update-composer:
	cd sites/${SITE} && make update-composer;

# update dependancies
site-update-depends:
	cd sites/${SITE} && make update-depends;

# update dependancies
site-build-depends:
	cd sites/${SITE} && make build-depends;

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
