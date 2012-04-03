<?php

	#################################################################

	function solr_woedb_select($args){

		$GLOBALS['cfg']['solr_endpoint'] = $GLOBALS['cfg']['solr_endpoint_woedb'];
		return solr_select($args);
	}

	#################################################################
?>
