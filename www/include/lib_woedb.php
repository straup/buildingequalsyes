<?php

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
