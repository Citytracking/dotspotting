DotSpotting
==

First things first: THIS SHOULD NOT BE CONSIDERED PRODUCTION-READY CODE YET.

That said, it works. It probably doesn't work everywhere and there are almost certainly bugs left to be found but more importantly it is still at a stage where the code and the architecture needs to be able to change without necessarily ensuring backwards compatibility.

Per the terms of the Knight News Challenge Grant that is funding the development of Dotspotting we are committed to working in public so this is the first step in that process. Once a stable baseline for the code and the architecture has been established Dotspotting will follow the conventions established for versioning and changes proposed in the Semantic Versioning specification (http://semver.org/). Right now, the version number for Dotspotting is: "Super Alpha-Beta Disco-Ball".

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

Dotspotting does not so much piggyback on a traditional framework as it does hold hands with an anti-framework called "flamework". 

Making Changes to Flamework
--

Dotspotting builds on top of Flamework. We use our own fork of Flamework, but pull and push changes to the main repository as appropriate. For the time being, our fork of Flamework sits in /ext/flamework as a submodule (hence the submodule lines in the installation instructions).

Sometimes you might have to make some changes to Flamework. Making changes to git submodules can be a bit strange, so let's explain:

1. Change into the `/ext/flamework` directory.
2. Submodules aren't attached to any branch - they just point to a specific revision - so we need to change onto a branch to stage our commits. `git checkout -b master`
3. Commit your changes.
4. Push them up to Github - make sure you're doing this from the /ext/flamework directory.
5. Now we need to point the Dotspotting to the new revision of the Flamework submodule that we just committed. Change back into the root Dotspotting directory and commit the change to /ext/flamework that you see.
6. Push that up to master.
7. Now everyone is happy and no-one has any detached heads.

Known Knowns
--

Dotspotting has proven to be fussy and problematic installing using the default OS X Apache + PHP binaries. We're not sure why but are continuing to poke at the problem. It works fine using tools like MAMP, though.

Dotspotting has not been tested on Windows yet.