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

				$val = $xls->val($i, $j);
				$type = $xls->type($i, $j);

				# type always seems to come back empty
				# (20110110/straup)

				# check lat,lon here

				$tmp[$fields[$j-1]] = import_scrub($val);
			}

			$data[] = $tmp;
		}

dumper($data);
exit;

		return array(
			'ok' => 1,
			'data' => &$data,
			'errors' => &$errors,
		);
	}

	#################################################################
?>