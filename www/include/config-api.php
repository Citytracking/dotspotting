<?php

	#
	# THIS IS SO NOT EVEN CLOSE TO BE DONE YET.
	# (20101120/straup)
	#

	$GLOBALS['cfg']['api']['methods'] = array(

		'dotspotting.dots.dotsForUser' => array(
			'documented' => 1,
			'enabled' => 1,
			'library' => 'api_dots',
		),
	);
	
	$GLOBALS['cfg']['api']['formats'] = array(
	   'valid'=>array(
	       'json'.
	       'jsop'
	   ),
	   'default'=>'json'
	);

?>