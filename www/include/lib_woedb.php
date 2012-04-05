<?php

	loadlib("solr");

	#################################################################

	function woedb_get_by_id($woeid, $more=array()){

		$more['solr_endpoint'] = $GLOBALS['cfg']['solr_endpoint_woedb'];
		$more['donot_assign_smarty_pagination'] = 1;

		$params = array(
			"q" => "woeid:{$woeid}",
		);

		$rsp = solr_select($params, $more);
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

		return $hierarchy;
	}

	#################################################################
?>
