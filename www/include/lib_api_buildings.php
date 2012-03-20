<?php

	loadlib("buildings");

	#################################################################

	function api_buildings_getInfo(){

		$bldg = _api_buildings_get_building();

		if (! $bldg['id']){
			api_output_error(404, 'Building not found');
		}

		api_output_ok($bldg);
	}

	#################################################################

	function _api_buildings_get_building(){

		# FIXME: account for short codes...

		if ($id = request_int64("building_id")){
			return buildings_get_by_id($id);
		}

		else if ($id = request_int32("wayid")){
			return buildings_get_by_wayid($id);
		}

		else {
			return null;
		}
	}

	#################################################################

?>
