<?php

	#
	# $Id$
	#

	# This file has been copied from the Citytracking fork of flamework.
	# It has not been forked, or cloned or otherwise jiggery-poked, but
	# copied: https://github.com/Citytracking/flamework (20101208/straup)

	#################################################################

	$GLOBALS['crypto_td'] = MCRYPT_RIJNDAEL_256;

	#################################################################

	function crypto_encrypt($data, $key){

		$enc = mcrypt_encrypt($GLOBALS['crypto_td'], $key, $data, MCRYPT_MODE_ECB);
		return base64_encode($enc);
	}

	#################################################################

	function crypto_decrypt($enc_b64, $key){

		$enc = base64_decode($enc_b64);
		$dec = mcrypt_decrypt($GLOBALS['crypto_td'], $key, $enc, MCRYPT_MODE_ECB);

		return trim($dec);
	}

	#################################################################

?>
