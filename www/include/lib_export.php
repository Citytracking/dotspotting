<?php

	#
	# $Id$
	#

	# Question: how to deal with caching (if at all) ?

	#################################################################

	# It is assumed that you've validated $format by now

	function export_dots(&$rows, $format, $fh=null){

		if (! $fh){		 
			$fh = fopen("php://output", 'w');
		}

		$keys = array_keys($rows[0]);
		$extras = array();

		if (in_array('extras', $keys)){
			$extras = array_keys($rows[0]['extras']);
		}

		$count_rows = count($rows);

		for ($i = 0; $i < $count_rows; $i++){

			$row = $rows[$i];

			foreach ($extras as $k){
				if (isset($row['extras'][$k])){
					$row[$k] = implode(",", $row['extras'][$k]);
				}	
			}

			unset($row['extras']);

			if (isset($row['perms'])){
				$map = dots_permissions_map();
				$row['perms'] = $map[$row['perms']];
			}

			if (isset($row['geocoded_by'])){
				$map = geo_geocode_service_map();
				$row['geocoded_by'] = ($row['geocoded_by']) ? $map[$row['geocoded_by']] : '';
			}

			$timestamps = array(
				'created',
				'imported',
				'last_modified',
			);

			foreach ($timestamps as $ts){

				if (isset($row[$ts])){
					$row[$ts] = gmdate('Y-m-d\TH:m:s e', $row[$ts]);
				}
			}

			$rows[$i] = $row;
		}

		loadlib($format);
		call_user_func_array("{$format}_export_dots", array(&$rows, $fh));
	}

	#################################################################

?>