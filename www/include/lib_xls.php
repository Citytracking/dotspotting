<?php

	#
	# $Id$
	#

	#################################################################

	# https://code.google.com/p/php-excel-reader/wiki/Documentation

	loadpear("Spreadsheet/Excel/Reader");

	#################################################################

	function xls_parse_fh($fh, $more=array()){

		fclose($fh);

		$xls = new Spreadsheet_Excel_Reader($more['file']['path']);

		$rows = $xls->rowcount(0);
		$cols = $xls->colcount(0);

		$data = array();
		$fields = array();

		for ($i=1; $i < $cols; $i++){
			$raw = $xls->val(1, $i);
			$fields[] = $raw;
		}

		for ($i=2; $i < $rows; $i++){

			$tmp = array();

			for ($j=1; $j < $cols; $j++){

				$tmp[$fields[$j-1]] = $xls->val($i, $j);
			}

			$data[] = $tmp;
		}

	}

	#################################################################
?>