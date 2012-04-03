<?php

	loadlib("solr_woedb");

	#################################################################

	function woedb_get_by_id($woeid, $more=array()){

		$url = "";

		$args = array(
			"q" => "woeid:{$woeid}",
		);

		$args = array_merge($more, $args);

		$rsp = solr_woedb_select($url, $args);
		return solr_single($rsp);
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
