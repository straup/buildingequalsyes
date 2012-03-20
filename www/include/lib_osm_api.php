<?php

	loadlib("oauth");

	#################################################################

	$GLOBALS['cfg']['osm_api_endpoint'] = 'http://api.openstreetmap.org/api/0.6/';

	#################################################################

	function osm_api_call($method, &$user_keys, $more=array()){

		$defaults = array(
			'http_method' => 'GET',
		);

		$more = array_merge($defaults, $more);

		$keys = array(
			'oauth_key' => $GLOBALS['cfg']['osm_oauth_key'],
			'oauth_secret' => $GLOBALS['cfg']['osm_oauth_secret'],
			'user_key' => $user_keys['oauth_token'],
			'user_secret' => $user_keys['oauth_token_secret'],
		);

		$url = $GLOBALS['cfg']['osm_api_endpoint'] . $method;

		$url = oauth_sign_get($keys, $url, $args, $more['http_method']);
		$rsp = http_get($url);

		if (! $rsp['ok']){
			return $rsp;
		}

		if ($more['raw']){
			return $rsp;
		}

		# sudo return JSON someday...

		$xml = new SimpleXMLElement($rsp['body']);

		if (! $xml){

			return array(
				'ok' => 0,
				'error' => 'xml parse error',
			);
		}

		$json = json_encode($xml);
		$data = json_decode($json, "as hash");

		# to do (maybe): iterate through $data and
		# remove (merge) all the @attribute crap...

		return array(
			'ok' => 1,
			'data' => $data,
		);
	}

	#################################################################
?>
