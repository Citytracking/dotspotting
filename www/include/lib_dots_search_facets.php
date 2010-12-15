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

	function dots_search_facets_by_name($viewer_id=0, $more=array()){

		# See those perms? That makes caching hard unless
		# we only facet on public things...

		$perms = "d.perms=0";

		if ($viewer_id){
			$enc_id = AddSlashes($viewer_id);
			$perms = "(d.perms=0 OR d.user_id='{$enc_id}')";
		}

		$sql = "SELECT e.name, COUNT(DISTINCT(e.value)) AS count_values, COUNT(d.dot_id) AS count_dots";
		$sql .= " FROM DotsSearch d, DotsSearchExtras e";
		$sql .= " WHERE d.dot_id=e.dot_id AND {$perms}";
		$sql .= " GROUP BY e.name";

		$rsp = db_fetch($sql, $more);

		# TODO: sort in memory

		return $rsp;
	}

	#################################################################

	function dots_search_facets_values_by_name($name, $viewer_id=0, $more=array()){

		# See those perms? That makes caching hard unless
		# we only facet on public things...

		$perms = "d.perms=0";

		if ($viewer_id){
			$enc_id = AddSlashes($viewer_id);
			$perms = "(d.perms=0 OR d.user_id='{$enc_id}')";
		}

		$enc_name = AddSlashes($name);

		$sql = "SELECT e.value, COUNT(d.dot_id) AS count_dots, COUNT(DISTINCT(d.sheet_id)) AS count_sheets";
		$sql .= " FROM DotsSearch d, DotsSearchExtras e";
		$sql .= " WHERE d.dot_id=e.dot_id AND e.name='{$name}' AND {$perms}";
		$sql .= " GROUP BY e.value";

		$rsp = db_fetch($sql, $more);

		# TODO: sort in memory

		return $rsp;
	}

	#################################################################

?>