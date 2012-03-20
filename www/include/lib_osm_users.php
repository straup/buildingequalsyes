<?php

	#################################################################

	function osm_users_create_user($user){

		$hash = array();

		foreach ($user as $k => $v){
			$hash[$k] = AddSlashes($v);
		}

		$rsp = db_insert('OsmUsers', $hash);

		if (!$rsp['ok']){
			return null;
		}

		$cache_key = "osm_user_{$user['osm_id']}";
		cache_set($cache_key, $user, "cache locally");

		$cache_key = "osm_user_{$user['id']}";
		cache_set($cache_key, $user, "cache locally");

		return $user;
	}

	#################################################################

	function osm_users_update_user(&$osm_user, $update){

		$hash = array();
		
		foreach ($update as $k => $v){
			$hash[$k] = AddSlashes($v);
		}

		$enc_id = AddSlashes($osm_user['user_id']);
		$where = "user_id='{$enc_id}'";

		$rsp = db_update('OsmUsers', $hash, $where);

		if ($rsp['ok']){

			$osm_user = array_merge($osm_user, $update);

			$cache_key = "osm_user_{$osm_user['osm_id']}";
			cache_unset($cache_key);

			$cache_key = "osm_user_{$osm_user['user_id']}";
			cache_unset($cache_key);
		}

		return $rsp;
	}

	#################################################################

	function osm_users_get_by_osm_id($osm_id){

		$cache_key = "osm_user_{$osm_id}";
		$cache = cache_get($cache_key);

		if ($cache['ok']){
			# return $cache['data'];
		}

		$enc_osm_id = AddSlashes($osm_id);

		$sql = "SELECT * FROM OsmUsers WHERE osm_id='{$enc_osm_id}'";
		$rsp = db_fetch($sql);
		$user = db_single($rsp);

		cache_set($cache_key, $user, "cache locally");
		return $user;
	}

	#################################################################

	function osm_users_get_by_user_id($user_id){

		$cache_key = "osm_user_{$user_id}";
		$cache = cache_get($cache_key);

		if ($cache['ok']){
			# return $cache['data'];
		}

		$enc_id = AddSlashes($user_id);

		$sql = "SELECT * FROM OsmUsers WHERE user_id='{$enc_id}'";

		$rsp = db_fetch($sql);
		$user = db_single($rsp);

		cache_set($cache_key, $user, "cache locally");
		return $user;
	}

	#################################################################

	# this is syntactic sugar, yes.

	function osm_users_get_oauth_keys(&$user){

		$osm_user = osm_users_get_by_user_id($user['id']);

		foreach ($osm_user as $k => $v){
			if (! preg_match("/^oauth_/", $k)){
				unset($osm_user[$k]);
			}
		}

		return $osm_user;
	}

	#################################################################
?>
