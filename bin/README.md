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

Reverse geocoding
--

https://github.com/straup/reverse-geoplanet
