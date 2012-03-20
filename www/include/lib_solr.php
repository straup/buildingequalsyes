<?php

	#
	# $Id$
	#

	# curl 'http://localhost:8985/solr/buildings/select?q=tags%3Aamenity+AND+tags%3A%2A%2Frestaurant&facet=on&facet.field=tags&facet.prefix=woe/country&wt=json&rows=0&indent=on'

	#################################################################

	# This is *not* a general purpose wrapper library for talking to Solr.

	#################################################################

	function solr_select($url, $params=array(), $more=array()){

		$params['wt'] = 'json';

		$params['timeAllowed'] = $GLOBALS['cfg']['http_timeout_solr'] * 1000;

		#

		$str_params = http_build_query($params);

		$cache_key = "solr_bldgdebug_select_" . md5($str_params);
		$cache = cache_get($cache_key);

		if ($cache['ok']){
			return $cache['data'];
		}

		$headers = array();

		$more = array(
			'http_timeout' => $GLOBALS['cfg']['http_timeout_solr'],
		);

		$http_rsp = http_get("{$url}?{$str_params}", $headers, $more);

		if (! $http_rsp['ok']){
			error_log("[SOLR] {$str_params}");
			return $http_rsp;
		}

		$as_array = True;
		$json = json_decode($http_rsp['body'], $as_array);

		if (! $json){
			return array(
				'ok' => 0,
				'error' => 'Failed to parse response',
			);
		}

		$rsp = array(
			'ok' => 1,
			'data' => $json,
		);

		cache_set($cache_key, $rsp);
		return $rsp;
	}

	#################################################################
?>
