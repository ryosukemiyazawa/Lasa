#!/bin/bash
docker run --name db_container \
	--platform linux/x86_64 \
	-e MYSQL_DATABASE=phpunit_test \
	-e MYSQL_ROOT_PASSWORD=root \
	-v db_storage:/var/lib/mysql \
	-p 3305:3306 \
	mysql:5.7