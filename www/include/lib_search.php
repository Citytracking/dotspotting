<?php

	#
	# $Id$
	#

	# HEY LOOK! I STILL HAVEN'T ADDED PROPER (DB) INDEXES FOR ANY
	# OF THIS STUFF YET (20101119/straup)

	#################################################################

	function search_dots(&$args, $viewer_id=0){

		$where_parts = _search_generate_where_parts($args);

		$where = array();

		# (latlon|geohash), dt, perms
		# (latlon|geohash), dt, type, perms
		# (latlon|geohash), perms
		# type, perms
		# dt, perms

		#
		# Note that order of these keys is important. They are dictated by
		# the indexes on DotsSearch.
		#

		foreach (array('user', 'geo', 'time', 'type', 'location') as $what){

			if (isset($where_parts[$what])){
				$where = array_merge($where, $where_parts[$what]);
			}
		}

		if (! count($where)){

			return array(
				'ok' => 0,
				'error' => 'No valid search criteria',
			);
		}

		#
		# Always with the public
		#

		$is_own = 0;

		if (($where_parts['user_row']) && ($where_parts['user_row']['id'] === $viewer_id)){
			$is_own = 1;
		}

		if (! $is_own){
			$where[] = "`perms`=0";
		}

		#
		# Check to see if we can just query a user's shard. This is
		# just a placeholder for now as everything reads from DotsSearch.
		# See also: README.SEARCH.md (20101120/straup)
		#

		# $use_shard = (isset($where_parts['user_row'])) ? 1 : 0;
		# 
		# if ($use_shard){
		# 	$use_shard = (isset($where_parts['type']) || isset($where_parts['location']) || isset($where_parts['time']) ? 1 : 0;
		# }

		#
		# Go!
		#

		$more = array(
		      'page' => $args['page'],
		);

		return _search_dots_all($where, $more);
	}

	#################################################################

	function _search_dots_all($where, $more=array()){

		#
		# Go!
		#

		$sql = "SELECT * FROM DotsSearch WHERE " . implode(" AND ", $where);
		$rsp = db_fetch_paginated($sql, $more);

		if (! $rsp['ok']){
			return $rsp;
		}

		$dots = array();

		$dot_more = array(
			'load_user' => 1,
		);

		foreach ($rsp['rows'] as $row){
			$dot_more['dot_user_id'] = $row['user_id'];
			$dots[] = dots_get_dot($row['dot_id'], $viewer_id, $dot_more);
		}

		return array(
			'ok' => 1,
			'dots' => &$dots,
		);
	}

	#################################################################

	function _search_generate_where_parts(&$args){

		$where_parts = array();

		#
		# Geo
		#

		if ($args['b']){

			list($swlat, $swlon, $nelat, $nelon) = explode(",", $args['bbox'], 4);

			$where_parts['geo'] = array(
				"`latitude` >= " . AddSlashes($swlat),
				"`longitude` >= " . AddSlashes($swlon),
				"`latitude` <= " . AddSlashes($nelat),
				"`longitude` <= " . AddSlashes($nelon),
			);

			$where_parts['geo_query'] = 'bbox';
		}

		else if ($args['gh']){

			$geohash = substr($args['gh'], 0, 5);

			$where_parts['geo'] = array(
				"`geohash` LIKE '" . AddSlashes($geohash) . "%'",
			);

			$where_parts['geo_query'] = 'geohash';
		}

		else {}

		#
		# type (or poorman's "what")
		#

		if ($args['t']){

			$where_parts['type'] = array(
				"`type`='" . AddSlashes($args['t']) . "'",
			);
		}

		#
		# location (or poorman's "where")
		#

		if ($args['l']){

			$where_parts['location'] = array(
				"`location`='" . AddSlashes($args['l']) . "'",
			);
		}

		#
		# Time
		#

		if ($args['dt']){

			$date_start = null;
			$date_end = null;

			# "Around" a given date. For example:
			# http://dotspotting.example.com/search/?dt=(2010-10)

			if (preg_match("/^\(((\d{4})(?:-(\d{2})(?:-(\d{2})(?:(?:T|\s)(\d{2})(?:\:(\d{2})(?:\:(\d{2}))?)?)?)?)?)\)$/i", $args['dt'], $m)){

				list($ignore, $dt, $year, $month, $day, $hour) = $m;

				$offset = 0;

				if ($hour){
					$offset = 60 * 60;
				}

				elseif ($day){
					$offset = 60 * 60 * 24;
				}

				elseif ($month){
					$offset = 60 * 60 * 24 * 28;
				}

				elseif ($year){
					$offset = 60 * 60 * 24 * 365;
				}

				if ($ts = strtotime($dt)){
					$date_start = $ts - $offset;
					$date_end = $ts + $offset;
				}
			}

			else {
				$parts = explode("/", $args['dt'], 2);
				$date_start = strtotime($parts[0]);

				if (count($parts) == 2){
					$date_end = strtotime($parts[1]);
				}
			}

			# ensure ($parts[0] && $date_start) and ($parts[1] && $end_date) here ?

			$time_parts = array();

			if ($date_start){
				$time_parts[] = "UNIX_TIMESTAMP(created) >= " . AddSlashes($date_start);
			}

			if ($date_end){
				$time_parts[] = "UNIX_TIMESTAMP(created) <= " . AddSlashes($date_end);
			}

			if (count($time_parts)){
				$where_parts['time'] = $time_parts;
			}
		}

		#
		# User stuff 
		#

		if ($args['u']){

			$user = users_get_by_id($args['u']);

			if (($user) && (! $user['deleted'])){

				$where_parts['user'] = array(
					"`user_id`=" . AddSlashes($user['id']),
				);

				$where_parts['user_row'] = $user;
			}
		}

		return $where_parts;
	}

	#################################################################

	function _search_generate_result_set(&$rsp, $viewer_id){

	}

	#################################################################
?>