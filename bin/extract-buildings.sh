#!/bin/sh

/usr/local/osm/osmfilter planet-latest.osm --keep= --keep-ways=building= --drop-relations -o=buildings.osm
