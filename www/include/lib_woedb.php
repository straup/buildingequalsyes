<?php

	loadlib("solr");

	#################################################################

	function woedb_get_by_id($woeid, $more=array()){

		$url = "http://localhost:8983/solr/geoplanet/select";

		$args = array(
			"q" => "woeid:{$woeid}",
		);

		$args = array_merge($more, $args);

		$rsp = solr_select($url, $args);

		$row = $rsp['data']['response']['docs'][0];
		return $row;
	}

	#################################################################

	function woedb_fetch_hierarchy(&$woe){

		$hierarchy = array();

		if (isset($more['include_self'])){
			$hierarchy[] = $woe;
		}

		$parent_woeid = $woe['parent_woeid'];

		while ($parent_woeid){

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

		return $hierarchy;
	}

	#################################################################
?>
