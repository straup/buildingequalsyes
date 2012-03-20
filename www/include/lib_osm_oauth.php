<?php

	# This uses lib_oauth for all the signing and building
	# URL crap but uses Flamework's lib_http for actually
	# talking to the network.
	
	loadlib("oauth");
	loadlib("http");

	#################################################################

	$GLOBALS['cfg']['osm_oauth_endpoint'] = 'http://www.openstreetmap.org/oauth/';

	#################################################################

	function osm_oauth_get_request_token($args=array()){

		$keys = array(
			'oauth_key' => $GLOBALS['cfg']['osm_oauth_key'],
			'oauth_secret' => $GLOBALS['cfg']['osm_oauth_secret'],
		);

		$url = $GLOBALS['cfg']['osm_oauth_endpoint'] . 'request_token/';

		if (! oauth_get_auth_token($keys, $url)){

			return array(
				'ok' => 0,
			);
		}

		return array(
			'ok' => 1,
			'data' => array(
				'oauth_token' => $keys['request_key'],
				'oauth_secret' => $keys['request_secret'],
			)
		);

	}

	#################################################################

	function osm_oauth_get_auth_url(&$args, &$user_keys){

		$keys = array(
			'oauth_key' => $GLOBALS['cfg']['osm_oauth_key'],
			'oauth_secret' => $GLOBALS['cfg']['osm_oauth_secret'],
			'user_key' => $user_keys['oauth_token'],
			'user_secret' => $user_keys['oauth_secret'],
		);

		$url = $GLOBALS['cfg']['osm_oauth_endpoint'] . 'authorize/';
		$url = oauth_sign_get($keys, $url, $args, 'GET');

		return $url;
	}

	#################################################################

	function osm_oauth_get_access_token(&$args, &$user_keys){

		$keys = array(
			'oauth_key' => $GLOBALS['cfg']['osm_oauth_key'],
			'oauth_secret' => $GLOBALS['cfg']['osm_oauth_secret'],
			'user_key' => $user_keys['oauth_token'],
			'user_secret' => $user_keys['oauth_secret'],
		);

		$url = $GLOBALS['cfg']['osm_oauth_endpoint'] . 'access_token/';

		$url = oauth_sign_get($keys, $url, $args, 'GET');
		$rsp = http_get($url);

		if (! $rsp['ok']){
			return $rsp;
		}

		$data = osm_oauth_rsp_to_hash($rsp['body']);

		return array(
			'ok' => 1,
			'data' => $data,
		);
	}

	#################################################################

	function osm_oauth_rsp_to_hash($rsp){

		$data = array();

		foreach (explode("&", $rsp) as $bit){
			list($k, $v) = explode('=', $bit, 2);
			$data[urldecode($k)] = urldecode($v);
		}

		return $data;
	}

	#################################################################
?>
