Configuring Dotspotting
--

*For the sake of brevity this document only covers config variables that are specific to Dotspotting. I will make a similar document for Flamework soon. I promise.*

The Basics
--

	#
	# We assume this is declared in flamework/include/config.php
	# $GLOBALS['cfg'] = array();
	#

	#
	# See that 'flamework_skip_init_config' flag? Don't change that
	# unless you want to break your installation of Dotspotting.
	#

	$GLOBALS['cfg']['dotspotting_version'] = '0.0.0';	# see also: http://semver.org/
	$GLOBALS['cfg']['flamework_skip_init_config'] = 1;

Feature Flags
--

	#
	# Various flags to control who can sign up or log in
	# to your service. Note how in this example password
	# retrieval is disabled (because maybe you haven't set
	# up an email server with which to send password
	# reminders). These are designed to let you gracefully
	# degrade an installation of Dotspotting if there are
	# growing pains or other unexpected freak outs.
	#

	$GLOBALS['cfg']['enable_feature_signup'] = 1;
	$GLOBALS['cfg']['enable_feature_signin'] = 1;
	$GLOBALS['cfg']['enable_feature_account_delete'] = 1;
	$GLOBALS['cfg']['enable_feature_password_retrieval'] = 0;

	$GLOBALS['cfg']['enable_feature_uploads'] = 1;

	$GLOBALS['cfg']['enable_feature_geocoding'] = 1;
	$GLOBALS['cfg']['enable_feature_search'] = 1;

	#
	# Toggle the use of Polymaps independent of whether a
	# user's browser supports SVG
	#

	$GLOBALS['cfg']['enable_feature_polymaps'] = 1;

	#
	# Magic words are a runtime display feature to hook in
	# to third-party APIs and services in order to display
	# dynamic content. You can read more about then in the
	# FAQ: http://your-dotspotting.example.com/faq/#magicwords
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
	# As in: Where your installation of Dotspotting lives
	# on the Internets
	#

	$GLOBALS['cfg']['abs_root_url'] = 'READ-FROM-CONFIG';
	$GLOBALS['cfg']['safe_abs_root_url'] = $GLOBALS['cfg']['abs_root_url'];

	#
	# Basic cookie stuff
	# 

	$GLOBALS['cfg']['auth_cookie_domain'] = parse_url($GLOBALS['cfg']['abs_root_url'], 1);
	$GLOBALS['cfg']['auth_cookie_name'] = 'a';

	$GLOBALS['cfg']['crypto_cookie_secret'] = 'READ-FROM-CONFIG';
	$GLOBALS['cfg']['crypto_password_secret'] = 'READ-FROM-CONFIG';

	#
	# If you need to ensure that people don't flood your installation
	# with dots, ensure that this has a value > 0. This is read in
	# lib_import.
	#

	$GLOBALS['cfg']['import_max_records'] = 1000;

	#
	# Pagination hooks for things like displaying lists of dots
	# or other search results. If 'pagination_spill' is defined
	# and the value of the last paginated set is less than 'spill'
	# those records will be included on the second to last page.
	#

	$GLOBALS['cfg']['pagination_per_page'] = 25;
	$GLOBALS['cfg']['pagination_spill'] = 5;

Database Stuff
--

	#
	# You will need at least one MySQL database in
	# order to run Dotspotting. Details go here.
	#

	$GLOBALS['cfg']['db_main'] = array(
		'host'	=> 'READ-FROM-CONFIG',
		'user'	=> 'READ-FROM-CONFIG',
		'pass'	=> 'READ-FROM-CONFIG',
		'name'	=> 'dotspotting',	# as in the name of your database
		'auto_connect' => 1,
	);

	#
	# See these? They are the magic flags that allow
	# Dotspotting to run on a single database without
	# any extra fussing. If your installation of
	# Dotspotting ever outgrows this setup you will
	# need to add complete config blocks for the following
	# database clusters: 'db_main_slaves', 'db_users'
	# and 'db_tickets'
	#

	$GLOBALS['cfg']['db_enable_poormans_slaves'] = 1;
	$GLOBALS['cfg']['db_enable_poormans_ticketing'] = 1;
	$GLOBALS['cfg']['db_enable_poormans_federation'] = 1;

Templates
--

	#
	# Basically just where Smarty can read/write its
	# templates. Remember that the 'smarty_compile_dir'
	# needs to be able to written to by your web server.
	#

	$GLOBALS['cfg']['smarty_template_dir'] = DOTSPOTTING_WWW_DIR . '/templates';
	$GLOBALS['cfg']['smarty_compile_dir'] = DOTSPOTTING_WWW_DIR . '/templates_c';
	$GLOBALS['cfg']['smarty_compile'] = 1;

Email
--

	#
	# Stuff used to fill in headers when sending email from
	# lib_email.php (in Flamework). Note that installing and
	# setting up an email server is not part of Dotspotting's
	# scope.
	#

	$GLOBALS['cfg']['email_from_name']	= 'READ-FROM-CONFIG';
	$GLOBALS['cfg']['email_from_email']	= 'READ-FROM-CONFIG';
	$GLOBALS['cfg']['auto_email_args']	= 'READ-FROM-CONFIG';

Third Party API keys
--

	#
	# Pretty much what it sounds like. A Flickr API key is required
	# for Flickr "magic words" support (at least until Flickr fixes
	# its Oembed endpoint).
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