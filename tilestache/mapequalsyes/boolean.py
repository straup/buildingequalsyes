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

import mapquest.xapi
import xapi.utils

from TileStache.Core import KnownUnknown
from TileStache.Geography import getProjectionByName

import Image
import ImageDraw

# All this stuff is here as an Easter egg so strictly
# speaking none of it is actually necessary. See below
# for details.

try:
    from psycopg2.extensions import adapt as _adapt
    from psycopg2 import connect as _connect
    from psycopg2.extras import RealDictCursor
except Exception, e:
    pass

def hex_to_rgb(colorstring, rgba=False):

    colorstring = colorstring.strip()
        
    if colorstring[0] == '#':
        colorstring = colorstring[1:]

    if len(colorstring) != 6:
        return (0, 0, 0)

    r, g, b = colorstring[:2], colorstring[2:4], colorstring[4:]
    r, g, b = [int(n, 16) for n in (r, g, b)]

    if not rgba :
        return (r, g, b)
    
    return (float(r) / 255.0, float(g) / 255.0, float(b) / 255.0)

class _InvisibleBike(Exception): pass

class SaveableResponse:

    def __init__(self, grid, width, height):
        self.grid = grid
        self.width = width
        self.height = height

    def save(self, out, format):

        if format not in ('PNG'):
            raise KnownUnknown('We only saves and .png tiles, not "%s"' % format)

        im = Image.new('RGBA', (self.width, self.height))
        dr = ImageDraw.Draw(im)

        for data in self.grid:

            if not data['has_stuff'] :
                continue

            fill = hex_to_rgb(data['fill'])

            x,y = data['rect'][0]

            dr.rectangle(data['rect'], fill=fill)

        im.save(out, 'PNG')

class Provider:

    def __init__(self, layer, type, query, **kwargs):

        self.mercator = getProjectionByName('spherical mercator')
        self.layer = layer
        self.type = type
        self. query = query
        self.table = kwargs.get('table', 'planet_osm_line')
        self.fill = kwargs.get('fill', '#000000')
        self.zoom_factor = kwargs.get('zoom_factor', 2)
        self.datasource = kwargs.get('datasource', 'xapi')

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
            self.pgis = _connect(kwargs.get('dbdsn', '')).cursor(cursor_factory=RealDictCursor)
        else:
            self.xapi = mapquest.xapi.xapi()

    def getTypeByExtension(self, extension):

        if extension.lower() == 'png':
            return 'image/png', 'PNG'

        raise KnownUnknown('I only know how to make .png tiles, not "%s"' % extension)

    def renderTile(self, width, height, srs, coord):

        nw = self.layer.projection.coordinateLocation(coord)
        se = self.layer.projection.coordinateLocation(coord.right().down())

        ul = self.mercator.locationProj(nw)
        lr = self.mercator.locationProj(se)

        rows = 2 ** self.zoom_factor
        cols = rows

        pixels_per_col = width / cols
        pixels_per_row = height / rows

        pixel_y = 0
        pixel_x = 0

        grid = []

        # File under possible optimizations:
        # Check to see if a parent cell is false and don't bother
        # with the children. This would be useful for cities like
        # San Francisco but not at all useful for a city like Paris.
        # Maybe it evens out in the wash?

        for row in range(2 ** self.zoom_factor):
            for col in range(2 ** self.zoom_factor):

                subcoord = coord.zoomBy(self.zoom_factor).right(row).down(col)
                
                ul = self.layer.projection.coordinateProj(subcoord)
                lr = self.layer.projection.coordinateProj(subcoord.right().down())

                nw = self.layer.projection.projLocation(ul)
                se = self.layer.projection.projLocation(lr)

                bbox_wgs84 = [ se.lat, nw.lon, nw.lat, se.lon ]
                bbox_merc = [ ul.x, lr.y, lr.x, ul.y ]

                count_stuff = 0
                has_stuff = None

		# See notes above inre: Easter eggs.

                if self.datasource == 'postgis':

                    pg_where, pg_keys = xapi.utils.parse_query_for_pgsql(self.query)

                    pg_condition = " OR ".join(pg_where)
                    pg_condition = "(%s)" % pg_condition
                    
                    pg_bbox = 'ST_SetSRID(ST_MakeBox2D(ST_MakePoint(%.6f, %.6f), ST_MakePoint(%.6f, %.6f)), 900913)' % (ul.x, ul.y, lr.x, lr.y)

                    query = "SELECT COUNT(*) AS count FROM \"%s\" WHERE %s AND ST_Intersects(!bbox!, way)" % (self.table, pg_condition)
                    query = query.replace('!bbox!', pg_bbox)

                    self.pgis.execute(query)
                    rsp = self.pgis.fetchone()

                    count_stuff = int(rsp['count'])
                    has_stuff = bool(count_stuff)

                else:

                    # This part is not awesome because we have to fetch/read
                    # the entire XAPI response just to get a count. In the
                    # absence of a way to get that directly from XAPI it might
                    # (might) make sense to use a SAX parser, or something,
                    # to just plow through the XAPI response ignoring everything
                    # as soon as we know whether the query is true or false.
                    # If nothing else it would save having to parse everything
                    # into an XML/DOM tree.

                    rsp = self.xapi.query(self.type, self.query, bbox_wgs84)

                    count_stuff = len(rsp['features'])
                    has_stuff = bool(count_stuff)

                if not has_stuff:
                    continue

                startx = row * pixels_per_row
                starty = col * pixels_per_col

                endx = startx + pixels_per_row
                endy = starty + pixels_per_col

                rect = ((startx, starty), (endx, endy))

                grid.append({
                        'has_stuff' : has_stuff,
                        'count_stuff' : count_stuff,
                        'rect' : rect,
                        'bbox' : bbox_wgs84,
                        'fill' : self.fill,
                        })

        return SaveableResponse(grid, width, height)
