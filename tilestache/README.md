TileStache (and Gunicorn)
--

[TileStache](http://www.tilestache.org/) is a "Python-based server application that can serve up map tiles based on rendered geographic data." [Gunicorn](http://gunicorn.org/) is a "Python WSGI HTTP Server for UNIX. It's a pre-fork worker model ported from Ruby's Unicorn project."

Or: Gunicorn is the "Apache" for map tiles and TileStache is framework for generating those tiles. Technically you could run TileStache under Apache's `mod_python` bindings but I've had very bad luck doing that. Gunicorn has a few rough edges but as a tile server its actually pretty great.

If you're not familiar with either package you should spend some time looking around their respective websites. The only thing you should really understand is how the [Tilestache config file works](http://tilestache.org/doc/#configuring-tilestache) and that's pretty straightforward.

The simplest example
--

A simple example of running TileStache under a Gunicorn server looks like this:

	$> gunicorn "TileStache:WSGITileServer('tilestache.cfg')"
	
	$> curl -v http://127.0.0.1:8000/dithered/16/19370/23450.jpg > test.png

Basically you're telling Gunicorn to load the `WSGITileServer` in the TileStache python libraries which, in turn, will look for a (JSON) configuration file called `tilestache.cfg`. [An example tilestache.cfg file](https://github.com/straup/buildingequalsyes/blob/master/tilestache/tilestache.cfg.example) is included for poking at. 

Next use curl (or your web browser) to request a tile from the `dithered` map provider. It should look the same as [example-tile.jpg](https://github.com/straup/buildingequalsyes/blob/master/tilestache/example-tile.jpg).

That's it!

The details
--

By default Gunicorn will listen for requests on port 8000. Note: Because you will already be running Apache on port 80 you will need to set up your Gunicorn server accordingly. This might include:

* Running it as-is on another public port, like port 81.

* Running it behind a proxy server like nginx on another public port, again like port 81. [A simple nginx config file](https://github.com/straup/buildingequalsyes/blob/master/tilestache/nginx.conf.example) has been included for the curious.

* Running it behind a proxy using Apache itself either as virtual host or just a special path on the same host as your b=y instance.

In the example above Gunicorn is started with a single worker in the foreground. A sample [init.d](https://github.com/straup/buildingequalsyes/blob/master/tilestache/init.d/tilestache-gunicorn.sh) script for running TileStache/Gunicorn as an automated service. You'll need to update things like the path to your copy of b=y and the number of workers (this will depend on the hardware you're using) but otherwise it should Just Work (tm).

Map layers
--

The [example tilestache.cfg](https://github.com/straup/buildingequalsyes/blob/master/tilestache/tilestache.cfg.example) file contains five map "layers":

* **microsoft_aerial** - these are [proxied](http://tilestache.org/doc/TileStache.Providers.html#Proxy) aerial map tiles served up by Microsoft/Bing.

* **dithered** - these are the `microsoft_aerial` tiles run through a 1-bit "dithering" filter using the [atkinstache](http://straup.github.com/tilestache-atkinstache/) provider (which is included with this repo).

* **buildings** - these are tiles that contain nothing but buildings in OSM drawn with a white fill on a transparent background using the [map=yes](http://mapequalsyes.stamen.com/code/) provider (which is also included with this repo). It is important to understand/remember that this layer will reach out across the Internet and ask the MapQuest XAPI servers for data. For details, consult the [map=yes](http://mapequalsyes.stamen.com/) project.

* **buildings-outline** - like the `buildings` tiles these are buildings from OSM but drawn with no fill and a black outline on a transparent background. Combining the two layers is an obvious place for improvements but that means mucking about in the [tilestache-mapequalsyes](https://github.com/straup/tilestache-mapequalsyes) code base so it hasn't happened yet.

* **buildingequalyes** - these are the actual tiles used by the site and the TileStache [Composite](http://tilestache.org/doc/TileStache.Goodies.Providers.Composite.html) provider to layer the two `buildings` related tiles on top of the `dithered` tiles. If you look carefully you'll notice that the layer sandwich only occurs at street-ish level zooms.

These are just some example map layers. The important to realize is that if you get this all set you'll be running your own tile server! 

Finally
--

Remember to update the [buildingequalsyes.js](https://github.com/straup/buildingequalsyes/blob/master/www/javascript/buildingequalsyes.js#L56) file to point to your tiles.
