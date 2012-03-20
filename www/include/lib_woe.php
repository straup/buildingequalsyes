<?php

	loadlib("http");
	loadlib("geohash");

	#################################################################

	function woe_reverse_geocode($lat, $lon){

		$short_lat = (float)sprintf("%.3f", $lat);
		$short_lon = (float)sprintf("%.3f", $lon);
		$gh = geohash_encode($short_lat, $short_lon);

		$cache_key = "woe_reverse_geo_{$gh}";
		$cache = cache_get($cache_key);

		if ($cache['ok']){
			return $cache['data'];
		}

		$args = array(
			'lat' => $short_lat,
			'lon' => $short_lon,
		);

		# See this? It's a server that I run out of
		# pocket not to be confused with a reliable
		# service (20110316/asc)

		$endpoint = "http://cloud.spum.org/";
		$query = http_build_query($args);

		$url = "{$endpoint}?{$query}";
		$rsp = http_get($url);

		if (! $rsp['ok']){
			return $rsp;
		}

		$json = json_decode($rsp['body'], "as a hash");

		if (! $json){
			return array(
				'ok' => 1,
				'error' => 'Failed to decode JSON response',
			);
		}

		$rsp = array(
			'ok' => 1,
			'data' => $json,
		);

		cache_set($cache_key, $rsp, "cache locally");
		return $rsp;
	}

	#################################################################
?>
