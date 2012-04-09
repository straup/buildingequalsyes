<?php

	# Hey look! There's magic delegated code happening here!
	# It's awesome, I know. I love this kind of goofy shit.
	# The reason this is here is that I (straup) also wrote
	# the WOEdb (or http://woe.spum.org) and when I launched
	# b=y I was pretty cavalier about just reaching across
	# the aisle and pulling things out of the WOEdb Solr 
	# instance. So a lot of the code in b=y expects to be able
	# to get WOE records accordingly. I would like nothing more
	# than to open up all of the WOEdb but that's just not going
	# to happen with this release. Instead, the code is using
	# YQL to retrieve WOE records and caches them locally. Or
	# more generally the code is written in such a way that the
	# final data (used by b=y) can be constructed from a variety
	# of providers. Currently there are only providers for Solr
	# and YQL but you could also write one for Flickr.
	# (20120409/straup)

	loadlib("woedb_{$GLOBALS['cfg']['woedb_provider']}");

	#################################################################

	function woedb_get_by_id($woeid, $more=array()){

		$func = "woedb_{$GLOBALS['cfg']['woedb_provider']}_get_by_id";
		return call_user_func_array($func, array($woeid, $more));
	}

	function woedb_fetch_hierarchy($woe){

		$func = "woedb_{$GLOBALS['cfg']['woedb_provider']}_fetch_hierarchy";
		return call_user_func_array($func, array($woe));
	}

	#################################################################

?>
