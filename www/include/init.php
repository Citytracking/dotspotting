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
	# (20100908/asc)
	#

	include(DOTSPOTTING_FLAMEWORK_DIR . '/include/config.php');

	include(DOTSPOTTING_WWW_DIR."/include/config.php");
	include(DOTSPOTTING_CONFIG_DIR . '/dotspotting.php');

	# Go, flamework! Go!!

	include_once(DOTSPOTTING_FLAMEWORK_DIR . '/include/init.php');

	#################################################################

	loadlib("buckets");
	loadlib("dots");
	loadlib("urls");
	loadlib("user_agent");
	loadlib("filter");

	#################################################################

	#
	# Hey look! Running code goes here!
	#

	$GLOBALS['filter'] = new lib_filter();

	# This is a shim in the absence of a saner and
	# plain-old function-y way to use lib_filter...

	function filter_strict($str){

		$whatevar = $GLOBALS['filter']->allowed;

		$GLOBALS['filter']->allowed = array();
		$str = $GLOBALS['filter']->go($str);

		$GLOBALS['filter']->allowed = $whatevar;
		return $str;
	}

	$GLOBALS['cfg']['browser'] = user_agent_info();

	$GLOBALS['cfg']['browser']['capabilities'] = array(
		'polymaps' => can_use_polymaps(),
	);

	#################################################################

	#
	# Dotspotting specific functions and utils go here
	#

	#################################################################

	function can_use_polymaps(){

		if (! $GLOBALS['cfg']['enable_feature_polymaps']){
			return 0;
		}

		$ok_browsers = array(
			'safari' => 5,
			'chrome' => 6,
			'firefox' => 3.5,
			'opera' => 10,
		);

		$browser = null;
		$version = null;

		foreach (array_keys($ok_browsers) as $browser){

			if ($version = $GLOBALS['cfg']['browser'][$browser]){

				# Check to see if we managed to get an actual
				# version number from lib_user_agent.php

				if ($GLOBALS['cfg']['browser']['version']){
					$version = $GLOBALS['cfg']['browser']['version'];
				}

				break;
			}
		}

		# dumper("{$browser} {$version}");

		if ((! $version) || ($version < $ok_browsers[$browser])){
			return 0;
		}

		return 1;
	}

	$GLOBALS['smarty']->register_function('can_use_polymaps', 'can_use_polymaps');

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

	function users_delete_user_callback(&$user){
		return buckets_delete_buckets_for_user($user);
	}

	#################################################################

	# Move this in to flamework ?
	# (20101024/straup)

	function ensure_valid_user_from_url($method=''){

		if (strtolower($method) == 'post'){
			$user_id = post_int64('user_id');
		}

		else {
			$user_id = get_int64('user_id');
		}

		if (! $user_id){
			error_404();
		}

		$user = users_get_by_id($user_id);

		if ((! $user) || ($user['deleted'])){
			error_404();
		}

		return $user;
	}

	#################################################################
?>
