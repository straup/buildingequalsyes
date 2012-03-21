import logging
logging.basicConfig(level=logging.DEBUG)

import TileStache

try:
    import PIL.Image as Image
    import PIL.ImageFilter as ImageFilter
except Exception, e:
    import Image
    import ImageFilter

import StringIO
import md5

class Provider:

    def __init__(self, layer, **kwargs):
        
        self.layer = layer
        self.source_layer = kwargs['source_layer']

	self.skip_on_checksum = kwargs.get('skip_on_checksum', False)
        self.checksum = kwargs.get('checksum', None)

        try:
            import atk
            self.plotter = 'atk'
        except Exception, e:
            logging.debug("unable to import atk: %s" % e)
            self.plotter = 'python'

    def renderTile(self, width, height, srs, coord):

        logging.info("[dithering] render tile %s..." % coord)

        source = self.layer.config.layers[ self.source_layer ]

        mime, body = TileStache.getTile(source, coord, 'png')

        if self.skip_on_checksum:
            hash = md5.new(body)
             
            if hash.hexdigest() == self.checksum:
                logging.info('[dithering] skip check sum matches %s : %s' % (coord, self.checksum))
		return Image.new('RGBA', (256, 256))
            
        img = Image.open(StringIO.StringIO(body))

        if self.plotter == 'atk':
            return self.dither_atk(img)

        return self.dither_python(img)

    def dither_atk(self, img):
	import atk
	img = img.convert('L')        
	tmp = atk.atk(img.size[0], img.size[1], img.tostring())
	new = Image.fromstring('L', img.size, tmp)
	return new.convert('RGBA')

    def dither_python(self, img):

        img = img.convert('L')

        threshold = 128*[0] + 128*[255]

        for y in range(img.size[1]):
            for x in range(img.size[0]):

                old = img.getpixel((x, y))
                new = threshold[old]
                err = (old - new) >> 3 # divide by 8

                img.putpixel((x, y), new)

                for nxy in [(x+1, y), (x+2, y), (x-1, y+1), (x, y+1), (x+1, y+1), (x, y+2)]:
                    try:
                        img.putpixel(nxy, img.getpixel(nxy) + err)
                    except IndexError:
                        pass

        return img.convert('RGBA')
