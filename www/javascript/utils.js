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

function utils_scrub_map(){

	// just blow away the map (if a datatable is filtered)

	var mom = $("#map");
	var kids = mom.children();

	if (kids.length){
		kids.remove();
	}
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
	'static' : static_tiles
    };	

    return rsp;
}

function ensure_valid_url_template(t){

    uri = parseUri(t);

    if (uri.protocol != 'http'){
	return null;
    }

    if (! uri.path.match(/\/{Z}\/{X}\/{Y}\.(?:jpg|png)$/) ){
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
		
		//	inital attempt to add touch support to polymaps
		if(_dotspotting.enable_touch_support){
			var touch = org.polymaps.touch();
			map.add(touch);
		}else{
			var drag = org.polymaps.drag();
			map.add(drag);

			var dblclick = org.polymaps.dblclick();	
			map.add(dblclick);
		}
		
	}else{
		$('#map_controls').remove();
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
	/* using our own buttons, but want the shift-click zoomy thing */
	var compass = org.polymaps.compass();
	compass.pan('none');
	compass.zoom('none');
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

		var to_process = new Array(
			[ f.element, data.properties ]
		);
		
		// Okay! Go!!
		var count_process = to_process.length;
		
		for (var k = 0; k < count_process; k ++){

			var el = to_process[k][0];
			var props = to_process[k][1];
			
			var classes = ['dot'];

			if (props && props.permissions){
				classes.push('dot_' + props.permissions);
			}
			
			//	just add the hover class for single dot pages
			//	clicking on them doesn't do anything anyways
			if(props && props.is_page_dot){
				classes.push('dotHover');
			}
			
			el.setAttribute('class', classes.join(' '));
			el.setAttribute('r', 8);			
		
			if (props && props.id){

		    		el.setAttribute('id', 'dot_' + props.id);
					utils_svg_title(el,props.id);
				
				if (props.is_interactive){
		    			var enc_id = encodeURIComponent(props.id);
	    				el.setAttribute('onmouseover', 'dot_onmouseover(' + enc_id + ');return false');
	    				el.setAttribute('onmouseout', 'dot_onmouseout(' + enc_id + ');return false');
						el.setAttribute('onclick', 'dot_onclick(' + enc_id + ','+f.data.geometry.coordinates[0]+','+f.data.geometry.coordinates[1]+');return false');

					//	$(el).bind('click', {props: props, geo: f.data.geometry}, dot_onclick); 
				}
			}

		}
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

		var to_process = new Array(
			[ f.element, data.properties ]
		);
	
		// Okay! Go!!

		var count_process = to_process.length;

		for (var k = 0; k < count_process; k ++){

			var el = to_process[k][0];
			var props = to_process[k][1];
			
			/* Move dot to bottom of group */
			
			el.parentNode.appendChild(el);
			

			el.setAttribute('class', 'sheet');

			if (props && props.id){

				var enc_id = encodeURIComponent(props.id);

				el.setAttribute('id', 'sheet_' + data.properties.id);
				el.setAttribute('onmouseover', 'sheet_onmouseover(' + enc_id + ');return false');
				el.setAttribute('onmouseout', 'sheet_onmouseout(' + enc_id + ');return false');
				el.setAttribute('onclick', 'sheet_onclick(' + enc_id + ');return false');
				utils_svg_title(el,props.label);
			}

		}
	}
}

// add title to svg elements (dots, sheets)
function utils_svg_title(el,title){
	el.setAttribute('title', title);
	el.appendChild( document.createElementNS(org.polymaps.svg.ns, "title") ).appendChild(document.createTextNode(title));
}

function utils_modestmap(map_id, more){
	if(more && more['static'])$('#map_controls').remove();
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
			new com.modestmaps.MouseHandler()
	];

	var map = new com.modestmaps.Map(map_id, provider, dims, handlers);
	
	
	return map;
}

function utils_modestmaps_add_compass(map){
    //com.modestmaps.Compass(map);
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
	
	// not sure if there is good way of doing this while trying to achieve 
	// a max scale and integer zoom levels, at the same time.
	offset = 0;
	bbox[0]['lat'] -= offset;
	bbox[0]['lon'] -= offset;
	bbox[1]['lat'] += offset;
	bbox[1]['lon'] += offset;
	
	return bbox;
}


////////////// seanc dumping ground //////////////////

function get_mm_dot_styles(){
	var attrs = {
		'fill' : 'rgb('+_dotspotting['dot_color']['fill'][0]+','+_dotspotting['dot_color']['fill'][1]+','+_dotspotting['dot_color']['fill'][2]+')',
		'fill-opacity' : _dotspotting['dot_color']['fill'][3],
		'stroke' : 'rgb('+_dotspotting['dot_color']['stroke'][0]+','+_dotspotting['dot_color']['stroke'][1]+','+_dotspotting['dot_color']['stroke'][2]+')',
		'stroke-width' : _dotspotting['dot_color']['stroke_width'],
		'stroke-opacity' :_dotspotting['dot_color']['stroke'][3]
	};

	var attrs_hover = {
		'fill' : 'rgb('+_dotspotting['dot_color']['fill_hover'][0]+','+_dotspotting['dot_color']['fill_hover'][1]+','+_dotspotting['dot_color']['fill_hover'][2]+')',
		'fill-opacity' : _dotspotting['dot_color']['fill_hover'][3],
		'stroke' : 'rgb('+_dotspotting['dot_color']['stroke_hover'][0]+','+_dotspotting['dot_color']['stroke_hover'][1]+','+_dotspotting['dot_color']['stroke_hover'][2]+')',
		'stroke-width' : _dotspotting['dot_color']['stroke_width'],
		'stroke-opacity' :_dotspotting['dot_color']['stroke_hover'][3]
	};
	return([attrs,attrs_hover]);
}

function get_mm_sheet_styles(){
	var attrs = {
		'fill' : 'rgb('+_dotspotting['sheet_color']['fill'][0]+','+_dotspotting['sheet_color']['fill'][1]+','+_dotspotting['sheet_color']['fill'][2]+')',
		'fill-opacity' : _dotspotting['sheet_color']['fill'][3],
		'stroke' : 'rgb('+_dotspotting['sheet_color']['stroke'][0]+','+_dotspotting['sheet_color']['stroke'][1]+','+_dotspotting['sheet_color']['stroke'][2]+')',
		'stroke-width' : _dotspotting['sheet_color']['stroke_width'],
		'stroke-opacity' :_dotspotting['sheet_color']['stroke'][3]
	};

	var attrs_hover = {
		'fill' : 'rgb('+_dotspotting['sheet_color']['fill_hover'][0]+','+_dotspotting['sheet_color']['fill_hover'][1]+','+_dotspotting['sheet_color']['fill_hover'][2]+')',
		'fill-opacity' : _dotspotting['sheet_color']['fill_hover'][3],
		'stroke' : 'rgb('+_dotspotting['sheet_color']['stroke_hover'][0]+','+_dotspotting['sheet_color']['stroke_hover'][1]+','+_dotspotting['sheet_color']['stroke_hover'][2]+')',
		'stroke-width' : _dotspotting['sheet_color']['stroke_width'],
		'stroke-opacity' :_dotspotting['sheet_color']['stroke_hover'][3]
	};
	return([attrs,attrs_hover]);
}

// create handlers for map controls
function utils_add_map_controls(map,map_type,extent){
	
	$('#map_controls').fadeIn();
	$("#map_controls a").each(function(){
		$(this).click(function(e){
			e.preventDefault();
			var type = $(this).attr("id");
			if(type && type.length){
				switch(type){
					case "zoom_in":
						(map_type == "mm") ? map.zoomIn() : map.zoomBy(1);
					break;
					case "zoom_out":
						(map_type == "mm") ? map.zoomOut() : map.zoomBy(-1);
					break;
					case "pan_left":
						(map_type == "mm") ? map.panBy(100,0) : map.panBy({x:100,y:0});
					break;
					case "pan_right":
						(map_type == "mm") ? map.panBy(-100,0) : map.panBy({x:-100,y:0});
					break;
					case "pan_up":
						(map_type == "mm") ? map.panBy(0,100) : map.panBy({x:0,y:100});
					break;
					case "pan_down":
						(map_type == "mm") ? map.panBy(0,-100) : map.panBy({x:0,y:-100});
					break;
					case "reset_bounds":
						if(map_type == "mm"){
							map.setExtent(extent);
						}
						else
						{
							map.extent(extent);
							map.zoom(Math.floor(map.zoom()));
						}
					break;
					default:
					//
					break;
				
				}
			}
		})
	});
}


// map toggle size button
function utils_map_toggle_size(map,map_type,tallSize,markers){
	var map_size = "small";
	
	$("#map_toggle_size a").click(function(e){
		e.preventDefault();
		var _this = $(this);
		var _w = Math.round($("#map").width());
		if(map_size == "small"){
			map_size = "large";	
			$("#map").animate({
			    height: tallSize
			  }, 100, function() {
				$(_this).text("Shorter map").removeClass('taller').addClass('shorter');
				if(map_type == "mm"){
					map.setSize(_w,tallSize);
					markers.forceAresize();
				}
				else
				{
					map.resize();
				}
			   
			  });
		}else{
			map_size = "small";
			$("#map").animate({
			    height: 300
			  }, 100, function() {
				$(_this).text("Taller map").removeClass('shorter').addClass('taller');
			    if(map_type == "mm"){
					map.setSize(_w,300);
					markers.forceAresize();
				}
				else
				{
					map.resize();
				}
			  });
		}
	});
}

/* 
	Add ToolTip & bind marker click event to #map
	
	tooltip appears on click
	
	#############################################################################
	code & styles from Portland crime map: https://github.com/Caged/portlandcrime
	#############################################################################
*/
function utils_add_map_tooltip(map,mapel,map_type){
	
	$("#map").bind('markerclick', function(e,dotid,coor) {
		
		var dot = dot_getinfo_json(dotid);
		
		if(mapel.length == 0) return;

		var props = new Object();
		props.location = (map_type == "mm") ? new com.modestmaps.Location(dot.latitude,dot.longitude) : coor;
		props.map = map;
		props.id = dotid;
		props.map_type = map_type;
	
		mapel.maptip(this)
		  .data(props)
	      .map(map)
	      .location(props.location)
	      .classNames(function(d) {
	        return d.code
	      })
	      .top(function(tip) {
	        var point = tip.props.map.locationPoint(this.props.location)
	        return parseFloat(point.y)
	      }).left(function(tip) {
	        var radius = tip.target.getAttribute('r'),
	            point = tip.props.map.locationPoint(this.props.location)

	        return parseFloat(point.x + (radius / 2.0) + 20)
	      }).content(function(d) {
	        var self = this,
	            props = d,
	            cnt = $('<div/>'),
	            hdr = $('<h2/>'),
	            bdy = $('<p/>'),

	            close = $('<span/>').addClass('close').text('X')
		        hdr.html(dot_tip_header(props.id));
		        hdr.append(close);
			
		        bdy.html(dot_tip_body(props.id))

		        cnt.append($('<div/>').addClass('nub'))
		        cnt.append(hdr).append(bdy) 

		        close.click(function() {
		          self.hide();
					dot_unselect(props.id);
					_dotspotting.selected_dot = null;
		        })   
				
	        	return cnt;
	      }).render()

	});
}