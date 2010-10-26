<?php

	#
	# $Id$
	#

	#################################################################

	loadlib("dots_extras");

	loadlib("geo_utils");
	loadlib("geo_geohash");
	loadlib("geo_geocode");

	#################################################################

	$GLOBALS['dots_local_cache'] = array();

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

		$errors = array();

		foreach ($dots as $dot){

			$received ++;

			$rsp = dots_create_dot($user, $bucket, $dot);

			if (! $rsp['ok']){
				$rsp['record'] = $received;
				$errors[] = $rsp;

				continue;
			}

			$processed ++;
		}

		$ok = ($processed) ? 1 : 0;

		return array(
			'ok' => $ok,
			'errors' => $errors,
			'dots_received' => $received,
			'dots_processed' => $processed,
		);

	}

	#################################################################

	function dots_create_dot(&$user, &$bucket, &$data){

		# Do the dot validation here mostly so that we
		# don't have to remember to always do it in the
		# various import libraries and/or loop over every
		# record twice in lib_uploads

		$rsp = dots_ensure_valid_data($data);

		if (! $rsp['ok']){
			return $rsp;
		}

		#

		$id = dbtickets_create(64);

		if (! $id){
			return array(
				'ok' => 0,
				'error' => 'Ticket server failed',
			);
		}

		# basic geo bits

		$collapse = 0;	# do not int-ify the coords
		
		# if we have an address field, and no latitude/longitude, do the geocode thing

		# TODO: move all of this in to a separate function that
		# can schedule offline/out-of-band tasks.
		# (20101025/straup)

		if (isset($data['address']) && (empty($data['latitude']) || empty($data['longitude']))){

			$geocode_rsp = geo_geocode_string($data['address']);

			# Like with geocoding being disabled, it's not clear
			# that returning an error is necessarily the best thing
			# to do. (20101023/straup)
		
			if (! $geocode_rsp['ok']){

				return array(
					'ok' => 0,
					'error' => 'Geocoder failed',
				);
			}

			$lat = $geocode_rsp['latitude'];
			$lon = $geocode_rsp['longitude'];
			$geocoded_by = $geocode_rsp['service_id'];

			$map = geo_geocode_service_map();
			$geocoder = $map[$geocoded_by];

			foreach ($geocode_rsp['extras'] as $k => $v){
				$data["{$geocoder}:{$k}"] = $v;
			}
		}

		else {

			$lat = $data['latitude'];
			$lon = $data['longitude'];
		}

		# creation date for the point (different from import date)

		$now = time();
		$created = $now;

		if ($alt_created = $data['created']){
			$created = (is_int($alt_created)) ? $alt_created : strtotime($alt_created);
		}

		# permissions

		$perms_map = dots_permissions_map('string keys');

		$perms = $perms_map['public'];

		if ($data['perms'] == 'private'){
			$perms = $perms_map['private'];
		}

		# go!

		$dot = array(
			'id' => $id,
			'user_id' => AddSlashes($user['id']),
			'bucket_id' => AddSlashes($bucket['id']),
			'imported' => $now,
			'created' => $created,
			'last_modified' => $now,
			'perms' => $perms,
		);
		
		# AddSlashes turns null into empty strings.
		# We don't add latitude/longitude to the $dot array unless they're present
		# because otherwise mysql will interpret the empty string as a zero.

		if (isset($lat) && isset($lon)) {

			$lat = geo_utils_prepare_coordinate($lat, $collapse);
			$lon = geo_utils_prepare_coordinate($lon, $collapse);

			$geohash = geo_geohash_encode($lat, $lon);

			$dot['latitude'] = AddSlashes($lat);
			$dot['longitude'] = AddSlashes($lon);
			$dot['geohash'] = AddSlashes($geohash);
		}

		if (isset($geocoded_by)){
			$dot['geocoded_by'] = AddSlashes($geocoded_by);
		}

		$rsp = db_insert_users($user['cluster_id'], 'Dots', $dot);

		if (! $rsp['ok']){
			return $rsp;
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

			$extra_rsp = dots_extras_create_extra($dot, $label, $data[$label]);

			if (! $extra_rsp['ok']){
				# do something...
			}
		}

		#

		$dot['public_id'] = dots_get_public_id($dot);

		$rsp['dot'] = $dot;
		return $rsp;
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

		if ($rsp['ok']){
			unset($GLOBALS['dots_local_cache'][$dot['id']]);
		}

		return $rsp;
	}

	#################################################################

	function dots_delete_dot(&$dot, $more=array()){

		$user = users_get_by_id($dot['user_id']);

		$enc_id = AddSlashes($dot['id']);

		$sql = "DELETE FROM DotsExtras WHERE dot_id='{$enc_id}'";
		$rsp = db_write_users($user['cluster_id'], $sql);

		if (! $rsp['ok']){
			return $rsp;
		}

		$sql = "DELETE FROM Dots WHERE id='{$enc_id}'";
		$rsp = db_write_users($user['cluster_id'], $sql);

		if (($rsp['ok']) && (! isset($more['skip_bucket_update']))){

			$bid = dots_get_public_id_for_bucket($dot);
			$bucket = buckets_get_bucket($bid);

			$rsp2 = buckets_update_dot_count_for_bucket($bucket);
			$rsp['update_bucket_count'] = $rsp2['ok'];
		}

		if ($rsp['ok']){
			unset($GLOBALS['dots_local_cache'][$dot['id']]);
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

	function dots_get_extent_for_bucket(&$bucket, $viewer_id=0){

		$user = users_get_by_id($bucket['user_id']);

		$enc_id = AddSlashes($bucket['id']);

		$sql = "SELECT MIN(latitude) AS swlat, MIN(longitude) AS swlon, MAX(latitude) AS nelat, MAX(longitude) AS nelon FROM Dots WHERE bucket_id='{$enc_id}'";

		if ($viewer_id !== $bucket['user_id']){

			$sql = _dots_where_public_sql($sql);
		}

		return db_single(db_fetch_users($user['cluster_id'], $sql));
	}

	#################################################################

	function dots_get_dot($public_id, $viewer_id=0){

		list($user_id, $dot_id) = dots_explode_public_id($public_id);

		if (isset($GLOBALS['dots_local_cache'][$dot_id])){
			return $GLOBALS['dots_local_cache'][$sot_id];
		}

		$user = users_get_by_id($user_id);

		$enc_id = AddSlashes($dot_id);
		$enc_user = AddSlashes($user['id']);

		$sql = "SELECT * FROM Dots WHERE id='{$enc_id}' AND user_id='{$enc_user}'";

		if ($viewer_id !== $user['id']){
			# $sql = _dots_where_public_sql($sql);
		}

		$rsp = db_fetch_users($user['cluster_id'], $sql);
		$dot = db_single($rsp);

		if ($rsp['ok']){

			if ($dot){
				$more = array( 'load_bucket' => 1);
				dots_load_extras($dot, $viewer_id, $more);
			}

			$GLOBALS['dots_local_cache'][$dot_id] = $dot;
		}

		return $dot;
	}

	#################################################################

	function dots_can_view_dot(&$dot, $viewer_id){

		if ($dot['user_id'] == $viewer_id){
			return 1;
		}

		$perms_map = dots_permissions_map();

		return ($perms_map[$dot['perms']] == 'public') ? 1 : 0;		
	}

	#################################################################

	function dot_explode_public_id($public_id){

		return explode("-", $public_id, 2);
	}

	#################################################################

	function dots_get_dots_for_bucket(&$bucket, $viewer_id=0, $more=array()){

		$user = users_get_by_id($bucket['user_id']);

		$enc_id = AddSlashes($bucket['id']);

		$sql = "SELECT * FROM Dots WHERE bucket_id='{$enc_id}'";

		if ($viewer_id !== $bucket['user_id']){

			$sql = _dots_where_public_sql($sql);
		}

		$rsp = db_fetch_paginated_users($user['cluster_id'], $sql, $more);
		$dots = array();

		foreach ($rsp['rows'] as $dot){

			dots_load_extras($dot, $viewer_id);
			$dots[] = $dot;
		}

		return $dots;
	}
	
	function dots_get_dots_for_user(&$user, $viewer_id=0, $args=array()) {

		$enc_id = AddSlashes($user['id']);

		$sql = "SELECT * FROM Dots WHERE user_id='{$enc_id}'";

		if ($viewer_id !== $user['id']){

			$sql = _dots_where_public_sql($sql, 1);
		}

		$sql .= " ORDER BY imported DESC";

		$rsp = db_fetch_paginated_users($user['cluster_id'], $sql, $args);
		$dots = array();

		$more = array(
		      'load_bucket' => 1,
		);

		foreach ($rsp['rows'] as $dot){

			dots_load_extras($dot, $viewer_id, $more);
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

	function dots_load_extras(&$dot, $viewer_id, $more=array()){

		$dot['public_id'] = dots_get_public_id($dot);
		$dot['url'] = dots_get_url($dot);

		$dot['extras'] = dots_extras_get_extras($dot);

		if ($more['load_bucket']){
			$bid = dots_get_public_id_for_bucket($dot);
	 		$dot['bucket'] = buckets_get_bucket($bid);
		}

	}

	#################################################################

	function dots_ensure_valid_data(&$data){

		$skip_required_latlon = 0;

		if (isset($data['address']) && (empty($data['latitude']) || empty($data['longitude']))){

			$skip_required_latlon = 1;

			# It is unclear whether this should really return an
			# error - perhaps it should simply add the dot with
			# NULL lat/lon values and rely on a separate cron job
			# to clean things up with geocoding is re-enabled.
			# (20101023/straup)

			if (! $GLOBALS['cfg']['enable_feature_geocoding']){
				return array( 'ok' => 0, 'error' => 'Geocoding is disabled.' );
			}

			if (strlen(trim($data['address'])) == 0){
				return array( 'ok' => 0, 'error' => 'Address is empty.' );
			}
		}

		else {

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
