<?php

	loadlib("woedb");
	loadlib("base58");

	loadlib("solr");
	loadlib("solr_machinetags");

	#################################################################

	$GLOBALS['buildings_last_woeid'] = 2147483647;
	$GLOBALS['buildings_total_count'] = 26161986;

	#################################################################

	function buildings_get_by_id($id){

		$cache_key = "building_id_{$id}";
		$cache = cache_get($cache_key);

		if ($cache['ok']){
			return $cache['data'];
		}

		$args = array(
			"q" => "id:{$id}",
		);

		$bldg = _buildings_fetch_one($args);

		if ($bldg){
			cache_set($cache_key, $bldg, "cache locally");
		}

		return $bldg;
	}

	#################################################################

	function buildings_get_by_shortcode($code){

		$id = base58_decode($code);
		return buildings_get_by_id($id);
	}

	#################################################################

	function buildings_get_by_wayid($wayid){

		$cache_key = "building_way_{$wayid}";
		$cache = cache_get($cache_key);

		if ($cache['ok']){
			return $cache['data'];
		}

		$args = array(
			"q" => "way_id:{$wayid}",
		);

		$bldg = _buildings_fetch_one($args);

		if ($bldg){
			cache_set($cache_key, $bldg, "cache locally");
		}

		return $bldg;
	}

	#################################################################

	function buildings_get_random_building(){

		$offset = rand(0, $GLOBALS['buildings_total_count']);
		$id = ($GLOBALS['buildings_last_woeid'] + 1) + $offset;

		return buildings_get_by_id($id);
	}

	#################################################################

	function buildings_get_nearby_for_building($building, $more=array()){

		$cache_key = "buildings_nearby_{$building['id']}";
		$cache = cache_get($cache_key);

		if ($cache['ok']){
			return $cache['data'];
		}

		$args = array(
			"q" => "NOT id:{$building['id']}",
		);

		list($lat, $lon) = explode(",", $building['centroid'], 2);

		$rsp = _buildings_fetch_nearby($lat, $lon, $args);

		if ($rsp['ok']){
			cache_set($cache_key, $rsp, "cache locally");
		}

		return $rsp;
	}

	#################################################################

	function buildings_get_nearby($lat, $lon, $more=array()){

		# TO DO: cache me...

		$args = array(
			"q" => "*:*",
			"d" => 1,
		);

		return _buildings_fetch_nearby($lat, $lon, $args);
	}

	#################################################################


	function buildings_get_tags_for_woe(&$woe, $more=array()){

		$cache_key = "buildings_tags_{$woe['woeid']}";
		$cache = cache_get($cache_key);

		if ($cache['ok']){
			return $cache['data'];
		}

		$q = _buildings_get_for_woe_query($woe);

		$params = array(
			'q' => $q,
			'facet.field' => 'tags',

			# why doesn't this work...
			'facet.query' => '-tags:woe/*',
		);

		$rsp = solr_facet($params, $more);

		if (! $rsp['ok']){
			return $rsp;
		}

		$tags = array();

		foreach ($rsp['facets'] as $tag => $count){

			if (($tag == 'woe') || (preg_match("/^woe\//", $tag))){
				continue;
			}

			if (! $tag){
				continue;
			}

			# the _inflate_tag should be tweaked to account
			# for stuff that comes out of facet queries
			# (20110514/straup)

			$parts = array();

			foreach (explode("/", $tag) as $p){
				$parts[] = solr_machinetags_remove_lazy8s($p);
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

			$tags[$tag] = $count;

			if (count(array_keys($tags)) == 20){
				break;
			}
		}

		cache_set($cache_key, $tags, "cache locally");

		return $tags;
	}

	#################################################################

	function _buildings_get_for_woe_query(&$woe){

		$woeid = $woe['woeid'];
		$woeid_tags = solr_machinetags_add_lazy8s($woeid);

		# why 3500? because it seems to work for canada...
		# (20110509/asc)

		$q = "parent_woeid:{$woeid} OR tags:woe/*/{$woeid_tags}";

		return $q;
	}

	#################################################################

	function buildings_get_for_woe(&$woe, $more=array()){

		# IMPORTANT: see how we are caching paginated
		# results? this is predicated on the assumption
		# that the underlying datastore (solr) does not
		# change frequently.
		#
		# See also: $GLOBALS['cfg']['cache_prefix']

		$cache_key = "buildings_woe_{$woe['woeid']}";

		if (isset($more['tag'])){
			$cache_key .= "_t{$more['tag']}";
		}

		$cache_pg = ($more['page']) ? $more['page'] : 1;
		$cache_key .= "_p{$cache_pg}";

		$cache = cache_get($cache_key);

		if ($cache['ok']){
			# return $cache['data'];
		}

		$q = _buildings_get_for_woe_query($woe);

		if (isset($more['tag'])){

			$tag_q = _buildings_get_for_tag_query($more['tag']);

			$params = array(
				'q' => "({$q}) AND ({$tag_q})",
			);

			$rsp = _buildings_fetch($params, $more);
		}

		# this shouldn't happen but does (20120405/straup)

		else if ((! isset($woe['latitude'])) || (! isset($woe['longitude']))){

			$params = array(
				'q' => $q,
			);

			$rsp = _buildings_fetch($params, $more);
		}

		else {

			$params = array(
				"q" => $q,
			);

			$more['d'] = 5000;
			$rsp = _buildings_fetch_nearby($woe['latitude'], $woe['longitude'], $params, $more);
		}

		if ($rsp['ok']){
			cache_set($cache_key, $rsp, "cache locally");
		}

		return $rsp;
	}

	#################################################################

	function buildings_get_for_nodeid($nodeid, $more=array()){

		$cache_key = "building_node_{$nodeid}";
		$cache = cache_get($cache_key);

		if ($cache['ok']){
			return $cache['data'];
		}

		$args = array(
			"q" => "nodes:{$nodeid}",
		);

		# $more['donot_assign_smarty_pagination'] = 1;

		$rsp = _buildings_fetch($args, $more);

		if ($rsp['ok']){
			cache_set($cache_key, $rsp, "cache locally");
		}

		return $rsp;
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

			$k = solr_machinetags_add_lazy8s($parts['predicate']);
			$v = solr_machinetags_add_lazy8s($parts['value']);
		}

		else {

			$k = implode("/", array(
 				solr_machinetags_add_lazy8s($parts['namespace']),
 				solr_machinetags_add_lazy8s($parts['predicate']),
			));

			$v = solr_machinetags_add_lazy8s($parts['value']);
		}

		#

		$query = array();

		$query[] = "tags:{$k}/*";

		$values = ($parts['value']) ? explode(" ", $parts['value']) : array();
		$count = count($values);

		for ($i=0; $i < $count; $i++){

			$v = solr_machinetags_add_lazy8s($values[$i]);

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

		# TO DO: cache me...

		$q = _buildings_get_for_tag_query($tag, $more);

		if (isset($more['woe'])){
			$woe_q = _buildings_get_for_woe_query($more['woe']);

			$q = "({$q}) AND ({$woe_q})";
		}

		$args = array(
			"q" => $q,
		);

		return _buildings_fetch($args, $more);
	}

	#################################################################

	function buildings_get_places_for_tag($tag, $more=array()){

		$cache_key = "places_tag_{$tag}";
		$cache = cache_get($cache_key);

		if ($cache['ok']){
			return $cache['data'];
		}

		$q = _buildings_get_for_tag_query($tag);

		$params = array(
			'q' => $q,
			'facet.field' => 'tags',
 			'facet.prefix' => 'woe/locality',
		);

		$rsp = solr_facet($params, $more);

		if (! $rsp['ok']){
			return $rsp;
		}

		$places = array();

		foreach ($rsp['facets'] as $f => $count){

			if (! preg_match("/^woe\/(?:[a-z]+)\/(\d+)$/", $f, $m)){
				continue;
			}

			$woeid = solr_machinetags_remove_lazy8s($m[1]);

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

		cache_set($cache_key, $places, "cache locally");

		return $places;
	}

	#################################################################

	function buildings_search($q, $more=array()){

		$q = solr_machinetags_add_lazy8s($q);

		$args = array(
			"q" => "name:{$q} OR tags:*{$q}*",
		);

		return _buildings_fetch($args, $more);
	}

	#################################################################

	function _buildings_fetch(&$args, $more=array()){

		$more['http_timeout'] = $GLOBALS['cfg']['http_timeout_solr'];

		$rsp = solr_select($args, $more);
		_buildings_inflate_rows($rsp);

		$GLOBALS['smarty']->assign('pagination', $rsp['pagination']);
		return $rsp;
	}

	#################################################################

	function _buildings_fetch_one($params, $more=array()){

		$more['donot_assign_smarty_pagination'] = 1;

		$rsp = _buildings_fetch($params, $more);
		return solr_single($rsp);
	}

	#################################################################

	function _buildings_fetch_nearby($lat, $lon, $args, $more=array()){

		$more['http_timeout'] = $GLOBALS['cfg']['http_timeout_solr'];
		$more['sfield'] = 'centroid';

		$rsp = solr_select_nearby($lat, $lon, $args, $more);
		_buildings_inflate_rows($rsp);

		$GLOBALS['smarty']->assign('pagination', $rsp['pagination']);
		return $rsp;
	}

	#################################################################

	function _buildings_inflate_rows(&$rsp){

		if (! $rsp['ok']){
			return;
		}

		$rows = array();

		foreach ($rsp['rows'] as $b){
			_buildings_inflate_row($b);
			$rows[] = $b;
		}

		$rsp['rows'] = $rows;

		# note the pass-by-ref
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
			$parts[$i] = solr_machinetags_add_lazy8s($parts[$i]);
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
			$parts[$i] = solr_machinetags_remove_lazy8s($parts[$i]);
		}

		$tag = array(
			"namespace" => $parts[0],
			"predicate" => $parts[1],
			"value" => $parts[2],
		);

		return $tag;
	}

	#################################################################

	# old and wtf?

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


?>
