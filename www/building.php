<?php

	include("include/init.php");
	loadlib("buildings");

	if ($id = get_int64("id")){
		$building = buildings_get_by_id($id);
	}

	else if ($wayid = get_int32("wayid")){
		$building = buildings_get_by_wayid($wayid);
	}

	else if ($code = get_str("shortcode")){
		$building = buildings_get_by_shortcode($code);
	}

	else { }

	if (! $building){
		error_404();
	}

	$more = array(
		'per_page' => 10,
		'd' => .1,
	);

	$nearby = buildings_get_nearby_for_building($building, $more);

	$GLOBALS['smarty']->assign_by_ref("building", $building);
	$GLOBALS['smarty']->assign_by_ref("nearby", $nearby['rows']);

	$GLOBALS['smarty']->display("page_building.txt");
	exit();
?>
