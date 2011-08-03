<?php

	#
	# $Id$
 	#

	# This file has been copied from the Citytracking fork of flamework.
	# It has not been forked, or cloned or otherwise jiggery-poked, but
	# copied: https://github.com/Citytracking/flamework (20101208/straup)

	########################################################################

	function auth_has_role($role, $who=0){

		# Currently, this is the only thing that works. It is
		# disabled by default (20101122/straup)

		# See also: https://github.com/exflickr/GodAuth/

		if (! $GLOBALS['cfg']['auth_enable_poormans_god_auth']){
			return 0;
		}

		if (! is_array($GLOBALS['cfg']['auth_poormans_god_auth'])){
			return 0;
		}

		$who = ($who) ? $who : $GLOBALS['cfg']['user']['id'];

		if (! $who){
			return 0;
		}

		if (! isset($GLOBALS['cfg']['auth_poormans_god_auth'][$who])){
			return 0;
		}

		$perms = $GLOBALS['cfg']['auth_poormans_god_auth'][$who];

		return (in_array($role, $perms['roles'])) ? 1 : 0;
	}

	########################################################################
?>
