<?php

	include("include/init.php");
	loadlib("buildings");

	$q = trim(get_str("q"));

	if (! $q){

		$bldg = buildings_get_random_building();
		$nearby = buildings_get_nearby_for_building($bldg);

		$GLOBALS['smarty']->assign_by_ref("building", $bldg);
		$GLOBALS['smarty']->assign_by_ref("nearby", $nearby['rows']);

		$GLOBALS['smarty']->display("page_search_form.txt");
		exit();
	}

	$GLOBALS['smarty']->assign("q", $q);

	$more = array(
		'page' => request_int32("page"),
		'sort_by_ip' => 1,
	);


	$buildings = buildings_search($q, $more);
	$GLOBALS['smarty']->assign_by_ref("buildings", $buildings['rows']);

	$enc_q = htmlspecialchars($q);
	$GLOBALS['smarty']->assign("pagination_url", "search/{$enc_q}/");

	$GLOBALS['smarty']->display("page_search.txt");
	exit();
?>
