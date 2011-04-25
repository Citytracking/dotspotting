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

	# This generates a file of type $format for $rows. Also, it is
	# assumed that you've validated $format by the time you get here.

	function export_dots(&$rows, $format, $more=array()){

		# TO DO: figure out how caching should work
		# here (20110127/straup)

		if (! isset($more['path'])){

			$tmp = tempnam(sys_get_temp_dir(), "export-{$format}") . ".{$format}";
			$more['path'] = $tmp;
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
		$exported_file = call_user_func_array("{$format}_export_dots", array(&$rows, &$more));

		return $exported_file;
	}

	#################################################################

	function export_send_file($path, $more=array()){

		$defaults = array(
			'unlink_file' => 1,
			'x-headers' => array(),
		);

		$more = array_merge($defaults, $more);

		# basic headers

		if (preg_match("/^image/", $more['mimetype'])){
			header("Content-Type: " . htmlspecialchars($more['mimetype']));
		}

		else if (! $more['inline']){
			header("Content-Type: " . htmlspecialchars($more['mimetype']));
			header("Content-Disposition: attachment; filename=\"{$more['filename']}\"");
		}

		else { }

		$fsize = filesize($path);
		header("Content-Length: {$fsize}");

		# CORS (http://www.w3.org/TR/cors/)

		if (($more['mimetype'] == 'application/x-javascript') && ($GLOBALS['cfg']['enable_feature_cors'])){
			header("Access-Control-Allow-Origin: *");
		}

		# x headers

		foreach ($more['x-headers'] as $k => $v){

			$v = trim($v);
			$k = trim($k);

			$header = htmlspecialchars("X-Dotspotting-{$k}");
			$value = htmlspecialchars($v);

			header("{$header}: {$value}");
		}

		# go!

		$fh = fopen($path, 'r');
		echo fread($fh, $fsize);
		fclose($fh);

		# clean up ?

		if ($more['unlink_file']){
			unlink($path);
		}
	}

	#################################################################

	function export_collect_user_properties($format){

		$props = array();

		if (! isset($GLOBALS['cfg']['export_valid_extras'][$format])){
			return $props;
		}

		$valid_extras = $GLOBALS['cfg']['export_valid_extras'][$format];

		foreach ($valid_extras as $extra => $details){

			$what = get_str($extra);

			if (! $what){
				continue;
			}

			if ((is_array($details)) && (! in_array($what, $details))){
				continue;
			}

			$props[$extra] = $what;
		}

		return $props;
	}

	#################################################################
?>
