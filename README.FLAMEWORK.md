Flamework
--

*"Working on the crumbly edge of future-proofing." -- Heather Champ*

Dotspotting does not so much piggyback on a traditional framework as it does hold hands with an anti-framework called "Flamework".

Flamework is the mythical PHP framework developed and used by the engineering team at Flickr. It is gradually being rewritten, from scratch, as an open-source project by former Flickr engineers. It is available to download and use on Github:

	[http://github.com/exflickr/flamework](http://github.com/exflickr/flamework "Flamework")

If you've never watched Cal Henderson's "Why I Hate Django" presentation now is probably as good a time as any. It will help you understand a lot about why things were done they were at Flickr and why those of us who've left prefer to keep doing them that way.

	[http://www.youtube.com/watch?v=i6Fr65PFqfk](http://www.youtube.com/watch?v=i6Fr65PFqfk "Why I Hate Django")

Flamework is not really a framework, at least not by today's standards. All software development is basically pain management and Flamework assumes that the most important thing is the *speed with which the code running an application can be re-arranged, in order to adapt to circumstances*, even if it's at the cost of "doing things twice" or "repeating ourselves".

Dotspotting itself may eventually become a framework but today it is *not*.

Today, Dotspotting is a nascent application that is still trying to recognize, never mind understand, its boundaries. That means it's just too soon for for a unified data and object model and nothing is gained by having to fight against one all the time in order to adapt it to the application itself.

A complete Flamework reference is out of scope for this document but here's the short version:

**Flamework is basically two things:**

1. A set of common libraries and functions.
2. A set of social conventions for how code is arranged

**Flamework also [WORDS]:**

* It uses Smarty for templating
* It uses global variables. Not many but it doesn't make a fuss about the idea of using them.
* It does not objects or "protected" variables.
* It breaks it own rules, occasionally and uses objects but only rarely and generally when they are defined by third-party libraries like Smarty.

**For all intents and purposes, Flamework *is* a model-view-controller (MVC) system:**

* There are shared libraries (the model)
* There are PHP files (the controller)
* There are templates (the view)

Here is a simple bare-bones example of how it all fits together:

	# lib_example.php

	<?php
		function example_foo(&$user){
			$max = ($user['id']) ? $user['id'] : 1000;
			return range(0, rand(0, $max));
		}
	?>

	# example.php
	#
	# note how we're importing lib_example.php (above)
	# and squirting everything out to page_example.txt (below)

	<?php>
		include("include/init.php");
		loadlib("example");
		$foo = example_foo($GLOBALS['cfg']['user']);
		$GLOBALS['smarty']->assign_by_ref("foo", example_foo());
		$GLOBALS['smarty']->display("page_example.txt");
		exit();
	</php>

	# page_example.txt

	{assign var="page_title" value="example page title"}
	{include file="inc_head.txt"}
	<p>{if $cfg.user.id}Hello, {$cfg.user.username|escape}!{else}Hello, stranger!{/if}</p>
	<p>foo is: {$foo|@join(",")|escape}</p>
	{include file="inc_foot.txt"}

The only "rules" here are:

1. Making sure you load "include/init.php"
2. The part where init.php handles authentication checking and assigns logged in users to the global 'cfg' variable (it also creates and assigns a global $smarty object)
3. The naming conventions for shared libraries, specifically: `lib_SOMETHING.php` which is imported as `loadlib("SOMETHING")`.

Page template names and all that other stuff is, ultimately, your business.

The database model
--

Flamework assumes a federated model with all the various user data spread across a series of databases.

By default Dotspotting relies on a series of special config flags (in Flamework) called `enable_feature_poormans_(SOME FEATURE)` that will trick Flamework in to treating a single database as many. The goal is to enable (and ensure) that when a given installation of Dotspotting grows beyond a [WORDS] that it can easily be migrated to a more robust system with a minimum of fuss.

1. db_main

2. db_users

These are federated tables, sometimes called "shards".

3. db_tickets

One of the things about storing federated user data is that from time to time you may need to "re-balance" your shards, for example moving all of a user's data from shard #5 to shard #23.

Search
--

Search is one of those things that's all tangled up in how your databases are set up, whether you are doing full-text or spatial queries.

The first release of Dotspotting is geared specifically towards MySQL because it is readily available ...

One consequence of using MySQL is that full-text search is not awesome. One consequence of a federated data model is that it makes doing global search (across all users) problematic enough as to be impossible. This is also not awesome.

What does this mean? It means that -- at least during the initial releases of Dotspotting:

1. There is no global full-text search.
2. There is only limited global spatial search, which is done using geohashes.

**MySQL**

This gets you dots and bounding box (and geohash) queries.

**Solr**

Solr is principally a search engine but it can also be used to do spatial queries. [WORDS]

 gets you "good enough" spatial queries, specifically radial queries using the [WORDS] plugin and whatever is being developed for the next release (1.5). It also, importantly,

**PostGIS**

PostGIS a proper spatial database that can do amazing things.

Configuring Flamework
--

Making Changes to Flamework
--

We use our own fork of Flamework, but pull and push changes to the main repository as appropriate. For the time being, our fork of Flamework sits in /ext/flamework as a submodule (hence the submodule lines in the installation instructions).

Sometimes you might have to make some changes to Flamework. Making changes to git submodules can be a bit strange, so let's explain:

1. Change into the `/ext/flamework` directory.
2. Submodules aren't attached to any branch - they just point to a specific revision - so we need to change onto a branch to stage our commits. `git checkout -b master`
3. Commit your changes.
4. Push them up to Github - make sure you're doing this from the /ext/flamework directory.
5. Now we need to point the Dotspotting to the new revision of the Flamework submodule that we just committed. Change back into the root Dotspotting directory and commit the change to /ext/flamework that you see.
6. Push that up to master.
7. Now everyone is happy and no-one has any detached heads.
