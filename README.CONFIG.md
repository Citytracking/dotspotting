Configuring Dotspotting (the long version)
--

This is meant to be the long and thorough version outlining all (most?) of the various knobs and levers in Dotspotting. This document is meant to be a reference and you do *not* need worry about most of things discussed here. Unless you're curious that way.

If you just want to dive in you should start with the [example Dotspotting config](https://github.com/Citytracking/dotspotting/blob/master/config/dotspotting.php.example) which contains only those configs you MUST, SHOULD and MAY want to change in order to get up and running.

*For the sake of brevity this document only covers config variables that are specific to Dotspotting. I will make a similar document for Flamework soon. I promise.*

The Basics
--

	#
	# We assume this is declared in flamework/include/config.php
	# $GLOBALS['cfg'] = array();
	#

	#
	# See that 'flamework_skip_init_config' flag? Don't change that unless you want to break your installation
	# of Dotspotting.
	#

	$GLOBALS['cfg']['dotspotting_version'] = '0.0.0';	# see also: http://semver.org/
	$GLOBALS['cfg']['flamework_skip_init_config'] = 1;

Feature Flags
--

	#
	# Various flags to control who can sign up or log in to your service. Note how in this example password
	# retrieval is disabled (because maybe you haven't set up an email server with which to send password
	# reminders). These are designed to let you gracefully degrade an installation of Dotspotting if there
	# are growing pains or other unexpected freak outs.
	#

	$GLOBALS['cfg']['enable_feature_signup'] = 1;
	$GLOBALS['cfg']['enable_feature_signin'] = 1;
	$GLOBALS['cfg']['enable_feature_account_delete'] = 1;
	$GLOBALS['cfg']['enable_feature_password_retrieval'] = 0;

	$GLOBALS['cfg']['enable_feature_uploads'] = 1;

	$GLOBALS['cfg']['enable_feature_geocoding'] = 1;
	$GLOBALS['cfg']['enable_feature_search'] = 1;
	$GLOBALS['cfg']['enable_feature_search_export'] = 0;	# this is still a work in progress

	#
	# Toggle the use of Polymaps independent of whether a user's browser supports SVG
	#

	$GLOBALS['cfg']['enable_feature_polymaps'] = 1;

	#
	# Magic words are a runtime display feature to hook in to third-party APIs and services in order
	# to display dynamic content. You can read more about then in the FAQ:
	# http://your-dotspotting.example.com/faq/#magicwords
 	#

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

App Specific Stuff
--

	#
	# As in: Where your installation of Dotspotting lives on the Internets.
	#

	# Strictly speaking you don't have to set this. If it's empty Dotspotting
	# will try to work out what the correct root URL is based on PHP's SERVER_NAME
	# setting and some extra hoop-jumping to determine what the directory root
	# is (for people running Dotspotting out of a public_html directory). If you
	# want to be explicit about the server name assign it here.

	# $GLOBALS['cfg']['abs_root_url'] = 'http://dotspotting.example.com/';
	# $GLOBALS['cfg']['safe_abs_root_url'] = $GLOBALS['cfg']['abs_root_url'];

	#
	# Basic cookie stuff
	# 

	$GLOBALS['cfg']['auth_cookie_domain'] = parse_url($GLOBALS['cfg']['abs_root_url'], 1);
	$GLOBALS['cfg']['auth_cookie_name'] = 'a';

	#
	# Poorman's god auth is a bare-bones version of the exflick
	# GodAuth system: https://github.com/exflickr/GodAuth
	# 
	# It works by assigning one or more "roles" to a user and
	# those roles are checked throughout Dotspotting (Flamework)
	# to decide whether to display/perform specific actions. At
	# the moment, poorman's god auth is keyed by a assigning one
	# or more user IDs (as in db_main:Users:id) in the 'auth_poormans_god_auth'
	# hash below. Poorman's god auth is disabled by default.
	#
	# $GLOBALS['cfg']['auth_enable_poormans_god_auth'] = 0;
	#
	# $GLOBALS['cfg']['auth_poormans_god_auth'] = array(
	# 	xxx => array(
	# 		'roles' => array( 'staff' ),
	# 	),
	# );
	#

	$GLOBALS['cfg']['crypto_cookie_secret'] = 'READ-FROM-CONFIG';
	$GLOBALS['cfg']['crypto_password_secret'] = 'READ-FROM-CONFIG';

	#
	# If you need to ensure that people don't flood your installation with dots, ensure that this has a
	# value > 0. This is read in lib_import.
	#

	$GLOBALS['cfg']['import_max_records'] = 1000;

	# If you've enable uploads by URL this determines whether Dotspotting will do an initial HEAD request
	# to check stuff like download length(s) and file type.

	$GLOBALS['cfg']['import_remoteurls_do_head'] = 1;

	#
	# This flag controls whether or not to include the inc_header_message.txt template at the top of every
	# page on Dotspotting.
	#

	$GLOBALS['cfg']['show_header_message'] = 0;

	#
	# Pagination hooks for things like displaying lists of dots or other search results. If 'pagination_spill'
	# is defined and the value of the last paginated set is less than 'spill' those records will be included
	# on the second to last page.
	#

	$GLOBALS['cfg']['pagination_per_page'] = 25;
	$GLOBALS['cfg']['pagination_spill'] = 5;

Database Stuff (simple)
--

	#
	# You will need at least one MySQL database in order to run Dotspotting. Details go here.
	#

	$GLOBALS['cfg']['db_main'] = array(
		'host'	=> 'READ-FROM-CONFIG',
		'user'	=> 'READ-FROM-CONFIG',
		'pass'	=> 'READ-FROM-CONFIG',
		'name'	=> 'dotspotting',	# as in the name of your database
		'auto_connect' => 1,
	);

	#
	# See these? They are the magic flags that allow Dotspotting to run on a single database without
	# any extra fussing. If your installation of Dotspotting ever outgrows this setup you will need
	# to add complete config blocks for the following database clusters: 'db_main_slaves', 'db_users'
	# and 'db_tickets'
	#

	$GLOBALS['cfg']['db_enable_poormans_slaves'] = 1;
	$GLOBALS['cfg']['db_enable_poormans_ticketing'] = 1;
	$GLOBALS['cfg']['db_enable_poormans_federation'] = 1;

Database Stuff (fancy)
--

	#
	# Here's an example of a 'properly' federated system. This just means any set of MySQL databases segregated
	# in to the following clusters: 1) a 'dbmain' cluster for things that require centralized lookup 2) one or
	# more 'dbusers' clusters where dots and buckets will live 3) one or more 'dbtickets' cluster which can be
	# used to generate unique IDs across your dotspotting installation. In the example below, we'll assumed there
	# are (3) databases set up and running on Amazon's RDS infrastructure (http://aws.amazon.com/rds/)
	#

	# If you haven't already, you'll need to set up each cluster with its corresponding database schema. Like this:
	# 
	# $> mysql -u example -h dotspotting-dbmain.null-island.rds.amazonaws.com -p main < schema/db_main.schema
	# $> mysql -u example -h dotspotting-dbusers-1.null-island.rds.amazonaws.com -p users < schema/db_users.schema
	# $> mysql -u example -h dotspotting-dbtickets-1.null-island.rds.amazonaws.com -p tickets < schema/db_tickets.schema

	# First, ensure that all the 'poorman' database configs are disabled
	#
	# $GLOBALS['cfg']['db_enable_poormans_slaves'] = 0;
	# $GLOBALS['cfg']['db_enable_poormans_ticketing'] = 0;
	# $GLOBALS['cfg']['db_enable_poormans_federation'] = 0;

	# Next, configure the 'db_main' cluster. This looks exactly like your database configs for a not-federated setup.
	#
	# $GLOBALS['cfg']['db_main']['name'] = 'main';
	# $GLOBALS['cfg']['db_main']['host'] = 'dotspotting-dbmain.null-islan.rds.amazonaws.com';
	# $GLOBALS['cfg']['db_main']['user'] = 'example';
	# $GLOBALS['cfg']['db_main']['pass'] = '******';

	# Now, add configs for your user shards. Note that there is only a single user shard (1) with a single database
	# tied to it. Over time you may want to configure your user shards to be "multi-master" but if you don't already
	# know that means, don't worry about it. This is all you need to do for now.
	#
	# $GLOBALS['cfg']['db_users']['name'] = array(
	# 	1 => 'users',
	# );
	#
	# $GLOBALS['cfg']['db_users']['host'] = array(
	# 	1 => 'dotspotting-dbusers-1.null-island.rds.amazonaws.com',
	# );
	#
	# $GLOBALS['cfg']['db_users']['user'] = 'example';
	# $GLOBALS['cfg']['db_users']['pass'] = '******';

	# Finally set up your ticket servers. In this example, there's only one ticketing server but you may have two in
	# case the first one crashes or become unreliable. In either case, you'll need to make sure that your database(s)
	# are set up to increment by a value of 2 per the following:
	#
	# http://code.flickr.com/blog/2010/02/08/ticket-servers-distributed-unique-primary-keys-on-the-cheap/
	#
	# $GLOBALS['cfg']['db_tickets']['name'] = array(
	#	1 => 'tickets',
	#	1 => 'tickets',
	# );
	#
	# $GLOBALS['cfg']['db_tickets']['host'] = array(
	# 	1 => 'dotspotting-dbtickets-1.null-island.rds.amazonaws.com',
	# 	2 => 'dotspotting-dbtickets-2.null-island.rds.amazonaws.com',
	# );
	#
	# $GLOBALS['cfg']['db_tickets']['user'] = 'example';
	# $GLOBALS['cfg']['db_tickets']['pass'] = '******';

	#
	# Since we're using RDS as an example set up, here's what I needed to do (around November 2010) to get things
	# working. Unfortunately, the web-based tools for dealing with MySQL configs in RDS still aren't awesome so you'll
	# need to get your hands dirty with what Amazon calls "DB parameter groups" which is essentially a canned
	# abstraction layer for MySQL configs. Creating them is easy using the web tools, so the following assumes that
	# you've created two groups: 'dotspotting-dbtickets-even' and 'dotspotting-dbtickets-odd'
	#
	# This is where it gets fun. The first thing you'll need to do is download and configure the RDS command line
	# tool: http://aws.amazon.com/developertools/Amazon-RDS/2928
	#
	# Next run the following commands (note that you'll need to adjust example.cfg to use your own AWS configs):
	#
	# $>./bin/rds rds-modify-db-parameter-group dotspotting-dbtickets-even \
	#    --parameters "name=auto_increment_increment, value=2, method=immediate"
	#    --parameters "name=auto_increment_offset, value=2, method=immediate"
	#    --aws-credential-file=./example.cfg
	#
	# $>./bin/rds rds-modify-db-parameter-group dotspotting-dbtickets-odd \
	#    --parameters "name=auto_increment_increment, value=2, method=immediate"
	#    --parameters "name=auto_increment_offset, value=1, method=immediate"
	#    --aws-credential-file=./example.cfg
	#
	# This is how you adjust RDS instances to use the settings defined in the article about ticketing. It's not
	# awesome, I know. Now you need to assign those database configs to your two (or one) ticketing servers. Let's
	# assume that they're called 'dotspotting-tickets-1' and 'dotspotting-tickets-2'. Again, adjust for taste you'd
	# run the following:
	#
	# $> ./bin/rds rds-modify-db-instance dotspotting-tickets-1 -g dotspotting-dbtickets-odd \
	#     --aws-credential-file=./aws.cfg
	#
	# $> ./bin/rds rds-modify-db-instance dotspotting-tickets-2 -g dotspotting-dbtickets-even \
	#     --aws-credential-file=./aws.cfg
	#
	# Finally, you'll need to reboot those two databases. You can do this from the command line or just the web
	# interface, whichever seems less painful at this point...
	#

Templates
--

	#
	# Basically just where Smarty can read/write its templates. Remember that the 'smarty_compile_dir'
	# needs to be able to written to by your web server.
	#

	$GLOBALS['cfg']['smarty_template_dir'] = DOTSPOTTING_WWW_DIR . '/templates';
	$GLOBALS['cfg']['smarty_compile_dir'] = DOTSPOTTING_WWW_DIR . '/templates_c';
	$GLOBALS['cfg']['smarty_compile'] = 1;

Email
--

	#
	# Stuff used to fill in headers when sending email from# lib_email.php (in Flamework). Note that installing
	# and setting up an email server is not part of Dotspotting's scope.
	#

	$GLOBALS['cfg']['email_from_name']	= 'READ-FROM-CONFIG';
	$GLOBALS['cfg']['email_from_email']	= 'READ-FROM-CONFIG';
	$GLOBALS['cfg']['auto_email_args']	= 'READ-FROM-CONFIG';

Third Party API keys
--

	#
	# Pretty much what it sounds like. A Flickr API key is required for Flickr "magic words" support (at least until Flickr
	# fixes its Oembed endpoint).
	#

	$GLOBALS['cfg']['flickr_apikey'] = 'READ-FROM-CONFIG';

Secrets
--

It is expected that passwords and secrets specific to your Dotspotting installation will be stored in `config/dotspotting.php` which is explicitly forbidden from being checked in to Git (in the `.gitignore` file).

Another way of dealing with all the password/secrets hoo-hah would be to create an new PHP file which is located somewhere safe (and entirely outside of source control) and then to include it at the end of your `config/dotspotting.php` file or even `www/include/init.php`.

For example, the following :

	$GLOBALS['cfg']['example_some_password'] = 's33kret';

Would become:

	$GLOBALS['cfg']['example_some_password'] = 'READ-FROM-SECRETS';

	include_once("/the/path/to/your-secrets-file.php");

?>
