#!/bin/sh

IMA=$1

#
# http://snowulf.com/archives/540-Truly-non-interactive-unattended-apt-get-install.html
#

export DEBIAN_FRONTEND=noninteractive

OPTS='-y -q=2 --force-yes'
INSTALL='apt-get '${OPTS}' install'

#
# I have no idea why this is sometimes necessary
# It's really annoying...
#

FIX_DPKG='dpkg --configure -a'

#
# First deal with any pending updates
# 

apt-get update
apt-get ${OPTS} upgrade

#
# "this is what we do at Etsy, and what we did at Flickr
# for a basic webserver" (Allspaw/20100518)
#

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

# ${INSTALL} tcsh
# ${INSTALL} vim
# ${INSTALL} emacs23-nox
# ${FIX_DPKG}

${INSTALL} git-core
${FIX_DPKG}

${INSTALL} ganglia-monitor
${FIX_DPKG}

${INSTALL} htop
${INSTALL} sysstat

${INSTALL} mysql-server

${INSTALL} apache2

ln -s /etc/apache2/mods-available/proxy.conf /etc/apache2/mods-enabled/
ln -s /etc/apache2/mods-available/proxy.load /etc/apache2/mods-enabled/
ln -s /etc/apache2/mods-available/proxy_http.load /etc/apache2/mods-enabled/
# ln -s /etc/apache2/mods-available/cache.load /etc/apache2/mods-enabled/
# ln -s /etc/apache2/mods-available/disk_cache.conf /etc/apache2/mods-enabled/
# ln -s /etc/apache2/mods-available/disk_cache.load /etc/apache2/mods-enabled/

/etc/init.d/apache2 restart

${INSTALL} php5
${INSTALL} php-pear
${INSTALL} php5-mysql
${INSTALL} php5-mcrypt
${INSTALL} php5-curl