<?php

	#
	# $Id$
	#

	#
	# Poor man's faceting in the absence of a proper search engine like
	# Solr. This is designed to work with a) a not-gigantic dataset and
	# b) preferably some kind of caching like Memcached. The hard part
	# is the roll-up which can't really be done without filesorts in
	# MySQL because the columns being GROUP BY -ed and ORDER BY -ed are
	# always different and the index gods cry. So for now, we'll do this.
	# (20101214/straup)
	#

	#################################################################

	function dots_search_facets_by_name(){

		$cache_key = "facets_by_name";
		$cache = cache_get($cache_key);

		if ($cache['ok']){
			$rollup = $cache['data'];
		}

		else {

			$rollup = array();

			$sql = "SELECT * FROM DotsSearchFacets WHERE count > 0";
			$rsp = db_fetch($sql);

			foreach ($rsp['rows'] as $row){

				$name = $row['name'];

				if (! isset($rollup[$name])){

					$rollup[$name] = array(
						'count_dots' => 0,
						'count_values' => 0,
						'values' => array(),
					);
				}

				$rollup[$name]['count_dots'] += $row['count'];
				$rollup[$name]['count_values'] += 1;
				$rollup[$name]['values'][$row['value']] = $row['count'];
			}

			# TO DO: sort me

			cache_set($cache_key, $rollup);
		}

		# array_slice me ?

		return $rollup;
	}

	#################################################################

	function dots_search_facets_add($name, $value, $count=1){

		if ((! $name) || (! $value)){
			return array( 'ok' => 0 );
		}

		$enc_name = AddSlashes($name);
		$enc_value = AddSlashes($value);
		$enc_count = AddSlashes($count);

		$sql = "INSERT INTO DotsSearchFacets (name, value, count) VALUES('{$enc_name}', '{$enc_value}', '{$enc_count}')";
		$sql .= " ON DUPLICATE KEY UPDATE count=count+{$enc_count}";

		$rsp = db_write($sql);

		if ($rsp['ok']){
			cache_unset("facets_by_name");
		}

		return $rsp;
	}

	#################################################################

	function dots_search_facets_remove($name, $value, $count=1){

		if ((! $name) || (! $value)){
			return array( 'ok' => 0 );
		}

		# http://dev.mysql.com/doc/refman/5.1/en/out-of-range-and-overflow.html
		# http://dev.mysql.com/doc/refman/5.1/en/server-sql-mode.html#sqlmode_no_unsigned_subtraction

		db_write("SET sql_mode='NO_UNSIGNED_SUBTRACTION'");

		$enc_name = AddSlashes($name);
		$enc_value = AddSlashes($value);
		$enc_count = AddSlashes($count);

		$sql = "UPDATE DotsSearchFacets SET count=count-{$enc_count} WHERE name='{$enc_name}' AND value='{$enc_value}'";
		$rsp = db_write($sql);

		if ($rsp['ok']){
			cache_unset("facets_by_name");
			db_write("DELETE FROM DotsSearchFacets WHERE count=0");
		}

		return $rsp;
	}

	#################################################################

?>