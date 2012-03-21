"""
Copyright (c) 2011 Stamen Design

 Permission is hereby granted, free of charge, to any person
 obtaining a copy of this software and associated documentation
 files (the "Software"), to deal in the Software without
 restriction, including without limitation the rights to use,
 copy, modify, merge, publish, distribute, sublicense, and/or sell
 copies of the Software, and to permit persons to whom the
 Software is furnished to do so, subject to the following
 conditions:

 The above copyright notice and this permission notice shall be
 included in all copies or substantial portions of the Software.

 THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
 EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES
 OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
 NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT
 HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY,
 WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING
 FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR
 OTHER DEALINGS IN THE SOFTWARE.
"""

# File under soon-to-future optimizations:
# - Use mapnik.Osm instead of mapnik.Ogr (for geojson).
#   This will require patching mapquest.xapi to return
#   raw data results instead of GeoJSON.

import mapquest.xapi
import xapi.utils
            
from TileStache.Core import KnownUnknown
from TileStache.Geography import getProjectionByName

import json
import Image
import mapnik

import os
import os.path
import tempfile

# All this stuff is here as an Easter egg so strictly
# speaking none of it is actually necessary. See below
# for details.

try:
    from psycopg2.extensions import adapt as _adapt
    from psycopg2 import connect as _connect
    from psycopg2.extras import RealDictCursor

    from copy import copy as _copy
    from shapely.wkb import loads as _loadshape
    from shapely.geometry import Polygon
    from shapely.geos import TopologicalError
    from binascii import unhexlify as _unhexlify
except Exception, e:
    pass

def shape2geometry(shape, projection, clip):

    if clip:
        try:
            shape = shape.intersection(clip)
        except TopologicalError:
            raise _InvisibleBike("Clipping shape resulted in a topological error")
        
        if shape.is_empty:
            raise _InvisibleBike("Clipping shape resulted in a null geometry")
    
    geom = shape.__geo_interface__
    
    if geom['type'] == 'Point':
        geom['coordinates'] = _p2p(geom['coordinates'], projection)
    
    elif geom['type'] in ('MultiPoint', 'LineString'):
        geom['coordinates'] = [_p2p(c, projection)
                               for c in geom['coordinates']]
    
    elif geom['type'] in ('MultiLineString', 'Polygon'):
        geom['coordinates'] = [[_p2p(c, projection)
                                for c in cs]
                               for cs in geom['coordinates']]
    
    elif geom['type'] == 'MultiPolygon':
        geom['coordinates'] = [[[_p2p(c, projection)
                                 for c in cs]
                                for cs in ccs]
                               for ccs in geom['coordinates']]
    
    return geom

class _Point:

    def __init__(self, x, y):
        self.x = x
        self.y = y

def row2feature(row, id_field, geometry_field):

    feature = {'type': 'Feature', 'properties': _copy(row)}
    geometry = feature['properties'].pop(geometry_field)
    feature['geometry'] = _loadshape(_unhexlify(geometry))
    feature['id'] = feature['properties'].pop(id_field)
    
    return feature

def _p2p(xy, projection):

    loc = projection.projLocation(_Point(*xy))
    return loc.lon, loc.lat

class _InvisibleBike(Exception): pass

class SaveableResponse:

    def __init__(self, data, width, height):
        self.data = data
        self.width = width
        self.height = height

    def save(self, out, format):

        if format not in ('PNG'):
            raise KnownUnknown('We only saves .png tiles, not "%s"' % format)

        if not self.data['has_stuff']:
            im = Image.new('RGBA', (self.width, self.height))
            im.save(out, 'PNG')
            return

        fh, tmpfile = tempfile.mkstemp('.json')

        os.write(fh, json.dumps(self.data['geojson']))
        os.close(fh)

        map = mapnik.Map(0, 0)
        map.srs = '+proj=merc +a=6378137 +b=6378137 +lat_ts=0.0 +lon_0=0.0 +x_0=0.0 +y_0=0 +k=1.0 +units=m +nadgrids=@null +no_defs'

        datasource = mapnik.Ogr(base=os.path.dirname(tmpfile), file=os.path.basename(tmpfile), layer='OGRGeoJSON') 
        os.unlink(tmpfile)

        lyr = mapnik.Layer('xapi_raster')
        lyr.srs = '+proj=latlong +datum=WGS84'
        lyr.datasource = datasource

        style = mapnik.Style()
        rule = mapnik.Rule()

        if self.data['filltype'] == 'line':
            fill = mapnik.Color(str(self.data['fill']))
            # TO DO: make me a config flag
            # rule.symbols.append(mapnik.LineSymbolizer(fill, 1.0))
            rule.symbols.append(mapnik.LineSymbolizer(fill, 3.0))
            style.rules.append(rule)
        else :
            fill = mapnik.Color(str(self.data['fill']))
            rule.symbols.append(mapnik.PolygonSymbolizer(fill))
            style.rules.append(rule)

        map.append_style('xapi_raster', style)

        lyr.styles.append('xapi_raster');
        map.layers.append(lyr)

        xmin, ymin, xmax, ymax = self.data['bbox']
        env = mapnik.Envelope(xmin, ymin, xmax, ymax)
            
        map.width = self.width
        map.height = self.height
        map.zoom_to_box(env)

        img = mapnik.Image(self.width, self.height)
        mapnik.render(map, img)
        
        img = Image.fromstring('RGBA', (self.width, self.height), img.tostring())
        img.save(out, 'PNG')

class Provider:

    def __init__(self, layer, type, query, **kwargs):

        self.mercator = getProjectionByName('spherical mercator')
        self.layer = layer
        self.type = type
        self.query = query
        self.table = kwargs.get('table', 'planet_osm_line')

        self.fill = kwargs.get('fill', '#FF0000')
        self.filltype = kwargs.get('filltype', 'polygon')

        self.datasource = kwargs.get('datasource', 'xapi')
        self.clipping = kwargs.get('clipping', False)

        self.xapi = None
        self.pgis = None

        # Hey look! You're reading the source code and have found an
        # Easter egg!! This is pretty much exactly what it looks like:
        # Instead of querying the Mapquest XAPI endpoint you can also
        # query a PostGIS database that has a copy of the rendering
        # database (the thing that osm2pgsql creates). Please note that
        # by querying a copy of the rendering database instead of a
        # tagging database that the results returned may be incomplete
        # and/or weird.

        if self.datasource == 'postgis':

            from psycopg2 import connect as _connect
            from psycopg2.extras import RealDictCursor

            self.pgis = _connect(kwargs.get('dbdsn', '')).cursor(cursor_factory=RealDictCursor)

        else:
            import mapquest.xapi
            self.xapi = mapquest.xapi.xapi()

    def getTypeByExtension(self, extension):

        if extension.lower() == 'png':
            return 'image/png', 'PNG'

        raise KnownUnknown('I only know how to make .json and .png tiles, not "%s"' % extension)

    def renderTile(self, width, height, srs, coord):

        nw = self.layer.projection.coordinateLocation(coord)
        se = self.layer.projection.coordinateLocation(coord.right().down())

        ul = self.mercator.locationProj(nw)
        lr = self.mercator.locationProj(se)

        clip = self.clipping and Polygon([(ul.x, ul.y), (lr.x, ul.y), (lr.x, lr.y), (ul.x, lr.y)]) or None

        # Hey look, it's a Geography Moment (tm) !
        # The first one is ymin, ymin...
        # The second one is xmin, ymin...

        bbox_wgs84 = [ se.lat, nw.lon, nw.lat, se.lon ]
        bbox_merc = [ ul.x, lr.y, lr.x, ul.y ]

        geojson = {
            'type': 'FeatureCollection',
            'features': []
            }

        count_stuff = 0
        has_stuff = None

        # See notes above inre: Easter eggs.

        if self.datasource == 'postgis':

            pg_where, pg_keys = xapi.utils.parse_query_for_pgsql(self.query)

            pg_condition = " OR ".join(pg_where)
            pg_condition = "(%s)" % pg_condition
                    
            pg_bbox = 'ST_SetSRID(ST_MakeBox2D(ST_MakePoint(%.6f, %.6f), ST_MakePoint(%.6f, %.6f)), 900913)' % (ul.x, ul.y, lr.x, lr.y)

            query = "SELECT * FROM \"%s\" WHERE %s AND ST_Intersects(!bbox!, way)" % (self.table, pg_condition)
            query = query.replace('!bbox!', pg_bbox)

            self.pgis.execute(query)

            for row in self.pgis.fetchall():

                feature = row2feature(row, 'osm_id', 'way')
            
                try:
                    geom = shape2geometry(feature['geometry'], self.mercator, clip)
                except _InvisibleBike:
                    # don't output this geometry because it's empty
                    pass
                else:
                    feature['geometry'] = geom
                    geojson['features'].append(feature)

            count_stuff = len(geojson['features'])
            has_stuff = bool(count_stuff)

        else:

            # See notes above inre: tweaking the code to accept plain
            # vanilla OSM XML blobs instead of GeoJSON (which is being
            # generated by the mapquest.xapi library).

            geojson = self.xapi.query(self.type, self.query, bbox_wgs84)

            count_stuff = len(geojson['features'])
            has_stuff = bool(count_stuff)

        rsp = {
            'has_stuff' : has_stuff,
            'geojson' : geojson,
            'bbox' : bbox_merc,
            'fill' : self.fill,
            'filltype' : self.filltype,
            }

        return SaveableResponse(rsp, width, height)
