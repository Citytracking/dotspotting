// This is probably (hopefully?) just a temporary place-holder for
// shared/common functions (20101101/straup) 

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

		var hash = po.hash();
		map.add(hash);
	}

	var url = po.url("http://{S}tile.cloudmade.com/1a1b06b230af4efdbb989ea99e9841af/998/256/{Z}/{X}/{Y}.png");
	url.hosts(["a.", "b.", "c.", ""]);

	var tileset = po.image();
	tileset.url(url);

	map.add(tileset);
	return map;
}

function utils_modestmap(map_id, more){

	var mm = com.modestmaps;
	
	var template = 'http://a.tile.cloudmade.com/1a1b06b230af4efdbb989ea99e9841af/998/256/{Z}/{X}/{Y}.png';
	var provider = new mm.TemplatedMapProvider(template);

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
