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

Caveats
--

I am almost 100% certain there are better ways to do this. Since launching the site I have not been able to revisit the import process. What you see here are the results of me cobbling a lot of different pieces together just in order to get things working. In many instances speed (and elegance) was sacrificed in the goal of the finished results, namely whether the final data was interesting enough to build a website around.

Patches and suggestions are definitely welcome.

Extracting buildings from OSM
--

You'll need to download a copy of the `planet.xml` file from the OpenStreetMap servers and decompress it. That means you'll need approximately 350-400 GB of diskspace (20 for the compressed data; about 300 for the uncompressed data; and the remainder for the buildings and padding). This isn't ideal but the `osmfilter` application isn't set up to deal with reading data from STDIN (or I am very dumb and would welcome a patch).

Like this:

	$> wget http://planet.openstreetmap.org/planet-latest.osm.bz2

	$> bunzip2 planet-latest.osm.bz2
	
Next you'll need to grab and compile the [osmfilter](https://wiki.openstreetmap.org/wiki/Osmfilter) application:

	$> wget -O - http://m.m.i24.cc/osmfilter.c | cc -x c - -O3 -o osmfilter
	
Finally, to extract all the buildings:

	$> osmfilter planet-latest.osm --keep= --keep-ways=building= --drop-relations -o=buildings.osm

Prepping the data (SQLite)
--

Now you're going to move all that data in to a SQLite database because it just makes it easier to do all the remaining work:

	$> python osm2sqlite.py buildings.osm

This will create a new file called `buildings.osm.db`. It will be large.

Prepping the data (reverse geocoding)
--

This part is a little involved. That's the bad news. The good news is that it's not actually hard. Here's what going on:

* The code is going to plow through all the nodes (points) and ways (collections of points that form buildings) in the SQLite database, calculate the centroid and then ask Flickr to reverse geocode them: To convert the latitude and longitude coordinates in to a unique place ID for that building.

* Rather than asking the Flickr servers over and over (and over) the code will look for an instance of a `reverse-geoplanet` server running that will proxy and cache the results of those reverse geocoding requests. In addition there is a shared library for talking to a reverse-geoplanet server that will create a local cache of those results so all those lookups can be a little faster. There are _a lot_ of buildings in OSM so every little bit helps.

You'll need to grab [a copy of the reverse-geoplanet](https://github.com/straup/reverse-geoplanet) code from Github and then follow the [installation instructions](https://github.com/straup/reverse-geoplanet/blob/master/INSTALL.md) to get started. Like b=y itself the reverse-geoplanet server is a plain vanilla Flamework application and its only dependencies are Apache and PHP and MySQL.	

I've included the libraries you'll need to talk to a reverse-geoplanet server in to this repo so all you'll need to do is include its URL when you run the `reverse-geocode.py` script. Like this:

	$> python reverse-geocode.py buildings.osm.db http://example.com/reverse-geoplanet/www/
	
Importing the data
--

Honestly, this one probably still has some bugs or gotchas in it. On the other hand it's not very complicated. Essentially you're just copying all the `ways` in the SQLite database in to Solr and generating a new unique 64-bit ID along the way.

	$> python sqlite2solr.py


