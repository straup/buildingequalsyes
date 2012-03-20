<?php

	#################################################################

	# http://www.hostip.info/use.html

	#################################################################

	function hostip_lookup($addr){

		$cache_key = "hostip_{$addr}";
		$cache = cache_get($cache_key);

		if ($cache['ok']){
			return $cache['data'];
		}

		$q = array("ip" => $addr);
		$url = "http://api.hostip.info/?" . http_build_query($q);

		$rsp = http_get($url);

		if (! $rsp['ok']){
			return $rsp;
		}

		$xml = new SimpleXMLElement($rsp['body']);

		$coords = $xml->xpath("*//gml:coordinates");

		if (! count($coords)){
			return array('ok' => 0, 'error' => 'failed to parse');
		}

		$coords = explode(",", $coords[0]);

		$rsp = array(
			'ok' => 1,
			'latitude' => $coords[1],
			'longitude' => $coords[0],
		);

		cache_set($cache_key, $rsp, 'cache locally');
		return $rsp;
	}

	#################################################################
?>
