if (!com){
	var com = {};
}

if (!com.modestmaps){
	com.modestmaps = {};
}

com.modestmaps.Markers = function(mm){

	this.drawn = new Array();

	this.modestmap = mm;
	this.container = mm.parent;

	this.div = document.createElement('div');
	this.div.setAttribute('id', 'modestmaps_markers');
	this.div.style.position = 'absolute';
	this.div.style.left = '0px';
	this.div.style.top = '0px';
	this.div.style.zIndex = '200';
	this.container.appendChild(this.div);

	// the thing that does the actual drawing
	// http://raphaeljs.com/

	this.canvas = Raphael(this.div, this.container.offsetWidth, this.container.offsetHeight);

	var _self = this;

	this.modestmap.addCallback('drawn', function(){
		_self._redrawMarkers();
	});
	
	// ??? what is _self.surface ???
	this.modestmap.addCallback('resized', function(){
		_self.surface.width = _self.container.offsetWidth;
		_self.surface.height = _self.container.offsetHeight;
		_self._redrawMarkers();
	});
};

/* not really sure why the resize callback is not getting called above, no time to worry about it
 * so just wrote this which is wired to the toggle map size button (seanc | 04042011)
*/
com.modestmaps.Markers.prototype.forceAresize = function(){
	this.canvas.setSize(this.container.offsetWidth,this.container.offsetHeight);
	this._redrawMarkers();
};

com.modestmaps.Markers.prototype.drawPoints = function(latlons, more){

	var prepped = this._locatifyLatLons(latlons);
	var locations = prepped[0];
	var extent = prepped[1];

	this._registerMarker({
		'type': 'point',
		'locations': locations,
		'extent': extent,
		'more': more
	});

	var drawn = this._actuallyDrawPoints(locations, extent, more);
	return drawn;
};

com.modestmaps.Markers.prototype.drawLines = function(latlons, more){

	var prepped = this._locatifyLatLons(latlons);
	var locations = prepped[0];
	var extent = prepped[1];

	this._registerMarker({
		'type': 'line',
		'locations': locations,
		'extent': extent,
		'more': more
	});

	var drawn = this._actuallyDrawLines(locations, draw_extent, more);
	return drawn;
};

com.modestmaps.Markers.prototype.drawPolygons = function(latlons, more){

	var prepped = this._locatifyLatLons(latlons);
	var locations = prepped[0];
	var extent = prepped[1];

	var uuid = this._registerMarker({
		'type': 'polygon',
		'locations': locations,
		'extent': extent,
		'more': more
	});

	var drawn = this._actuallyDrawPolygons(locations, extent, more);
	return drawn;
};

// This is just a helper function that hands off to drawPolygons
// It takes a list of swlat,swlon,nelat,nelon strings and does
// all the hoop jumping required to draw polygons. The default
// delimeter is a single space but you can override that by passing
// in a 'delimeter' argument in a second optional hash of args.

com.modestmaps.Markers.prototype.drawBoundingBoxes = function(bboxes, more){

	var delimeter = ((more) && (more['delimeter'])) ? more['delimeter'] : ' ';
	var polygons = [];

	var count_bboxes = bboxes.length;

	for (var i = 0; i < count_bboxes; i++){

		var bbox = bboxes[i].split(delimeter);

		var sw = [bbox[0], bbox[1]];
		var ne = [bbox[2], bbox[3]];

		var nw = [ne[0], sw[1]];
		var se = [sw[0], ne[1]];

		var coords = [
			[sw[0], sw[1]],
			[nw[0], nw[1]],
			[ne[0], ne[1]],
			[se[0], se[1]],
			[sw[0], sw[1]]
		];

		polygons.push(coords);
	}

	var drawn = this.drawPolygons(polygons, more);
	return drawn;
};

com.modestmaps.Markers.prototype.drawGeoJson = function(features, more){

	// http://geojson.org/geojson-spec.html

	if (! more){
		more = {};
	}

	var drawn = new Array();

	var count_features = features.length;

	for (var i = 0; i < count_features; i++){

		var f = features[i];

		var _more = {};

		for (key in more){
		    _more[ key ] = more[ key ];
		}

		_more['properties'] = f['properties'];

		var geom = (f.geometry.type == 'GeometryCollection') ? f.geometry.geometries : [ f.geometry ];
		var count_geom = geom.length;
	
		for (var j = 0; j < count_geom; j++){

			var type = geom[j]['type'];
			var coords = geom[j]['coordinates'];

			coords = this._lonlat2latlon(coords);

			// to do: GeometryCollections

			if ((type == 'Point') || (type == 'MultiPoint')){

				if (type == 'Point'){
					coords = [ coords ];
				}

				var d = this.drawPoints(coords, _more);
				drawn.push(d);
			}

			else if ((type == 'Polygon') || (type == 'MultiPolygon')){
				var d = this.drawPolygons(coords, _more);
				drawn.push(d);
			}

			else if ((type == 'LineString') || (type == 'MultiLineString')){

				if (type == 'LineString'){
					coords = [ coords ];
				}

				var d = this.drawLines(coords, _more);
				drawn.push(d);
			}

			else { }
		}

	}

	return drawn;
};

// essentially the drawGeoJson method above, with added goodies
com.modestmaps.Markers.prototype.drawKirbys = function(features, more){

	// http://geojson.org/geojson-spec.html

	if (! more){
		more = {};
	}

	var drawn = new Array();
	var drawn_back = new Array();

	var count_features = features.length;
	

	for (var i = 0; i < count_features; i++){

		var f = features[i];
        var bottom_more = {};
		var _more = {};

		for (key in more){
		    _more[ key ] = more[ key ];
		}

		_more['properties'] = f['properties'];
        bottom_more['properties'] = f['properties'];
        bottom_more['attrs'] = more['attrs_back'];
        bottom_more['radius'] = 12;
        bottom_more['back'] = true;
        
		var geom = (f.geometry.type == 'GeometryCollection') ? f.geometry.geometries : [ f.geometry ];
		var count_geom = geom.length;
	
		for (var j = 0; j < count_geom; j++){

			var type = geom[j]['type'];
			var coords = geom[j]['coordinates'];

			coords = this._lonlat2latlon(coords);

			// to do: GeometryCollections

			if ((type == 'Point') || (type == 'MultiPoint')){

				if (type == 'Point'){
					coords = [ coords ];
				}
				
				_more['id'] = "dot_"+f['properties'].id;

				var d = this.drawPoints(coords, _more);
				drawn.push(d);
				
				// clone me???
				var c = this.drawPoints(coords, bottom_more);
				drawn.push(c);

			}

			else if ((type == 'Polygon') || (type == 'MultiPolygon')){
				var d = this.drawPolygons(coords, _more);
				drawn.push(d);
			}

			else if ((type == 'LineString') || (type == 'MultiLineString')){

				if (type == 'LineString'){
					coords = [ coords ];
				}

				var d = this.drawLines(coords, _more);
				drawn.push(d);
			}

			else { }
		}

	}

	return drawn;
};


// things you may want to care about

com.modestmaps.Markers.prototype.purgeMarkers = function(to_preserve){

	if (to_preserve){

		if (to_preserve >= this.drawn.length){
			return;
		}

		this.drawn = this.drawn.slice(0, to_preserve);
	}

	else {
		this.drawn = new Array();
	}

	this._redrawMarkers();
};

// things you don't need to care about

com.modestmaps.Markers.prototype._lonlat2latlon = function(coords, reversed){

	if (typeof(coords[0]) == 'number'){
		return [ coords[1], coords[0] ];
	}

	var reversed = new Array();
	var count = coords.length;

	for (var i = 0; i < count; i++){
		reversed.push(this._lonlat2latlon(coords[i]));
	}

	return reversed;
};

com.modestmaps.Markers.prototype._locatifyLatLons = function(latlons){

	// Convert a bunch of latlon arrays in to lists of ModestMaps
	// Location objects and calculate the extent for the set.

	var prepared = new Array();

	for (i in latlons){

		if (typeof(latlons[i][0]) === 'object'){
			var prepped = this._locatifyLatLons(latlons[i]);
			prepared.push(prepped[0]);
			continue;
		}

		var loc = new com.modestmaps.Location(latlons[i][0], latlons[i][1]);
		prepared.push(loc);
	}

	var extent = this._extentForPoints(prepared);
	return new Array(prepared, extent);
};

com.modestmaps.Markers.prototype._registerMarker = function(data){

	// http://stackoverflow.com/questions/105034/how-to-create-a-guid-uuid-in-javascript/2117523#2117523
	// http://www.broofa.com/2008/09/javascript-uuid-function/

	var uuid = 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function(c) {
		var r = Math.random()*16|0, v = c == 'x' ? r : (r&0x3|0x8);
		return v.toString(16);
	}).toUpperCase();

	uuid = 'marker-' + uuid;

	// so that (eventually) we have IDs we can assign to elements (or wrapper
	// elements) for mucking with in JS and CSS

	data['id'] = uuid;

	this.drawn.push(data);
	return uuid;
};

com.modestmaps.Markers.prototype._scrubSurface = function(){
    this.canvas.clear();
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

com.modestmaps.Markers.prototype._actuallyDrawLines = function(lines, extent, more){

	// sudo make this work
	// var draw_extent = this._calculateDrawExtent(extent, more);
	// lines = this._enpointifyByExtent(lines, extent)

	var drawn = new Array();

	for (var i in lines){

		var coords = new Array();
		var ln = lines[i];

		// quick and dirty hack to account for GeoJSON MultiLines...

		if (ln.length == 1){
			ln = ln[0];
		}

		for (var j in ln){

			var pt = this.modestmap.locationPoint(ln[j]);
			coords.push({ 'x': pt.x, 'y': pt.y });
		}

		var line = this._line(coords, more);
		drawn.push(line);
	}

	return line;
};

com.modestmaps.Markers.prototype._actuallyDrawPolygons = function(polygons, extent, more){

	// sudo make this work
	// var draw_extent = this._calculateDrawExtent(extent, more);
	// polygons = this._enpointifyByExtent(polygons, extent)

	var drawn = new Array();

	for (var i in polygons){

		var coords = new Array();
		var ln = polygons[i];

		// quick and dirty hack to account for GeoJSON MultiPolygons...

		if (ln.length == 1){
			ln = ln[0];
		}

		for (var j in ln){
			var pt = this.modestmap.locationPoint(ln[j]);
			coords.push({ 'x': pt.x, 'y': pt.y });
		}

		var poly = this._polygon(coords, more);
		drawn.push(poly);
	}

	return drawn;
};

com.modestmaps.Markers.prototype._actuallyDrawPoints = function(points, extent, more){

	// sudo make these work
	// var draw_extent = this._calculateDrawExtent(extent, more);
	// points = this._enpointifyByExtent(points, extent)

	// once this is all working, please to be using
	// the 'set' method (20101202/straup)
	// http://raphaeljs.com/reference.html#set

	var drawn = new Array();

	for (i in points){

		var pt = this.modestmap.locationPoint(points[i]);
		var coords = { 'x': pt.x, 'y': pt.y };

		var circle = this._circle(coords, more);
		drawn.push(circle);
	}

	return drawn;
};

com.modestmaps.Markers.prototype._radiusByZoomLevel = function(){

	// TO DO: apply math shapes
	// var zoom = this.modestmap.getZoom();

	return 5;
};

com.modestmaps.Markers.prototype._extentForDrawnPoints = function(){

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

com.modestmaps.Markers.prototype._extentForPoints = function(points){

	// ensure we don't mangle the copy of points being
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

com.modestmaps.Markers.prototype._calculateDrawExtent = function(local_extent, more){

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

com.modestmaps.Markers.prototype._enpointifyByExtent = function(locations, extent){

	var map_extent = this.modestmap.getExtent();
	var points = new Array();

	for (i in locations){

		var loc = locations[i];

		// polylines and polygons

		if (typeof(loc.lat) !== 'number'){
			var tmp = this._enpointifyByExtent(loc, extent);
			points.push(tmp);
			continue;
		}

		if (! this._isContainedBy(loc, extent)){
			continue;
		}

		if (! this._isContainedBy(loc, map_extent)){
			continue;
		}

		var pt = this.modestmap.locationPoint(loc);
		points.push(pt);
	}

	return points;
};

com.modestmaps.Markers.prototype._isContainedBy = function(loc, extent){

	var sw = extent[0];
	var ne = extent[1];

	// TODO: write me...

	return true;
};

// canned primitives (hey look! actual drawing and not just number crunching!!)

com.modestmaps.Markers.prototype._polygon = function(coords, more){

	var count = coords.length;

	var path = [ "M" + coords[0]['x'] + " " + coords[0]['y'] ];

	for (var i=1; i < count; i++){
		path.push("L" + coords[i]['x'] + " " + coords[i]['y']);
	}

	path.push("Z");

	var ln = this.canvas.path(path.join(""));
	return this._decorate(ln, more);
};

com.modestmaps.Markers.prototype._line = function(coords, more){

	var count = coords.length;

	var path = [ "M" + coords[0]['x'] + " " + coords[0]['y'] ];

	for (var i=1; i < count; i++){
		path.push("L" + coords[i]['x'] + " " + coords[i]['y']);
	}

	var ln = this.canvas.path(path.join(""));
	return this._decorate(ln, more);
};

com.modestmaps.Markers.prototype._circle = function(coords, more){

	var r = ((more) && (more['radius'])) ? more['radius'] : 8;

	var c = this.canvas.circle(coords['x'], coords['y'], r);
	
	if ((more) && (more['back'])){
	    c.toBack();   
    }
    
    if ((more) && (more['id'])){
	    c.node.id=more['id'];   
    }
    
	return this._decorate(c, more);
};


com.modestmaps.Markers.prototype._decorate = function(el, more){

	if ((more) && (more['attrs'])){

		// http://raphaeljs.com/reference.html#attr
		el.attr(more['attrs']);
	}

	if ((more) && (more['onload'])){
		more['onload'](el, more['properties']);
	}
	return el;
};