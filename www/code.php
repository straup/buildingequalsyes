<?php

	include("include/init.php");

	loadlib("buildings");

	$bldg = buildings_get_random_building();
	$nearby = buildings_get_nearby_for_building($bldg);

	$GLOBALS['smarty']->assign_by_ref("building", $bldg);
	$GLOBALS['smarty']->assign_by_ref("nearby", $nearby['rows']);

	$GLOBALS['smarty']->display("page_code.txt");
	exit();
?>
