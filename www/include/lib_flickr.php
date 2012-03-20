<?php

	#
	# $Id$
	#

	loadlib("http");
	loadlib("geohash");

	#################################################################

	function flickr_reverse_geocode($lat, $lon){

		$short_lat = (float)sprintf("%.3f", $lat);
		$short_lon = (float)sprintf("%.3f", $lon);

		$geohash = geohash_encode($short_lat, $short_lon);
		$cache_key = "flickr_reversegeocode_{$geohash}";

		$cache = cache_get($cache_key);

		if ($cache['ok']){
			return $cache['data'];
		}

		$args = array(
			'lat' => $short_lat,
			'lon' => $short_lon,
		);

		$rsp = flickr_api_call('flickr.places.findByLatLon', $args);

		if (! $rsp['ok']){
			return;
		}

		$loc = $rsp['rsp']['places']['place'][0];
		cache_set($cache_key, $loc, 'set locally');

		return $loc;
	}

	#################################################################

	function flickr_get_woeid($woeid){

		$cache_key = "flickr_woeid_{$woeid}";

		$cache = cache_get($cache_key);

		if ($cache['ok']){
			return $cache['data'];
		}

		$args = array(
			'woe_id' => $woeid,
		);

		$rsp = flickr_api_call('flickr.places.getInfo', $args);

		if (! $rsp['ok']){
			return;
		}

		$loc = $rsp['rsp']['place'];
		cache_set($cache_key, $loc, 'set locally');

		return $loc;
	}

	#################################################################

	function flickr_api_call($method, $args=array()){

		$args['api_key'] = $GLOBALS['cfg']['flickr_apikey'];

		$args['method'] = $method;
		$args['format'] = 'json';
		$args['nojsoncallback'] = 1;

		if (isset($args['auth_token'])){
			$api_sig = _flickr_api_sign_args($args);
			$args['api_sig'] = $api_sig;
		}

		$url = "http://api.flickr.com/services/rest";

		$rsp = http_post($url, $args);

		if (! $rsp['ok']){
			return $rsp;
		}

		$json = json_decode($rsp['body'], 'as a hash');

		if (! $json){
			return array( 'ok' => 0, 'error' => 'failed to parse response' );
		}

		if ($json['stat'] != 'ok'){
			return array( 'ok' => 0, 'error' => $json['message']);
		}

		unset($json['stat']);
		return array( 'ok' => 1, 'rsp' => $json );
	}

	#################################################################

	function _flickr_api_sign_args($args){

		$parts = array(
			$GLOBALS['cfg']['flickr_apisecret']
		);

		$keys = array_keys($args);
		sort($keys);

		foreach ($keys as $k){
			$parts[] = $k . $args[$k];
		}

		$raw = implode("", $parts);
		return md5($raw);
	}

	#################################################################
?>
