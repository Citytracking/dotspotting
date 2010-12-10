The database model
--

**By default Dotspotting does not require that it be run under a fully-federated database system.** It takes advantage of Flamework's ability to run in "poor man's federated" mode which causes the database libraries to act as though there are multiple database clusters when there's only really one. Specifically, all the various databases are treated as though they live in the `db_main` cluster. The goal is to enable (and ensure) that when a given installation of Dotspotting outgrows a simple one or two machine setup that it can easily be migrated to a more robust system with a minimum of fuss.

Examples of both a fully federated and "poorman's" database setup are included below. These are the clusters that Flamework (and by extension Dotspotting) defines:

+ **db_main**

This is the database cluster where user accounts and other lookup-style database tables live.

+ **db_main_slave**

These are read-only versions of the `db_main` cluster that are updated using [MySQL replication](http://dev.mysql.com/doc/refman/5.0/en/replication.html).

+ **db_users**

These are the federated tables, sometimes called "shards". This is where the bulk of the data in Dotspotting is stored because it can be spread out, in smaller chunks, across a whole bunch of databases rather than a single monolithic monster database that becomes a single point of failure and it just generally a nuisance to maintain.

+ **db_tickets**

One of the things about storing federated user data is that from time to time you may need to "re-balance" your shards, for example moving all of a user's data from shard #5 to shard #23. That means you can no longer rely on an individual database to generate auto-incrementing unique IDs because each database shard creates those IDs in isolation and if you try to move a dot, for example, with ID `123` to a shard with another dot that already has the same ID everything will break and there will be tears.

The way around this is to use "ticketing" servers whose only job is to sit around and assign unique IDs. A discussion of ticketing servers is outside the scope of this document but [Kellan wrote a good blog post about the subject](http://code.flickr.com/blog/2010/02/08/ticket-servers-distributed-unique-primary-keys-on-the-cheap/) if you're interested in learning more. Which is a long way of saying: Flamework uses tickets and they come from the `db_tickets` cluster.

By default Dotspotting relies on a series of special config flags called `enable_feature_poormans_(SOME FEATURE)` that will trick Flamework in to treating a single database as many. Specifically, all the various databases are treated as though they live in the `db_main` cluster. The goal is to enable (and ensure) that when a given installation of Dotspotting outgrows a simple one or two machine setup that it can easily be migrated to a more robust system with a minimum of fuss.

Example Setup (simple)
--

	#
	# You will need at least one MySQL database in order to run Dotspotting. Details go here.
	#

	# If you haven't already, you'll need to set up each cluster with its corresponding database schema. Like this:
	# 
	# $> mysql -u example -h localhost -p dotspotting < schema/db_main.schema
	# $> mysql -u example -h localhost -p dotspotting < schema/db_users.schema
	# $> mysql -u example -h localhost -p dotspotting < schema/db_tickets.schema

	$GLOBALS['cfg']['db_main'] = array(
		'host'	=> 'main',
		'user'	=> 'example',
		'pass'	=> '******',
		'name'	=> 'dotspotting',
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

Example Setup (fancy)
--

**It is worth noting that Flamework is still not actually set up for multiple shards. Specifically, the code for hashing a user to more than a single shard hasn't been written yet. [Patches are welcome](https://github.com/Citytracking/dotspotting/blob/master/www/include/lib_users.php) :D**

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

	$GLOBALS['cfg']['db_enable_poormans_slaves'] = 0;
	$GLOBALS['cfg']['db_enable_poormans_ticketing'] = 0;
	$GLOBALS['cfg']['db_enable_poormans_federation'] = 0;

	# Next, configure the 'db_main' cluster. This looks exactly like your database configs for a not-federated setup.

	$GLOBALS['cfg']['db_main']['name'] = 'main';
	$GLOBALS['cfg']['db_main']['host'] = 'dotspotting-dbmain.null-islan.rds.amazonaws.com';
	$GLOBALS['cfg']['db_main']['user'] = 'example';
	$GLOBALS['cfg']['db_main']['pass'] = '******';

	# Now, add configs for your user shards. Note that there is only a single user shard (1) with a single database
	# tied to it. Over time you may want to configure your user shards to be "multi-master" but if you don't already
	# know that means, don't worry about it. This is all you need to do for now.

	$GLOBALS['cfg']['db_users']['name'] = array(
		1 => 'users',
	);

	$GLOBALS['cfg']['db_users']['host'] = array(
		1 => 'dotspotting-dbusers-1.null-island.rds.amazonaws.com',
	);

	$GLOBALS['cfg']['db_users']['user'] = 'example';
	$GLOBALS['cfg']['db_users']['pass'] = '******';

	# Finally set up your ticket servers. In this example, there's only one ticketing server but you may have two in
	# case the first one crashes or become unreliable. In either case, you'll need to make sure that your database(s)
	# are set up to increment by a value of 2 per the following:
	#
	# http://code.flickr.com/blog/2010/02/08/ticket-servers-distributed-unique-primary-keys-on-the-cheap/

	$GLOBALS['cfg']['db_tickets']['name'] = array(
		1 => 'tickets',
		1 => 'tickets',
	);

	$GLOBALS['cfg']['db_tickets']['host'] = array(
	 	1 => 'dotspotting-dbtickets-1.null-island.rds.amazonaws.com',
	 	2 => 'dotspotting-dbtickets-2.null-island.rds.amazonaws.com',
	);

	$GLOBALS['cfg']['db_tickets']['user'] = 'example';
	$GLOBALS['cfg']['db_tickets']['pass'] = '******';

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


