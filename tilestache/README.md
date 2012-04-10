TileStache
--

Gunicorn
--

The simplest example
--

The simple example for running TileStache under a Gunicorn server looks like this:

	$> gunicorn "TileStache:WSGITileServer('tilestache.cfg')"
	
	$> curl -v http://127.0.0.1:8000/dithered/16/19370/23450.jpg > test.png

Basically you're telling Gunicorn to load the `WSGITileServer` in the TileStache python libraries which, in turn, will look for a (JSON) configuration file called `tilestache.cfg`. [An example tilestache.cfg file](https://github.com/straup/buildingequalsyes/blob/master/tilestache/tilestache.cfg.example) is included. 

By default Gunicorn will listen for requests on port 8000.

Next use curl (or your web browser) to request a tile from the `dithered` map provider. It should look the same as [example-tile.jpg](https://github.com/straup/buildingequalsyes/blob/master/tilestache/tilestache.cfg.example).

That's it!

buildingequalyes map providers
--


