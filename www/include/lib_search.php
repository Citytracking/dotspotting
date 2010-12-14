<?php

	#
	# $Id$
	#

	#################################################################

	function search_dots(&$args, $viewer_id=0, $more=array()){

		$where_parts = _search_generate_where_parts($args);

		$where = array();

		#
		# Note that order of these keys is^H^H will be important.
		# They are dictated by the indexes on DotsSearch which have
		# been thrown into disarray again (20101123/straup)
		#

		foreach (array('sheet', 'user', 'geo', 'time', 'extras') as $what){

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

			# $where[] = "`perms`=0";

			if ($GLOBALS['cfg']['user']['id']){
				$enc_id = AddSlashes($GLOBALS['cfg']['user']['id']);
				$where[] = "(`perms`=0 OR `user_id`='{$enc_id}')";	# glurgh... indexes...
			}

			else {
				$where[] = "`perms`=0";
			}
		}

		#
		# Go!
		#

		$search_more = array(
			'page' => $args['page'],
		);

		if ($more['do_export']){

			$search_more = array(
				'page' => 1,
				'per_page' => $GLOBALS['cfg']['import_max_records'],
			);
		}

		if (isset($where_parts['order'])){
			$search_more['order'] = $where_parts['order'];
		}

		#
		# Okay! Go! For real! But where...
		#

		if (isset($where_parts['user_row'])){

			$search_more['cluster_id'] = $where_parts['user_row']['cluster_id'];

			if (isset($where_parts['extras'])){
				$search_more['has_extras'] = 1;
			}

			return _search_dots_user($where, $viewer_id, $search_more);
		}

		return _search_dots_all($where, $viewer_id, $search_more);
	}

	#################################################################

	function _search_dots_user($where, $viewer_id, $more){

		if ($more['has_extras']){
			$sql = 'SELECT d.*, e.name, e.value FROM Dots d, DotsExtras e WHERE d.id=e.dot_id AND ';
		}

		else {
			$sql = 'SELECT * FROM Dots d WHERE ';
		}

		#

		$sql .= implode(" AND ", $where);

		$rsp = db_fetch_paginated_users($more['cluster_id'], $sql, $more);

		if (! $rsp['ok']){
			return $rsp;
		}

		$dots = array();

		$dot_more = array(
			'load_user' => 1,
		);

		foreach ($rsp['rows'] as $row){
			$dot_more['dot_user_id'] = $row['user_id'];
			$dots[] = dots_get_dot($row['id'], $viewer_id, $dot_more);
		}

		return array(
			'ok' => 1,
			'dots' => &$dots,
		);
	}

	#################################################################

	function _search_dots_all($where, $viewer_id, $more=array()){

		#
		# Go!
		#

		$sql = "SELECT * FROM DotsSearch d WHERE " . implode(" AND ", $where);

		if (isset($more['order'])){

			$sql .= " ORDER BY `{$more['order']['by']}` {$more['order']['sort']}";
		}

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

	# TO DO: extras

	function _search_generate_where_parts(&$args){

		$search_params = array(
			'b' => 'bbox',
			'dt' => 'created',		# change to be 'c' ?
			'gh' => 'geohash',
			'la' => 'latitude',
			'ln' => 'longitude',
			's' => 'sheet_id',
			't' => 'type',
			'u' => 'user_id',
			'e' => 'extras',
		);

		$b = sanitize($args['b'], 'str');		# bounding box
		$dt = sanitize($args['dt'], 'str');		# datetime
		$gh = sanitize($args['gh'], 'str');		# geohash
		$s = '';					# sanitize($args['s'], 'int32');		# sheetid
		$u = sanitize($args['u'], 'int32');		# userid

		$e = sanitize($args['e'], 'str');		# extras

		$sortby = '';		# sanitize($args['_s'], 'str');		# sort by
		$sortorder = '';	# sanitize($args['_o'], 'str');		# sort order

		$where_parts = array();

		if ($s){

			$sheet = sheets_get_sheet($s);

			if ($sheet['id']){
				$where_parts['sheet'] = array(
					"d.sheet_id = " . AddSlashes($sheet['id']),
				);
			}
		}

		#
		# Geo
		#

		if ($b){

			# TO DO: convert to a geohash of (n) length
			# dumper(geo_geohash_encode($swlat, $swlon));
			# dumper(geo_geohash_encode($nelat, $nelon));

			list($swlat, $swlon, $nelat, $nelon) = explode(",", $b, 4);

			$where_parts['geo'] = array(
				"d.latitude >= " . AddSlashes(floatval($swlat)),
				"d.longitude >= " . AddSlashes(floatval($swlon)),
				"d.latitude <= " . AddSlashes(floatval($nelat)),
				"d.longitude <= " . AddSlashes(floatval($nelon)),
			);

			$where_parts['geo_query'] = 'bbox';
		}

		else if ($gh){

			$geohash = substr($gh, 0, 5);

			$where_parts['geo'] = array(
				"d.geohash LIKE '" . AddSlashes($geohash) . "%'",
			);

			$where_parts['geo_query'] = 'geohash';
		}

		else {}

		#
		# Time
		#

		if ($dt){

			$date_start = null;
			$date_end = null;

			# "Around" a given date. For example:
			# http://dotspotting.example.com/search/?dt=(2010-10)

			# This doesn't always work, specifically when passed
			# something like '2010-11-19 12'. Punting for now...

			if (preg_match("/^\(((\d{4})(?:-(\d{2})(?:-(\d{2})(?:(?:T|\s)(\d{2})(?:\:(\d{2})(?:\:(\d{2}))?)?)?)?)?)\)$/", $dt, $m)){

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
				$parts = explode("/", $dt, 2);
				$date_start = strtotime($parts[0]);

				if (count($parts) == 2){
					$date_end = strtotime($parts[1]);
				}
			}

			# ensure ($parts[0] && $date_start) and ($parts[1] && $end_date) here ?

			$time_parts = array();

			if ($date_start){
				$time_parts[] = "UNIX_TIMESTAMP(d.created) >= " . AddSlashes($date_start);
			}

			if ($date_end){
				$time_parts[] = "UNIX_TIMESTAMP(d.created) <= " . AddSlashes($date_end);
			}

			if (count($time_parts)){
				$where_parts['time'] = $time_parts;
			}
		}

		#
		# User stuff 
		#

		if ($u){

			$user = users_get_by_id($u);

			if (($user) && (! $user['deleted'])){

				$where_parts['user'] = array(
					"d.user_id=" . AddSlashes($user['id']),
				);

				$where_parts['user_row'] = $user;
			}
		}

		#
		# Extras (requires user)
		#

		if (($e) && ($where_parts['user_row'])){

			$extras = array();

			foreach (explode(";", $e) as $parts){

				list($name, $value) = explode(":", $parts);

				$enc_name = AddSlashes($name);
				$enc_value = AddSlashes($value);

				$extras[] = "(e.name='{$enc_name}' AND e.value='{$enc_value}')";
			}

			if (count($extras)){
				$where_parts['extras'] = $extras;
			}
		}

		#
		# Sorting
		#

		if ($sortby){

			if (in_array($sortby, array_values($search_params))){
				# pass
			}

			else if (in_array($sortby, array_keys($search_params))){
				$sortby = $search_params[ $sortby ];
			}

			else {
				$sortby = null;
			}

			if ($sortby){
				$sortorder = (strtolower($sortorder) == 'desc') ? 'DESC' : 'ASC';

				$where_parts['order'] = array(
					'by' => $sortby,
					'sort' => $sortorder,
				);
			}
		}

		return $where_parts;
	}

	#################################################################
?>