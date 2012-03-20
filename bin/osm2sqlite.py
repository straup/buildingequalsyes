#! /usr/bin/python

import xml.sax
import sqlite3
import json
import sys
import os.path

class Node(object):
    def __init__(self, id=None, lon=None, lat=None, tags=None):
        self.id = id
        self.lon, self.lat = lon, lat
        if tags:
            self.tags = tags
        else:
            self.tags = {}

    def __repr__(self):
        return "Node(id=%r, lon=%r, lat=%r, tags=%r)" % (self.id, self.lon, self.lat, self.tags)

class Way(object):
    def __init__(self, id, nodes=None, tags=None):
        self.id = id
        if nodes:
            self.nodes = nodes
        else:
            self.nodes = []
        if tags:
            self.tags = tags
        else:
            self.tags = {}

    def __repr__(self):
        return "Way(id=%r, nodes=%r, tags=%r)" % (self.id, self.nodes, self.tags)

class NodePlaceHolder(object):
    def __init__(self, id):
        self.id = id

    def __repr__(self):
        return "NodePlaceHolder(id=%r)" % (self.id)

class OSMXMLFile(object):
    def __init__(self, filename):
        self.filename = filename

        self.__db_init()

        self.nodes = {}
        self.ways = {}
        self.__parse()
        # print repr(self.ways)

    def nodedb_add(self, node):


        tags = ''
        name = ''

        if len(node.tags.keys()):

            tags = node.tags

            if tags.has_key('name') :
                name = tags['name']

            tags = json.dumps(tags)

        try :
            self.dbcurs.execute("INSERT INTO nodes (id, lat, lon, tags, name) VALUES(?, ?, ?, ?, ?)", (node.id, node.lat, node.lon, tags, name))
            self.dbconn.commit()
        except sqlite3.IntegrityError:
            pass
        except Exception, e:
            raise Exception, e

    def waydb_add(self, way):

        return

        tags = ''
        name = ''

        if len(way.tags.keys()):

            tags = way.tags

            if tags.has_key('name') :
                name = tags['name']

            tags = json.dumps(tags)

        nodes = []

        for n in way.nodes:
            nodes.append(str(n.id))

            sql = "SELECT ways FROM nodes WHERE id=%s" % (n.id,)
            print sql

            self.dbcurs.execute(sql)
            row = self.dbcurs.fetchone();
            
            ways = []

            if row[0]:

                _ways = row[0].split(",")

                if str(n.id) not in _ways:
                    ways.append(str(way.id))

            else :
                ways.append(str(way.id))

            ways = ",".join(ways)
                    
            sql = "UPDATE nodes SET ways='%s' WHERE id=%s" % (ways, n.id)
            print sql

            self.dbcurs.execute(sql)
            self.dbconn.commit()

        nodes = ",".join(nodes)

        try :
            self.dbcurs.execute("INSERT INTO ways (id, nodes, tags, name) VALUES(?, ?, ?, ?)", (way.id, nodes, tags, name))
            self.dbconn.commit()
        except sqlite3.IntegrityError:
            pass
        except Exception, e:
            raise Exception, e


    def __db_init(self):

        db = "%s.db" % self.filename
        create_table = True

        if os.path.exists(db):
            create_table = False

        self.dbconn = sqlite3.connect(db)
        self.dbcurs = self.dbconn.cursor()

        if create_table :
            self.dbcurs.execute("CREATE TABLE nodes (id INTEGER, lat DECIMAL, lon DECIMAL, ways TEXT, tags TEXT, name TEXT)")
            self.dbcurs.execute("CREATE UNIQUE INDEX by_nodeid ON nodes (id)")
            self.dbconn.commit()

            self.dbcurs.execute("CREATE TABLE ways (id INTEGER, lat DECIMAL, lon DECIMAL, woeid INTEGER, nodes TEXT, tags TEXT, name TEXT)")
            self.dbcurs.execute("CREATE UNIQUE INDEX by_wayid ON ways (id)")
            self.dbcurs.execute("CREATE INDEX by_woeid ON ways (woeid)")
            self.dbconn.commit()

    def __parse(self):
        """Parse the given XML file"""

        parser = xml.sax.make_parser()
        parser.setContentHandler(OSMXMLFileParser(self))
        parser.parse(self.filename)

        return

        # now fix up all the refereneces
        for way in self.ways.values():
            way.nodes = [self.nodes[node_pl.id] for node_pl in way.nodes]

        # convert them back to lists
        self.nodes = self.nodes.values()
        self.ways = self.ways.values()

class OSMXMLFileParser(xml.sax.ContentHandler):
    def __init__(self, containing_obj):

        self.containing_obj = containing_obj
        self.curr_node = None
        self.curr_way = None

    def __nodedb_add(self, node):

        tags = ''
        name = ''

        if len(tags.keys()):
            tags = json.dumps(node.tags)

            if tags.has_key('name') :
                name = tags['name']

        self.dbcurs.execute("INSERT INTO nodes (id, lat, lon, tags, name) VALUES(?, ?, ?,, ?)""", (node.id, node.lat, node.lon, tags, name))
        self.dbconn.commit()

    def startElement(self, name, attrs):
        #print "Start of node " + name
        if name == 'node':
            self.curr_node = Node(id=attrs['id'], lon=attrs['lon'], lat=attrs['lat'])
        elif name == 'way':
            #self.containing_obj.ways.append(Way())
            self.curr_way = Way(id=attrs['id'])
        elif name == 'tag':
            #assert not self.curr_node and not self.curr_way, "curr_node (%r) and curr_way (%r) are both non-None" % (self.curr_node, self.curr_way)
            if self.curr_node:
                self.curr_node.tags[attrs['k']] = attrs['v']
            elif self.curr_way:
                self.curr_way.tags[attrs['k']] = attrs['v']
        elif name == "nd":
            assert self.curr_node is None, "curr_node (%r) is non-none" % (self.curr_node)
            assert self.curr_way is not None, "curr_way is None"
            self.curr_way.nodes.append(NodePlaceHolder(id=attrs['ref']))


    def endElement(self, name):
        #print "End of node " + name
        #assert not self.curr_node and not self.curr_way, "curr_node (%r) and curr_way (%r) are both non-None" % (self.curr_node, self.curr_way)
        if name == "node":
            self.containing_obj.nodedb_add(self.curr_node)
            # self.containing_obj.nodes[self.curr_node.id] = self.curr_node
            # print self.curr_node
            self.curr_node = None
        elif name == "way":
            self.containing_obj.waydb_add(self.curr_way)
            # self.containing_obj.ways[self.curr_way.id] = self.curr_way
            # print self.curr_way
            self.curr_way = None

if __name__ == '__main__':

    import sys

    src = sys.argv[1]
    osm = OSMXMLFile(src)
    sys.exit()
