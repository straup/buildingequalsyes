#!/usr/bin/env python

import sys
import shapely
import sqlite3
import urllib2
import json
import time
import reversegeo

from shapely.geometry import Polygon
from shapely.geometry import LineString

def munge(path) :
        
    #

    rg = reversegeo.reversegeo()

    dbconn = sqlite3.connect(path)
    dbcurs = dbconn.cursor()

    dbcurs.execute("SELECT COUNT(id) AS count FROM ways")
    row = dbcurs.fetchone()
    count = row[0]

    offset = 0
    limit = 5000

    while offset < count :

        sql = "SELECT * FROM ways LIMIT %s, %s" % (offset, limit)

        print "%s (%s)" % (sql, count)

        dbcurs.execute(sql)

        for row in dbcurs.fetchall():

            way_id, lat, lon, woeid, nodes, tags = row

            if lat and lon:
                pass
                # continue

            if woeid > 0:
                continue

            nodes = nodes.split(',')
            points = []

            for node_id in nodes:
                
                dbcurs.execute("SELECT * FROM nodes WHERE id=?", (node_id, ))
                node = dbcurs.fetchone()

                points.append((node[2], node[1]))

            center = None

            if len(points) == 2:
                line = LineString(points)
                center = line.centroid
            else :

                points.append(points[0])

                poly = Polygon(points)
                center = poly.centroid

            if not center:
                print "no centroid for way %s" % way_id
                print poly
                continue

            lat = center.y
            lon = center.x

            woeid = 0

            geo = rg.reverse_geocode(lat, lon)

            if geo:
                woeid = geo['woeid']

            print "[%s] update %s lat: %s, lon: %s, woeid: %s" % (offset, way_id, lat, lon, woeid)

            dbcurs.execute("UPDATE ways SET lat=?, lon=?, woeid=? WHERE id=?", (lat, lon, woeid, way_id))
            dbconn.commit()

        time.sleep(2)
        offset += limit

    return


if __name__ == '__main__' :

    path = sys.argv[1]
    munge(path)
