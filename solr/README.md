Quick start
--

From inside the buildingequalsyes/solr directory type:

	java -Dsolr.solr.home=. -Dsolr.solr.cores=. -jar start.jar

This will start a Solr endpoint for buildingequalsyes on port 9999. You can query
it by typing:

	curl http://localhost:9999/solr/buildingequalsyes/select?q=*:*

There is also an `init.d` script for automating a lot of the boring details
around starting and stopping Solr:

[https://github.com/straup/buildingequalsyes/blob/master/solr/init.d/solr.sh](https://github.com/straup/buildingequalsyes/blob/master/solr/init.d/solr.sh)

What's going on here?
--

* _start.jar_ is the thing that starts Solr

* _start.jar_ is going to spin up a web server using Jetty on port 9999; you can
  change the port number in [buildingequalsyes/solr/etc/jetty.xml](https://github.com/straup/buildingequalsyes/blob/master/solr/etc/jetty.xml).

* _start.jar_ is going to look for a file called _solr.xml_ in the
  _solr.solr.home_ directory. Its presence will indicate that Solr is being run
  in "multicore" mode. If you don't know what that means don't worry other than
  to know that the _solr.xml_ file is where Solr will look for details about
  what to load next.

* See the _solr.solr.cores_ flag we're passing? That's going to be referenced
  from a bunch of files that have or are about to be loaded. Solr doesn't try to
  be overly clever about where to look for things so it's best just to be
  explicit.
  
* The _solr.xml_ file looks like this:

	&lt;?xml version="1.0" encoding="UTF-8" ?&gt;

	&lt;solr persistent="false"&gt;
		&lt;cores adminPath="/admin/cores"&gt;
			&lt;core name="buildingequalsyes" instanceDir="${solr.solr.cores}/buildingequalsyes" /&gt;
		&lt;/cores&gt;
	&lt;/solr&gt;

* If you look carefully you'll see there isn't a default _solr.xml_ file,
  because it is explicitly prevented from being checked in to git for security
  and privacy reasons. You will need to copy the
  [solr.xml.example](https://github.com/straup/buildingequalsyes/blob/master/solr/solr.xml.example)
  file instead.

* Then, _start.jar_ will look for a directory in
  ${solr.solr.cores}/buildingequalsyes called "conf" which contains a bunch of
  config files specific to the buildingequalsyes index (or "core"). There are two
  you care about right now: [schema.xml](https://github.com/straup/buildingequalsyes/blob/master/solr/buildingequalsyes/conf/solrconfig.xml) and [solrconfig.xml](https://github.com/straup/buildingequalsyes/blob/master/solr/buildingequalsyes/conf/solrconfig.xml).
  
* The first contains information about what gets indexed. The second contains
  information about how that index is stored and queried. It is also where you
  tell Solr _where_ to store the index on disk. By default that is:
  
	&lt;dataDir&gt;${solr.solr.cores}/buildingequalsyes/data&lt;/dataDir&gt;  

* _start.jar_ will launch Solr as a "foreground" application. If you want to run
  it as a proper "background" service take a look at the
  [init.d/solr.sh](https://github.com/straup/buildingequalsyes/blob/master/solr/init.d/solr.sh) file.

Important
--

Solr doesn't have any kind of built-in authorization or authentication model so
you should be careful not to run it on a port that is accessible to the public
Internet. If you do and a bad person discovers it they will be able to freely
read and write to your Solr database.

To do:
--

* A pretty fierce stop word list for indexing

See also:
--

* [Solr](https://lucene.apache.org/solr/)
