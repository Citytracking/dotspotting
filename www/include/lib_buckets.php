<?php

	#
	# $Id$
	#

	#################################################################

	$GLOBALS['buckets_local_cache'] = array();

	#################################################################

	# Hey look! We're deliberately punting any user-defined
	# properties like title, etc. for now.
	# (20101015/asc)

	function buckets_create_bucket(&$user, $more=array()){

		$bucket_id = dbtickets_create(32);

		if (! $bucket_id){

			return array(
				'ok' => 0,
				'error' => 'Ticket server failed',
			);
		}

		$now = time();

		$bucket = array(
			'user_id' => AddSlashes($user['id']),
			'created' => $now,
			'last_modified' => $now,
			'id' => $bucket_id,
		);

		$optional = array(
			'label',
			'mime_type',
		);

		foreach ($optional as $o){

			if (isset($more[$o])){
				$bucket[$o] = AddSlashes($more[$o]);
			}
		}

		$rsp = db_insert_users($user['cluster_id'], 'Buckets', $bucket);

		if (! $rsp['ok']){
			return null;
		}

		buckets_load_extras($bucket);

		$rsp['bucket'] = $bucket;
		return $rsp;
	}

	#################################################################

	# Note the pass-by-ref

	function buckets_load_extras(&$bucket){

		$bucket['public_id'] = buckets_get_public_id($bucket);
	}

	#################################################################

	function buckets_get_public_id(&$bucket){

		return $bucket['user_id'] . "-" . $bucket['id'];
	}

	#################################################################

	function buckets_explode_public_id($public_id){

		return explode("-", $public_id, 2);
	}

	#################################################################

	# Should this count public dots?

	function buckets_get_bucket($public_id){

		list($user_id, $bucket_id) = buckets_explode_public_id($public_id);

		if (isset($GLOBALS['buckets_local_cache'][$bucket_id])){
			return $GLOBALS['buckets_local_cache'][$bucket_id];
		}

		$user = users_get_by_id($user_id);

		$enc_id = AddSlashes($bucket_id);

		$sql = "SELECT * FROM Buckets WHERE id='{$enc_id}'";

		$rsp = db_fetch_users($user['cluster_id'], $sql);
		$bucket = db_single($rsp);

		if ($bucket){
			$GLOBALS['buckets_local_cache'][$bucket_id] = $bucket;
		}

		return $bucket;
	}

	#################################################################

	function buckets_update_bucket(&$bucket, $update){

		$user = users_get_by_id($bucket['user_id']);

		$enc_id = AddSlashes($bucket['id']);
		$where = "id='{$enc_id}'";

		foreach ($update as $k => $v){
			$update[$k] = AddSlashes($v);
		}

		$update['last_modified'] = time();

		$rsp = db_update_users($user['cluster_id'], 'Buckets', $update, $where);

		if ($rsp['ok']){
			unset($GLOBALS['buckets_local_cache'][$bucket['id']]);
		}

		return $rsp;
	}

	#################################################################

	function buckets_delete_bucket(&$bucket){

		$user = users_get_by_id($bucket['user_id']);

		$enc_bucket_id = AddSlashes($bucket['id']);

		# delete the bucket first on the grounds that
		# it is "easier" to replace that all the dots
		# (20101015/asc)

		$sql = "DELETE FROM Buckets WHERE id='{$enc_bucket_id}'";
		$rsp = db_write_users($user['cluster_id'], $sql);

		if (! $rsp['ok']){
			return $rsp;
		}

		$sql = "DELETE FROM Dots WHERE bucket_id='{$enc_bucket_id}'";
		$rsp = db_write_users($user['cluster_id'], $sql);

		if ($rsp['ok']){
			unset($GLOBALS['buckets_local_cache'][$bucket['id']]);
		}

		return $rsp;
	}

	#################################################################

	function buckets_buckets_for_user($user, $viewer_id=0, $args=array()){

		$enc_id = AddSlashes($user['id']);

		$sql = "SELECT * FROM Buckets WHERE user_id='{$enc_id}'";

		if ($user['id'] != $viewer_id){

			$sql .= " AND count_dots_public > 0";
		}

		return db_fetch_paginated_users($user['cluster_id'], $sql, $args);
	}

	#################################################################

	function buckets_update_dot_count_for_bucket(&$bucket){

		$counts = dots_count_dots_for_bucket($bucket);

		$update = array(
			'count_dots' => $counts['total'],
			'count_dots_public' => $counts['public'],
		);

		return buckets_update_bucket($bucket, $update);
	}

	#################################################################

	# this is unfinished and needs to do factor in 
	# public/private counts (that might mean a totally
	# different function too...)

	function buckets_counts_for_user(&$user, $viewer_id=0){

		$enc_id = AddSlashes($user['id']);

		if ($viewer_id == $user['id']){
			$sql = "SELECT COUNT(id) AS count_buckets, SUM(count_dots) AS count_dots FROM Buckets WHERE user_id='{$enc_id}'";
			return db_single(db_fetch_users($user['cluster_id'], $sql));
		}

		$sql = "SELECT COUNT(id) AS count_buckets, SUM(count_dots_public) AS count_dots FROM Buckets WHERE user_id='{$enc_id}' AND count_dots_public > 0";
		return db_single(db_fetch_users($user['cluster_id'], $sql));
	}

	#################################################################
?>
