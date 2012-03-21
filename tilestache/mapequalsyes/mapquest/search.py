# This is a snapshot from June 2011 of:
# https://github.com/straup/py-mapquest

import sys
import urllib
import urllib2
import json

class bbox:

    def __init__ (self, apikey):
        self.endpoint = endpoint = "http://www.mapquestapi.com/search/v1/rectangle"
        self.apikey = apikey

    def restaurants (self, bbox):
        q = "MQA.NTPois,T='3016',I,N,Food"
        return self.hosted_data(bbox, q)

    # "hostedData" : "MQA.GeoTownsPois,PopCat='1',I,N,PopCat",

    def hosted_data(self, bbox, args):

        args = {
            "boundingBox" : bbox,
            "hostedData" : args,
            "outFormat" : "json",
            "maxMatches" : 500,
            }

        url = self.endpoint + "?" + urllib.urlencode(args) + "&key=" + self.apikey

        rsp = urllib2.urlopen(url)
        data = json.load(rsp)

        if data['info']['statuscode'] != 0:
            raise Exception, data['info']

        features = []

        count = int(data['resultsCount'])

        if count > 0 :

            for r in data['searchResults']:

                lat, lon = r['shapePoints']
                del(r['shapePoints'])

                coords = (float(lon), float(lat))

                properties = r['fields']

                for w in ('distance', 'distanceUnit', 'key'):
                    properties[w] = r[w]

                    features.append({
                            'type' : 'Point',
                            'coordinates' : coords,
                            'properties' : properties,
                            })

        return {
            'type' : 'FeatureCollection',
            'features' : features
            }

if __name__ == '__main__' :

    apikey = sys.argv[1]
    bounds = sys.argv[2:6]

    mq = bbox(apikey)
    rsp = mq.restaurants(",".join(map(str, bounds)))

    print json.dumps(rsp, indent=2)
