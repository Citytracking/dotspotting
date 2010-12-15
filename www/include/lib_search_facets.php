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

		$sql = "SELECT e.name, COUNT(DISTINCT(e.value)) AS count_values, COUNT(d.dot_id) AS count_dots";
		$sql .= " FROM DotsSearch d, DotsSearchExtras e";
		$sql .= " WHERE d.dot_id=e.dot_id";

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

		# TODO: sort in memory

		return $rsp;
	}

	#################################################################

	function search_facets_extras_values_by_name($name, $viewer_id=0, $more=array()){

		$enc_name = AddSlashes($name);

		$sql = "SELECT e.value, COUNT(d.dot_id) AS count_dots, COUNT(DISTINCT(d.sheet_id)) AS count_sheets";
		$sql .= " FROM DotsSearch d, DotsSearchExtras e";
		$sql .= " WHERE d.dot_id=e.dot_id";

		if ($more['user_id']){
			$enc_user = AddSlashes($more['user_id']);
			$sql .= " AND d.user_id='{$enc_user}'";
		}

		$sql .= " AND e.name='{$enc_name}'";

		# See those perms? That makes caching hard unless
		# we only facet on public things...

		if ($perms = _search_facets_perms($viewer_id, $more)){
			$sql .= " AND {$perms}";
		}

		$sql .= " GROUP BY e.value";

		$rsp = db_fetch($sql, $more);

		# TODO: sort in memory

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
?>