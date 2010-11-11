<?php

	#
	# $Id$
	#

	#################################################################

	# do we need to track perms here for search-iness ?

	# do we need to track who created the extra (probably
	# but probably not for version one)

	function dots_extras_create_extra(&$dot, $extra){

		$user = users_get_by_id($dot['user_id']);

		$extra['user_id'] = $user['id'];
		$extra['dot_id'] = $dot['id'];

		if (strpos($extra['label'], ":")){
			list($ns, $label) = explode(":", $extra['label'], 2);

			$extra['namespace'] = $ns;
			$extra['label'] = $label;
		}

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

			$label = $row['label'];

			if ($ns = $row['namespace']){
				$label = implode(":", array($ns, $label));
			}

			# if (isset($dot[$label])){
			#	continue;
			# }

			if (! is_array($extras[$label])){
				$extras[$label] = array();
			}

			$extras[$label][] = $row;
		}

		return $extras;
	}

	#################################################################

	#
	# This is just a dumb utility function and
	# is called from inc_dots_list.php
	#

	function dots_extras_keys_for_listview($dot){

		$keys = array();

		foreach ($dot['extras'] as $key => $ignore){
			if (! isset($dot[$key])){
				$keys[] = $key;
			}
		}

		return $keys;
	}

	#################################################################

?>