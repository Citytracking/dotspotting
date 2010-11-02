Flamework
--

*"Working on the crumbly edge of future-proofing." -- [Heather Champ](http://www.hchamp.com/)*

Dotspotting does not so much piggyback on a traditional framework as it does hold hands with an anti-framework called "Flamework".

Flamework is the mythical ("mythical") PHP framework developed and used by [the engineering team at Flickr](http://code.flickr.com). It is gradually being rewritten, from scratch, as an open-source project by [former Flickr engineers](http://github.com/exflickr). It is available to download and use on Github:

+ [http://github.com/exflickr/flamework](http://github.com/exflickr/flamework "Flamework")

If you've never watched [Cal Henderson](http://www.iamcal.com)'s "Why I Hate Django" presentation now is probably as good a time as any. It will help you understand a lot about why things were done they were at Flickr and why those of us who've left prefer to keep doing them that way:

+ [http://www.youtube.com/watch?v=i6Fr65PFqfk](http://www.youtube.com/watch?v=i6Fr65PFqfk "Why I Hate Django")

Flamework is not really a framework, at least not by most people's standards. All software development is basically pain management and Flamework assumes that the most important thing is *the speed with which the code running an application can be re-arranged, in order to adapt to circumstances*, even if it's at the cost of "doing things twice" or "repeating ourselves".

Dotspotting itself may eventually become a framework but today it is *not*.

Today, Dotspotting is a nascent application that is still trying to recognize, never mind understand, its boundaries. That means it's just too soon for for a unified database or object model and nothing is gained by having to fight against one all the time in order to adapt it to the needs of the application itself.

A complete Flamework reference is out of scope for this document but here's the short version:

**Flamework is basically two things:**

1. A set of common libraries and functions.
2. A series of social conventions for how code is arranged.

**Flamework also takes the following for granted:**

* It uses [Smarty](http://www.smarty.net "Smarty") for templating.
* It uses global variables. Not many of them but it also doesn't make a fuss about the idea of using them.
* It does not use objects or "protected" variables.
* It breaks it own rules occasionally and uses objects but only rarely and generally when they are defined by third-party libraries (like [Smarty](http://www.smarty.net/)).
* That ["normalized data is for sissies"](http://kottke.org/04/10/normalized-data).

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

1. Making sure you load `include/init.php`
2. The part where `init.php` handles authentication checking and assigns logged in users to the global `$cfg` variable (it also creates and assigns a global `$smarty` object)
3. The naming conventions for shared libraries, specifically: `lib_SOMETHING.php` which is imported as `loadlib("SOMETHING")`.
4. Functions defined in libraries are essentially "namespaced".

Page template names and all that other stuff is, ultimately, your business.

The database model
--

Flamework assumes a federated model with all the various user data spread across a series of databases, or "clusters". For each cluster there are a series of corresponding helper functions defined in `lib_db.php`. As of this writing the following clusters are defined:

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

Search
--

*Note: This is really specific to Dotspotting but the basic principle(s) apply equally to Flamework.*

Search is one of those things that's all tangled up in how your databases are set up, whether you are doing full-text or spatial queries.

The first release of Dotspotting is geared specifically towards MySQL because it is readily available on shared web-hosting services, easy to install on both the server and desktop and has a large community of users and documentation. One consequence of using MySQL is that full-text search is not awesome. One consequence of a federated data model is that it makes doing global search (search across all of your users) problematic enough as to be impossible. This is also not awesome.

What does this mean? It means that during the initial releases of Dotspotting:

1. There is no global full-text search.
2. There is only limited global spatial search, which is done using [Geohashes](http://en.wikipedia.org/wiki/Geohash) (stored in a lookup table on the `db_main` cluster).

Moving forward we imagine the code being written in such a way that it can support a limited number of additional databases or search engines, assuming they've been installed and configured by users, with little more effort than adding specific [configuration variables](http://github.com/citytracking/dotspotting/blob/master/README.CONFIG.md). *Before you start asking all the obvious questions, the answer is probably: We don't know yet but it seems like a good plan so we'll try to figure out a way to make it work.*

We're not actively working on this architecture yet but are thinking about it as we go, with an eye towards supporting the following:

+ **[MySQL](http://www.mysql.com/)**

This is the default and gets you dots and bounding box (and Geohash) queries. It's also really really fast.

+ **[Solr](http://lucene.apache.org/solr/)**

Solr is a open source document indexer written in Java and is principally used a full-text search engine but it can also be used to do spatial queries. Currently radial queries are only available by using a [third-party plugin](http://blog.jteam.nl/2009/08/03/geo-location-search-with-solr-and-lucene/) but spatial indexing for both points and polygons is being [actively developed](http://wiki.apache.org/solr/SpatialSearch) for the next release of Solr (1.5).

+ **[PostGIS](http://postgis.refractions.net/)**

PostGIS a "proper" spatial database that can do amazing things so it's a no-brainer in so far as Dotspotting is concerned. It is also not always the easiest tool to install and maintain and in many cases is probably overkill for the problems people are trying to use Dotspotting to solve which is why, for the time being, it is not the default choice.

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
