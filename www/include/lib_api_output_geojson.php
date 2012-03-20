<?php

	#################################################################

	function api_output_ok($rsp){

		$geojson = array(
			'type' => 'FeatureCollection',
			'features' => array(),
		);

		foreach ($rsp['places'] as $pl){

			# See this? It only deals with points right now

			$geom = array(
				'type' => 'Point',
				'coordinates' => array( $pl['centroid']['x'], $pl['centroid']['y'] ),
			);

			unset($pl['centroid']);

			$geojson['features'][] = array(
				'type' => 'Feature',
				'geometry' => $geom,
				'properties' => $pl,
			);
		}

		api_output_send($geojson);
	}

	#################################################################

	function api_output_error($code=999, $msg=''){

		$out = array('error' => array(
			'code' => 999,
			'error' => $msg,
		));

		api_output_send($out, "ima error");
	}

	#################################################################

	function api_output_send($rsp, $is_error=0){

		$json = json_encode($rsp);

		utf8_headers();

		if ($is_error){
			header("HTTP/1.1 500 Server Error");
			header("Status: 500 Server Error");
		}

		header("Access-Control-Allow-Origin: *");

		header("Content-Type: text/json");
		header("Content-Length: " . strlen($json));

		echo $json;
		exit();
	}

	#################################################################

?>
