Dotspotting
==

First things first: THIS SHOULD NOT BE CONSIDERED PRODUCTION-READY CODE YET.

That said, it works. It probably doesn't work everywhere and there are almost certainly bugs left to be found but more importantly it is still at a stage where the code and the architecture needs to be able to change without necessarily ensuring backwards compatibility.

Per the terms of the Knight News Challenge Grant that is funding the development of Dotspotting we are committed to working in public so this is the first step in that process. Once a stable baseline for the code and the architecture has been established Dotspotting will follow the conventions established for versioning and changes proposed in the Semantic Versioning specification ([http://semver.org/](http://semver.org/ "Semantic Versioning")).

Right now, the version number for Dotspotting is: "Super Alpha-Beta Disco-Ball".

Dependencies
--

* Apache 2.x (with mod_rewrite enabled)
* MySQL 5.x
* PHP 5.x (with mysql and mycrypt support)
* Flamework (discussed below)

Installation Instructions
--

1. `git clone git@github.com:Citytracking/dotspotting.git`
2. `git submodule init`
3. `git submodule update`
4. In the `config` directory, copy `dotspotting.php.example` to `dotspotting.php` and adjust the values to suit your configuration.
5. Ensure that the `www/templates_c` directory can be written to by your web server.
6. Enable mod_rewrite in your local Apache.

Flamework (?!)
--

Dotspotting does not so much piggyback on a traditional framework as it does hold hands with an anti-framework called "Flamework". Flamework is the mythical PHP framework developed and used by the engineering team at Flickr. It is gradually being rewritten, from scratch, as an open-source project by former Flickr engineers.

If you just want to run Dotspotting that's really all you need to know, now.  If you want to get a better understanding of what's going on under the hood and to glean the relationship between Dotspotting and Flamework you should look at the [README.FLAMEWORK.md](http://github.com/citytracking/dotspotting/blob/master/README.FLAMEWORK.md) document.

Configuring Dotspotting
--

Here's what's actually happening when a page on Dotspotting is loaded:

	include(DOTSPOTTING_FLAMEWORK_DIR . '/include/config.php');
	include(DOTSPOTTING_WWW_DIR."/include/config.php");
	include(DOTSPOTTING_CONFIG_DIR . '/dotspotting.php');

1. Load the default Flamework config file
2. Load the default Dotspotting config file
3. Load the config file specific to *your* installation of Dotspotting.

The important thing to note here is that each file may override values defined in the previous config file. We are hoping that in most cases the only one of those three files you'll need to worry about is the last one ([dotspotting.php](http://github.com/Citytracking/dotspotting/blob/master/config/dotspotting.php.example)) but both Flamework and Dotspotting have a lot of knobs and this is where they can be tweaked.

... consult the [README.CONFIG.md](http://github.com/citytracking/dotspotting/blob/master/README.CONFIG.md) document.

Known Knowns
--

Dotspotting has proven to be fussy and problematic installing using the default OS X Apache + PHP binaries. We're not sure why but are continuing to poke at the problem. It works fine using tools like MAMP, though.

Dotspotting has not been tested on Windows yet.
