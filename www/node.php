<?php

	# http://buildingequalsyes.spum.org/node/434736825

	include("include/init.php");
	loadlib("buildings");

	$nodeid = get_int32("nodeid");

	if (! $nodeid){
		error_404();
	}

	$GLOBALS['smarty']->assign("nodeid", $nodeid);

	$more = array(
		'page' => get_int32("page"),
		'per_page' => 50,
	);

	$buildings = buildings_get_for_nodeid($nodeid, $more);
	$GLOBALS['smarty']->assign_by_ref("buildings", $buildings['rows']);

	$GLOBALS['smarty']->display("page_node.txt");
	exit();
?>

