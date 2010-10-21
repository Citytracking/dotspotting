<?php

	#
	# $Id$
	#

	#################################################################

	function dots_extra_create_extra(&$dot, $key, $value){


	}

	#################################################################

	function dots_extras_extras_for_dot(&$dot){

		$user = users_get_by_id($dot['user_id']);

		$enc_id = AddSlashes($dot['id']);

		$sql = "SELECT * FROM DotsExtras WHERE dot_id='{$enc_id}'";

		$rsp = db_fetch_users($user['cluster_id'], $sql);
		$extras = array();

		# HEY LOOK! THIS STILL DOESN'T DEAL WITH KEYS THAT
		# HAVE MULTIPLE VALUES

		foreach ($rsp['rows'] as $row){

			if (strpos($row['key'], ":")){

				list($ns, $key) = explode(":", $row['key'], 2);

				if (! is_array($extras[$ns])){
					$extras[$ns] = array();
				}

				$extras[$ns][$key] = $value;				
			}

			else {
				$extras[ $row['key'] ] = $row['value'];
			}
		}

		return $extras;
	}

	#################################################################
?>