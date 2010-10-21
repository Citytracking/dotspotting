<?php

	#
	# $Id$
	#

	# This is painful, but you're not really expected to have
	# to think about it if you're doing a plain vanilla dotspotting
	# install.
	
	define('DOTSPOTTING_WWW_DIR', dirname(dirname(__FILE__)));
	define('DOTSPOTTING_SECRETS_DIR', dirname(DOTSPOTTING_WWW_DIR) . '/secrets');
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
	include(DOTSPOTTING_SECRETS_DIR . '/dotspotting.php');

	# Go, flamework! Go!!

	include_once(DOTSPOTTING_FLAMEWORK_DIR . '/include/init.php');

	#################################################################

	loadlib("urls");
?>