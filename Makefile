todo:
	touch TODO.txt
	echo "# This file was generated automatically by grep-ing for 'TO DO' in the source code." > ./TODO.txt
	echo "# This file is meant as a pointer to the actual details in the files themselves." >> TODO.txt
	echo "# This file was created "`date` >> TODO.txt
	echo "" >> TODO.txt
	grep -n -r -e "TO DO" www >> TODO.txt
	grep -n -r -e "TO DO" bin >> TODO.txt

templates:

	php -q ./bin/compile-templates.php

secret:
	php -q ./bin/generate_secret.php

clean:
	rm -f ./TODO.txt

js:

	java -Xmx64m -jar lib/google-compiler/compiler-20100616.jar \
		--js www/javascript/array_merge.js \
		--js www/javascript/htmlspecialchars.js \
		> www/javascript/phpjs.min.js

	java -Xmx64m -jar lib/google-compiler/compiler-20100616.jar \
		--js www/javascript/modestmaps.markers.js \
		--js www/javascript/modestmaps.touch.js \
		> www/javascript/modestmaps.bells-and-whistles.min.js