Installing buildingequalsyes (the web application)
--

buildingequalsyes is built on top of [Flamework](https://github.com/exflickr/flamework) which means it's basically just a vanilla Apache + PHP application. You can run it as a dedicated virtual host or as a subdirectory of an existing host. 

You will need to make a copy of the [config.php.example](https://github.com/straup/buildingequalsyes/blob/master/www/include/config.php.example) file and name it `config.php`. You will need to update this new file and add the various specifics for databases and third-party APIs.

It uses [Solr](https://lucene.apache.org/solr/) as its datastore. Setup instructions and details for running Solr are located in the [solr/README.md] document. Instructions for load data in to your Solr instance are in [FIX ME].

The basics
===

	# There's actually very little you need to change in your `config.php` file if you
	# stick with the defaults and already have memcached (and the PHP bindings) installed.
	
	# This is something you might need to change, specifically the
	# port number (or hostname) of your Solr installation.

	$GLOBALS['cfg']['solr_endpoint_buildings'] = 'http://localhost:9999/solr/buildings/';
	$GLOBALS['cfg']['solr_endpoint'] = $GLOBALS['cfg']['solr_endpoint_buildings'];

	# You do not have to run memcache with b=y but it is encouraged.
	# If you don't (or can't) simply set the 'cache_remote_engine' to ''.

	$GLOBALS['cfg']['cache_remote_engine'] = 'memcache';
	$GLOBALS['cfg']['cache_prefix'] = '201204';
	$GLOBALS['cfg']['memcache_host'] = 'localhost';
	$GLOBALS['cfg']['memcache_port'] = '11211';

	# Don't change this (for now). It may become a thing you can change in
	# the future but for now it is not. See the comments at the top of
	# www/include/lib_woedb.php for details.

	$GLOBALS['cfg']['woedb_provider'] = 'yql';
	$GLOBALS['cfg']['yql_api_endpoint'] = "http://query.yahooapis.com/v1/public/yql?q=";

	# OSM as SSO provider and user accounts. Unless you're feeling adventurous
	# you really don't need to worry about this. Because it doesn't work yet. Or
	# rather the logging in with an OSM account works but none of the code for
	# actually doing anything as a logged-in OSM user is complete. This is work
	# that will probably start soon. (20120409/straup)

	# See also: https://github.com/straup/flamework-osmapp

	$GLOBALS['cfg']['enable_feature_signup'] = 0;
	$GLOBALS['cfg']['enable_feature_signin'] = 0;
	$GLOBALS['cfg']['enable_feature_account_delete'] = 0;
	$GLOBALS['cfg']['enable_feature_password_retrieval'] = 0;

	$GLOBALS['cfg']['osm_oauth_secret'] = '';
	$GLOBALS['cfg']['osm_oauth_key'] = '';

	$GLOBALS['cfg']['crypto_cookie_secret'] = '';
	$GLOBALS['cfg']['crypto_password_secret'] = '';
	$GLOBALS['cfg']['crypto_crumb_secret'] = '';
	$GLOBALS['cfg']['crypto_oauth_cookie_secret'] = '';

	$GLOBALS['cfg']['db_main'] = array(
	 	'host'	=> 'localhost',
	 	'name'	=> 'buildingequalsyes',
	 	'user'	=> 'buildingequalsyes',
	 	'pass'	=> '',
	 	'auto_connect' => 0,
	);

Remaining details
===

	# This is only relevant if are running parallel-ogram on a machine where you
	# can not make the www/templates_c folder writeable by the web server. If that's
	# the case set this to 0 but remember that you'll need to pre-compile all
	# of your templates before they can be used by the site.
	# See also: https://github.com/straup/parallel-ogram/blob/master/bin/compile-templates.php

	$GLOBALS['cfg']['smarty_compile'] = 1;

That's it. Or should be. If I've forgotten something please let me know or
submit a pull request.

