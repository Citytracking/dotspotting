<?php

	#
	# $Id$
	#

	#################################################################

	loadlib("dots_derive");
	loadlib("dots_lookup");
	loadlib("dots_search");
	loadlib("dots_search_extras");

	loadlib("geo_utils");

	#################################################################

	function dots_permissions_map($string_keys=0){

		$map = array(
			0 => 'public',
			1 => 'private',
		);

		if ($string_keys){
			$map = array_flip($map);
		}

		return $map;
	}

	#################################################################

	function dots_get_bookends_for_dot(&$dot, $viewer_id=0, $count=1){

		$user = users_get_by_id($dot['user_id']);

		$enc_id = AddSlashes($dot['id']);
		$enc_sheet = AddSlashes($dot['sheet_id']);

		$enc_count = AddSlashes($count);

		$sql = "SELECT * FROM Dots WHERE sheet_id='{$enc_sheet}' AND id < '{$enc_id}'";
		$sql = _dots_where_public_sql($sql);
		
		// added ORDER BY to SQL to grab previous dot, not the first dot (seanc | 02.11.2011)
		$order_by = AddSlashes('id');
		$order_sort = AddSlashes('DESC');
		$sql .= " ORDER BY {$order_by} {$order_sort}";

		$sql .= " LIMIT {$enc_count}";

		$rsp_before = db_fetch_users($user['cluster_id'], $sql);

		$sql = "SELECT * FROM Dots WHERE sheet_id='{$enc_sheet}' AND id > '{$enc_id}'";
		$sql = _dots_where_public_sql($sql);

		$sql .= " LIMIT {$enc_count}";

		$rsp_after = db_fetch_users($user['cluster_id'], $sql);

		$before = array();
		$after = array();

		# Note the $_dot so we don't accidentally blow away
		# $dot which has been passed by reference...
		foreach ($rsp_before['rows'] as $_dot){
			dots_load_details($_dot, $viewer_id);
			$before[] = $_dot;
		}

		foreach ($rsp_after['rows'] as $_dot){
			dots_load_details($_dot, $viewer_id);
			$after[] = $_dot;
		}

		return array(
			'before' => $before,
			'after' => $after,
			'count' => count($before) + count($after),
		);
	}

	#################################################################

	function dots_import_dots(&$user, &$sheet, &$dots, $more=array()){

		$received = 0;
		$processed = 0;

		$errors = array();
		$search = array();
		$extras = array();
		$lookup = array();

		$timings = array(
			0 => 0
		);

		$start_all = microtime_ms() / 1000;

		# As in: don't update DotsSearch inline but save
		# all the inserts and do them at at end. 

		$more['buffer_search_inserts'] = 1;
		$more['buffer_extras_inserts'] = 1;
		$more['buffer_lookup_inserts'] = 1;

		foreach ($dots as $dot){

			$received ++;

			$start = microtime_ms() / 1000;

			$rsp = dots_create_dot($user, $sheet, $dot, $more);

			$end = microtime_ms() / 1000;

			$timings[ $received ] = $end - $start;

			if (! $rsp['ok']){
				$rsp['record'] = $received;
				$errors[] = $rsp;

				continue;
			}

			if (isset($rsp['search'])){
				$search[] = $rsp['search'];
			}

			if (isset($rsp['extras'])){
				$extras = array_merge($extras, $rsp['extras']);
			}

			if (isset($rsp['lookup'])){
				$lookup[] = $rsp['lookup'];
			}

			$processed ++;
		}

		#
		# Buffered inserts (at some point this might/should
		# be handed to an offline tasks system)
		#

		if (count($lookup)){

			$lookup_rsp = dots_lookup_add_lots_of_dots($lookup);

			if (! $lookup_rsp){
				# What then ?
			}
		}

		if (count($search)){

			$search_rsp = dots_search_add_lots_of_dots($search);

			if (! $search_rsp){
				# What then ?
			}
		}

		if (count($extras)){

			$extras_rsp = dots_search_extras_add_lots_of_extras($extras);

			if (! $extras_rsp){
				# What then ?
			}
		}

		#

		$end_all = microtime_ms() / 1000;
		$timings[0] = $end_all - $start_all;

		$ok = ($processed) ? 1 : 0;

		return array(
			'ok' => $ok,
			'errors' => &$errors,
			'timings' => &$timings,
			'dots_received' => $received,
			'dots_processed' => $processed,
		);

	}

	#################################################################

	function dots_create_dot(&$user, &$sheet, &$data, $more=array()){

		# if we've gotten here via lib_uploads then
		# we will have already done validation.

		if (! $more['skip_validation']){

			$rsp = dots_ensure_valid_data($row);

			if (! $rsp['ok']){
				return $rsp;
			}
		}

		#

		$id = dbtickets_create(64);

		if (! $id){

			return array(
				'ok' => 0,
				'error' => 'Ticket server failed',
			);
		}

		#
		# Assign basic geo bits - keep track of stuff that has
		# been derived so that we can flag them accordingly in
		# the DotsExtras table.
		#

		list($data, $derived) = dots_derive_location_data($data);

		#
		# creation date for the point (different from import date)
		# should this be stored/flagged as an extra?
		#

		$now = time();

		if ($created = $data['created']){

			#
			# Because intval("2010-09-23T00:18:55Z") returns '2010' ...
			# Because is_numeric(20101029154025.000) returns true ...
			# Because strtotime(time()) returns false ...
			# BECAUSE GOD HATES YOU ...
			#

			$created = (preg_match("/^\d+$/", $created)) ? $created : strtotime($created);

			# if ! $created then reassign $now ?

			# Now convert everything back in to a datetime string

			if ($created){
				$data['created'] = gmdate('Y-m-d H:i:s', $created);
			}
		}

		else {
			$data['created'] = gmdate('Y-m-d H:i:s', $now);
		}

		#
		# permissions
		#

		$perms_map = dots_permissions_map('string keys');
		$perms = $perms_map['public'];

		if (($data['perms'] == 'private') || ($more['mark_all_private'])){
			$perms = $perms_map['private'];
		}

		#
		# Go! Or rather... start!
		#

		$dot = array(
			'id' => $id,
			'user_id' => $user['id'],
			'sheet_id' => $sheet['id'],
			'perms' => $perms,
		);

		# Always store created date in the user Sheets table; it's
		# not clear how this relates/works with the dots extras
		# stuff yet (20101210/straup)

		$to_denormalize = array(
			'created',
		);

		foreach ($to_denormalize as $key){

			if ((isset($data[$key])) && (! empty($data[$key]))){
				$dot[$key] = $data[$key];
			}
		}

		# Please to write me: A discussion on the relationship between
		# details, extras, 'indexed' and search. (20101213/straup)

		#
		# Dots extras (as in: extra things you can search for)
		#

		$details = array();
		$extras = array();

		if (($GLOBALS['cfg']['enable_feature_dots_indexing']) && ($more['dots_index_on'])){

			$index_on = array();

			$tmp = explode(",", $more['dots_index_on'], $GLOBALS['cfg']['dots_indexing_max_cols']);

			foreach ($tmp as $field){

				$field = trim($field);

				if (! isset($data[$field])){
					continue;
				}

				$extras[] = array(
					'dot_id' => $id,
					'sheet_id' => $sheet['id'],
					'user_id' => $user['id'],
					'name' => $field,
					'value' => $data[$field],
				);

				$index_on[] = AddSlashes($field);
			}

			$dot['index_on'] = implode(",", $index_on);
		}

		#
		# Store any remaining fields in a big old JSON blob
		#

		foreach (array_keys($data) as $label){

			$label = filter_strict(trim($label));

			if (! $label){
				continue;
			}

			$value = $data[$label];
			$value = filter_strict(trim($value));

			if (! $value){
				continue;
			}

			$ns = null;
			$pred = $label;

			if (strpos($label, ':')){
				list($ns, $pred) = explode(':', $label, 2);
			}

			$detail = array(
				'namespace' => $ns,
				'label' => $pred,
				'value' => $data[$label],
			);

			if (isset($derived[$label])){
				$extra['derived_from'] = $derived[$label];
			}

			if (! is_array($details[$label])){
				$details[$label] = array();
			}

			$details[$label][] = $detail;
		}

		$dot['details_json'] = json_encode($details);

		#
		# Look, we are FINALLY NOW creating the dot
		#

		$insert = array();

		foreach ($dot as $key => $value){
			$insert[$key] = AddSlashes($value);
		}

		$rsp = db_insert_users($user['cluster_id'], 'Dots', $insert);

		if (! $rsp['ok']){
			return $rsp;
		}

		$dot['details'] = $details;

		#
		# Update the DotsLookup table
		#

		$lookup = array(
			'dot_id' => $id,
			'sheet_id' => $sheet['id'],
			'user_id' => $user['id'],
			'imported' => $now,
			'last_modified' => $now,
		);

		if ($more['buffer_lookup_inserts']){
			$rsp['lookup'] = $lookup;
		}

		else {
			$lookup_rsp = dots_lookup_create($lookup);

			if (! $lookup_rsp['ok']){
				# What then...
			}
		}

		#
		# Now the searching (first the basics then any 'extras' specific to this dot)
		#

		$search = array(
			'dot_id' => $id,
			'sheet_id' => $sheet['id'],
			'user_id' => $user['id'],
			'imported' => $now,
			'created' => $data['created'],
			'perms' => $perms,
			'geohash' => $data['geohash'],
		);

		#
		# Don't assign empty strings for lat/lon because MySQL will
		# store them as 0.0 rather than NULLs
		# 

		foreach (array('latitude', 'longitude') as $coord){

			if (is_numeric($data[$coord])){
				$search[$coord] = $data[$coord];
			}
		}

		if ($more['buffer_search_inserts']){
			$rsp['search'] = &$search;
		}

		else {
			$search_rsp = dots_search_add_dot($search);

			if (! $search_rsp['ok']){
				# What then...
			}
		}

		# extras

		if ($more['buffer_extras_inserts']){
			$rsp['extras'] = $extras;
		}

		else {
			$extras_rsp = dots_search_extras_add_lots_of_extras($extras);

			if (! $extras_rsp['ok']){
				# What then...
			}
		}

		#
		# Happy happy
		#

		$rsp['dot'] = &$dot;
		return $rsp;
	}

	#################################################################

	function dots_update_dot(&$dot, $update){

		$user = users_get_by_id($dot['user_id']);

		$enc_id = AddSlashes($dot['id']);
		$where = "id='{$enc_id}'";

		foreach ($update as $k => $v){
			$update[$k] = AddSlashes($v);
		}

		$rsp = db_update_users($user['cluster_id'], 'Dots', $update, $where);

		if ($rsp['ok']){
			$cache_key = "dot_{$dot['id']}";
			cache_unset($cache_key);
		}

		#
		# Update search: TODO
		#

		#
		# Update the lookup table?
		#

		$sheet = sheets_get_sheet($dot['sheet_id']);
		$count_rsp = sheets_update_dot_count_for_sheet($sheet);

		$lookup_update = array(
			'last_modified' => $now,
		);

		$lookup_rsp = dots_lookup_update($dot, $lookup_update);

		if (! $lookup_rsp['ok']){
			# What?
		}

		# Happy!		

		return $rsp;
	}

	#################################################################

	function dots_delete_dot(&$dot, $more=array()){

		#
		# Update the search and extras table (check to see that
		# we haven't already done this, for example if we're in
		# the process of deleting a user or a sheet)
		#

 		if (! isset($more['skip_update_search'])){

			$search_rsp = dots_search_remove_dot($dot);

			if (! $search_rsp['ok']){
				# What?
			}

			$extras_rsp = dots_search_extras_remove_dot($dot);

			if (! $extras_rsp['ok']){
				# What?
			}
		}

		#
		# Okay. Let's start deleting the dot itself!
		#

		$user = users_get_by_id($dot['user_id']);

		$enc_id = AddSlashes($dot['id']);

		$sql = "DELETE FROM Dots WHERE id='{$enc_id}'";
		$rsp = db_write_users($user['cluster_id'], $sql);

		if (! $rsp['ok']){
			return $rsp;
		}

 		if (! isset($more['skip_update_sheet'])){

			$sheet = sheets_get_sheet($dot['sheet_id']);

			$rsp2 = sheets_update_dot_count_for_sheet($sheet);
			$rsp['update_sheet_count'] = $rsp2['ok'];
		}

		#
		# Update the extras table
		#

		$extras_rsp = dots_search_extras_remove_dot($dot);

		if (! $extras_rsp['ok']){
			# What?
		}
		
		#
		# Update the lookup table
		#

		$lookup_update = array(
			'deleted' => time(),
		);

		$lookup_rsp = dots_lookup_update($dot, $lookup_update);

		if (! $lookup_rsp['ok']){
			# What?
		}

		#

		if ($rsp['ok']){
			$cache_key = "dot_{$dot['id']}";
			cache_unset($cache_key);
		}

		return $rsp;
	}

	#################################################################

	function dots_get_extent_for_sheet(&$sheet, $viewer_id=0){

		$enc_id = AddSlashes($sheet['id']);

		$sql = "SELECT MIN(latitude) AS swlat, MIN(longitude) AS swlon, MAX(latitude) AS nelat, MAX(longitude) AS nelon FROM DotsSearch WHERE sheet_id='{$enc_id}'";

		if ($viewer_id !== $sheet['user_id']){

			$sql = _dots_where_public_sql($sql);
		}

		return db_single(db_fetch($sql));
	}

	#################################################################

	#
	# Fetch the dot from the shards
	#	

	function dots_get_dot($dot_id, $viewer_id=0, $more=array()){

		# Can has cache! Note this is just the raw stuff
		# from the Dots table and that 'details' get loaded
		# below.

		$cache_key = "dot_{$dot_id}";
		$cache = cache_get($cache_key);

		if ($cache['ok']){
			$dot = $cache['data'];
		}

		else {

			# This is the kind of thing that would be set by lib_search

			if ($user_id = $more['dot_user_id']){
				$user = users_get_by_id($more['dot_user_id']);
			}

			else {
				$lookup = dots_lookup_dot($dot_id);

				if (! $lookup){
					return;
				}

				if ($lookup['deleted']){
					return array(
						'id' => $lookup['dot_id'],
						'deleted' => $lookup['deleted'],
					);
				}

				$user = users_get_by_id($lookup['user_id']);
			}

			if (! $user){
				return;
			}

			$enc_id = AddSlashes($dot_id);
			$enc_user = AddSlashes($user['id']);

			$sql = "SELECT * FROM Dots WHERE id='{$enc_id}'";

			$rsp = db_fetch_users($user['cluster_id'], $sql);
			$dot = db_single($rsp);

			if ($rsp['ok']){
				cache_set($cache_key, $dot, 'cache locally');
			}
		}

		if (($dot) && (($viewer_id !== $user['id']))){

			if (! dots_can_view_dot($dot, $viewer_id)){
				$dot = null;
			}
		}

		if ($dot){
			$more['load_sheet'] = 1;
			dots_load_details($dot, $viewer_id, $more);
		}

		return $dot;
	}

	#################################################################

	function dots_can_view_dot(&$dot, $viewer_id){

		if ($dot['user_id'] == $viewer_id){
			return 1;
		}

		$perms_map = dots_permissions_map();

		return ($perms_map[$dot['perms']] == 'public') ? 1 : 0;		
	}

	#################################################################

	#
	# I am not (even a little bit) convinced this is a particularly
	# awesome way to do this. But it's a start. For now.
	# (20101026/straup)
	#
	
	function dots_get_dots_recently_imported($to_fetch=15){

		$recent = array();

		$sheet_sql = "SELECT * FROM SheetsLookup WHERE deleted=0 ORDER BY created DESC";
		$sheet_args = array( 'page' => 1 );

		$page_count = null;
		$total_count = null;

		$iters = 0;
		$max_iters = 15;

		while((! isset($page_count)) || ($page_count >= $sheet_args['page'])){

			$sheet_rsp = db_fetch_paginated($sheet_sql, $sheet_args);

			if (! $sheet_rsp['ok']){
				break;
			}

			if (! isset($page_count)){
				$page_count = $sheet_rsp['pagination']['page_count'];
				$total_count = $sheet_rsp['pagination']['total_count'];
			}

			foreach ($sheet_rsp['rows'] as $sheet){

				$enc_sheet = AddSlashes($sheet['sheet_id']);

				$dot_sql = "SELECT * FROM DotsSearch WHERE sheet_id='{$enc_sheet}' AND perms=0 ORDER BY imported DESC";
				$dot_args = array( 'per_page' => 15 );

				$dot_rsp = db_fetch_paginated($dot_sql, $dot_args);

				if (! $dot_rsp['ok']){
					break;
				}

				$default_limit = 3;	# sudo, make me smarter
				$limit = min($default_limit, count($dot_rsp['rows']));

				if ($limit){

					shuffle($dot_rsp['rows']);

					foreach (array_slice($dot_rsp['rows'], 0, $limit) as $row){

						$viewer_id = 0;
						$more = array('load_user' => 1);

						$recent[] = dots_get_dot($row['dot_id'], $viewer_id, $more);
					}

					if (count($recent) == $to_fetch){
						break;
					}
				}
			}

			if (count($recent) == $to_fetch){
				break;
			}

			$sheet_args['page'] ++;
			$iters ++;

			if ($iters == $max_iters){
				break;
			}
		}

		shuffle($recent);
		return $recent;
	}

	#################################################################

	function dots_get_dots_for_sheet(&$sheet, $viewer_id=0, $more=array()){

		$user = users_get_by_id($sheet['user_id']);

		$enc_id = AddSlashes($sheet['id']);

		$sql = "SELECT * FROM Dots WHERE sheet_id='{$enc_id}'";

		if ($viewer_id !== $sheet['user_id']){

			$sql = _dots_where_public_sql($sql);
		}

		$order_by = 'id';
		$order_sort = 'ASC';

		# check $more here for additioning sorting

		if ($more['sort']){
			$order_by = $more['sort'];
		}

		if (strtolower($more['order']) == 'desc'){
			$order_sort = 'DESC';
		}

		$order_by = AddSlashes($order_by);
		$order_sort = AddSlashes($order_sort);

		$sql .= " ORDER BY {$order_by} {$order_sort}";

		$rsp = db_fetch_paginated_users($user['cluster_id'], $sql, $more);

		#

		$dots = array();

		foreach ($rsp['rows'] as $dot){

			dots_load_details($dot, $viewer_id, $more);
			$dots[] = $dot;
		}

		return $dots;
	}

	#################################################################

	function dots_get_dots_for_user(&$user, $viewer_id=0, $more=array()) {

		$enc_id = AddSlashes($user['id']);

		$sql = "SELECT * FROM Dots WHERE user_id='{$enc_id}'";

		if ($viewer_id !== $user['id']){

			$sql = _dots_where_public_sql($sql, 1);
		}

		$order_by = 'id';
		$order_sort = 'DESC';

		# check $args here for additioning sorting

		$order_by = AddSlashes($order_by);
		$order_sort = AddSlashes($order_sort);

		$sql .= " ORDER BY {$order_by} {$order_sort}";

		$rsp = db_fetch_paginated_users($user['cluster_id'], $sql, $more);
		$dots = array();

		$even_more = array(
			'load_sheet' => 1,
		);

		foreach ($rsp['rows'] as $dot){

			dots_load_details($dot, $viewer_id, $even_more);
			$dots[] = $dot;
		}

		return $dots;
	}

	#################################################################

	function dots_count_dots_for_sheet(&$sheet){

		$user = users_get_by_id($sheet['user_id']);
		$enc_id = AddSlashes($sheet['id']);

		$sql = "SELECT COUNT(id) AS count_total FROM Dots WHERE sheet_id='{$enc_id}'";

		$rsp = db_fetch_users($user['cluster_id'], $sql);
		$row = db_single($rsp);

		$count_total = $row['count_total'];

		$sql = "SELECT COUNT(id) AS count_public FROM Dots WHERE sheet_id='{$enc_id}'";

		$sql = _dots_where_public_sql($sql);

		$rsp = db_fetch_users($user['cluster_id'], $sql);
		$row = db_single($rsp);

		$count_public = $row['count_public'];

		return array(
			'total' => $count_total,
			'public' => $count_public,
		);
	}

	#################################################################

	# Note the pass-by-ref

	function dots_load_details(&$dot, $viewer_id=0, $more=array()){

		$index_on = array();

		if ($GLOBALS['cfg']['enable_feature_dots_indexing']){

			if ($dot['index_on']){
				$index_on = explode(",", $dot['index_on']);
			}

			$dot['index_on'] = $index_on;
		}

		# This bit is more than deprecated - I'm just keeping
		# it around while the _dots_indexing stuff is worked
		# out (20101201/straup)

		else {
			$index_on = array( 'location', 'type' );
		}

		#

		$dot['details'] = json_decode($dot['details_json'], 1);

		$geo_bits = array(
			'latitude',
			'longitude',
			'geohash'
		);

		foreach (array_merge($geo_bits, $index_on) as $what){
			$dot[$what] = (isset($dot['details'][$what])) ? $dot['details'][$what][0]['value'] : '';
		}

		$listview = array();

		foreach ($dot['details'] as $label => $ignore){

			if (! isset($dot[$label])){
				$listview[] = $label;
			}
		}
		
		$dot['details_listview'] = implode(", ", $listview);

		#

		if ($more['load_sheet']){

			$sheet_more = array(
				'sheet_user_id' => $dot['user_id'],
			);

	 		$dot['sheet'] = sheets_get_sheet($dot['sheet_id'], $viewer_id, $sheet_more);
		}

		if ($more['load_user']){
			$dot['user'] = users_get_by_id($dot['user_id']);
		}

	}

	#################################################################

	function dots_indexed_on(&$dots){

		$indexed = array();

		foreach ($dots as $dot){
			
			if (! is_array($dot['index_on'])){
				continue;
			}

			foreach ($dot['index_on'] as $i){

				if (! in_array($i, $indexed)){
					$indexed[] = $i;
				}
			}
		}

		return $indexed;
	}

	#################################################################

	function dots_ensure_valid_data(&$data){

		$skip_required_latlon = 0;

		if (isset($data['address']) && ((empty($data['latitude'])) || (empty($data['longitude'])))){

			$skip_required_latlon = 1;

			# It is unclear whether this should really return an
			# error - perhaps it should simply add the dot with
			# NULL lat/lon values and rely on a separate cron job
			# to clean things up with geocoding is re-enabled.
			# (20101023/straup)

			if (! $GLOBALS['cfg']['enable_feature_geocoding']){
				return array( 'ok' => 0, 'error' => 'Geocoding is disabled.' );
			}

			if (strlen(trim($data['address'])) == 0){
				return array( 'ok' => 0, 'error' => 'Address is empty.' );
			}
		}

		else {

			if (! isset($data['latitude'])){
				return array( 'ok' => 0, 'error' => 'Missing latitude.' );
			}

			if (! isset($data['longitude'])){
				return array( 'ok' => 0, 'error' => 'Missing longitude.' );
			}

			if (! geo_utils_is_valid_latitude($data['latitude'])){
				return array( 'ok' => 0, 'error' => "Invalid latitude: '{$data['latitude']}'" );
			}

			if (! geo_utils_is_valid_longitude($data['longitude'])){
				return array( 'ok' => 0, 'error' => "Invalid longitude: '{$data['longitude']}'" );
			}
		}

		return array( 'ok' => 1 );
	}

	#################################################################

	#
	# Do not include any dots that may in the queue
	# waiting to be geocoded, etc.
	#

	function _dots_where_public_sql($sql, $has_where=1){

		$where .= ($has_where) ? "AND" : "WHERE";

		$sql .= " {$where} perms=0";
		return $sql;
	}

	#################################################################
?>
