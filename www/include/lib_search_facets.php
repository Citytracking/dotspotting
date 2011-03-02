<?php

	#
	# $Id$
	#

	#
	# Poor man's faceting in the absence of a proper search engine like
	# Solr. This is designed to work with a) a not-gigantic dataset and
	# b) preferably some kind of caching like Memcached or c) you're not
	# running this on the Public Internet and don't mind waiting a bit
	# for results to come back. Definitely file this one under trying
	# not to prematurely optimize. Let's get the basics working and then
	# come back and tighten it all up. (20101214/straup)
	#

	#################################################################

	function search_facets_by_extras_name($viewer_id=0, $more=array()){

		# global facets are always public
		# so are user scoped unless they're your own

		$cache_key = "facets_extras_name";

		if ($more['user_id']){

			$extra = ($more['user_id'] == $viewer_id) ? $more['user_id'] : "{$more['user_id']}_public";

			$cache_key .= "_{$extra}";
		}

		# cache sets are also disabled (below)
		# $cache = cache_get($cache_key);

		$cache = array( 'ok' => 0 );

		#

		if ($cache['ok']){
			$rsp = $cache['data'];
		}

		else {

			$sql = "SELECT e.name, COUNT(DISTINCT(e.value)) AS count_values, COUNT(d.dot_id) AS count_dots";
			$sql .= " FROM DotsSearch d, DotsSearchExtras e WHERE 1"; 

			$sql .= " AND d.dot_id=e.dot_id";

			if ($more['user_id']){
				$enc_user = AddSlashes($more['user_id']);
				$sql .= " AND d.user_id='{$enc_user}'";
			}

			# See those perms? That makes caching hard unless
			# we only facet on public things...

			if ($perms = _search_facets_perms($viewer_id, $more)){
				$sql .= " AND {$perms}";
			}

			$sql .= " GROUP BY e.name";

			$rsp = db_fetch($sql, $more);

			# We sort in memory because ORDER-ing by 'count_values' in MySQL
			# will always cause a filesort (because we're already grouping on
			# another column)

			function cmp($a, $b){
				if ($a['count_values'] == $b['count_values']) {
					return 0;
				}

				return ($a['count_values'] > $b['count_values']) ? -1 : 1;
			}

			usort($rsp['rows'], 'cmp');

			# cache_set($cache_key, $rsp);
		}

		#

		_search_facets_paginate($rsp, $more);
		return $rsp;
	}

	#################################################################

	function search_facets_extras_values_by_name($name, $viewer_id=0, $more=array()){

		$enc_name = AddSlashes($name);

		$sql = "SELECT e.value, COUNT(d.dot_id) AS count_dots, COUNT(DISTINCT(d.sheet_id)) AS count_sheets";
		$sql .= " FROM DotsSearch d, DotsSearchExtras e WHERE 1";

		$sql .= " AND d.dot_id=e.dot_id";
		$sql .= " AND e.name='{$enc_name}'";

		if ($more['user_id']){
			$enc_user = AddSlashes($more['user_id']);
			$sql .= " AND d.user_id='{$enc_user}'";
		}

		# See those perms? That makes caching hard unless
		# we only facet on public things...

		if ($perms = _search_facets_perms($viewer_id, $more)){
			$sql .= " AND {$perms}";
		}

		$sql .= " GROUP BY e.value";

		$rsp = db_fetch($sql, $more);

		# We sort in memory because ORDER-ing by 'count_sheets' in MySQL
		# will always cause a filesort (because we're already grouping on
		# another column)

		function cmp($a, $b){
			if ($a['count_sheets'] == $b['count_sheets']) {
				return 0;
			}

			return ($a['count_sheets'] > $b['count_sheets']) ? -1 : 1;
		}

		usort($rsp['rows'], 'cmp');

		#

		_search_facets_paginate($rsp, $more);
		return $rsp;
	}

	#################################################################

	function _search_facets_perms($viewer_id, $more=array()){

		$perms = "d.perms=0";

		if ($viewer_id){

			if (($more['user_id']) && ($more['user_id'] == $viewer_id)){
				$perms = '';
			}

			else {
				$enc_id = AddSlashes($viewer_id);
				$perms = "(d.perms=0 OR d.user_id='{$enc_id}')";
			}
		}

		return $perms;
	}

	#################################################################

	function _search_facets_paginate(&$rsp, $args){

		$page = isset($args['page']) ? max(1, $args['page']) : 1;
		$per_page = isset($args['per_page']) ? max(1, $args['per_page']) : $GLOBALS['cfg']['pagination_per_page'];

		$total_count = count($rsp['rows']);
		$page_count = ceil($total_count / $per_page);

		$pagination = array(
			'total_count' => $total_count,
			'page' => $page,
			'per_page' => $per_page,
			'page_count' => $page_count,
		);		

		if ($total_count > $per_page){

			$offset = ($page - 1) * $per_page;
			$length = $per_page;

			$rows = array_slice($rsp['rows'], $offset, $length);
			$rsp['rows'] = $rows;
		}

		$rsp['pagination'] = $pagination;

		# Note the pass-by-ref
	}

	#################################################################
?>