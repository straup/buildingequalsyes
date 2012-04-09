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

		$cache_key = "woedb_solr_hierarchy_{$woe['woeid']}";
		$cache = cache_get($cache_key);

		if ($cache['ok']){
			return $cache['data'];
		}

		$query = "SELECT * FROM geo.places.ancestors WHERE descendant_woeid={$woe['woeid']}"; 
		$rsp = _woedb_yql_api_call($query);

		if (! $rsp['ok']){
			return;
		}

 		if (! $rsp['query']['count']){
			return;
		}

		$hierarchy = array();

		if (isset($more['include_self'])){
			$hierarchy[] = $woe;
		}

		$placetypes = array(
			'Town',
			'State',
			'Country',
		);

		# oh YQL... y u so dumb??

		if ($rsp['query']['count'] == 1){

			$rsp['query']['results']['place'] = array(
				$rsp['query']['results']['place']
			);
		}

		foreach ($rsp['query']['results']['place'] as $loc){

			if (! in_array($loc['placeTypeName']['content'], $placetypes)){
				continue;
			}

			$loc = _woedb_yql_normalize($loc);
			$hierarchy[] = $loc;
		}

		cache_set($cache_key, $hierarchy, "cache locally");

		return $hierarchy;
	}

	######################################################

	function _woedb_yql_get_parent_woeid($woe){

		$hier = woedb_yql_fetch_hierarchy($woe);
		return $hier[0]['woeid'];
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

		$loc = array(
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

		# generate a fullname

		$parts = array(
			'locality2',
			'locality1',
			'admin1',
			'country',
		);

		$fullname = array();

		foreach ($parts as $p){

			if (! $row[$p]){
				continue;
			}

			$fullname[] = $row[$p]['content'];
		}

		$loc['fullname'] = implode(", ", $fullname);

		# get the parent WOE ID

		$parent_id = 1;

		if ($loc['placetype'] != 'Country'){
			$parent_id = _woedb_yql_get_parent_woeid($loc);
		}

		$loc['parent_woeid'] = $parent_id;

		# Okay, good!

		return $loc;
	}

	######################################################

?>
