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

# this assumes you've already installed git-core because
# otherwise you wouldn't be reading this...

${INSTALL} sysstat
${INSTALL} htop

${INSTALL} git-core

${INSTALL} apache2
${INSTALL} memcached
${INSTALL} mysql-server

${INSTALL} php5
${INSTALL} php5-mysql
${INSTALL} php5-curl
${INSTALL} php5-mcrypt

${INSTALL} python-gevent

${INSTALL} python_setuptools
${INSTALL} python-pyproj

${INSTALL} gdal-bin
${INSTALL} libmapnik-dev
${INSTALL} python-mapnik

${EASY_INSTALL} ModestMaps
${EASY_INSTALL} TileStache
${EASY_INSTALL} gunicorn
${EASY_INSTALL} shapely
${EASY_INSTALL} geojson
${EASY_INSTALL} Geohash

${INSTALL} openjdk-6-jre-headless
${FIX_DPKG}

ln -s  /etc/apache2/mods-available/rewrite.load /etc/apache2/mods-enabled/
/etc/init.d/apache2 restart
