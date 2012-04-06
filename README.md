building=yes
==

Four pieces:

* The raw data and the import process

* The datastore

* The website / application itself

* The map tiles

Importing the data
--

First, you grab a copy of the planet.xml file from OpenStreetMap and extract the buildings:

	planet-latest.osm -> extract-buildings.sh -> buildings.osm
	
Second, you extract all the nodes and ways in to a SQLite database:

	buildings.osm -> osm2sqlite.py -> buildings.db

Third, you reverse geocode all the ways (by first calculating their centroid):

	buildings.db -> locatify-sqlite.py -> buildings.db
	
Finally, you import all the data in to Solr:

	buildings.db -> sqlite2solr.py -> PROFIT!

The website (Apache + PHP + MySQL)
--

The website is built on top of [Flamework](). That means the core is nothing
more than a vanilla Apache + PHP (+ MySQL) application that can be run out of a
user's home directory or a top-level domain.

As of this writing the MySQL piece is entirely optional since it is only used
for account management and editing buildings via OpenStreetMap neither of which
are enabled (or stable yet).

The search-y bits (Solr)
--

buildignequalsyes uses the [Solr]() document index as its primary data
store. That means the PHP code (above) needs to be able to connect to the
designated Solr port, typically on localhost (read: the same machine).

See the [solr/README.md]() document for details.

The map tiles (TileStache)
--

Map tiles are generated and served using [TileStache](). I run TileStache under
the [gunicorn]() server framework-thing-y because I like it and its stable but
there are others.

See the [tilestache/README.md]() document for details.
