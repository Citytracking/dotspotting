<?php

	#
	# $Id$
	#

	#################################################################

	# Hey look! We're deliberately punting any user-defined
	# properties like title, etc. for now.
	# (20101015/asc)

	function bucket_create_bucket(&$user, $more=array()){

		$bucket_id = dbtickets_create(32);

		if (! $bucket_id){
			return null;
		}

		$label = ($more['label']) ? $more['label'] : '';

		$now = time();

		$bucket = array(
			'user_id' => AddSlashes($user['id']),
			'created' => $now,
			'last_modified' => $now,
			'id' => $bucket_id,
			'label' => AddSlashes($label),
		);

		$rsp = db_insert_users($user['cluster_id'], 'Buckets', $bucket);

		if (! $rsp['ok']){
			return null;
		}

		buckets_load_extras($bucket);

		return $bucket;
	}

	#################################################################

	# Note the pass-by-ref

	function buckets_load_extras(&$bucket){

		$bucket['public_id'] = buckets_get_public_id($bucket);
		$bucket['url'] = buckets_get_url($bucket);
	}

	#################################################################

	function buckets_get_public_id(&$bucket){

		return $bucket['user_id'] . "-" . $bucket['id'];
	}

	#################################################################

	function buckets_get_url(&$bucket){

		$user = users_get_by_id($bucket['user_id']);

		return implode("/", array(
			$GLOBALS['cfg']['abs_root_url'],
			'dots',
			$user['id'],
			'buckets',
			$bucket['id'],
		));
	}

	#################################################################

	function buckets_explode_public_id($public_id){

		return explode("-", $public_id, 2);
	}

	#################################################################

	function buckets_get_bucket($public_id){

		list($user_id, $bucket_id) = buckets_explode_public_id($public_id);

		$user = users_get_by_id($bucket['user_id']);

		$enc_id = AddSlashes($bucket_id);

		$sql = "SELECT * FROM Buckets WHERE id='{$enc_id}'";

		$rsp = db_fetch_users($user['cluster_id'], $sql);
		return db_single($rsp);
	}

	#################################################################

	function buckets_get_dots_for_bucket(&$bucket, $viewer_id=0){

		$user = users_get_by_id($bucket['user_id']);

		$enc_id = AddSlashes($bucket['id']);

		$sql = "SELECT * FROM Dots WHERE bucket_id='{$enc_id}'";

		if ($viewer_id !== $bucket['user_id']){
			$sql .= " AND perms=0";
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

	function buckets_update_bucket(&$bucket, $update){

		$user = users_get_by_id($bucket['user_id']);

		$enc_id = AddSlashes($bucket['id']);
		$where = "id='{$enc_id}'";

		foreach ($update as $k => $v){
			$update[$k] = AddSlashes($v);
		}

		$update['last_modified'] = time();

		return db_update_users($user['cluster_id'], 'Buckets', $update, $where);
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

		return $rsp;
	}

	#################################################################

	function buckets_count_dots_for_bucket(&$bucket){

		$user = users_get_by_id($bucket['user_id']);

		$enc_id = AddSlashes($bucket['id']);

		$sql = "SELECT COUNT(id) AS count_dots FROM Dots WHERE id='{$enc_id}'";

		$rsp = db_fetch_users($user['cluster_id'], $sql);
		$row = db_single($rsp);

		return $row['count_dots'];
	}

	#################################################################

	function buckets_update_dot_count_for_bucket(&$bucket){

		$count = buckets_count_dots_for_bucket($bucket);

		$update = array(
			'count_dots' => $count,
		);

		return buckets_update_bucket($bucket, $update);
	}

	#################################################################


	#################################################################
?>
