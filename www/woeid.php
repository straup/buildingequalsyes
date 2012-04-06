<?php

	include("include/init.php");
	loadlib("buildings");

	$woeid = get_int32("woeid");

	if (! $woeid){

		$bldg = buildings_get_random_building();
		$nearby = buildings_get_nearby_for_building($bldg);

		$GLOBALS['smarty']->assign_by_ref("building", $bldg);
		$GLOBALS['smarty']->assign_by_ref("nearby", $nearby['rows']);

		$GLOBALS['smarty']->display("page_woeid_form.txt");
		exit();
	}

	$woe = woedb_get_by_id($woeid);

	if (! $woe){
		error_404();
	}

	$hierarchy = woedb_fetch_hierarchy($woe);

	$GLOBALS['smarty']->assign("woeid", $woeid);
	$GLOBALS['smarty']->assign_by_ref("woe", $woe);
	$GLOBALS['smarty']->assign_by_ref("hierarchy", $hierarchy);

	$more = array();

	if ($page = get_int32("page")){
		$more['page'] = $page;
	}

	if ($tag = get_str('tag')){
		$more['tag'] = $tag;
	}

	$rsp = buildings_get_for_woe($woe, $more);

	/*
	if (! $rsp['ok']){
		dumper($rsp);
		exit();
	}
	*/

	$GLOBALS['smarty']->assign_by_ref("buildings", $rsp['rows']);

	if ((count($buildings)) && (! $tag)){

		$mincount = ($woe['placetype'] == 'Country') ? 50 : 1;

		$tags_more = array(
			'mincount' => $mincount,
		);

		$tags = buildings_get_tags_for_woe($woe, $tags_more);
		$GLOBALS['smarty']->assign_by_ref("tags", $tags);
	}

	$pagination_url = "woe/{$woeid}/";

	if ($tag){
		$GLOBALS['smarty']->assign("has_tag", $tag);

		$enc_tag = htmlspecialchars($tag);
		$pagination_url .= "t:{$enc_tag}/";
	}

	$GLOBALS['smarty']->assign("pagination_url", $pagination_url);
	$GLOBALS['smarty']->display("page_woeid.txt");
	exit();
?>
