#!/bin/sh

export DEBIAN_FRONTEND=noninteractive

OPTS='-y -q=2 --force-yes'
INSTALL='apt-get '${OPTS}' install'

#
# I have no idea why this is sometimes necessary
# It's really annoying...
#

FIX_DPKG='dpkg --configure -a'

${INSTALL} memcached
${FIX_DPKG}

mv /etc/defaults/memcached /etc/defaults/memcached.dist
echo 'ENABLE_MEMCACHED=yes' > /etc/defaults/memcached
/etc/init.d/memcached start

${INSTALL} php5-memcache

/etc/init.d/apache2 restart