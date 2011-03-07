
<?php

	# This file has been copied from the Citytracking fork of flamework.
	# It has not been forked, or cloned or otherwise jiggery-poked, but
	# copied: https://github.com/Citytracking/flamework
	#
	# It has also been *modified* to include Dotspotting specific stuff.

	#############################################################

	#
	# You should NOT be editing this file. You should instead be editing
	# the config file found in dotspotting/config/dotspotting.php. See also:
	# https://github.com/Citytracking/dotspotting/blob/master/README.CONFIG.md
	#

	$GLOBALS['cfg'] = array();

	$GLOBALS['cfg']['dotspotting_version'] = '0.0.0';	# see also: http://semver.org/

	#
	# Things you might want to do quickly
	#

	$GLOBALS['cfg']['disable_site'] = 0;
	$GLOBALS['cfg']['show_show_header_message'] = 0;

	#
	# Feature flags
	# See also: http://code.flickr.com/blog/2009/12/02/flipping-out/
	#

	$GLOBALS['cfg']['enable_feature_import'] = 1;
	$GLOBALS['cfg']['enable_feature_import_by_url'] = 0;

	$GLOBALS['cfg']['enable_feature_import_archive'] = 0;

	$GLOBALS['cfg']['enable_feature_dots_indexing'] = 1;

	# Don't turn this on until there is a working offline tasks system
	# $GLOBALS['cfg']['enable_feature_enplacify'] = 0;

	$GLOBALS['cfg']['enable_feature_api'] = 1;

	$GLOBALS['cfg']['enable_feature_signup'] = 1;
	$GLOBALS['cfg']['enable_feature_signin'] = 1;
	$GLOBALS['cfg']['enable_feature_account_delete'] = 1;
	$GLOBALS['cfg']['enable_feature_password_retrieval'] = 1;

	$GLOBALS['cfg']['password_retrieval_from_email'] = "do-not-reply@{$_SERVER['SERVER_NAME']}";
	$GLOBALS['cfg']['password_retrieval_from_name'] = 'Dotspotting Password Helper Robot';

	$GLOBALS['cfg']['enable_feature_geocoding'] = 1;

	$GLOBALS['cfg']['enable_feature_search'] = 1;
	$GLOBALS['cfg']['enable_feature_search_export'] = 1;
	$GLOBALS['cfg']['enable_feature_search_facets'] = 1;

	$GLOBALS['cfg']['enable_feature_http_prefetch'] = 0;

	$GLOBALS['cfg']['enable_feature_magicwords'] = array(

		'flickr' => array(
			'id' => 1,
		),

		'foursquare' => array(
			'venue' => 1,
		),

		'geonames' => array(
			'id' => 0,
		),

		'oam' => array(
			'mapid' => 1,
		),

		'walkingpapers' => array(
			'scanid' => 1,
		),

		'yahoo' => array(
			'woeid' => 1,
		),
	);

	#
	# God auth
	#

	$GLOBALS['cfg']['auth_enable_poormans_god_auth'] = 0;

	# $GLOBALS['cfg']['auth_poormans_god_auth'] = array(
	# 	xxx => array(
	# 		'roles' => array( 'staff' ),
	# 	),
	# );

	#
	# Crypto stuff
	#

	$GLOBALS['cfg']['crypto_cookie_secret'] = 'READ-FROM-CONFIG';
	$GLOBALS['cfg']['crypto_password_secret'] = 'READ-FROM-CONFIG';
	$GLOBALS['cfg']['crypto_crumb_secret'] = 'READ-FROM-CONFIG';

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
	# API stuff
	#

	# This is defined in config-api.php and gets pulled in Dotspotting's init.php
	# assuming that 'enable_feature_api' is true.

	#
	# Templates
	#

	$GLOBALS['cfg']['smarty_template_dir'] = DOTSPOTTING_WWW_DIR . '/templates';
	$GLOBALS['cfg']['smarty_compile_dir'] = DOTSPOTTING_WWW_DIR . '/templates_c';
	$GLOBALS['cfg']['smarty_compile'] = 1;

	#
	# App specific stuff
	#

	# Just blow away whatever Flamework says for abs_root_url. The user has the chance to reset these in
	# config/dotspotting.php and we want to ensure that if they don't the code in include/init.php for
	# wrangling hostnames and directory roots has a clean start. (20101127/straup)

	$GLOBALS['cfg']['abs_root_url'] = '';
	$GLOBALS['cfg']['safe_abs_root_url'] = '';

	$GLOBALS['cfg']['auth_cookie_domain'] = parse_url($GLOBALS['cfg']['abs_root_url'], 1);
	$GLOBALS['cfg']['auth_cookie_name'] = 'a';

	$GLOBALS['cfg']['maptiles_template_url'] = 'http://{S}tile.cloudmade.com/1a1b06b230af4efdbb989ea99e9841af/26490/256/{Z}/{X}/{Y}.png';
	$GLOBALS['cfg']['maptiles_template_hosts'] = array( 'a.', 'b.', 'c.' );
	$GLOBALS['cfg']['maptiles_license'] = 'Map data <a href="http://creativecommons.org/licenses/by-sa/3.0/">CCBYSA</a> 2010 <a href="http://openstreetmap.org/">OpenStreetMap.org</a> contributors';
	

	$GLOBALS['cfg']['pagination_per_page'] = 25;
	$GLOBALS['cfg']['pagination_spill'] = 5;
	$GLOBALS['cfg']['pagination_assign_smarty_variable'] = 1;

	$GLOBALS['cfg']['import_max_records'] = 1000;
	$GLOBALS['cfg']['import_by_url_do_head'] = 1;

	$GLOBALS['cfg']['import_archive_root'] = '';

	# a list of format which might be simplified

	$GLOBALS['cfg']['import_do_simplification'] = array(
		'kml' => 0, # when coordinates are stored in LineStrings
		'gpx' => 0, # basically always
	);

	$GLOBALS['cfg']['dots_indexing_max_cols'] = 2;

	# If these two are arrays they will be checked by the upload_by_url.php
	# code. They are expected to be lists of hostnames

	$GLOBALS['cfg']['import_by_url_blacklist'] = '';
	$GLOBALS['cfg']['import_by_url_whitelist'] = '';

	$GLOBALS['cfg']['import_kml_resolve_network_links'] = 1;

	#
	# Email
	#

	$GLOBALS['cfg']['email_from_name']	= 'READ-FROM-CONFIG';
	$GLOBALS['cfg']['email_from_email']	= 'READ-FROM-CONFIG';
	$GLOBALS['cfg']['auto_email_args']	= 'READ-FROM-CONFIG';

	#
	# Geo
	#

	$GLOBALS['cfg']['geo_geocoding_service'] = 'yahoo';
	$GLOBALS['cfg']['geo_geocoding_yahoo_apikey'] = '';

	# See also: lib_dots_derive.php

	$GLOBALS['cfg']['dots_derived_from'] = array(
		0 => 'user',
		1 => 'dotspotting',
		2 => 'geocoded (yahoo)',
		3 => 'geohash',
	);

	#
	# Enplacification
	#

	# This requires that 'enable_feature_enplacify' be enabled (see above)

	$GLOBALS['cfg']['enplacify'] = array(

		'chowhound' => array(
			'uris' => array(
				"/chow\.com\/restaurants\/([^\/]+)/",
			),
		),

		'dopplr' => array(
			'uris' => array(
				"/dplr\.it\/(eat|stay|explore)\/([^\/]+)/",
				"/dopplr\:(eat|stay|explore)=(.+)$/",
			),
		),

		'flickr' => array(
			'uris' => array(
				"/flickr\.com\/photos\/(?:[^\/]+)\/(\d+)/",
				# flickr short Uris
			),
			'machinetags' => array(
				'dopplr' => array('eat', 'explore', 'stay'),
				'foodspotting' => array('place'),
				'foursquare' => array('venue'),
				'osm' => array('node', 'way'),
				'yelp' => array('biz'),
			),
		),

		'foodspotting' => array(
			'uris' => array(
				"/foodspotting\.com\/places\/(\d+)/",
				"/foodspotting\:place=(.+)$/",
			),
		),

		'foursquare' => array(
			'uris' => array(
				"/foursquare\.com\/venue\/(\d+)/",
				"/foursquare\:venue=(\d+)$/",
			),
		),

		'openstreetmap' => array(
			'uris' => array(
				"/openstreetmap.org\/browse\/(node)\/(\d+)/",
				"/osm\:(node)=(\d+)$/",
			),
		),

		'yelp' => array(
			'uris' => array(
				"/yelp\.com\/biz\/([^\/]+)/",
				"/yelp\:biz=([^\/]+)/",
			),
		),
	);

	#
	# Third-party API keys
	#

	$GLOBALS['cfg']['flickr_apikey'] = 'READ-FROM-CONFIG';

	#
	# Things you can probably not worry about
	#

	$GLOBALS['cfg']['user'] = null;

	$GLOBALS['cfg']['smarty_compile'] = 1;

	$GLOBALS['cfg']['http_timeout'] = 3;

	$GLOBALS['cfg']['check_notices'] = 1;

	$GLOBALS['cfg']['db_profiling'] = 0;
	
	# 
	# USER STYLES
	#
	# implemented in global styles and include/lib_maps.php
	# TODO: apply these to other export options
	#
	
	# dot color props
	# fill,stroke array = rgba
	# alpha =  0 = completely transparent, 1 = opaque
	$GLOBALS['cfg']['dot_color_scheme'] = array(
		'fill' => array(11,189,255,1),
		'stroke' => array(255,255,255,1),
		'stroke_width' => 3,
		'fill_hover' => array(0,221,238,1),
		'stroke_hover' => array(0,17,45,1),
		'stroke_width_hover' => 5,
		'private' => array(255,0,0,1)
	);
	
	# sheet color props
	# fill,stroke array = rgba
	# alpha =  0 = completely transparent, 1 = opaque
	$GLOBALS['cfg']['sheet_color_scheme'] = array(
		'fill' => array(11,189,255,.5),
		'stroke' => array(11,189,255,1),
		'stroke_width' => 4,
		'fill_hover' => array(11,189,255,.1),
		'stroke_hover' => array(11,189,255,1),
		'stroke_width_hover' => 4
	);
?>