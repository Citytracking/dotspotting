<?php

	#
	# $Id$
	#

	#################################################################

	function search_dots(&$args, $viewer_id=0, $more=array()){
		return _search_by($args, 'dots', $viewer_id, $more);
	}

	function search_sheets(&$args, $viewer_id=0, $more=array()){
		return _search_by($args, 'sheets', $viewer_id, $more);
	}

	#################################################################

	function _search_by(&$args, $search_by, $viewer_id=0, $more=array()){

		$where_parts = _search_generate_where_parts($args);

		$where = array();

		#
		# Note that order of these keys is important for database
		# indexes.
		#

		foreach (array('user', 'geo', 'time', 'extras') as $what){

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

			if ($GLOBALS['cfg']['user']['id']){
				$enc_id = AddSlashes($GLOBALS['cfg']['user']['id']);
				$where[] = "(d.perms=0 OR d.user_id='{$enc_id}')";	# glurgh... indexes...
			}

			else {
				$where[] = "d.perms=0";
			}
		}

		#
		# Go!
		#

		$search_more = array(
			'page' => $args['page'],
		);

		if (isset($where_parts['order'])){
			$search_more['order'] = $where_parts['order'];
		}

		#
		# Okay! Go! For real! But where...
		#

		if (isset($where_parts['extras'])){
			$search_more['has_extras'] = 1;
		}

		if ($search_by == 'sheets'){
			return _search_sheets_all($where, $viewer_id, $search_more);
		}

		return _search_dots_all($where, $viewer_id, $search_more);
	}

	#################################################################

	function _search_dots_all($where, $viewer_id, $more=array()){

		$sql = 'SELECT * FROM DotsSearch d WHERE ';

		if ($more['has_extras']){
			$sql = 'SELECT d.*, e.name, e.value FROM DotsSearch d, DotsSearchExtras e WHERE d.dot_id=e.dot_id AND ';
		}

		#

		$sql .= implode(" AND ", $where);

		if (isset($more['order'])){

			$sql .= " ORDER BY d.{$more['order']['by']} {$more['order']['sort']}";
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

			if (! $row['dot_id']){
				continue;
			}

			$dot_more['dot_user_id'] = $row['user_id'];
			$dots[] = dots_get_dot($row['dot_id'], $viewer_id, $dot_more);
		}

		return array(
			'ok' => 1,
			'dots' => &$dots,
		);
	}

	#################################################################

	function _search_sheets_all($where, $viewer_id, $more=array()){

		$sql = "SELECT DISTINCT(d.sheet_id) FROM DotsSearch d WHERE ";

		if ($more['has_extras']){
			$sql = "SELECT DISTINCT(d.sheet_id) FROM DotsSearch d, DotsSearchExtras e WHERE d.dot_id=e.dot_id AND ";
		}

		#

		$sql .= implode(" AND ", $where);

		$rsp = db_fetch_paginated($sql, $more);

		if (! $rsp['ok']){
			return $rsp;
		}

		$sheets = array();

		$sheet_more = array(
			'load_user' => 1,
			'load_extent' => 1,
		);

		foreach ($rsp['rows'] as $row){

			if (! $row['sheet_id']){
				continue;
			}

			$sheet_more['sheet_user_id'] = $row['user_id'];
			$sheets[] = sheets_get_sheet($row['sheet_id'], $viewer_id, $sheet_more);
		}

		return array(
			'ok' => 1,
			'sheets' => &$sheets,
		);
	}

	#################################################################

	function _search_generate_where_parts(&$args){

		$search_params = array(
			'b' => 'bbox',
			# 'd' is reserved for 'display' (as in 'sheets' or 'dots')
			'dt' => 'created',		# change to be 'c' ?
			'e' => 'extras',
			# 'gh' => 'geohash',
			'la' => 'latitude',
			'ln' => 'longitude',
			'll' => 'latitude,latitude',
			't' => 'type',
			'u' => 'user_id',
		);

		$b = sanitize($args['b'], 'str');		# bounding box
		$dt = sanitize($args['dt'], 'str');		# datetime
		# $gh = sanitize($args['gh'], 'str');		# geohash
		$ll = sanitize($args['ll'], 'str');		# latitude, longitude
		$u = sanitize($args['u'], 'int32');		# userid

		$e = sanitize($args['e'], 'str');		# extras

		$sortby = '';		# sanitize($args['_s'], 'str');		# sort by
		$sortorder = '';	# sanitize($args['_o'], 'str');		# sort order

		$where_parts = array();

		#
		# Geo
		#

		if ($b){

			list($swlat, $swlon, $nelat, $nelon) = explode(",", $b, 4);

			$where = implode(" AND ", array(
				"d.latitude >= " . AddSlashes(floatval($swlat)),
				"d.longitude >= " . AddSlashes(floatval($swlon)),
				"d.latitude <= " . AddSlashes(floatval($nelat)),
				"d.longitude <= " . AddSlashes(floatval($nelon))
			));

			$where_parts['geo'] = array(
				"({$where})",
			);

			$where_parts['geo_query'] = 'bbox';
		}

		else if ($ll){

			list($lat, $lon) = explode(",", $ll, 2);

			list($swlat, $swlon, $nelat, $nelon) = geo_utils_nearby_bbox($lat, $lon);

			$where = implode(" AND ", array(
				"d.latitude >= " . AddSlashes(floatval($swlat)),
				"d.longitude >= " . AddSlashes(floatval($swlon)),
				"d.latitude <= " . AddSlashes(floatval($nelat)),
				"d.longitude <= " . AddSlashes(floatval($nelon))
			));

			$where_parts['geo'] = array(
				"({$where})",
			);

			$where_parts['geo_query'] = 'nearby';

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
		# Extras
		#

		if (($e) && ($GLOBALS['cfg']['enable_feature_dots_indexing'])){

			$extras = array();

			# This (the part with the ";" and the ":") is not the final syntax.
			# I'm just working through the other bits first. (20101213/straup)

			foreach (explode(";", $e) as $parts){

				list($name, $value) = explode(":", $parts);

				$tmp = array();

				if ($name){
					$enc_name = AddSlashes($name);
					$tmp[] = "e.name='{$enc_name}'";
				}

				if ($value){

					if (preg_match("/^CONTAINS\((.+)\)$/", $value, $m)){
						$enc_value = AddSlashes($m[1]);
						$tmp[] = "e.value LIKE '%{$enc_value}%'";
					}

					# Also, to do: work out some way to specify CAST-ing requirements
					# for searching on extras. It would be nice to spend the time working
					# out a simplified syntax for doing basic operations (greater than,
					# between, etc.) but then you get sucked in to a twisty maze of edge
					# cases where you also want to do <= or that when strings are cast
					# as ints they become 0 or whether to use decimals and how big they
					# should or ... anyway, you get the idea. There are more pressing
					# things to deal with Now. (20101216/straup)
					#
					# http://dev.mysql.com/doc/refman/5.0/en/cast-functions.html#function_cast

					else {
						$enc_value = AddSlashes($value);
						$tmp[] = "e.value='{$enc_value}'";
					}

					# Something to consider if it's ever possible to feel
					# safe and comfortible evulating regular expressions
					# from user input... (20101216/straup)
					# http://dev.mysql.com/doc/refman/5.1/en/regexp.html

				}

				if (count($parts)){
					$extras[] = "(" . implode(" AND ", $tmp) . ")";
				}
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
