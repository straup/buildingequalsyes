<?php

	include("include/init.php");
	loadlib("buildings");

	$ll = get_str("ll");

	if (! $ll){

		# Do IP lookup here?

		$bldg = buildings_get_random_building();

		$more = array(
			'per_page' => 20,
			'd' => 5,
		);

		$nearby = buildings_get_nearby_for_building($bldg);

		$GLOBALS['smarty']->assign_by_ref("building", $bldg);
		$GLOBALS['smarty']->assign_by_ref("nearby", $nearby['rows']);

		$GLOBALS['smarty']->display("page_nearby_form.txt");
		exit();
	}

	list($lat, $lon) = explode(",", $ll);

	$lat = floatval(trim($lat));
	$lon = floatval(trim($lon));

	# check lat, lon here...

	$GLOBALS['smarty']->assign("lat", $lat);
	$GLOBALS['smarty']->assign("lon", $lon);

	$more = array(
		"page" => get_int32("page"),
	);

	$buildings = buildings_get_nearby($lat, $lon, $more);
	$GLOBALS['smarty']->assign_by_ref("buildings", $buildings['rows']);

	$enc_ll = htmlspecialchars($ll);
	$GLOBALS['smarty']->assign("pagination_url", "nearby/{$enc_ll}/");

	$GLOBALS['smarty']->display("page_nearby.txt");
	exit();
?>
