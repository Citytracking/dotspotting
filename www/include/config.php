<?php

	#
	# HEY LOOK! THESE ARE THE *DEFAULT* CONFIG SETTINGS FOR 
	# DOTSPOTTING. IF YOU NEED TO CHANGE THINGS YOU SHOULD DO
	# IT OVER IN: dotspotting/config/dotspotting.php
	#
	# SEE ALSO: dotspotting/README.CONFIG.md
	#

	#
	# We assume this is declared in flamework/include/config.php
	# $GLOBALS['cfg'] = array();
	#

	$GLOBALS['cfg']['dotspotting_version'] = '0.0.0';	# see also: http://semver.org/
	$GLOBALS['cfg']['flamework_skip_init_config'] = 1;

	$GLOBALS['cfg']['site_disabled'] = 0;

	#
	# Feature flags
	# See also: http://code.flickr.com/blog/2009/12/02/flipping-out/
	#

	$GLOBALS['cfg']['enable_feature_uploads'] = 1;
	$GLOBALS['cfg']['enable_feature_api'] = 0;

	$GLOBALS['cfg']['enable_feature_signup'] = 1;
	$GLOBALS['cfg']['enable_feature_signin'] = 1;
	$GLOBALS['cfg']['enable_feature_account_delete'] = 1;
	$GLOBALS['cfg']['enable_feature_password_retrieval'] = 0;

	$GLOBALS['cfg']['enable_feature_geocoding'] = 1;
	$GLOBALS['cfg']['enable_feature_search'] = 1;

	$GLOBALS['cfg']['enable_feature_polymaps'] = 1;		# independent of whether the browser supports SVG

	$GLOBALS['cfg']['enable_feature_magicwords'] = array(

		'flickr' => array(
			'id' => 1,
		),

		'geonames' => array(
			'id' => 0,
		),

		'oam' => array(
			'mapid' => 1,
		),

		'walkingpapers' => array(
			'scanid' => 0,
		),

		'yahoo' => array(
			'woeid' => 1,
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
	# See also: lib_dots_derive.php
	#

	$GLOBALS['cfg']['dots_derived_from'] = array(
		0 => 'user',
		1 => 'dotspotting',
		2 => 'geocoded (yahoo)',
		3 => 'geohash',
	);

	#
	# API stuff
	#

	include_once('config-api.php');

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