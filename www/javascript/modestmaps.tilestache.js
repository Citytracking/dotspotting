com.modestmaps.TileStacheStaticMapProvider = function(template){

    this.topLeftOuterLimit =  new com.modestmaps.Coordinate(0,0,0);
    this.bottomRightInnerLimit =  new com.modestmaps.Coordinate(1,1,0).zoomTo(18);

    // utility functions...

    function addZeros(i, zeros){

        if (zeros === undefined){
            zeros = 6;
        }

        var s = i.toString();

        while (s.length < zeros){
            s = '0' + s;
        }

        return s;
    }
    
    function tilePad(i){

	var padded = new String(addZeros(i));
	padded = padded.substr(0,3) + '/' + padded.substr(3,3);

	return padded;
    }

    com.modestmaps.MapProvider.call(this, function(coord){
    
        coord = this.sourceCoordinate(coord);

        if (! coord){
            return null;
        }

        var mod = coord.copy();

        var url = template.replace('{Z}', mod.zoom)
                          .replace('{X}', tilePad(mod.column))
                          .replace('{Y}', tilePad(mod.row));
        
        return url;        
    });
    
};

com.modestmaps.TileStacheStaticMapProvider.prototype.setZoomRange = function(minZoom, maxZoom){
        this.topLeftOuterLimit = this.topLeftOuterLimit.zoomTo(minZoom);
        this.bottomRightInnerLimit = this.bottomRightInnerLimit.zoomTo(maxZoom);
}

com.modestmaps.extend(com.modestmaps.TileStacheStaticMapProvider, com.modestmaps.MapProvider);
