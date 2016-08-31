import shapefile





w = shapefile.Writer(shapefile.POINT)
w.point(41,1)
w.point(3,1)
w.point(4,3)
w.point(2,2)
w.field('FIRST_FLD')
w.field('SECOND_FLD','C','40')
w.record('First','Point')
w.record('Second','Point')
w.record('Third','Point')
w.record('Fourth','Point')
w.save('C:\\test')