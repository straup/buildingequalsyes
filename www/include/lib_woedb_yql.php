<?php

	loadlib("http");
	loadlib("random");

	######################################################

	function woedb_yql_get_by_id($woeid, $more=array()){

		$cache_key = "woedb_yql_id_{$woeid}";
		$cache = cache_get($cache_key);

		if ($cache['ok']){
			return $cache['data'];
		}

		$query = "SELECT * FROM geo.places WHERE woeid={$woeid}";
		$rsp = _woedb_yql_api_call($query);

		if (! $rsp['ok']){
			return;
		}

 		if (! $rsp['query']['count']){
			return;
		}

		$loc = $rsp['query']['results']['place'];
		$loc = _woedb_yql_normalize($loc);

		cache_set($cache_key, $loc, "cache locally");
		return $loc;
	}

	######################################################

	function woedb_yql_fetch_hierarchy($woe){

	}

	######################################################

	function _woedb_yql_api_call($query){

		$url = $GLOBALS['cfg']['yql_api_endpoint'];
		$url .= urlencode($query);
		$url .= "&format=json";

		loadlib("random");
		$url .= "&appid=" . random_string(23);

		$rsp = http_get($url);

		if (! $rsp['ok']){
			return $rsp;
		}

		$json = json_decode($rsp['body'], "fuck off php");

		# check for YQL errors here

		$json['ok'] = 1;
		return $json;
	}

	######################################################

	function _woedb_yql_normalize($row){

		return array(
			'woeid' => $row['woeid'],
			'name' => $row['name'],
			'placetype' => $row['placeTypeName']['content'],
			'iso' => $row['country']['code'],
			'latitude' => $row['centroid']['latitude'],
			'sw_longitude' => $row['centroid']['longitude'],
			'sw_latitude' => $row['boundingBox']['northEast']['latitude'],
			'ne_longitude' => $row['centroid']['longitude'],
			'ne_latitude' => $row['boundingBox']['northEast']['latitude'],
			'longitude' => $row['boundingBox']['northEast']['longitude'],
			'provider' => 'yql.geo.places',
		);
	}

	######################################################

?>
