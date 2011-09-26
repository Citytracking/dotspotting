<?php

	#
	# $Id$
	#

	#################################################################

	function csv_parse_fh($fh, $more=array()){

		$keys = array();
		$data = array();

		$checked_fieldnames = 0;	# see below

		$field_names = (is_array($more['field_names'])) ? $more['field_names'] : null;

		$errors = array();
		$record = 0;

		while (! feof($fh)){

			$record ++;

			if (($more['max_records']) && ($record > $more['max_records'])){
				break;
			}

			$ln = trim(fgets($fh));

			if (! $ln){
				continue;
			}

			if (preg_match("/^#/", $ln)){
				continue;
			}

			$row = str_getcsv($ln);
			

			if (! $row){
				continue;
			}
			
			// lets do away with any row, whose combined string length is empty (seanc | 20110926)
			$row2str = implode("",$row);
			if(strlen($row2str) < 1){
			    continue;
			}

			if (($record === 1) && (! $field_names)){

				$has_latitude = (in_array('latitude', $row)) ? 1 : 0;
				$has_longitude = (in_array('longitude', $row)) ? 1 : 0;

				$possible_lat = $GLOBALS['cfg']['import_fields_mightbe_latitude'];
				$possible_lon = $GLOBALS['cfg']['import_fields_mightbe_longitude'];

				foreach ($row as $col){

					$col = strtolower($col);
					
					// do away with columns that are empty  (seanc | 20110926)
					if(strlen(trim($col))<1)continue;
	

					if ((! $has_latitude) && (in_array($col, $possible_lat))){
						$col = 'latitude';
					}

					if ((! $has_longitude) && (in_array($col, $possible_lon))){
						$col = 'longitude';
					}

					$field_names[] = $col;
				}

				continue;
			}

			#
			# Okay, first check to make sure that we have some kind
			# of remotely sane input as column names - if we don't
			# even have that then there's not much point in going any
			# further.
			#

			if (! $checked_fieldnames){

				for ($i = 0; $i < count($field_names); $i++){

					$raw = $field_names[$i];
					$clean = sanitize($raw, 'str');

					if (! $clean){

						return array(
							'ok' => 0,
							'error' => "invalid column name",
							'column' => $raw,
						);
					}

					$field_names[$i] = $clean;
				}

				$checked_fieldnames = 1;
			}

			#
			# Okay, go!
			#

			$tmp = array();

			for ($i = 0; $i < count($field_names); $i++){

				$raw = trim($row[$i]);
				$clean = sanitize($raw, 'str');

				$tmp[ $field_names[$i] ] = $clean;

				if (($raw) && (! $clean)){

					$errors[] = array(
						'record' => $record,
						'error' => "invalid input",
						'column' => $field_names[$i],
					);
				}
			}

			# ensure valid latitude/longitude

			if ((isset($tmp['latitude'])) && (! geo_utils_is_valid_latitude($tmp['latitude']))){
				$errors[] = array(
					'record' => $record,
					'error' => 'invalid latitude',
					'column' => 'latitude',
				);
			}

			if ((isset($tmp['longitude'])) && (! geo_utils_is_valid_longitude($tmp['longitude']))){
				$errors[] = array(
					'record' => $record,
					'error' => 'invalid longitude',
					'column' => 'longitude',
				);
			}

			else {}

			# done...

			$data[] = $tmp;
		}

		fclose($fh);

		return array(
			'ok' => 1,
			'data' => &$data,
			'errors' => &$errors,
		);
	}

	#################################################################

	function csv_export_dots(&$rows, $more=array()){

		$fh = fopen($more['path'], 'w');

		fputcsv($fh, $more['columns']);

		foreach ($rows as $row){
			fputcsv($fh, array_values($row));
		}

		fclose($fh);
		return $more['path'];
	}

	#################################################################
?>
