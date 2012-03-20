#!/usr/bin/env python

import pysolr
import json

import sys
woeid = sys.argv[1]

bldgdb = pysolr.Solr('http://localhost:8985/solr/buildings')

query="parent_woeid:%s OR tags:woe/*/%s" % (woeid, woeid)

# FIX ME: account for pagination...
rsp = bldgdb.search(q="parent_woeid:2463583 OR tags:woe/*/24635883", rows=950)

features = []

for d in rsp.docs:

    poly = json.loads(d['polygon'])

    feature = {
            'type' : 'Feature',
            'properties' : {
                'id' : d['id'],
                'parent_woeid' : d['parent_woeid'],
                'way_id' : d['way_id'],
                },
            'geometry' : poly
            }

    if d.get('name', False):
        feature['properties']['name'] = d['name']

    features.append(feature)


geojson = {
    'type' : 'FeatureCollection',
    'features': features
}

print json.dumps(geojson, indent=2)
