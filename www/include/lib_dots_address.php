<?php

	#################################################################

	# where $dot really just means a hash with fields that we'll
	# investigate to build a string (20110609/straup)

	function dots_address_parse_for_geocoding(&$dot){

		# these are the things we care about and will
		# try to find corresponding matches for in $dot

		$addr = array(
			'street' => array(),
			'city' => array(),
			'state' => array(),
			'country' => array(),
			'zip' => array(),
		);

		foreach ($dot as $k => $v){

			if (trim($v) == ''){
				continue;
			}

			# first, normalize everything so as to minimize
			# the desire to stab ourselves in the face...

			$k = str_replace(" ", "", $k);
			$k = str_replace("_", "", $k);
			$k = strtolower($k);

			# make sure this is always placed in front of
			# anything else that might be classified as a
			# street (like 'address' below)

			if (preg_match("/^street/", $k)){
				array_unshift($addr['street'], $v);
				continue;
			}

			if ($k == 'address'){
				$addr['street'][] = $v;
				continue;
			}

			if (preg_match("/^(?:city|town)/", $k)){
				$addr['city'][] = $v;
				continue;
			}

			if ($k == 'borough'){
				$addr['city'][] = $v;
				continue;
			}

			if ($k == 'state'){
				$addr['state'][] = $v;
				continue;
			}

			if ($k == 'country'){
				$addr['country'][] = $v;
				continue;
			}

			if (preg_match("/^(?:zip|postal)/", $k)){
				$addr['zip'][] = $v;
				continue;
			}
		}

		# okay, now iterate over the stuff we've found
		# and return a comma-separated string

		$rsp = array();

		foreach ($addr as $k => $v){

			if (! count($addr[$k])){
				continue;
			}

			$rsp[] = implode(" ", $v);
		}
		
		return implode(", ", $rsp);
	}

	#################################################################
?>
