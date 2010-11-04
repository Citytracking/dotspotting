<?php

	#
	# $Id$
	#

	#################################################################

	# do we need to track perms here for search-iness ?

	# do we need to track who created the extra (probably
	# but probably not for version one)

	function dots_extras_create_extra(&$dot, $label, $value){

		$user = users_get_by_id($dot['user_id']);

		if (strpos($label, ":")){
			list($ns, $label) = explode(":", $label, 2);
		}

		$extra = array(
			'user_id' => $user['id'],
			'dot_id' => $dot['id'],
			'namespace' => $ns,
			'label' => $label,
			'value' => $value,
		);

		$hash = array();

		foreach ($extra as $k => $v){
			$hash[$k] = AddSlashes($v);
		}

		$rsp = db_insert_users($user['cluster_id'], 'DotsExtras', $hash);

		if ($rsp['ok']){
			$rsp['extra'] = $extra;
		}

		return $rsp;
	}

	#################################################################

	function dots_extras_delete_extra(){
		# remember to update Dots:has_extras here
	}

	#################################################################

	function dots_extras_get_extras(&$dot, $more=array()){

		$user = users_get_by_id($dot['user_id']);

		$enc_id = AddSlashes($dot['id']);

		$sql = "SELECT * FROM DotsExtras WHERE dot_id='{$enc_id}'";

		$rsp = db_fetch_users($user['cluster_id'], $sql);
		$extras = array();

		foreach ($rsp['rows'] as $row){

			if ($ns = $row['namespace']){
				$row['label'] = implode(":", array(
					$row['namespace'],
					$row['label']
				));
			}

			$label = $row['label'];

			if (! is_array($extras[$label])){
				$extras[$label] = array();
			}

			$extras[$label][] = $row['value'];
		}

		return $extras;
	}

	#################################################################
?>