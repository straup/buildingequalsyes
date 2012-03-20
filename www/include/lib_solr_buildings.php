<?php

	loadlib("solr");

	#################################################################

	function solr_buildings_select_one(&$args){

		$url = 'http://localhost:8985/solr/buildings/select';

		$rsp = solr_select($url, $args);

		$rows = _buildings_inflate_rows($rsp);
		return $rows[0];
	}

	#################################################################
?>
