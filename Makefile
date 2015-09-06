
BASE=$(shell pwd)

# update composer
install-composer:
	cd composer && curl -sS https://getcomposer.org/installer | php;

# update dependancies
update-depends:
	rm -rf composer/vendor/ckeditor/ckeditor/plugins/onchange
	rm -rf composer/vendor/ckeditor/ckeditor/plugins/font

	cd composer && php composer.phar update;

	cd composer/vendor/ckeditor/ckeditor/plugins && cp -a ../../../../../composer/vendor/ckeditor/onchange ./onchange
	cd composer/vendor/ckeditor/ckeditor/plugins && cp -a ../../../../../composer/vendor/ckeditor/font ./font
	cd composer/vendor/ckeditor/ckeditor/plugins && cp -a ../../../../../core/themes/default/packages/ckeditor_mathjax ./mathjax

# update site composer
site-update-composer:
	cd sites/${SITE} && make update-composer;

# update site dependancies
site-update-depends:
	cd module/${SITE} && make update-depends;

# update module composer
module-update-composer:
	cd module/${MODULE} && make update-composer;

# update module dependancies
module-update-depends:
	cd sites/${MODULE} && make update-depends;

# create database
create-database:
	./bin/create_database.php

# update database
update-database:
	./bin/update_database.php

# buid all docs
build-docs:
	. bin/env.sh && make build-schemaspy-pgsql
	make build-doxygen

# run doxygen
build-doxygen:
	./bin/create_docs_mainpage.php && doxygen docs/doxygen.conf

# make database schema
build-schemaspy-pgsql:
	echo ${BASE}
	cd composer/vendor/schemaspy/schemaspy && \
		java net.sourceforge.schemaspy.Main -t pgsql \
		-o ${BASE}/docs/schemaspy -dp ../postgres/ \
		-host ${PGHOST} -db ${PGDATABASE} -u ${PGUSER} -p ${PGPASSWORD} -s ${PGSCHEMA}
