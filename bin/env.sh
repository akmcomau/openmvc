#!/bin/sh


for line in `./bin/env.php`; do
	export $line;
done;
