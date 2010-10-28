// question: should this be updated to use or expect bags of geojson
// features to work more easily alongside polymaps? probably, but not
// today (20101028/straup)

if (! com){
	var com = {};
}

if (! com.modestmaps){
	com.modestmaps = {};
}

com.modestmaps.Markers = function(mm){

	this.drawn = new Array();

	this.modestmap = mm;
	this.container = mm.parent;

	this.surface = document.createElement('canvas');
	this.surface.style.position = 'absolute';
	this.surface.style.left = '0px';
	this.surface.style.top = '0px';
	this.surface.setAttribute('width', this.container.offsetWidth);
	this.surface.setAttribute('height', this.container.offsetHeight);

	this.container.appendChild(this.surface);

	var _self = this;

	this.modestmap.addCallback('drawn', function(){
		_self._redrawMarkers();
	});

	this.modestmap.addCallback('resized', function(){
		_self.surface.width = _self.container.offsetWidth;
		_self.surface.height = _self.container.offsetHeight;
		_self._redrawMarkers();
	});

};

com.modestmaps.Markers.prototype.registerMarker = function(data){
	var uuid = 'fixme';
	this.drawn.push(data);
	return uuid;
};

com.modestmaps.Markers.prototype.purgeMarkers = function(to_preserve){

	if (to_preserve){

		if (to_preserve >= this.drawn.length){
			return;
		}

		this.drawn = this.drawn.slice(0, to_preserve);
	}

	else{
		this.drawn = new Array();
	}

	this._redrawMarkers();
};

com.modestmaps.Markers.prototype.drawPoints = function(latlons, more){

	var prepped = this.locatifyLatLons(latlons);
	var locations = prepped[0];
	var extent = prepped[1];

	this.registerMarker({
		'type': 'point',
		'locations': locations,
		'extent': extent,
		'more': more,
	});

	var draw_extent = this.calculateDrawExtent(extent, more);
	this._actuallyDrawPoints(locations, draw_extent, more);
}

com.modestmaps.Markers.prototype.drawLines = function(latlons, more){

	var prepped = this.locatifyLatLons(latlons);
	var locations = prepped[0];
	var extent = prepped[1];

	this.registerMarker({
		'type': 'line',
		'locations': locations,
		'extent': extent,
		'more': more,
	});

	var draw_extent = this.calculateDrawExtent(extent, more);
	this._actuallyDrawLines(locations, draw_extent, more);
};

com.modestmaps.Markers.prototype.drawPolygons = function(latlons, more){

	var prepped = this.locatifyLatLons(latlons);
	var locations = prepped[0];
	var extent = prepped[1];

	this.registerMarker({
		'type': 'polygon',
		'locations': locations,
		'extent': extent,
		'more': more,
	});

	this._actuallyDrawPolygons(locations, extent, more);
};

com.modestmaps.Markers.prototype.drawSequence = function(seq){
	// TODO: write me...
};

com.modestmaps.Markers.prototype.calculateDrawExtent = function(local_extent, more){

	// Figure out the extent of the viewport for the stuff we're going to draw.

    	if (typeof(more) === 'undefined'){
		return this.modestmap.getExtent();
	}

	var draw_extent = (more['extentify_all']) ? this.extentForDrawnPoints() : local_extent;

	if (more['extentify_redraw']){
		this.modestmap.setExtent(draw_extent);
	}

	return draw_extent;
};

com.modestmaps.Markers.prototype.locatifyLatLons = function(latlons){

	// Convert a bunch of latlon arrays in to lists of ModestMaps
	// Location objects and calculate the extent for the set.

	var prepared = new Array();

	for (i in latlons){

		if (typeof(latlons[i][0]) === 'object'){
			var prepped = this.locatifyLatLons(latlons[i]);
			prepared.push(prepped[0]);
			continue;
		}

		var loc = new com.modestmaps.Location(latlons[i][0], latlons[i][1]);
		prepared.push(loc);
	}

	var extent = this.extentForPoints(prepared);
	return new Array(prepared, extent);
};

com.modestmaps.Markers.prototype._scrubSurface = function(){

	// Prepare the canvas/surface for re-drawing

	var ctx = this.surface.getContext('2d');
	ctx.clearRect(0,0, ctx.canvas.width, ctx.canvas.height);
};

com.modestmaps.Markers.prototype._redrawMarkers = function(){

	// Redraw everything. Just scope it all the map viewport since
	// this will have been triggered by a 'drawn' or 'changed' event.

	this._scrubSurface();

	var extent = this.modestmap.getExtent();

	for (i in this.drawn){

		var type = this.drawn[i]['type'];
		var locs = this.drawn[i]['locations'];
		var more = this.drawn[i]['more'];

		if (type == 'point'){
			this._actuallyDrawPoints(locs, extent, more);
		}

		else if (type == 'line'){
			this._actuallyDrawLines(locs, extent, more);
		}

		else if (type == 'polygon'){
			this._actuallyDrawPolygons(locs, extent, more);
		}

		else {
			// console.log('what is this: ' + type);
		}
	}
};

// TO DO: please to finish fixing formatting...

com.modestmaps.Markers.prototype._actuallyDrawLines = function(locations, extent, more){

    // Convert our Location objects in to Points and then pass the x,y
    // coordinates off to the appropriate canvas method/primitive.

    var lines = this.enpointifyByExtent(locations, extent)

    for (var i in lines){

        var coords = new Array();
        var ln = lines[i];

        for (var j in ln){

            var pt = ln[j];
            var x = parseInt(pt.x)
            var y = parseInt(pt.y)

            coords.push({ 'x': x, 'y': y });
        }

        this.line(coords, more);
    }

};

com.modestmaps.Markers.prototype._actuallyDrawPolygons = function(locations, extent, more){

    // Convert our Location objects in to Points and then pass the x,y
    // coordinates off to the appropriate canvas method/primitive.

    var lines = this.enpointifyByExtent(locations, extent)

    for (var i in lines){

        var coords = new Array();
        var ln = lines[i];

        for (var j in ln){

            var pt = ln[j];
            var x = parseInt(pt.x)
            var y = parseInt(pt.y)

            coords.push({ 'x': x, 'y': y });
        }

        this.polygon(coords, more);
    }

};

com.modestmaps.Markers.prototype._actuallyDrawPoints = function(locations, extent, more){

    var points = this.enpointifyByExtent(locations, extent)

    for (i in points){

        var pt = points[i];
        var x = parseInt(pt.x)
        var y = parseInt(pt.y)

        coords = { 'x': x, 'y': y };
        this.circle(coords, more);
    }

};

com.modestmaps.Markers.prototype._radiusByZoomLevel = function(){
	// TO DO: apply math shapes
	// var zoom = this.modestmap.getZoom();

	return 5;
};

com.modestmaps.Markers.prototype.extentForDrawnPoints = function(){

    var swlat = undefined;
    var swlon = undefined;
    var nelat = undefined;
    var nelon = undefined;

    for (i in this.drawn){

        var sw = this.drawn[i]['extent'][0];
        var ne = this.drawn[i]['extent'][1];

        swlat = (swlat === undefined) ? sw.lat : Math.min(swlat, sw.lat);
        swlon = (swlon === undefined) ? sw.lon : Math.min(swlon, sw.lon);
        nelat = (nelat === undefined) ? ne.lat : Math.max(nelat, ne.lat);
        nelon = (nelon === undefined) ? ne.lon : Math.max(nelon, ne.lon);
    }

    var sw = new com.modestmaps.Location(swlat, swlon);
    var ne = new com.modestmaps.Location(nelat, nelon);

    return new Array(sw, ne);

};

com.modestmaps.Markers.prototype.extentForPoints = function(points){

    // ensure we don't fuck up the copy of points being
    // passed in if we're dealing with multiple polygons
    // or lines

    var _points = points;

    var swlat = undefined;
    var swlon = undefined;
    var nelat = undefined;
    var nelon = undefined;

    // polylines and polygons...

    if (typeof(_points[0].lat) === 'undefined'){
        var tmp = new Array();

        for (i in _points){

            for (j in _points[i]){
                tmp.push(_points[i][j]);
            }
        }

        _points = tmp;
    }

    for (i in _points){

        swlat = (swlat === undefined) ? _points[i].lat : Math.min(swlat, _points[i].lat);
        swlon = (swlon === undefined) ? _points[i].lon : Math.min(swlon, _points[i].lon);
        nelat = (nelat === undefined) ? _points[i].lat : Math.max(nelat, _points[i].lat);
        nelon = (nelon === undefined) ? _points[i].lon : Math.max(nelon, _points[i].lon);
    }

    var sw = new com.modestmaps.Location(swlat, swlon);
    var ne = new com.modestmaps.Location(nelat, nelon);

    return new Array(sw, ne);
};

com.modestmaps.Markers.prototype.enpointifyByExtent = function(locations, extent){

    var map_extent = this.modestmap.getExtent();
    var points = new Array();

    for (i in locations){

        var loc = locations[i];

        // polylines and polygons

        if (typeof(loc.lat) !== 'number'){
            var tmp = this.enpointifyByExtent(loc, extent);
            points.push(tmp);
            continue;
        }

        if (! this.isContainedBy(loc, extent)){
            continue;
        }

        if (! this.isContainedBy(loc, map_extent)){
            continue;
        }

        var pt = this.modestmap.locationPoint(loc);
        points.push(pt);
    }

    return points;
};

com.modestmaps.Markers.prototype.isContainedBy = function(loc, extent){

    var sw = extent[0];
    var ne = extent[1];

    // TODO: write me...

    return true;
};

// canned primitives

com.modestmaps.Markers.prototype.polygon = function(coords, more){

    var fill_style = (more['fillStyle']) ? more['fillStyle'] : '#00A308';
    var stroke_style = (more['strokeStyle']) ? more['strokeStyle'] : '#ccff99';
    var line_width = (more['lineWidth']) ? more['lineWidth'] : 1;
    var line_join = (more['lineJoin']) ? more['lineJoin'] : 'miter';
    var line_cap = (more['lineCap']) ? more['lineCap'] : 'butt';

    var ctx = this.surface.getContext('2d');

    ctx.strokeStyle = stroke_style;
    ctx.fillStyle = fill_style;
    ctx.lineWidth = line_width;
    ctx.lineJoin = line_join;
    ctx.lineCap = line_cap;

    ctx.beginPath();

    for (i in coords){

        var c = coords[i];

        if (i == 0){
            ctx.moveTo(c['x'], c['y']);
            continue;
        }

        ctx.lineTo(c['x'], c['y']);
    }

    ctx.stroke();

    if (more['is_line']){
        return;
    }

    ctx.closePath();
    ctx.fill();
};

com.modestmaps.Markers.prototype.line = function(coords, more){
    more['is_line'] = 1;
    this.polygon(coords, more);
};

com.modestmaps.Markers.prototype.circle = function(coords, more){

    var r = ((more) && (more['r'])) ? more['r'] : this._radiusByZoomLevel();

    var fill_style = ((more) && (more['fillStyle'])) ? more['fillStyle'] : '#00A308';
    var stroke_style = ((more) && (more['strokeStyle'])) ? more['strokeStyle'] : '#ccff99';
    var line_width = ((more) && (more['lineWidth'])) ? more['lineWidth'] : 2;
    var line_join = ((more) && (more['lineJoin'])) ? more['lineJoin'] : 'miter';
    var line_cap = ((more) && (more['lineCap'])) ? More['lineCap'] : 'butt';

    var ctx = this.surface.getContext('2d');

    ctx.strokeStyle = stroke_style;
    ctx.fillStyle = fill_style;
    ctx.lineWidth = line_width;
    ctx.lineJoin = line_join;
    ctx.lineCap = line_cap;

    ctx.beginPath();
    ctx.arc(coords['x'], coords['y'], r, 0, Math.PI * 2, true);
    ctx.closePath();

    if ((! more) || (! more['no_fill'])){
        ctx.fill();
    }

    if ((! more) || (! more['no_stroke'])){
        ctx.stroke();
    }
};