<?php

	#
	# $Id$
	#

	#################################################################

	$GLOBALS['buckets_local_cache'] = array();

	#################################################################

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
			'user_id' => $user['id'],
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
				$bucket[$o] = $more[$o];
			}
		}

		$hash = array();

		foreach ($bucket as $k => $v){
			$hash[$k] = AddSlashes($v);
		}

		$rsp = db_insert_users($user['cluster_id'], 'Buckets', $hash);

		if (! $rsp['ok']){
			return null;
		}

		buckets_load_extras($bucket, $user['id']);

		$rsp['bucket'] = $bucket;
		return $rsp;
	}

	#################################################################

	function buckets_delete_bucket(&$bucket){

		$user = users_get_by_id($bucket['user_id']);

		$enc_id = AddSlashes($bucket['id']);
		$sql = "SELECT * FROM Dots WHERE bucket_id='{$enc_id}'";

		$more = array(
			'page' => 1,
			'per_page' => 1000,
		);

		$page_count = null;
		$total_count = null;
		$dots_deleted = 0;

		while((! isset($page_count)) || ($page_count >= $more['page'])){

			$rsp = db_fetch_paginated_users($user['cluster_id'], $sql, $more);		

			if (! $rsp['ok']){
				$rsp['dots_deleted'] = $dots_deleted;
				$rsp['dots_count'] = $total_count;
				return $rsp;
			}

			if (! isset($page_count)){
				$page_count = $rsp['pagination']['page_count'];
				$total_count = $rsp['pagination']['total_count'];
			}

			foreach ($rsp['rows'] as $dot){

				$dot_more = array(
					'skip_bucket_update' => 1
				);

				$dot_rsp = dots_delete_dot($dot, $dot_more);

				if ($dot_rsp['ok']){
					$dots_deleted ++;
				}
			}

			$more['page'] ++;
		}

		$sql = "DELETE FROM Buckets WHERE id='{$enc_id}'";
		$rsp = db_write_users($user['cluster_id'], $sql);

		$rsp['dots_deleted'] = $dots_deleted;
		$rsp['dots_count'] = $total_count;

		return $rsp;
	}

	#################################################################

	function buckets_delete_buckets_for_user(&$user){

		$enc_id = AddSlashes($user['id']);
		$sql = "SELECT * FROM Buckets WHERE user_id='{$enc_id}'";

		$more = array(
			'page' => 1,
			'per_page' => 100,
		);

		$page_count = null;

		$buckets_count = 0;
		$buckets_deleted = 0;

		$dots_count = 0;
		$dots_deleted = 0;

		while((! isset($page_count)) || ($page_count >= $more['page'])){

			$rsp = db_fetch_paginated_users($user['cluster_id'], $sql, $more);		

			if (! $rsp['ok']){
				$rsp['buckets_deleted'] = $buckets_deleted;
				$rsp['buckets_count'] = $buckets_count;
				$rsp['dots_deleted'] = $dots_deleted;
				$rsp['dots_count'] = $dots_count;

				return $rsp;
			}

			if (! isset($page_count)){
				$page_count = $rsp['pagination']['page_count'];
				$buckets_count = $rsp['pagination']['total_count'];
			}

			foreach ($rsp['rows'] as $bucket){

				$bucket_rsp = buckets_delete_bucket($bucket);

				if ($bucket_rsp['ok']){

					$dots_count += $bucket_rsp['dots_count'];
					$dots_deleted += $bucket_rsp['dots_deleted'];

					$buckets_deleted ++;
				}
			}

			$more['page'] ++;
		}

		return array(
			'ok' => 1,
			'buckets_deleted' => $buckets_deleted,
			'buckets_count' => $buckets_count,
			'dots_deleted' => $dots_deleted,
			'dots_count' => $dots_count,
		);
	}

	#################################################################

	# Note the pass-by-ref

	function buckets_load_extras(&$bucket, $viewer_id=0, $more=array()){

		$bucket['public_id'] = buckets_get_public_id($bucket);
		$bucket['extent'] = dots_get_extent_for_bucket($bucket, $viewer_id);

		if ($more['load_dots']){
			$bucket['dots'] = dots_get_dots_for_bucket($bucket, $viewer_id);
		}
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

	function buckets_get_bucket($public_id, $viewer_id=0, $more=array()){

		list($user_id, $bucket_id) = buckets_explode_public_id($public_id);

		if (isset($GLOBALS['buckets_local_cache'][$bucket_id])){
			return $GLOBALS['buckets_local_cache'][$bucket_id];
		}

		$user = users_get_by_id($user_id);

		$enc_id = AddSlashes($bucket_id);
		$enc_user = AddSlashes($user['id']);

		$sql = "SELECT * FROM Buckets WHERE id='{$enc_id}' AND user_id='{$enc_user}'";

		$rsp = db_fetch_users($user['cluster_id'], $sql);
		$bucket = db_single($rsp);

		if ($bucket){

			buckets_load_extras($bucket, $viewer_id, $more);
			$GLOBALS['buckets_local_cache'][$bucket_id] = $bucket;
		}

		return $bucket;
	}

	#################################################################

	function buckets_can_view_bucket(&$bucket, $viewer_id=0){

		if ($bucket['user_id'] == $viewer_id){
			return 1;
		}

		if ($bucket['count_dots_public'] >= 1){
			return 1;
		}

		return 0;
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

	function buckets_buckets_for_user($user, $viewer_id=0, $args=array()){

		$enc_id = AddSlashes($user['id']);

		$sql = "SELECT * FROM Buckets WHERE user_id='{$enc_id}'";

		if ($user['id'] != $viewer_id){

			$sql .= " AND count_dots_public > 0";
		}

		$rsp = db_fetch_paginated_users($user['cluster_id'], $sql, $args);
		$buckets = array();

		foreach ($rsp['rows'] as $row){
			buckets_load_extras($row, $viewer_id);
			$buckets[] = $row;
		}

		return $buckets;
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
