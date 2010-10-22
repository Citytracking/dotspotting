DotSpotting
==

Installation Instructions
--

1. `git clone git@github.com:Citytracking/dotspotting.git`
2. `git submodules init`
3. `git submodules update`
4. In the `secrets` directory, copy `dotspotting.php.example` to `dotspotting.php` and adjust the values to suit your configuration.
5. Ensure that the `www/templates_c` directory can be written to by your web server.

Known Knowns
--

Dotspotting has proven to be fussy and problematic installing using the default
OS X Apache + PHP binaries. We're not sure why but are continuing to poke at the
problem. It works fine using tools like MAMP, though.
