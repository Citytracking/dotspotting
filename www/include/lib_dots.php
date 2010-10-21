<?php

	#
	# $Id$
	#

	#################################################################

	loadlib("dots_extras");

	loadlib("geo_utils");
	loadlib("geo_geohash");

	#################################################################

	function dots_permissions_map($string_keys=0){

		$map = array(
			0 => 'public',
			1 => 'private',
		);

		if ($string_keys){
			$map = array_flip($map);
		}

		return $map;
	}

	#################################################################

	function dots_import_dots(&$user, &$bucket, &$dots){

		$received = 0;
		$processed = 0;

		foreach ($dots as $dot){

			$received ++;

			if (dots_create_dot($user, $bucket, $dot)){
				$processed ++;
			}
		}

		$ok = ($processed) ? 1 : 0;

		return array(
			'ok' => $ok,
			'dots_received' => $received,
			'dots_processed' => $processed,
		);

	}

	#################################################################

	function dots_create_dot(&$user, &$bucket, &$data){

		$id = dbtickets_create(64);

		if (! $id){
			return null;
		}

		# basic geo bits

		$collapse = 0;	# do not int-ify the coords

		$lat = geo_utils_prepare_coordinate($data['latitude'], $collapse);
		$lon = geo_utils_prepare_coordinate($data['longitude'], $collapse);

		$geohash = geo_geohash_encode($data['latitude'], $data['longitude']);

		# creation date for the point (different from import date)

		$now = time();
		$created = $now;

		if ($alt_created = intval($data['created'])){
			$created = $alt_created;
		}

		# permissions

		$perms_map = dots_permissions_map('string keys');

		$perms = $perms_map['public'];

		if ($data['perms'] == 'private'){
			$perms = $perms_map['private'];
		}

		# go!

		$dot = array(
			'user_id' => AddSlashes($user['id']),
			'bucket_id' => AddSlashes($bucket['id']),
			'latitude' => AddSlashes($lat),
			'longitude' => AddSlashes($lon),
			'geohash' => AddSlashes($geohash),
			'imported' => $now,
			'created' => $created,
			'last_modified' => $now,
			'perms' => $perms,
			'id' => $id,
		);

		$rsp = db_insert_users($user['cluster_id'], 'Dots', $dot);

		if (! $rsp['ok']){
			return null;
		}

		# extras

		$extras_ignore = array(
			'latitude',
			'longitude',
			'created',
			'perms',
		);

		foreach (array_keys($data) as $label){

			if (in_array($label, $extras_ignore)){
				continue;
			}

			if (! trim($data[$label])){
				continue;
			}

			if (! dots_extras_create_extra($dot, $label, $data[$label])){
				# do something...
			}
		}

		#

		$dot['public_id'] = dots_get_public_id($dot);
		return $dot;
	}

	#################################################################

	function dots_update_dot(&$dot, $update){

		$user = users_get_by_id($dot['user_id']);

		$enc_id = AddSlashes($dot['id']);
		$where = "id='{$enc_id}'";

		foreach ($update as $k => $v){
			$update[$k] = AddSlashes($v);
		}

		$update['last_modified'] = time();

		$rsp = db_update_users($user['cluster_id'], 'Dots', $update, $where);

		if (! $rsp['ok']){
			return null;
		}

		return 1;
	}

	#################################################################

	function dots_delete_dot(&$dot, $more=array()){

		$user = users_get_by_id($dot['user_id']);

		$enc_id = AddSlashes($dot['id']);

		$sql = "DELETE FROM Dots WHERE id='{$enc_id}'";

		$rsp = db_write_users($user['cluster_id'], $sql);

		if (($rsp['ok']) && (! isset($more['skip_bucket_update']))){

			$bid = dots_get_public_id_for_bucket($dot);
			$bucket = buckets_get_bucket($bid);

			$rsp2 = buckets_update_dot_count_for_bucket($bucket);
			$rsp['update_bucket_count'] = $rsp2['ok'];
		}

		return $rsp;
	}

	#################################################################

	function dots_get_url(&$dot){

		return '';
	}

	#################################################################

	function dots_get_public_id(&$dot){

		return $dot['user_id'] . "-" . $dot['id'];
	}

	#################################################################

	function dots_get_public_id_for_bucket(&$dot){

		return $dot['user_id'] . "-" . $dot['bucket_id'];
	}

	#################################################################

	function dots_explode_public_id($public_id){

		return explode("-", $public_id, 2);
	}

	#################################################################

	function dots_get_dots_for_bucket(&$bucket, $viewer_id=0){

		$user = users_get_by_id($bucket['user_id']);

		$enc_id = AddSlashes($bucket['id']);

		$sql = "SELECT * FROM Dots WHERE bucket_id='{$enc_id}'";

		if ($viewer_id !== $bucket['user_id']){

			$sql = _dots_where_public_sql($sql);

			$sql .= " AND perms=0";
			$sql .= " AND (latitude IS NOT NULL AND longitude IS NOT NULL)";
		}

		$rsp = db_fetch_users($user['cluster_id'], $sql);
		$dots = array();

		foreach ($rsp['rows'] as $dot){

			dots_load_extra($dot);
			$dots[] = $dot;
		}

		return $dots;
	}

	#################################################################

	function dots_count_dots_for_bucket(&$bucket){

		$user = users_get_by_id($bucket['user_id']);
		$enc_id = AddSlashes($bucket['id']);

		$sql = "SELECT COUNT(id) AS count_total FROM Dots WHERE bucket_id='{$enc_id}'";

		$rsp = db_fetch_users($user['cluster_id'], $sql);
		$row = db_single($rsp);

		$count_total = $row['count_total'];

		$sql = "SELECT COUNT(id) AS count_public FROM Dots WHERE bucket_id='{$enc_id}'";

		$sql = _dots_where_public_sql($sql);

		$rsp = db_fetch_users($user['cluster_id'], $sql);
		$row = db_single($rsp);

		$count_public = $row['count_public'];

		return array(
			'total' => $count_total,
			'public' => $count_public,
		);
	}

	#################################################################

	# Note the pass-by-ref

	function dots_load_extra(&$dot){

		$dot['public_id'] = dots_get_public_id($dot);
		$dot['url'] = dots_get_url($dot);

		$dot['extras'] = dots_extras_get_extras($dot);
	}

	#################################################################

	function dots_ensure_valid_data(&$data){

		if (! isset($data['latitude'])){
			return array( 'ok' => 0, 'error' => 'missing latitude' );
		}

		if (! isset($data['longitude'])){
			return array( 'ok' => 0, 'error' => 'missing longitude' );
		}

		if (! geo_utils_is_valid_longitude($data['latitude'])){
			return array( 'ok' => 0, 'error' => 'invalid latitude' );
		}

		if (! geo_utils_is_valid_longitude($data['longitude'])){
			return array( 'ok' => 0, 'error' => 'invalid longitude' );
		}

		return array( 'ok' => 1 );
	}

	#################################################################

	# Do not include any dots that may in the queue
	# waiting to be geocoded, etc.

	function _dots_where_public_sql($sql, $has_where=1){

		$where .= ($has_where) ? "AND" : "WHERE";

		$sql .= " {$where} perms=0 AND (latitude IS NOT NULL AND longitude IS NOT NULL)";

		return $sql;
	}

	#################################################################
?>
