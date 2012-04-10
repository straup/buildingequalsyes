The broad strokes
--

First, you grab a copy of the planet.xml file from OpenStreetMap and extract the buildings:

	planet-latest.osm -> extract-buildings.sh -> buildings.osm
	
Second, you extract all the nodes and ways in to a SQLite database:

	buildings.osm -> osm2sqlite.py -> buildings.db

Third, you reverse geocode all the ways (by first calculating their centroid):

	buildings.db -> locatify-sqlite.py -> buildings.db
	
Finally, you import all the data in to Solr:

	buildings.db -> sqlite2solr.py -> PROFIT!

Each one of these steps is slow in their own way so take the time to read through this entire document before you start.

Extracting buildings from OSM
--

You'll need to download a copy of the `planet.xml` file from the OpenStreetMap servers and decompress it. That means you'll need approximately 350-400 GB of diskspace (20 for the compressed data; about 300 for the uncompressed data; and the remainder for the buildings and padding). This isn't ideal but the `osmfilter` application isn't set up to deal with reading data from STDIN (or I am very dumb and would welcome a patch).

Like this:

	$> wget http://planet.openstreetmap.org/planet-latest.osm.bz2

	$> bunzip2 planet-latest.osm.bz2
	
Next you'll need to grab and compile the [osmfilter](https://wiki.openstreetmap.org/wiki/Osmfilter):

	$> wget -O - http://m.m.i24.cc/osmfilter.c |cc -x c - -O3 -o osmfilter
	
Finally, to extract all the buildings:

	$> osmfilter planet-latest.osm --keep= --keep-ways=building= --drop-relations -o=buildings.osm

Prepping the data (SQLite)
--

Now you're going to move all that data in to a SQLite database because it just makes it easier to do all the remaining work:

	$> python osm2sqlite.py buildings.osm

Prepping the data (reverse geocoding)
--

	https://github.com/straup/reverse-geoplanet

Importing the data
--

Honestly, this one probably still has some bugs or gotchas in it. On the other hand it's not very complicated. Essentially you're just copying all the `ways` in the SQLite database in to Solr and generating a new unique 64-bit ID along the way.

	$> python sqlite2solr.py

