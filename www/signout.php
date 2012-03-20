<?
	#
	# $Id$
	#

	include("include/init.php");

	login_ensure_loggedin();

	loadlib("buildings");

	$bldg = buildings_get_random_building();
	$nearby = buildings_get_nearby_for_building($bldg);

	$GLOBALS['smarty']->assign_by_ref("building", $bldg);
	$GLOBALS['smarty']->assign_by_ref("nearby", $nearby['rows']);

	$crumb_key = 'logout';
	$smarty->assign("crumb_key", $crumb_key);

	if (post_isset('done') && crumb_check($crumb_key)){

		login_do_logout();

		$smarty->display('page_signout_done.txt');
		exit;
	}

	$smarty->display("page_signout.txt");
	exit();
?>
