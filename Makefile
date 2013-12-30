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

# update composer
site-update-composer:
	cd sites/${SITE} && make update-composer;

# update dependancies
site-update-depends:
	cd sites/${SITE} && make update-depends;

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
