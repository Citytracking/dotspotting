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

		$extra = array(
			'user_id' => AddSlashes($user['id']),
			'dot_id' => AddSlashes($dot['id']),
			'label' => AddSlashes($label),
			'value' => AddSlashes($value),
		);

		$rsp = db_insert_users($user['cluster_id'], 'DotsExtras', $extra);

		if (! $rsp['ok']){
			return null;
		}

		return 1;
	}

	#################################################################

	function dots_extras_get_extras(&$dot){

		$user = users_get_by_id($dot['user_id']);

		$enc_id = AddSlashes($dot['id']);

		$sql = "SELECT * FROM DotsExtras WHERE dot_id='{$enc_id}'";

		$rsp = db_fetch_users($user['cluster_id'], $sql);
		$extras = array();

		foreach ($rsp['rows'] as $row){

			if (strpos($row['label'], ":")){

				list($ns, $label) = explode(":", $row['label'], 2);

				if (! is_array($extras[$ns])){
					$extras[$ns] = array();
				}

				if (! is_array($extras[$ns][$label])){
					$extras[$ns][$label] = array();
				}

				$extras[$ns][$label][] = $value;				
			}

			else {

				$label = $row['label'];

				if (! is_array($extras[$label])){
					$extras[$label] = array();
				}

				$extras[$label][] = $row['value'];
			}
		}

		return $extras;
	}

	#################################################################
?>