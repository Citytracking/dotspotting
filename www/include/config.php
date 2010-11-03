<?php

	#
	# We assume this is declared in flamework/include/config.php
	# $GLOBALS['cfg'] = array();
	#

	$GLOBALS['cfg']['dotspotting_version'] = '0.0.0';	# see also: http://semver.org/
	$GLOBALS['cfg']['flamework_skip_init_config'] = 1;

	#
	# Feature flags
	# See also: http://code.flickr.com/blog/2009/12/02/flipping-out/
	#

	$GLOBALS['cfg']['enable_feature_uploads'] = 1;

	$GLOBALS['cfg']['enable_feature_signup'] = 1;
	$GLOBALS['cfg']['enable_feature_signin'] = 1;
	$GLOBALS['cfg']['enable_feature_account_delete'] = 1;
	$GLOBALS['cfg']['enable_feature_password_retrieval'] = 0;

	$GLOBALS['cfg']['enable_feature_geocoding'] = 1;
	$GLOBALS['cfg']['enable_feature_search'] = 1;

	$GLOBALS['cfg']['enable_feature_polymaps'] = 1;		# independent of whether the browser supports SVG

	$GLOBALS['cfg']['enable_feature_magicwords'] = array(

		'geonames' => array(
			'id' => 0,
		),

		'flickr' => array(
			'id' => 1,
		),

		'yahoo' => array(
			'woeid' => 0,
		),
	);

	#
	# Database stuff
	#

	$GLOBALS['cfg']['db_main'] = array(
		'host'	=> 'READ-FROM-CONFIG',
		'user'	=> 'READ-FROM-CONFIG',
		'pass'	=> 'READ-FROM-CONFIG',
		'name'	=> 'dotspotting',
		'auto_connect' => 1,
	);

	$GLOBALS['cfg']['db_enable_poormans_slaves'] = 1;
	$GLOBALS['cfg']['db_enable_poormans_ticketing'] = 1;
	$GLOBALS['cfg']['db_enable_poormans_federation'] = 1;

	#
	# Templates
	#

	$GLOBALS['cfg']['smarty_template_dir'] = DOTSPOTTING_WWW_DIR . '/templates';
	$GLOBALS['cfg']['smarty_compile_dir'] = DOTSPOTTING_WWW_DIR . '/templates_c';
	$GLOBALS['cfg']['smarty_compile'] = 1;

	#
	# App specific stuff
	#

	$GLOBALS['cfg']['abs_root_url'] = 'READ-FROM-CONFIG';

	$GLOBALS['cfg']['pagination_per_page'] = 25;
	$GLOBALS['cfg']['pagination_spill'] = 5;

	$GLOBALS['cfg']['auth_cookie_domain'] = parse_url($GLOBALS['cfg']['abs_root_url'], 1);
	$GLOBALS['cfg']['auth_cookie_name'] = 'a';

	$GLOBALS['cfg']['crypto_cookie_secret'] = 'READ-FROM-CONFIG';
	$GLOBALS['cfg']['crypto_password_secret'] = 'READ-FROM-CONFIG';

	$GLOBALS['cfg']['import_max_records'] = 1000;

	$GLOBALS['cfg']['show_show_header_message'] = 0;

	#
	# Email
	#

	$GLOBALS['cfg']['email_from_name']	= 'READ-FROM-CONFIG';
	$GLOBALS['cfg']['email_from_email']	= 'READ-FROM-CONFIG';
	$GLOBALS['cfg']['auto_email_args']	= 'READ-FROM-CONFIG';

	#
	# Third-party API keys
	#

	$GLOBALS['cfg']['flickr_apikey'] = 'READ-FROM-CONFIG';

?>