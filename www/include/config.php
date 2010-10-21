<?php

	# We assume this is declared in flamework/include/config.php
	# $GLOBALS['cfg'] = array();

	$GLOBALS['cfg']['dotspotting_version'] = '0.0.0';	# see also: http://semver.org/

	$GLOBALS['cfg']['flamework_skip_init_config'] = 1;

	# Database stuff

	$GLOBALS['cfg']['db_main'] = array(
		'host'	=> 'READ-FROM-SECRETS',
		'user'	=> 'READ-FROM-SECRETS',
		'pass'	=> 'READ-FROM-SECRETS',
		'name'	=> 'dotspotting',
		'auto_connect' => 1,
	);

	$GLOBALS['cfg']['db_enable_poormans_slaves'] = 1;

	$GLOBALS['cfg']['db_enable_poormans_ticketing'] = 1;
	$GLOBALS['cfg']['db_enable_poormans_federation'] = 1;

	# Templates

	$GLOBALS['cfg']['smarty_template_dir'] = DOTSPOTTING_WWW_DIR . '/templates';
	$GLOBALS['cfg']['smarty_compile_dir'] = DOTSPOTTING_WWW_DIR . '/templates_c';
	$GLOBALS['cfg']['smarty_compile'] = 1;

	# App specific stuff

	$GLOBALS['cfg']['abs_root_url']		= 'READ-FROM-SECRETS';
	$GLOBALS['cfg']['safe_abs_root_url']	= $GLOBALS['cfg']['abs_root_url'];

	$GLOBALS['cfg']['auth_cookie_domain'] = parse_url($GLOBALS['cfg']['abs_root_url'], 1);
	$GLOBALS['cfg']['auth_cookie_name'] = 'a';

	$GLOBALS['cfg']['crypto_cookie_secret'] = 'READ-FROM-SECRETS';
	$GLOBALS['cfg']['crypto_password_secret'] = 'READ-FROM-SECRETS';
	
	$GLOBALS['cfg']['geo_geocode']['service'] = 'yahoo';

?>