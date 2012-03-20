<?php

	loadlib("woedb");
	loadlib("base58");

	# http://wiki.apache.org/solr/SimpleFacetParameters

	#################################################################

	$GLOBALS['buildings_last_woeid'] = 2147483647;
	$GLOBALS['buildings_total_count'] = 26161986;

	#################################################################

	function buildings_get_by_id($id){

		$args = array(
			"q" => "id:{$id}",
		);

		return _buildings_fetch_one($args);
	}

	#################################################################

	function buildings_get_by_shortcode($code){

		$id = base58_decode($code);
		return buildings_get_by_id($id);
	}

	#################################################################

	function buildings_get_by_wayid($wayid){

		$args = array(
			"q" => "way_id:{$wayid}",
		);

		return _buildings_fetch_one($args);
	}

	#################################################################

	function buildings_get_random_building(){

		$offset = rand(0, $GLOBALS['buildings_total_count']);
		$id = ($GLOBALS['buildings_last_woeid'] + 1) + $offset;

		$args = array(
			"q" => "id:{$id}",
		);

		return _buildings_fetch_one($args);
	}

	#################################################################

	function buildings_get_nearby_for_building(&$building, $more=array()){

		$args = array(
			"q" => "NOT id:{$building['id']}",
			"pt" => $building['centroid'],
		);

		return buildings_get_nearby($args, $more);
	}

	#################################################################

	function buildings_get_nearby($args, $more=array()){

		$defaults = array(
			"q" => "*:*",
			"d" => 1,
		);

		$args = array_merge($defaults, $args);

		$args["fq"] = "{!geofilt}";
		$args["sfield"] = "centroid";
		$args["sort"] = "geodist() asc";

		return _buildings_fetch_paginated($args, $more);

		if (! $rsp['ok']){
			return $rsp;
		}

		$nearby = array();

		foreach ($rsp['data']['response']['docs'] as $b){
			$nearby[] = array(
				'id' => $b['id'],
				'name' => $b['name'],
				'polygon' => $b['polygon'],
			);
		}

		return array(
			'ok' => 1,
			'rows' => $nearby,
			'pagination' => $rsp['pagination'],
		);
	}

	#################################################################

	function buildings_get_tags_for_woe(&$woe, $more=array()){

		$more['donot_inflate'] = 1;
		$more['donot_assign_pagination'] = 1;

		$more['facet'] = array(
			"facet" => "on",
			"facet.field" => "tags",
			"facet.mincount" => 1,

			# why doesn't this work...
			"facet.query" => "-tags:woe/*",
		);

		$rsp = buildings_get_for_woe($woe, $more);

		$fields = $rsp['data']['facet_counts']['facet_fields']['tags'];

		$tags = array();

		foreach (range(0, count($fields), 2) as $i){

			$f = $fields[$i];

			# see ntoe about facet.query above...

			if (($f == 'woe') || (preg_match("/^woe\//", $f))){
				continue;
			}

			if (! $f){
				continue;
			}

			# the _inflate_tag should be tweaked to account
			# for stuff that comes out of facet queries
			# (20110514/straup)

			$parts = array();

			foreach (explode("/", $f) as $p){
				$parts[] = _buildings_remove_lazy8s($p);
			}

			$count_parts = count($parts);

			if ($count_parts == 3){
				$tag = "{$parts[0]}:{$parts[1]}={$parts[2]}";
			}

			else if ($count_parts == 2){
				$tag = "{$parts[0]}={$parts[1]}";
			}

			else {
				$tag = $parts[0];
			}

			$count = $fields[$i + 1];

			$tags[$tag] = $count;

			if (count(array_keys($tags)) == 20){
				break;
			}
		}

		return $tags;
	}

	#################################################################

	function _buildings_get_for_woe_query(&$woe){

		$woeid = $woe['woeid'];
		$woeid_tags = _buildings_add_lazy8s($woeid);

		# why 3500? because it seems to work for canada...
		# (20110509/asc)

		$q = "parent_woeid:{$woeid} OR tags:woe/*/{$woeid_tags}";

		return $q;
	}

	function buildings_get_for_woe(&$woe, $more=array()){

		$q = _buildings_get_for_woe_query($woe);

		if (isset($more['tag'])){

			$tag_q = _buildings_get_for_tag_query($more['tag']);

			$args = array(
				'q' => "($q) AND ({$tag_q})",
			);

			return _buildings_fetch_paginated($args, $more);
		}

		# search nearby...

		$args = array(
			"q" => $q,
			"pt" => "{$woe['latitude']},{$woe['longitude']}",
			"d" => 5000,
		);

		return buildings_get_nearby($args, $more);
	}

	#################################################################

	function buildings_get_for_nodeid($nodeid, $more=array()){

		$args = array(
			"q" => "nodes:{$nodeid}",
		);

		return _buildings_fetch_paginated($args, $more);
	}

	#################################################################

	# things to test with:
	# http://buildingequalsyes.spum.org/tags/gnis:feature_id=2461281
	# http://buildingequalsyes.spum.org/tags/name=Valley%20View%20Library
	# http://buildingequalsyes.spum.org/tags/horse=yes
	# http://buildingequalsyes.spum.org/tags/ele=114 <-- borked, possible to make work w/ literal fq?

	function _buildings_get_for_tag_query($tag, $more=array()){

		$parts = _buildings_deflate_tag_parts($tag);

		if ($parts['namespace'] == 'osm'){

			$k = _buildings_add_lazy8s($parts['predicate']);
			$v = _buildings_add_lazy8s($parts['value']);
		}

		else {

			$k = implode("/", array(
 				_buildings_add_lazy8s($parts['namespace']),
 				_buildings_add_lazy8s($parts['predicate']),
			));

			$v = _buildings_add_lazy8s($parts['value']);
		}

		#

		$query = array();

		$query[] = "tags:{$k}/*";

		$values = ($parts['value']) ? explode(" ", $parts['value']) : array();
		$count = count($values);

		for ($i=0; $i < $count; $i++){

			$v = _buildings_add_lazy8s($values[$i]);

			if ($count == 1){
				$q = "tags:*/{$v}";
			}

			else if ($i == 0){
				$q = "tags:{$k}/{$v}*";
			}

			else if ($i == ($count-1)){
				$q = "tags:{$k}/*{$v}";
			}

			else {
				$q = "tags:{$k}/*{$v}*";
			}

			$query[] = $q;
		}

		$q = implode(" AND ", $query);

		return $q;
	}

	#################################################################

	function buildings_get_for_tag($tag, $more=array()){

		$q = _buildings_get_for_tag_query($tag, $more);

		if (isset($more['woe'])){
			$woe_q = _buildings_get_for_woe_query($more['woe']);

			$q = "({$q}) AND ({$woe_q})";
		}

		$args = array(
			"q" => $q,
		);

		return _buildings_fetch_paginated($args, $more);
	}

	#################################################################

	function buildings_get_places_for_tag($tag, $more=array()){

		$more['donot_inflate'] = 1;
		$more['donot_assign_pagination'] = 1;

		$more['facet'] = array(
			"facet" => "on",
			"facet.field" => "tags",
			"facet.prefix" => "woe/locality",
			"facet.mincount" => 1,
		);

		$rsp = buildings_get_for_tag($tag, $more);

		if (! $rsp['ok']){
			return;
		}

		$places = array();

		$fields = $rsp['data']['facet_counts']['facet_fields']['tags'];

		foreach (range(0, count($fields), 2) as $i){

			$f = $fields[$i];

			if (! preg_match("/^woe\/(?:[a-z]+)\/(\d+)$/", $f, $m)){
				continue;
			}

			$woeid = _buildings_remove_lazy8s($m[1]);
			$count = $fields[$i + 1];

			$loc = woedb_get_by_id($woeid);

			# is flickr using > WOE 7.6 ?

			if (! $loc['woeid']){
				continue;
			}

			$loc['bldg_tag_count'] = $count;
			$loc['bldg_tag'] = $tag;
	
			$places[] = $loc;

			if (count($places) == 30){
				break;
			}
		}

		return $places;
	}

	#################################################################

	function buildings_search($q, $more=array()){

		$q = _buildings_add_lazy8s($q);

		$args = array(
			"q" => "name:{$q} OR tags:*{$q}*",
		);

		return _buildings_fetch_paginated($args, $more);
	}

	#################################################################

	function _buildings_fetch(&$args, $more=array()){

		if (is_array($more['facet'])){
			$args = array_merge($args, $more['facet']);
			$args['rows'] = 0;
		}

		$url = 'http://localhost:8985/solr/buildings/select';
		return solr_select($url, $args);
	}

	#################################################################

	function _buildings_sort_by_ip($ip=null){

		if (! $ip){
			$ip = $_SERVER['REMOTE_ADDR'];
		}

		loadlib("hostip");
		$rsp = hostip_lookup($ip);

		if (! $rsp['ok']){
			return;
		}

		return array(
			"fq" => "{!geofilt}",
			"sfield" => "centroid",
			"pt" => "{$rsp['latitude']},{$rsp['longitude']}",
			"d" => 50000,
			"sort" => "geodist() asc, score desc",
		);
	}

	#################################################################

	function _buildings_fetch_paginated(&$args, $more=array()){

		$page = (isset($more['page'])) ? max(1, $more['page']) : 1;
		$per_page = isset($more['per_page']) ? max(1, $more['per_page']) : 10;

		$args['start'] = $per_page * ($page - 1);
		$args['rows'] = $per_page;

		#

		if ($more['sort_by_ip']){
			if ($_args = _buildings_sort_by_ip()){
				$args = array_merge($args, $_args);
			}
		}

		#

		$rsp = _buildings_fetch($args, $more);

		if (! $rsp['ok']){
			return $rsp;
		}

		$count = $rsp['data']['response']['numFound'];
		$pages = ceil($count / $per_page);

		$pagination = array(
			'page' => $page,
			'per_page' => $per_page,
			'page_count' => $pages,
			'total_count' => $count,
		);

		if (($GLOBALS['cfg']['pagination_assign_smarty_variable']) && (! isset($more['donot_assign_pagination']))){
			$GLOBALS['smarty']->assign('pagination', $pagination);
			$GLOBALS['smarty']->register_function('pagination', 'smarty_function_pagination');
		}

		if (isset($more['donot_inflate'])){
			$rsp['pagination'] = $pagination;
			return $rsp;
		}

		$rows = _buildings_inflate_rows($rsp);

		return array(
			'ok' => 1,
			'rows' => $rows,
			'pagination' => $pagination,
		);
	}

	#################################################################

	function _buildings_fetch_one(&$args){

		$rsp = _buildings_fetch($args);

		$rows = _buildings_inflate_rows($rsp);
		return $rows[0];
	}

	#################################################################

	function _buildings_inflate_rows($rsp){

		$rows = array();

		if (! is_array($rsp['data']['response']['docs'])){
			return $rows;
		}

		foreach ($rsp['data']['response']['docs'] as $b){
			_buildings_inflate_row($b);
			$rows[] = $b;
		}

		return $rows;
	}

	#################################################################

	function _buildings_inflate_row(&$row){

		# geo stuff
		_buildings_inflate_geometries($row);

		list($lat, $lon) = explode(",", $row['centroid']);

		$row['latitude'] = (float)$lat;
		$row['longitude'] = (float)$lon;

		# tags

		$tags = array();

		if (is_array($row['tags'])){
			foreach ($row['tags'] as $tag){

				$tag = _buildings_inflate_tag($tag);
				list($ns, $pred, $value) = array_values($tag);

				if (! is_array($tags[$ns])){
					$tags[$ns] = array();
				}

				$tags[$ns][$pred] = $value;
			}
		}

		$row['tags'] = $tags;

		# woe

		if (is_array($row['tags']['woe'])){

			$woe = array();

			foreach ($row['tags']['woe'] as $placetype => $woeid){
				$record = woedb_get_by_id($woeid);
				$record['_placetype'] = $placetype;
				$woe[$woeid] = $record;
			}

			$row['woe'] = $woe;
		}

		# nodes

		array_pop($row['nodes']);

		# shortcode

		$row['shortcode'] = base58_encode($row['id']);

		# Note the pass-by-ref
	}

	#################################################################

	function _buildings_inflate_geometries(&$row){

		$properties = array(
			'id' => $row['id'],
		);

		$row['geometries'] = array();

		$polygon = json_decode($row['polygon'], 'as hash');

		$row['geometries']['polygon'] = array(
			'type' => 'Feature',
			'properties' => $properties,
			'geometry' => $polygon,
		);

		if (0){

		$swlat = null;
		$swlon = null;
		$nelat = null;
		$nelon = null;

		foreach ($row['geometries']['polygon']['coordinates'] as $pt){

			list($lon, $lat) = $pt;

			if ((! $swlat) || ($lat > $swlat)){
				$swlat = $lat;
			}

			if ((! $swlon) || ($lat > $swlon)){
				$swlon = $lat;
			}

			if ((! $nelat) || ($lat < $nelat)){
				$nelat = $lat;
			}

			if ((! $nelon) || ($lon < $nelon)){
				$nelon = $lon;
			}
		}

		$row['geometries']['boundingbox'] = array(
			'type' => 'Feature',
			'properties' => $properties,
			'geometry' => array(
				'type' => 'Polygon',
				'coordinates' => array()
			),
		);

		}

		list($lat, $lon) = explode(",", $row['centroid']);

		$row['geometries']['centroid'] = array(
			'type' => 'Feature',
			'properties' => $properties,
			'geometry' => array(
				'type' => 'Point',
				'coordinates' => array(floatval($lon), floatval($lat)),
			),
		);

		# note the pass-by-ref
	}

	#################################################################

	function _buildings_deflate_tag_parts($tag){

		list($nspred, $value) = explode("=", $tag, 2);
		list($ns, $pred) = explode(":", $nspred, 2);

		# osm/machinetag hack

		if (! $pred){
			$pred = $ns;
			$ns = 'osm';
		}

		$parts = array(
			'namespace' => $ns,
			'predicate' => $pred,
			'value' => $value,
		);

		return $parts;
	}

	#################################################################

	function _buildings_deflate_tag($tag){

		$parts = _buildings_deflate_tag_parts($tag);

		# osm/machinetag hack

		if ($parts['namespace'] == 'osm'){
			unset($parts['namespace']);
		}

		$parts = array_values($parts);
		$count = count($parts);

		for ($i=0; $i < $count; $i++){
			$parts[$i] = _buildings_add_lazy8s($parts[$i]);
		}

		return implode("/", $parts);
	}

	#################################################################

	function _buildings_inflate_tag($tag, $as_array=0){

		$parts = explode("/", $tag);
		$count = count($parts);

		# osm/machinetag hack

		if ($count == 2){
			array_unshift($parts, "osm");
			$count = count($parts);
		}
			
		for ($i=0; $i < $count; $i++){
			$parts[$i] = _buildings_remove_lazy8s($parts[$i]);
		}

		$tag = array(
			"namespace" => $parts[0],
			"predicate" => $parts[1],
			"value" => $parts[2],
		);

		return $tag;
	}

	#################################################################

	function _buildings_add_lazy8s($str){
		$str = preg_replace("/8/", "88", $str);
		$str = preg_replace("/:/", "8c", $str);
		$str = preg_replace("/\//", "8s", $str);
		return $str;
	}

	function _buildings_remove_lazy8s($str){

	# http://buildingequalsyes.spum.org/id/2150118100

		$str = preg_replace("/8s/", "/", $str);
		$str = preg_replace("/8c/", ":", $str);
		$str = preg_replace("/88/", "8", $str);

		return $str;
	}

	#################################################################

?>
