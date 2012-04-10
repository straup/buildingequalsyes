TileStache (and Gunicorn)
--

[TileStache](http://www.tilestache.org/) is a "Python-based server application that can serve up map tiles based on rendered geographic data."

[Gunicorn](http://gunicorn.org/) is a "Python WSGI HTTP Server for UNIX. It's a pre-fork worker model ported from Ruby's Unicorn project."

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

buildingequalyes map providers
--


