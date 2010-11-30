<?php

	#
	# $Id$
	#

	# This is painful, but you're not really expected to have
	# to think about it if you're doing a plain vanilla dotspotting
	# install.

	define('DOTSPOTTING_WWW_DIR', dirname(dirname(__FILE__)));

	define('DOTSPOTTING_CONFIG_DIR', dirname(DOTSPOTTING_WWW_DIR) . '/config');
	define('DOTSPOTTING_FLAMEWORK_DIR', dirname(DOTSPOTTING_WWW_DIR) . '/ext/flamework');

	#
	# See what's going on here? There are three separate config
	# files and the order they're loaded is important:
	#
	# 1: Load the flamework config file and start with all the
	# defaults
	#
	# 2: Load the dotspotting config and -- this is important --
	# set the 'flamework_skip_init_config' flag so that when
	# we load the flamework init.php file (below) we don't
	# blow away $cfg, then set dotspotting configs where necessary
	#
	# 3: Load the dotspotting secrets file to fill in any missing
	# passwords and other things that shouldn't be checked in
	# to source control
	#
	# (20100908/straup)
	#

	include(DOTSPOTTING_FLAMEWORK_DIR . '/include/config.php');

	include(DOTSPOTTING_WWW_DIR."/include/config.php");
	include(DOTSPOTTING_CONFIG_DIR . '/dotspotting.php');

	if ($GLOBALS['cfg']['enable_feature_api']){
		include_once(DOTSPOTTING_WWW_DIR . '/include/config-api.php');
	}

	#
	# Hey look! Running code. (We do this here so that paths/URLs will
	# still work if the site has been disabled.)
	#

	#
	# First, ensure that 'abs_root_url' is both assigned and properly
	# set up to run out of user's public_html directory (if need be).
	# 

	$server_url = $GLOBALS['cfg']['abs_root_url'];

	if (! $server_url){
		$scheme = ($_SERVER['SERVER_PORT'] == 443) ? "https" : "http";
		$server_url = "{$scheme}://{$_SERVER['SERVER_NAME']}";
	}

	$cwd = '';

        if ($parent_dirname = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/')){

		$parts = explode("/", $parent_dirname);
		$cwd = implode("/", array_slice($parts, 1));

		# see below
		$cwd = rtrim($cwd, '/');
	}

	# See this? We expect that abs_root_url always have a trailing slash.
	# Really it's just about being consistent. It doesn't really matter which
	# one you choose because either way it's going to be pain or a nuisance
	# at some point or another. So we choose trailing slashes.

	$GLOBALS['cfg']['abs_root_url'] = rtrim($server_url, '/') . "/";

	if ($cwd){
		$GLOBALS['cfg']['abs_root_url'] .= $cwd . "/";
	}

	$GLOBALS['cfg']['auth_cookie_domain'] = parse_url($GLOBALS['cfg']['abs_root_url'], 1);

	#
	# Go, flamework! Go!!
	#

	include_once(DOTSPOTTING_FLAMEWORK_DIR . '/include/init.php');

	#################################################################

	loadlib("dots");
	loadlib("sheets");
	loadlib("urls");
	loadlib("user_agent");
	loadlib("filter");

	#################################################################


	#
	# Other stuff
	#

	$GLOBALS['cfg']['browser'] = user_agent_info();

	#################################################################

	# This is a shim in the absence of a saner and
	# plain-old function-y way to use lib_filter...

	function filter_strict($str){

		$filter = new lib_filter();
		$filter->allowed = array();
		return $filter->go($str);
	}

	#################################################################

	function smarty_function_pagination(){
		echo($GLOBALS['smarty']->fetch('inc_pagination.txt'));
	}

	#################################################################

	function smarty_modifier_possess($str){

		$ending = (preg_match("/s$/", $str)) ? "'" : "'s";

		return $str . $ending;
	}

	$GLOBALS['smarty']->register_modifier('possess', 'smarty_modifier_possess');

	#################################################################

	# This is called by the Flamework users_delete_user function

	function users_delete_user_callback(&$user){
		return sheets_delete_sheets_for_user($user);
	}

	#################################################################

	# http://www.php.net/manual/en/function.parse-url.php#90365

	function dotspotting_parse_url($url){

		$r  = "(?:([a-z0-9+-._]+)://)?";
		$r .= "(?:";
		$r .=   "(?:((?:[a-z0-9-._~!$&'()*+,;=:]|%[0-9a-f]{2})*)@)?";
		$r .=   "(?:\[((?:[a-z0-9:])*)\])?";
		$r .=   "((?:[a-z0-9-._~!$&'()*+,;=]|%[0-9a-f]{2})*)";
		$r .=   "(?::(\d*))?";
		$r .=   "(/(?:[a-z0-9-._~!$&'()*+,;=:@/]|%[0-9a-f]{2})*)?";
		$r .=   "|";
		$r .=   "(/?";
		$r .=     "(?:[a-z0-9-._~!$&'()*+,;=:@]|%[0-9a-f]{2})+";
		$r .=     "(?:[a-z0-9-._~!$&'()*+,;=:@\/]|%[0-9a-f]{2})*";
		$r .=    ")?";
		$r .= ")";
		$r .= "(?:\?((?:[a-z0-9-._~!$&'()*+,;=:\/?@]|%[0-9a-f]{2})*))?";
		$r .= "(?:#((?:[a-z0-9-._~!$&'()*+,;=:\/?@]|%[0-9a-f]{2})*))?";

		if (! preg_match("`$r`i", $url, $match)){
			return array( 'ok' => 0 );
		}

		$parts = array(
			"ok" => 1,
			"scheme"=>'',
			"userinfo"=>'',
			"authority"=>'',
			"host"=> '',
			"port"=>'',
			"path"=>'',
			"query"=>'',
			"fragment"=>''
		);

		switch (count($match)){
			case 10: $parts['fragment'] = $match[9];
			case 9: $parts['query'] = $match[8];
			case 8: $parts['path'] =  $match[7];
			case 7: $parts['path'] =  $match[6] . $parts['path'];
			case 6: $parts['port'] =  $match[5];
			case 5: $parts['host'] =  $match[3]?"[".$match[3]."]":$match[4];
			case 4: $parts['userinfo'] =  $match[2];
			case 3: $parts['scheme'] =  $match[1];
		}

		$parts['authority'] = ($parts['userinfo']?$parts['userinfo']."@":"") .
		$parts['host'] .
		($parts['port'] ? ":" . $parts['port'] : "");

		return $parts;
	}

	#################################################################

?>