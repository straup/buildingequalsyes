<?php

	include("include/init.php");
	loadlib("buildings");

	$bldg = buildings_get_random_building();

	$url = "{$GLOBALS['cfg']['abs_root_url']}id/{$bldg['id']}";

	header("location: {$url}");
	exit();
?>
