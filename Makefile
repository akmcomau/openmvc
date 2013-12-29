# update composer
update-composer:
	cd composer && curl -sS https://getcomposer.org/installer | php && php composer.phar install;

# update dependancies
update-depends:
	rm -f composer/vendor/ckeditor/ckeditor/plugins/mathjax
	rm -f composer/vendor/ckeditor/ckeditor/plugins/onchange
	rm -f composer/vendor/ckeditor/ckeditor/plugins/font

	cd composer && php composer.phar update;

	cd composer/vendor/ckeditor/ckeditor/plugins && ln -s ../../../../../core/themes/default/packages/ck_mathjax ./mathjax
	cd composer/vendor/ckeditor/ckeditor/plugins && ln -s ../../../../../composer/vendor/ckeditor/onchange ./onchange
	cd composer/vendor/ckeditor/ckeditor/plugins && ln -s ../../../../../composer/vendor/ckeditor/font ./font

# build dependancies
build-depends:
	echo "To build jQuery and jQuery-UI you must install the NPM dependances"
	echo "     cd composer/vendor/jquery/jquery && sudo npm install -g grunt-cli"
	echo "     cd composer/vendor/jquery/jquery-ui && sudo npm install -g grunt-cli"
	cd composer/vendor/jquery/jquery && npm install && grunt
	cd composer/vendor/jquery/jquery-ui && npm install && grunt && grunt build

# update composer
site-update-composer:
	cd sites/${SITE} && make update-composer;

# update dependancies
site-update-depends:
	cd sites/${SITE} && make update-depends;

# update dependancies
site-build-depends:
	cd sites/${SITE} && make build-depends;

# update dependancies
create-database:
	./bin/create_database.php
	./bin/create_database_data.php

# setup enviroment
setup-env:
	. ./bin/env.php

# buid all docs
build-docs:
	. bin/env.php
	make build-schemaspy-pgsql
	make build-doxygen

# run doxygen
build-doxygen:
	./bin/create_docs_mainpage.php && doxygen docs/doxygen.conf

# make database schema
build-schemaspy-pgsql:
	cd composer/vendor/schemaspy/schemaspy && \
		java net.sourceforge.schemaspy.Main -t pgsql \
		-o docs/schemaspy -dp ../postgres/ \
		-host ${PGHOST} -db ${PGDATABASE} -u ${PGUSER} -p ${PGPASSWORD} -s ${PGSCHEMA}
