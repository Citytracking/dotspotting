<?php

	#
	# $Id$
	#

	#################################################################

	function csv_parse_file($path, $field_names=null){

		$fh = fopen($path, 'r');

		if (! $fh){
			return array(
				'ok' => 0,
				'error' => 'failed to open file'
			);
		}

		$keys = array();
		$data = array();

		$ln = 1;

		while ($row = fgetcsv($fh)){

			if (! $row){
				continue;
			}

			if (($ln === 1) && (! $field_names)){
				$field_names = $row;
				continue;
			}

			$tmp = array();

			for ($i = 0; $i < count($field_names); $i++){
				$tmp[ $field_names[$i] ] = $row[$i];
			}

			$data[] = $tmp;
			$ln ++;

			if (uploads_exceeds_max_records($ln)){
				break;
			}
		}

		fclose($fh);

		return array( 'ok' => 1, 'data' => &$data );
	}

	#################################################################

	function csv_export_dots(&$rows, $fh){

		fputcsv($fh, array_keys($rows[0]));

		foreach ($rows as $row){
			fputcsv($fh, array_values($row));
		}
	}

	#################################################################
?>