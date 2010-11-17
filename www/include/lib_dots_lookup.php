<?php

	#
	# $Id$
	#

	#################################################################

	function dots_lookup_create(&$lookup){

		$hash = array();

		foreach ($lookup as $key => $value){
			$hash[$key] = AddSlashes($value);
		}

		return db_insert('DotsLookup', $hash);
	}

	#################################################################

?>