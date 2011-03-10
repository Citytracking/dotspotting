<?
	#
	# $Id$
	#

	# This file has been copied from the Citytracking fork of flamework.
	# It has not been forked, or cloned or otherwise jiggery-poked, but
	# copied: https://github.com/Citytracking/flamework (20101208/straup)
	#
	# It has also since been *modified* to include Dotspotting specific
	# stuff.

	#############################################################

	#
	# some startup tasks which come before anything else:
	#  * set up the timezone
	#  * record the time
	#  * set the mbstring encoding
	#

	error_reporting((E_ALL | E_STRICT) ^ E_NOTICE);

	putenv('TZ=PST8PDT');
	date_default_timezone_set('America/Los_Angeles');

	mb_internal_encoding('UTF-8');

	#############################################################

	$GLOBALS['loaded_libs'] = array();

	$GLOBALS['timings'] = array();
	$GLOBALS['timings']['execution_start'] = microtime_ms();
	$GLOBALS['timing_keys'] = array();

	#############################################################

	# Go!

	define('DOTSPOTTING_WWW_DIR', dirname(dirname(__FILE__)) );
	define('DOTSPOTTING_INCLUDE_DIR', DOTSPOTTING_WWW_DIR . '/include/');

	define('FPDF_FONTPATH', DOTSPOTTING_INCLUDE_DIR . '/pear/fpdf_fonts/');

	# See this? We're being super restrictive about where we look
	# for libs. Dotspotting should be able to run as-is with everything
	# located locally. (20110110/straup)

	$include_path = array(
		".",
		DOTSPOTTING_INCLUDE_DIR,
		DOTSPOTTING_INCLUDE_DIR . "pear/"
	);

	ini_set("include_path", implode(":", $include_path));

	define('DOTSPOTTING_CONFIG_DIR', dirname(DOTSPOTTING_WWW_DIR) . '/config/');

	include(DOTSPOTTING_INCLUDE_DIR . "config.php");

	if ($GLOBALS['cfg']['enable_feature_api']){
		include(DOTSPOTTING_INCLUDE_DIR . "config-api.php");
	}

	include(DOTSPOTTING_CONFIG_DIR . "dotspotting.php");

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

	#############################################################

	if (strtolower($_SERVER['HTTP_X_MOZ']) == 'prefetch'){

		if (! $GLOBALS['cfg']['enable_feature_http_prefetch']){
			error_403();
		}
	}

	#############################################################

	# put these in $cfg...

	$this_is_apache		= strlen($_SERVER['REQUEST_URI']) ? 1 : 0;
	$this_is_shell		= $_SERVER['SHELL'] ? 1 : 0;
	$this_is_webpage	= $this_is_apache && !$this_is_api ? 1 : 0;

	$GLOBALS['cfg']['admin_flags_no_db'] = ($_GET['no_db']) ? 1 : 0;
	$GLOBALS['cfg']['admin_flags_show_notices'] = ($_GET['debug']) ? 1 : 0;

	#
	# Load some core (Flamework) libraries which we will 'always'
	# need even if it is just to disable the site
	#

	loadlib('auth');
	loadlib('log');		# logging comes first, so that other modules can log during startup
	loadlib('smarty');	# smarty comes next, since other libs register smarty modules
	loadlib('utf8');	# make sure utf8/header stuff is present in case we need to take the site down

	if (($GLOBALS['cfg']['disable_site']) && (! $this_is_shell)){
		$smarty->display("page_site_disabled.txt");
		exit();
	}

	#
	# install an error handler to check for dubious notices?
	# we do this because we only care about one of the notices
	# that gets generated. we only want to run this code in
	# devel environments. we also want to run it before any
	# libraries get loaded so that we get to check their syntax.
	#

	if ($GLOBALS['cfg']['check_notices']){
		set_error_handler('handle_error_notices', E_NOTICE);
		error_reporting(E_ALL | E_STRICT);
	}

	function handle_error_notices($errno, $errstr){
		if (preg_match('!^Use of undefined constant!', $errstr)) return false;
		return true;
	}

	#
	# Poor man's database configs:
	# See notes in config.php
	#

	if ($GLOBALS['cfg']['db_enable_poormans_slaves']){

		$GLOBALS['cfg']['db_main_slaves'] = $GLOBALS['cfg']['db_main'];

		$GLOBALS['cfg']['db_main_slaves']['host'] = array(
			1 => $GLOBALS['cfg']['db_main']['host'],
		);

		$GLOBALS['cfg']['db_main_slaves']['name'] = array(
			1 => $GLOBALS['cfg']['db_main']['name'],
		);
	}

	if ($GLOBALS['cfg']['db_enable_poormans_ticketing']){

		$GLOBALS['cfg']['db_tickets'] = $GLOBALS['cfg']['db_main'];
	}

	if ($GLOBALS['cfg']['db_enable_poormans_federation']){

		$GLOBALS['cfg']['db_users'] = $GLOBALS['cfg']['db_main'];

		$GLOBALS['cfg']['db_users']['host'] = array(
			1 => $GLOBALS['cfg']['db_main']['host'],
		);

		$GLOBALS['cfg']['db_users']['name'] = array(
			1 => $GLOBALS['cfg']['db_main']['name'],
		);

	}
	
	# (seanc | 03102011)
	# creating a global variable to store current page name
	# mainly used for setting navigation
	# added a special check for dashboard (user page)
	if(isset($_SERVER['REQUEST_URI']) && !empty($_SERVER['REQUEST_URI'])){
		$incoming = trim($_SERVER['REQUEST_URI'],"/");
		if(isset($incoming) && !empty($incoming)){
			if(preg_match('/^u\/([0-9]+)$/',$incoming)){
				$GLOBALS['cfg']['page_crumb'] = array("dashboard");
			}else{
				$GLOBALS['cfg']['page_crumb'] = explode("/",$incoming);
			}
			
		}else{
			$GLOBALS['cfg']['page_crumb'] = array();
		}
	}
		
		
		
		/*
		$page_crumb_raw = explode("/",$page_crumb_extract);
		if(isset($page_crumb_raw[0]) && !empty($page_crumb_raw[0])){
			if(preg_match('/^u\/([0-9]+)$/',$page_crumb_extract)){
				$GLOBALS['cfg']['page_crumb'] = "dashboard";
			}else{
				$GLOBALS['cfg']['page_crumb'] = $page_crumb_raw[0];
			}
		}else{
			$GLOBALS['cfg']['page_crumb'] = "";
		}
	}else{
		$GLOBALS['cfg']['page_crumb'] = "";
	}
	*/
	
	# More stuff from Flamework (see above)

	loadlib('error');
	loadlib('sanitize');
	loadlib('db');
	loadlib('dbtickets');
	loadlib('cache');
	loadlib('crypto');
	loadlib('crumb');
	loadlib('login');
	loadlib('email');
	loadlib('users');
	loadlib('http');
	loadlib('sanitize');
	loadlib("filter");

	# Stuff from Dotspotting that should always be loaded

	loadlib("dots");
	loadlib("sheets");
	loadlib("urls");
	loadlib("user_agent");

	if ($this_is_webpage){
		login_check_login();
	}

	#
	# this timer stores the end of core library loading
	#

	$GLOBALS['timings']['init_end'] = microtime_ms();

	$GLOBALS['error'] = array();
	$GLOBALS['smarty']->assign_by_ref('error', $error);
	
	#################################################################

	function dumper($foo){
		echo "<pre style=\"text-align: left;\">";
		echo HtmlSpecialChars(var_export($foo, 1));
		echo "</pre>\n";
	}

	function intval_range($in, $lo, $hi){
		return min(max(intval($in), $lo), $hi);
	}

	function microtime_ms(){
		list($usec, $sec) = explode(" ", microtime());
		return intval(1000 * ((float)$usec + (float)$sec));
	}

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

	function smarty_modifier_possess($str){

		$ending = (preg_match("/s$/", $str)) ? "'" : "'s";

		return $str . $ending;
	}

	$GLOBALS['smarty']->register_modifier('possess', 'smarty_modifier_possess');

	#################################################################

	#
	# the module loading code.
	#
	# we track which modules we've loaded ourselves instead of
	# using include_once(). we do this so that we can avoid the
	# stat() overhead involved in figuring out the canonical path
	# to a file. so long as we always load modules via this
	# method, we save some filesystem overhead.
	#
	# we can also ensure that modules don't pollute the global
	# namespace accidentally, since they are always loaded in a
	# function's private scope.
	#

	function loadlib($name){

		if ($GLOBALS['loaded_libs'][$name]){
			return;
		}

		$GLOBALS['loaded_libs'][$name] = 1;

		$fq_name = DOTSPOTTING_INCLUDE_DIR . "lib_{$name}.php";
		include_once($fq_name);
	}

	function loadpear($name){

		if ($GLOBALS['loaded_libs']['PEAR:'.$name]){
			return;
		}

		$GLOBALS['loaded_libs']['PEAR:'.$name] = 1;

		$fq_name = DOTSPOTTING_INCLUDE_DIR . "pear/{$name}.php";
		include_once($fq_name);
	}

	#################################################################
?>
