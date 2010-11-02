<?php

	#
	# $Id$
	#

	#################################################################

	#
	# This is *not* the generic search function. That's going to
	# take a little more planning. This is a quick and dirty shim
	# to allow searching across geohashes. Also, this assumes you're
	# passing pagination information by hand in $args.
	# (20101026/straup)
	#

	function search_dots_for_geohash($geohash, $viewer_id=0, $args=array()){

		$enc_hash = AddSlashes($geohash);

		# No point in doing a LIKE operation when it's a FQ geohash

		if (strlen($enc_hash) == 12){
			$sql = "SELECT * FROM DotsLookup WHERE deleted=0 AND perms=0 AND geohash='{$enc_hash}'";
		}

		else {
			$sql = "SELECT * FROM DotsLookup WHERE deleted=0 AND perms=0 AND geohash LIKE '{$enc_hash}%'";
		}

		$rsp = db_fetch_paginated($sql, $args);

		if ($rsp['ok']){

			$dots = array();

			$more = array(
				'load_user' => 1,
			);

			foreach ($rsp['rows'] as $row){

				$dots[] = dots_get_dot($row['dot_id'], $viewer_id, $more);
			}
		}

		else {
			# Logging?
		}

		return $dots;
	}

	#################################################################
?>