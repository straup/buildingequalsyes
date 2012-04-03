<?php

	include("include/init.php");
	loadlib("buildings");

	$bldg = buildings_get_random_building();
dumper($bldg);

	$nearby = buildings_get_nearby_for_building($bldg);
dumper($nearby);
exit;
	$GLOBALS['smarty']->assign_by_ref("building", $bldg);
	$GLOBALS['smarty']->assign_by_ref("nearby", $nearby['rows']);

	$GLOBALS['smarty']->display("page_index.txt");
	exit;
?>
