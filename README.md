Dotspotting
==

First things first:** THIS IS NOT PRODUCTION-READY CODE YET.**

That said, it works. It probably doesn't work everywhere and there are almost certainly bugs left to be found but more importantly it is still at a stage where *the code and the architecture need to be able to change* without necessarily ensuring backwards compatibility.

There are a whole bunch of features slated for a "1.0" release that simply haven't even been started yet. These include: API endpoints, import and export support for multiple data formats, interactive maps not to mention install scripts and tools that don't require you to think about all the stuff below. In short: There are lots of things left to do.

But per the terms of the [Knight News Challenge Grant](http://content.stamen.com/we_got_a_knight_news_grant) that is funding the development of Dotspotting we are committed to working (and hopefully not failing too much) in public so this is the first step in that process.

*(We think this is a good thing, by the way :-)*

Once a stable baseline for the code and the architecture has been established Dotspotting will follow the conventions established for versioning and changes proposed in the Semantic Versioning specification ([http://semver.org/](http://semver.org/ "Semantic Versioning")).

Right now, the version number for Dotspotting is: **"Super Alpha-Beta Disco-Ball"**.

Also, if you haven't already read the [the introductory blog post about Dotspotting](http://content.stamen.com/working_on_the_knight_moves) you should do that now.

Dependencies
--

* Apache 2.x (with mod_rewrite enabled)
* MySQL 5.x
* PHP 5.x (with support for: curl; mbstring, mcrypt; mysql; GD)

(See ec2/dotspotting.boot.sh for an example set up, assuming a Ubuntu server)

Installation Instructions
--

1. Install and configure Apache, MySQL and PHP.
2. `git clone git@github.com:Citytracking/dotspotting.git`
3. cd `dotspotting`
4. Load the various `*.schema` files in the `schema` directory in to MySQL
5. In the `config` directory, copy `dotspotting.php.example` to `dotspotting.php` and adjust the values to suit your configuration. (see below)
6. Ensure that the `www/templates_c` directory can be written to by your web server.
7. Ensure that mod_rewrite is enabled in your local Apache configuration.

Configuring Dotspotting
--

Copy the Dotspotting `config/dotspotting.php.example` file ([this one](https://github.com/Citytracking/dotspotting/blob/master/config/dotspotting.php.example)) to `config/dotspotting.php` and adjust various site configs for your Dotspotting installation. The example file is heavily commented and all the various configs are grouped by things you MUST, SHOULD and MAY need to change.

The important thing to note here is the `config/dotspotting.php` overrides any values defined in the application's [default config file](https://github.com/Citytracking/dotspotting/blob/master/www/include/config.php).

For a complete list of Dotspotting-specific config options, you should consult the [README.CONFIG.md](http://github.com/citytracking/dotspotting/blob/master/README.CONFIG.md) document.

Flamework
--

Dotspotting does not so much piggyback on a traditional framework as it does hold hands with an anti-framework called "Flamework".

Flamework is the mythical ("mythical") PHP framework developed and used by [the engineering team at Flickr](http://code.flickr.com). It is gradually being rewritten, f om scratch, as an open-source project by [former Flickr engineers](http://github.com/exflickr).

**We use our own fork of Flamework**, but pull and push changes to the main repository as appropriate. The Citytracking fork of Flamework is then copied directly in to the Dotspotting include directory. Earlier versions of Dotspotting would load Flamework as [Git submodule](http://speirs.org/blog/2009/5/11/understanding-git-submodules.html) but the nuisance and hoop-jumping factor required to set this up eventually outweighed any imagined benefits. The plan is to continue to track changes on the exflickr branch of Flamework and update Dotspotting accordingly (and to do the same in the other direction as well).

If you just want to run Dotspotting that's really all you need to know, right now. If you want to get a better understanding of what's going on under the hood and to glean the relationship between Dotspotting and Flamework you should look at the [README.FLAMEWORK.md](http://github.com/citytracking/dotspotting/blob/master/README.FLAMEWORK.md) document.

Global Variables
--

[Flamework](https://github.com/exflickr/flamework) uses and assigns global PHP variables on the grounds that it's really just not that big a deal. A non-exhaustive list of global variables that Flameworks assigns is:

* *$GLOBALS['cfg']* This is a great big hash that contains all the various site and (logged in) user configs.

* *$GLOBALS['smarty']* A [Smarty](http://www.smarty.net/) templating object.

* *$GLOBALS['timings']* A hash used to store site performance metrics.

* *$GLOBALS['loaded_libs']* A hash used to store information about libraries that have been loaded.

* *$GLOBALS['local_cache']* A hash used to store locally cached data.

* *$GLOBALS['error']* A hash used to assign site errors to; this is also automagically assigned to a corresponding Smarty variable.

As of this writing, Dotspotting is migrating to a place where it will:

* Only use *$GLOBALS['cfg']* and *$GLOBALS['smarty']* in its code.

* Not assign any globals of its own.

(Other) Known Knowns
--

+ Database indexes and other optimizations are not even close to being considered "done".

+ The JavaScript is all over the place. None of it is properly encapsulated or minified yet. It seems a bit soon for those kinds of optimizations, still.

+ Dotspotting should Just Work (tm) when run out of a user's `public_html` folder but if you tell me there are bugs I won't be surprised.

+ Dotspotting has not been tested on Windows.
