<?php

	#
	# $Id$
	#

	#################################################################

	function formats_valid_import_map($key_by_extension=0){

		$map = array(
			'text/csv' => 'csv',
		);

		if ($key_by_extension){
			$map = array_flip($map);
		}

		return $map;
	}

	#################################################################

	function formats_valid_export_map($key_by_extension=0){

		$map = array(
			'text/csv' => 'csv',
		);

		if ($key_by_extension){
			$map = array_flip($map);
		}

		return $map;
	}

	#################################################################
?>