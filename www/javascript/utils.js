// This is probably (hopefully?) just a temporary place-holder for
// shared/common functions (20101101/straup) 

// a bare-bones helper function that assumes you've defined a 
// 'draw_map' function before you call it.

function utils_load_map(args){

	 if (_dotspotting.js_loadlib == true){
	 	draw_map(args);
		return;
	 }

	 setTimeout(function(){
		utils_load_map(args);
	 }, 500);
}

function utils_tile_provider(){

    var template = _dotspotting.maptiles_template_url;
    var hosts = _dotspotting.maptiles_template_hosts;

    var static_tiles = 0;

    // can has URL template?

    var qs = (window.location.hash != '') ? window.location.hash.substring(1) : window.location.search.substring(1);

    if (qs){

	qs = new Querystring(qs);

	var t = (qs.contains('template')) ? qs.get('template') : null;

	// currently only TileStache-style cache tile
	// URLs are supported (20101111/straup)

	// See that? We're redefining 't' on the fly

	if ((t) && (t = ensure_valid_url_template(t))){

		template = t;
		hosts = null;
		static_tiles = (qs.contains('static')) ? 1 : 0;
	}

    }

    var rsp = {
	'template' : template,
	'hosts' : hosts,
	'static' : static_tiles,
    };	

    return rsp;
}

function ensure_valid_url_template(t){

    uri = parseUri(t);

    if (uri.protocol != 'http'){
	return null;
    }

    if (! uri.path.match(/\/{Z}\/{X}\/{Y}\.(?:jpg|png)$/)){
	return null;
    }

    var parts = uri.path.split(/\/{Z}\/{X}\/{Y}\.(jpg|png)$/);

    var path = parts[0].split('/');
    var ext = parts[1];

    var clean = [];

    for (i in path){
	clean.push(encodeURIComponent(path[i])); 
    }

    var template = 
	uri.protocol + '://' + uri.host + 
	clean.join('/') + 
	'/{Z}/{X}/{Y}' +
	'.' + ext;

    return template;
}

function utils_polymap(map_id, more){

	var svg = org.polymaps.svg("svg");

	var div = document.getElementById(map_id);
	div.appendChild(svg);

	var map = org.polymaps.map();
	map.container(svg);

	if ((! more) || (! more['static'])){

		var drag = org.polymaps.drag();
		map.add(drag);

		var dblclick = org.polymaps.dblclick();	
		map.add(dblclick);

		// add hash control here? anecdotally it seems
		// to be more hassle/confusing than not...
		// (2010111/straup)
	}

	var tp = utils_tile_provider();

	var url = (tp['static']) ? tilestache(tp['template']) : org.polymaps.url(tp['template']);

	if (tp['hosts']){
		url.hosts(tp['hosts']);
	}

	var tileset = org.polymaps.image();
	tileset.url(url);

	map.add(tileset);

	// we add the map compass on a case-by-case 
	return map;
}

function utils_polymaps_add_compass(map){

	var compass = org.polymaps.compass();
	compass.pan('none');
	compass.zoom('small');
	map.add(compass);
}

function utils_polymaps_assign_dot_properties(e){

	var count = e.features.length;

	if (! count){
		return;
	}

	for (var i=0; i < count; i++){

		var f = e.features[i];
		var data = f.data;

		var classes = ['dot'];

		if (data.properties && data.properties.permissions){
		    classes.push('dot_' + data.properties.permissions);
		}

		f.element.setAttribute('class', classes.join(' '));

		if (data.properties && data.properties.id){
		    f.element.setAttribute('id', 'dot_' + data.properties.id);

		    // f.element.setAttribute('onmouseover', 'show_dot(' + data.properties.id + ');return false');
		    // f.element.setAttribute('onmouseout', 'hide_dot(' + data.properties.id + ');return false');
		    // f.element.setAttribute('onclick', 'javascript: alert(' + data.properties.id + ');return false');
		}

		f.element.setAttribute('r', 8);

	}

}

function utils_polymaps_assign_sheet_properties (e){

	var count = e.features.length;

	if (! count){
		return;
	}

	for (var i=0; i < count; i++){
		var f = e.features[i];
		var data = f.data;

		f.element.setAttribute('class', 'sheet');

		if (data.properties && data.properties.id){
		    f.element.setAttribute('id', 'sheet_' + data.properties.id);
		}
	}

}

function utils_modestmap(map_id, more){

	var tp = utils_tile_provider();

	var provider = null;

	if (tp['static']){
	    provider = new com.modestmaps.TileStacheStaticMapProvider(tp['template'], tp['hosts']);
	}

	else {
	    provider = new com.modestmaps.TemplatedMapProvider(tp['template'], tp['hosts']);
	}

	var dims = undefined;

	var handlers = [
			// how to disable the scroll wheel ?
			new com.modestmaps.MouseHandler(),
			]

	var map = new com.modestmaps.Map(map_id, provider, dims, handlers);
	return map;
}

function utils_modestmaps_add_compass(map){

    com.modestmaps.Compass(map);
}

// quick and dirty function to tweak the extents of a bounding
// box so that dots don't get cropped by the edge of the map.
// this will undoubtedly require finesse-ing over time...
// (20101027/straup)

function utils_adjust_bbox(bbox){

	var sw = new LatLon(bbox[0]['lat'], bbox[0]['lon']);
	var ne = new LatLon(bbox[1]['lat'], bbox[1]['lon']);

	var offset = 0;
	var dist = sw.distanceTo(ne);

	if (dist >= 10){
		offset = .5;
	}

	else if (dist >= 100){
		offset = 1;
	}

	else if (dist >= 500){
		offset = 2;
	}

	else {}

	bbox[0]['lat'] -= offset;
	bbox[0]['lon'] -= offset;
	bbox[1]['lat'] += offset;
	bbox[1]['lon'] += offset;
	return bbox;
}
