<?php

	#################################################################

	function api_auth_ensure_auth(){

		if (! api_auth_is_auth()){
			api_output_error(403, 'Forbidden');
		}
	}

	#################################################################

	function api_auth_is_auth(){

		# please write me...

		# check config file for switch between delegated auth and cookies ?
		# allow switch to fallback on cookies ?

		# this is a shim in the meantime...

		if ($GLOBALS['cfg']['user']['id']){
			return 1;
		}

		return 0;
	}

	#################################################################
?>
