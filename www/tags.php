<?php

	include("include/init.php");
	loadlib("buildings");
	loadlib("woedb");

	$tag = get_str("tag");

	if (! $tag){
		error_404();
	}

	$more = array(
		'page' => get_int32("page"),
	);

	if (! preg_match("/^woe\:/", $tag)){
		$more['sort_by_ip'] = 1;
	}

	if ($woeid = get_str('woeid')){

		$woe = woedb_get_by_id($woeid);

		if ($woe['woeid']){
			$more['woe'] = $woe;
		}

		$GLOBALS['smarty']->assign_by_ref("woe", $woe);
		$GLOBALS['smarty']->assign("has_woeid", $woe['woeid']);
	}

	$buildings = buildings_get_for_tag($tag, $more);
	$GLOBALS['smarty']->assign_by_ref("buildings", $buildings['rows']);

	$GLOBALS['smarty']->assign("tag", $tag);
	$GLOBALS['smarty']->assign("has_tag", $tag);

	if ((count($buildings)) && (! isset($more['woe']))){
		$places = buildings_get_places_for_tag($tag, $more);
		$GLOBALS['smarty']->assign_by_ref("places", $places);
	}

	$enc_tag = htmlspecialchars($tag);
	$pagination_url = "tags/{$enc_tag}/";

	if ($more['woe']){
		$enc_woeid = htmlspecialchars($woe['woeid']);
		$pagination_url .= "w:{$enc_woeid}/";
	}

	$GLOBALS['smarty']->assign("pagination_url", $pagination_url);
	$GLOBALS['smarty']->display("page_tags.txt");
	exit();
?>
