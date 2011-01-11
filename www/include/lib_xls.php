<?php

	#
	# $Id$
	#

	#################################################################

	# https://code.google.com/p/php-excel-reader/wiki/Documentation

	loadpear("Spreadsheet/Excel/Reader");
	loadpear("Spreadsheet/Excel/Writer");

	#################################################################

	function xls_parse_fh($fh, $more=array()){

		fclose($fh);

		$xls = new Spreadsheet_Excel_Reader($more['file']['path']);

		$rows = $xls->rowcount(0);
		$cols = $xls->colcount(0);

		$fields = array();

		for ($i=1; $i < $cols; $i++){
			$raw = $xls->val(1, $i);
			$fields[] = strtolower($raw);
		}

		# ensure lat, lon here

		foreach (array() as $what){

			if (! in_array($what, $fields)){

				$errors[] = array(
					'record' => 0,
					'error' => "missing {$what} column",
				);

				continue;
			}
		}

		$data = array();
		$errors = array();
		$record = 1;

		for ($i=2; $i < $rows; $i++){

			$record ++;

			if (($more['max_records']) && ($record > $more['max_records'])){
				break;
			}

			$tmp = array();

			for ($j=1; $j < $cols; $j++){

				$label = $fields[$j-1];
				$value = $xls->val($i, $j);

				# type always seems to come back empty
				# (20110110/straup)
				# $type = $xls->type($i, $j);

				if ($label == 'latitude'){

					if (! geo_utils_is_valid_latitude($value)){
						$errors[] = array( 'record' => $record, 'error' => 'invalid latitude' );
						continue;
					}
				}

				if ($label == 'longitude'){

					if (! geo_utils_is_valid_longitude($value)){
						$errors[] = array( 'record' => $record, 'error' => 'invalid longitude' );
						continue;
					}
				}

				# TO DO : dates and times (they seem to be always be weird)

				$tmp[$label] = import_scrub($value);
			}

			$data[] = $tmp;
		}

		return array(
			'ok' => 1,
			'data' => &$data,
			'errors' => &$errors,
		);
	}

	#################################################################

	function xls_export_dots(&$rows, $fh){

		$xls = new Spreadsheet_Excel_Writer();
		$sheet = $xls->addWorksheet();

		$col_names = array_keys($rows[0]);
		$count = count($col_names);

		for ($colnum=0; $colnum < $count; $colnum++){
			$sheet->write(0, $colnum, $col_names[$colnum]);
		}

		$rownum = 1;

		foreach ($rows as $row){

			$values = array_values($row);
			$count = count($values);

			for ($colnum=0; $colnum < $count; $colnum++){
				$sheet->write($rownum, $colnum, $values[$colnum]);
			}

			$rownum ++;
		}

		$xls->close();
	}

	#################################################################
?>