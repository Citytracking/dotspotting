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

	function export_dots(&$rows, $format, $more=array()){

		if (! isset($more['fh'])){
			$more['fh'] = fopen("php://output", 'w');
		}

		# Are you ready? This gets hairy very fast because we
		# need to not only unpack the "details" and the "index on"
		# properties for a sheet full of dots but if export for
		# search then we also need to reconcile columns across
		# all the rows. This is probably not the best way to do
		# it but a) it works and b) everything is still in flux
		# (20110114/straup)

		$keys = array();
		$details = array();

		if (! $GLOBALS['cfg']['enable_feature_search_export']){

			$_keys = array_keys($rows[0]);
		
			foreach ($_keys as $k){

				if (in_array($key, $GLOBALS['export_ignore_columns'])){
					continue;
				}
			}
	
			if (isset($rows[0]['details'])){
				$details = array_keys($rows[0]['details']);
			}
		}

		# Okay, so we've enabled export for search. Which means we're
		# working with a big bag of who knows what. So we need to iterate
		# over the list and get a list of relevant keys. (20110114/straup)

		else {

			$seen = array();

			foreach ($rows as $row){

				$_keys = array_keys($row);

				foreach ($_keys as $k){

					if (in_array($k, $keys)){
						continue;
					}

					if (in_array($k, $GLOBALS['export_ignore_columns'])){
						continue;
					}

					$keys[] = $k;
				}

				if (isset($row['details'])){

					foreach (array_keys($row['details']) as $k){

						if (! in_array($k, $details)){
							$details[] = $k;
						}						
					}
				}

				# sigh...
			}
		}

		#

		$count_rows = count($rows);

		for ($i = 0; $i < $count_rows; $i++){

			$row = $rows[$i];

			# see above...

			if ($GLOBALS['cfg']['enable_feature_search_facets']){

				foreach ($keys as $k){

					if (! isset($row[$k])){
						$row[$k] = '';
					}
				}
			}

			foreach ($GLOBALS['export_ignore_columns'] as $key){

				if (isset($row[$key])){
					unset($row[$key]);
				}
			}

			# okay... first let's pull out all the 'details'

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

				else {
					$row[$k] = '';
				}
			}

			unset($row['details']);

			# next, make perms and timestamps pretty

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

			$rows[$i] = $row;
		}

		# this sucks...

		$cols = array();

		foreach (array_merge($keys, $details) as $k){

			if ($k == 'details'){
				continue;
			}

			if (in_array($k, $cols)){
				continue;
			}

			$cols[] = $k;
		}

		# carry on

		$more['columns'] = $cols;

		loadlib($format);
		call_user_func_array("{$format}_export_dots", array(&$rows, &$more));
	}

	#################################################################

?>