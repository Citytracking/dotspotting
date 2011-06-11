<?php

	#################################################################

	# where $dot really just means a hash with fields that we'll
	# investigate to build a string (20110609/straup)

	function dots_address_parse_for_geocoding(&$dot){

		# these are the things we care about and will
		# try to find corresponding matches for in $dot

		$addr = array(
			'street' => '',
			'city' => '',
			'state' => '',
			'country' => '',
			'zip' => '',
		);

		# first just lower-case everything so as to
		# minimize the desire to stab ourselves in the
		# face.

		$tmp = array();

		foreach ($dot as $k => $v){
			$tmp[ strtolower($k) ] = $v;
		}

		# do stuff here

		# okay, now iterate over the stuff we've found
		# and return a comma-separated string

		$rsp = array();

		foreach ($addr as $k => $v){

			if (! $addr[$k]){
				continue;
			}

			$rsp[] = $v;
		}

		return implode(", ", $rsp);
	}

	#################################################################
?>
