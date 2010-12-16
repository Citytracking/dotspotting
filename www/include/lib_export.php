<?php

	#
	# $Id$
	#

	# Question: how to deal with caching (if at all) ?

	#################################################################

	#
	# These are columns to explicitly remove from the export data.
	# It's not an awesome way to do things on the other hand I'm
	# not sure there's anything like a "right way" to deal with this
	# outside of replacing the bad smell (below) with a boat-load
	# of painful twisty code. I will gladly be proven wrong...
	# (20101111/straup)
	# 

	$GLOBALS['export_ignore_columns'] = array(
		'details_json',
		'details_listview',
		'index_on',

		# for export from search - probably not the
		# best way to do things (20101122/straup)

		'user',
		'sheet',
	);

	#################################################################

	# It is assumed that you've validated $format by now

	function export_dots(&$rows, $format, $fh=null){

		if (! $fh){		 
			$fh = fopen("php://output", 'w');
		}

		$keys = array_keys($rows[0]);
		$details = array();

		if (in_array('details', $keys)){
			$details = array_keys($rows[0]['details']);
		}

		$count_rows = count($rows);

		for ($i = 0; $i < $count_rows; $i++){

			$row = $rows[$i];

			# See above.

			foreach ($GLOBALS['export_ignore_columns'] as $key){

				if (isset($row[$key])){
					unset($row[$key]);
				}
			}

			foreach ($details as $k){

				# assume that the data in Dots trumps all

				if ($row[$k]){
					continue;
				}

				if (isset($row['details'][$k])){

					$values = array();

					foreach ($row['details'][$k] as $e){
						$values[] = $e['value'];
					}

					$row[$k] = implode(",", $values);
				}	
			}

			unset($row['details']);

			if (isset($row['perms'])){
				$map = dots_permissions_map();
				$row['perms'] = $map[$row['perms']];
			}

			$timestamps = array(
				'imported',
				'last_modified',
			);

			foreach ($timestamps as $ts){

				if (isset($row[$ts])){
					$row[$ts] = gmdate('Y-m-d\TH:m:s e', $row[$ts]);
				}
			}

			$to_prefix = array(
				'id',
				'sheet_id',
				'user_id',
				'perms',
				'geohash',
			);

			foreach ($to_prefix as $col){

				if (! array_key_exists($col, $row)){
					continue;
				}

				$row["dotspotting:{$col}"] = $row[$col];
				unset($row[$col]);
			}

			$rows[$i] = $row;
		}

		loadlib($format);
		call_user_func_array("{$format}_export_dots", array(&$rows, $fh));
	}

	#################################################################

?>