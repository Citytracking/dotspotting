<?php

	#
	# $Id$
	#

	# THIS IS VERY MUCH STILL A WORK IN PROGRESS.
	# IT IS NOT READY FOR ACTUAL USE.
	#
	# (20101210/straup)

	#################################################################

	function dots_extras_create(&$dot, $key, $value){

		$user = users_get_by_id($dot['user_id']);

		$id = dbtickets_create(32);

		if (! $id){

			return array(
				'ok' => 0,
				'error' => 'Ticket server failed',
			);
		}

		$data = array(
			'id' => $id,
			'dot_id' => $dot['id'],
			'user_id' => $user['id'],
			'created' => $now,
			'name' => $name
		);

		# TO DO:
		#
		# * am I a string or am I a number?
		# 
		# $data['is_numeric'] = 'fix me';

		$hash = array();

		foreach ($data as $_key => $_value){
			$hash[ $key ] = AddSlashes($value);
		}

		$rsp = db_insert_users($user['cluster_id'], 'DotsExtras', $hash);

		if ($rsp['ok']){
			$rsp['data'] = $data;
		}

		return $rsp;
	}

	#################################################################

?>