dotspotting.boot.sh
--

This is just a plain old shell script that you can run on an Amazon EC2 Ubuntu machine to install all the various dependencies required in order to run Dotspotting.

*Note: It does not clone Dotspotting from Github (.com) and you'll need to do that yourself.*

ec2-launch-example.cfg
--

This is an example configuration file for a tool I wrote, called `ec2-launch.py`, designed to spin up EC2 machines from the command line:

  [https://github.com/straup/aws-tools](https://github.com/straup/aws-tools)

There are many other similar tools, including [Chef](http://wiki.opscode.com/display/chef/) and [Puppet](http://www.puppetlabs.com/puppet/), but this is the one that I use. It is provided as-is.