#!/usr/bin/env python

import pysolr
import reversegeo
import os.path
import sqlite3
import sys
import json
import Geohash
import re
import geojson

import shapely.wkt
from shapely.geometry import Polygon
from shapely.geometry import LineString

geo = reversegeo.reversegeo('localhost')

solr = pysolr.Solr('http://localhost:9999/solr/buildings')
solr.delete(q='*:*')

dbconn = sqlite3.connect('buildings.osm.db')
dbcurs = dbconn.cursor()

rgconn = sqlite3.connect('reversegeo.db')
rgcurs = rgconn.cursor()

last_woeid = 2147483647
uid = last_woeid

count = 26161986
offset = 0
limit = 10000

counter = 0

docs = []

# counter = offset
# uid = 2150733647

while offset < count :

    sql = "SELECT * FROM ways LIMIT %s, %s" % (offset, limit)

    print "%s (%s)" % (sql, count)

    dbcurs.execute(sql)

    for row in dbcurs.fetchall():

        counter += 1

        uid = uid + 1

        way_id, lat, lon, woeid, nodes, tags = row

        if not lat or not lon:
            continue

        if float(lat) < -90. or float(lat) > 90.:
            continue

        if float(lon) < -180. or float(lon) > 180.:
            continue

        if not woeid:
            woeid = 0

        nodes = nodes.split(',')

        points = []
        poly = None
        center = None

        alltags = {}
        name = None

        tags = json.loads(tags)

        if tags.get('name', False):
            name = tags['name']

        for node_id in nodes:
                
            dbcurs.execute("SELECT * FROM nodes WHERE id=?", (node_id, ))
            node = dbcurs.fetchone()

            points.append((node[2], node[1]))

            try:
                _tags = json.loads(node[3])
                for k,v in _tags.items():
                    alltags[k] = v
            except Exception, e:
                pass

        # TO DO: fix me (define line)

        if len(points) == 2:
            line = LineString(points)
            poly = line.centroid
            center = line.centroid
        else :
            points.append(points[0])
            poly = Polygon(points)
            center = poly.centroid

        # TO DO : trim decimal coordinates

        if poly:
            # poly = shapely.wkt.dumps(poly)
            poly = geojson.dumps(poly)

        if center :
            lat = center.y
            lon = center.x

        """    
        lat = "%.6f" % lat
        lon = "%.6f" % lon

        lat = float(lat)
        lon = float(lon)
        """

        # guh...

        short_lat = float("%.3f" % lat)
        short_lon = float("%.3f" % lon)

        gh = Geohash.encode(short_lat, short_lon, 8)

        gh_sql = "SELECT country, region, locality, woeid FROM reversegeo WHERE geohash='%s'" % gh

        rgcurs.execute(gh_sql)
        _row = rgcurs.fetchone()

        # GAH...why are most of these missing?

        if _row:

            country, region, locality, _woeid = _row

            if country:
                tags['woe:country'] = country

            if region:
                tags['woe:region'] = region

            if locality:
                tags['woe:locality'] = locality

            if _woeid and _woeid not in (country, region, locality):
                tags['woe:neighbourhood'] = _woeid

        else:

            # print "calling cloud.spum...."

            print "[%s] re-reversegeocoding" % counter

            woe = geo.reverse_geocode(lat, lon)

            if woe:

                try:
                    rgcurs.execute("""INSERT INTO reversegeo (name, locality, woeid, region, created, longitude, placetype, geohash, country, latitude) VALUES (?,?,?,?,?,?,?,?,?,?)""", woe.values())
                    rgconn.commit()
                except Exception, e:
                    print "FUCK"

                _ids = []

                for pl in ('country', 'region', 'locality'):
                    if not woe.get(pl, False):
                        break
                    k = "woe:%s" % pl
                    tags[k] = woe[pl]

                    _ids.append(woe[pl])

                if woe['woeid'] not in _ids:
                    tags['woe:neighbourhood'] = woe['woeid']

        # tags

        for k,v in tags.items():
            alltags[k] = v

        if alltags.get('building') and alltags['building'] == 'yes':
            del(alltags['building'])

        _alltags = []

        for k,v in alltags.items():
            tmp = k.split(":")

            v = unicode(v)
            v = re.sub("8", "88", v)
            v = re.sub("/", "8s", v)
            v = re.sub(":", "8c", v)

            tmp.append(v)

            _alltags.append("/".join(map(unicode, tmp)))

        alltags = _alltags

        # go!

        lat = float("%.6f" % lat)
        lon = float("%.6f" % lon)

        #

        def stupid_floating_points(m):
            return m.group(1)

        poly = re.sub(r'(\.\d{6})\d+', stupid_floating_points, poly)

	#

        doc = {
                'id' : uid,
		'parent_woeid' : woeid,
                'way_id' : way_id,
                'nodes' : nodes,
                'centroid' : "%s,%s" % (lat,lon),
                }

        if poly != None :
            doc['polygon'] = poly

        if len(alltags):
            doc['tags'] = alltags

        if name != None:
            doc['name'] = name

        for k,v in doc.items():
            if v == None or v == '':
                print "WTF %s : %s" % (k, v)
                sys.exit()

        print "[%s] add doc" % counter
        docs.append(doc)

#        if doc.get('tags'):
#            print doc['tags']

    try:
        solr.add(docs)
    except Exception, e:
        fh = open('add.json', 'w')
        fh.write(json.dumps(docs, indent=2))
        fh.close()

        raise Exception, e

    docs = []

    offset += limit

if len(docs):
    solr.add(docs)
