<?php

	#
	# $Id$
	#

	#################################################################

	function csv_parse_file($path, $more=array()){

		$fh = fopen($path, 'r');

		if (! $fh){
			return array(
				'ok' => 0,
				'error' => 'failed to open file'
			);
		}

		$keys = array();
		$data = array();

		$field_names = (is_array($more['field_names'])) ? $more['field_names'] : null;

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

			if (($more['max_records']) && ($ln >= $more['max_records'])){
				break;
			}

			$ln ++;
		}

		fclose($fh);

		return array(
			'ok' => 1,
			'data' => &$data
		);
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