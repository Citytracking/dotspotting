#!/bin/sh

IMA=$1

# http://snowulf.com/archives/540-Truly-non-interactive-unattended-apt-get-install.html
export DEBIAN_FRONTEND=noninteractive

OPTS='-y -q=2 --force-yes'
INSTALL='apt-get '${OPTS}' install'

# I have no idea why this is sometimes necessary
# It's really annoying...
FIX_DPKG='dpkg --configure -a'

apt-get update
apt-get ${OPTS} upgrade

# "this is what we do at etsy, and what we did at flickr
# for a basic webserver" (allspaw/20100518)

sysctl -w kernel.panic=1
sysctl -w kernel.shmmax=2147483648
sysctl -w net.ipv4.conf.eth0.rp_filter=0
sysctl -w net.ipv4.tcp_fin_timeout=30
sysctl -w net.ipv4.tcp_retrans_collapse=0
sysctl -w net.ipv4.tcp_syncookies=1
sysctl -w net.ipv4.tcp_tw_recycle=1
sysctl -w vm.overcommit_ratio=90
sysctl -w vm.overcommit_memory=2
sysctl -w kernel.core_uses_pid=1
sysctl -w net.core.rmem_max=16777216
sysctl -w net.core.wmem_max=16777216
sysctl -w net.ipv4.tcp_rmem='4096 87380 16777216'
sysctl -w net.ipv4.tcp_wmem='4096 65536 16777216'
sysctl -w net.ipv4.tcp_timestamps=0
sysctl -w net.core.netdev_max_backlog=2500

${INSTALL} tcsh
${INSTALL} bc
${INSTALL} git-core
${FIX_DPKG}

${INSTALL} emacs23-nox
${FIX_DPKG}

${INSTALL} vim

${INSTALL} ganglia-monitor
${FIX_DPKG}

${INSTALL} unzip

${INSTALL} htop
${INSTALL} sysstat

#

${INSTALL} apache2

ln -s /etc/apache2/mods-available/proxy.conf /etc/apache2/mods-enabled/
ln -s /etc/apache2/mods-available/proxy.load /etc/apache2/mods-enabled/
ln -s /etc/apache2/mods-available/proxy_http.load /etc/apache2/mods-enabled/
ln -s /etc/apache2/mods-available/cache.load /etc/apache2/mods-enabled/
ln -s /etc/apache2/mods-available/disk_cache.conf /etc/apache2/mods-enabled/
ln -s /etc/apache2/mods-available/disk_cache.load /etc/apache2/mods-enabled/

/etc/init.d/apache2 restart

# ${INSTALL} squid

# mv /var/spool/squid /mnt/var-spool-squid
# ln -s /mnt/var-spool-squid /var/spool/squid
# mv /var/log/squid /mnt/var-log-squid
# ln -s /mnt/var-log-squid /var/log/squid

# Root and www users needs to be configured manually

${INSTALL} memcached
${FIX_DPKG}

mv /etc/defaults/memcached /etc/defaults/memcached.dist
echo 'ENABLE_MEMCACHED=yes' > /etc/defaults/memcached
/etc/init.d/memcached start

${INSTALL} php5
${INSTALL} php5-memcache

${INSTALL} php-pear

# http://pear.php.net/package/DB read:
# "This package has been superseded, but is still maintained for bugs
# and security fixes. Use MDB2 instead." See also:
# http://www.phpied.com/db-2-mdb2/

${INSTALL} php-mdb2
${INSTALL} php-mdb2-driver-mysql

${INSTALL} php-net-url
${INSTALL} php-http-request
${INSTALL} php-mcrypt
