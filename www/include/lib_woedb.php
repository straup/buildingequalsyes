<?php

	loadlib("solr");

	#################################################################

	function woedb_get_by_id($woeid, $more=array()){

		$cache_key = "woedb_id_{$woeid}";
		$cache = cache_get($cache_key);

		if ($cache['ok']){
			return $cache['data'];
		}

		$more['solr_endpoint'] = $GLOBALS['cfg']['solr_endpoint_woedb'];
		$more['donot_assign_smarty_pagination'] = 1;

		$params = array(
			"q" => "woeid:{$woeid}",
		);

		$rsp = solr_select($params, $more);
		$loc = solr_single($rsp);

		if ($rsp['ok']){
			cache_set($cache_key, $loc, "cache locally");
		}

		return $loc;
	}

	#################################################################

	function woedb_fetch_hierarchy(&$woe){

		$cache_key = "woedb_hierarchy_{$woe['woeid']}";
		$cache = cache_get($cache_key);

		if ($cache['ok']){
			return $cache['data'];
		}

		$hierarchy = array();

		if (isset($more['include_self'])){
			$hierarchy[] = $woe;
		}

		$parent_woeid = $woe['parent_woeid'];

		while ($parent_woeid){

			# FIX ME...

			$more = array(
				'fl' => 'name,woeid,placetype,parent_woeid,iso'
			);

			$parent = woedb_get_by_id($parent_woeid, $more);

			if ((! $parent) || ($parent['woeid'] == 1)){
				break;
			}

			if ($parent['placetype'] != 'County'){
				$hierarchy[] = $parent;
			}

			$parent_woeid = $parent['parent_woeid'];
		}

		cache_set($cache_key, $hierarchy, "cache locally");
		return $hierarchy;
	}

	#################################################################
?>
