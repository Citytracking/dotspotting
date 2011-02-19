<?php

	#
	# $Id$
	#

	#################################################################

	function xls_parse_fh($fh, $more=array()){

		loadpear("Spreadsheet/Excel/Reader");

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
					'error' => "missing required column",
					'column' => $what,
				);

				continue;
			}
		}

		$data = array();
		$errors = array();
		$record = 0;

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

						$errors[] = array(
							'record' => $record,
							'error' => 'invalid latitude',
							'column' => 'latitude',
						);

						continue;
					}
				}

				if ($label == 'longitude'){

					if (! geo_utils_is_valid_longitude($value)){

						$errors[] = array(
							'record' => $record,
							'error' => 'invalid longitude',
							'column' => 'longitude',
						);

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

	function xls_export_dots(&$rows, $more){

		loadpear("PHPExcel");

		$xls = new PHPExcel();

		$sheet = $xls->setActiveSheetIndex(0);

		$col_names = array_keys($rows[0]);

		$row = 1;
		$col = 0;

		foreach ($col_names as $c){
			$sheet->setCellValueByColumnAndRow($col, $row, $c);
			$col ++;
		}

		$row = 2;
		$col = 0;

		foreach ($rows as $_row){

			foreach (array_values($_row) as $value){
				$sheet->setCellValueByColumnAndRow($col, $row, $value);
				$col++;
			}

			$row ++;
			$col = 0;
		}

		# Excel 2007 is just plain weird and confuses
		# both OpenOffice and Numbers.app
		# (20110201/straup)

		$writer = PHPExcel_IOFactory::createWriter($xls, 'Excel5');
		$writer->save($more['path']);

		return $more['path'];
	}

	#################################################################
?>