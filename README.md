building=yes
==

This is the source code for the [building=yes](http://buildingequalsyes.spum.org/) (b=y) website.

The code is available as-is under a [BSD license](https://github.com/straup/buildingequalsyes/blob/master/LICENSE) on first principles and in the hopes that it can serve as an example or learning tool for other projects. Or maybe you just want to run a private copy of b=y. That's your business. Patches and suggestions are not only welcome but encouraged (particularly for the documentation).

_As of this writing this isn't actually the source code running the b=y site itself. There are a few remaining gotchas to work out but that's just a question of time at this point. This is the new new. (20120410/straup)_

How does it work
--

The site and the code is divided in to roughly four pieces:

* The raw data and the import process (a series of bespoke scripts)

* The datastore (Solr)

* The website / application itself (Apache + PHP)

* The map tiles (TileStache)

The data itself
--

As of this writing the data is imported by grabbing a complete copy of the [OpenStreetMap (OSM) "planet" XML file](https://wiki.openstreetmap.org/wiki/Planet.osm), extracting all the buildings and then further post-processing the remaining data to reverse-geocode buildings and store them in a Solr document index.

See the [bin/README.md](https://github.com/straup/buildingequalsyes/blob/master/bin/README.md) document for details.

_There is a short-term goal/plan of creating an self-updating version of the site that would pull daily (hourly?) changes from the OSM servers but that work has not been started yet._

The website (Apache + PHP + MySQL)
--

The website is built on top of [Flamework](https://github.com/exflickr/flamework). That means the core is nothing
more than a vanilla Apache + PHP (+ MySQL) application that can be run out of a
user's home directory or a top-level domain.

As of this writing the MySQL piece is entirely optional since it is only used
for account management and editing buildings via OpenStreetMap neither of which
are enabled (or stable yet).

See the [INSTALL.md](https://github.com/straup/buildingequalsyes/blob/master/INSTALL.md) document for details.

The datastore and the search-y bits (Solr)
--

buildingequalsyes uses the [Solr](https://lucene.apache.org/solr/) document index as its primary data
store. That means the PHP code (above) needs to be able to connect to the
designated Solr port, typically on localhost (read: the same machine).

See the [solr/README.md](https://github.com/straup/buildingequalsyes/blob/master/solr/README.md) document for details.

The map tiles (TileStache)
--

Map tiles are generated and served using [TileStache](http://www.tilestache.org/). I run TileStache under
the [gunicorn](http://www.gunicorn.org/) server framework-thing-y because I like it and its stable but
there are others.

See the [tilestache/README.md](https://github.com/straup/buildingequalsyes/blob/master/tilestache/README.md) document for details.

Other stuff
--

As you see there are quite a lot of moving pieces and software. There is not a magic-pony tool for setting up everything but there is an example installation script that can be consulted: It is called suprisingly enough [ubuntu/install.sh](https://github.com/straup/buildingequalsyes/blob/master/ubuntu/install.sh)

Although it is specific to Ubuntu-flavoured Linux distributions (and the `apt-*` package management tools) it does contain a list of all the various software packages that you'll need to have installed on any machines running b=y.
