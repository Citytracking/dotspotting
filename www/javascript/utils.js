// This is probably (hopefully?) just a temporary place-holder for
// shared/common functions (20101101/straup) 

function utils_tile_provider(){

    var template = 'http://{S}tile.cloudmade.com/1a1b06b230af4efdbb989ea99e9841af/998/256/{Z}/{X}/{Y}.png';
    var hosts = [ 'a.', 'b.', 'c.' ];

    var uri = new info.aaronland.URI();
    
    var t = (uri.query.contains('template')) ? uri.query.get('template') : null;
    var h = (uri.query.contains('hosts')) ? uri.query.get('hosts') : null;

    // currently only TileStache-style cache tile
    // URLs are supported (20101111/straup)

    var s = (uri.query.contains('static')) ? 1 : 0;

    // seriously, do some proper validation here...

    if ((t) && (t.indexOf('http') === 0)){

	    template = t;

	    hosts = ((h) && (h.indexOf('{S}') != -1)) ? h : null;
    }

    var rsp = {
	'template' : template,
	'hosts' : hosts,
	'static' : s,
    };	

    return rsp;
}

function utils_polymap(map_id, more){

	var po = org.polymaps;
	var svg = po.svg("svg");

	var div = document.getElementById(map_id);
	div.appendChild(svg);

	var map = po.map();
	map.container(svg);

	if ((! more) || (! more['static'])){

		var wheel = po.wheel();
		wheel.smooth(false);
		map.add(wheel);

		var drag = po.drag();
		map.add(drag);

		var dblclick = po.dblclick();	
		map.add(dblclick);

		// add hash control here? anecdotally it seems
		// to be more hassle/confusing than not...
		// (2010111/straup)

	}

	var tp = utils_tile_provider();

	var url = (tp['static']) ? tilestache(tp['template']) : po.url(tp['template']);

	if (tp['hosts']){
		url.hosts(tp['hosts']);
	}

	var tileset = po.image();
	tileset.url(url);

	map.add(tileset);
	return map;
}

function utils_modestmap(map_id, more){

	var mm = com.modestmaps;
	var tp = utils_tile_provider();

	var provider = null;

	if (tp['static']){
	    provider = new mm.TileStacheStaticMapProvider(tp['template'], tp['hosts']);
	}

	else {
	    provider = new mm.TemplatedMapProvider(tp['template'], tp['hosts']);
	}

	var dims = undefined;
	var handlers = undefined;

	if ((more) && (more['static'])){
	    handlers = [];
	}

	var map = new mm.Map(map_id, provider, dims, handlers);
	return map;
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
