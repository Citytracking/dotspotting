/*
 * Initializes widget
 * loads necesary support files
 * 
 * TODO: just about everything. Not even at a disco stage, we're waltzing here
 */
(function() {
	
	var script_count = 2;
	var mapParent = null;
	var params = "";
	
	// Load jQuery if not present
	if (window.jQuery === undefined || window.jQuery.fn.jquery !== '1.5.1') {
	    var script_tag = document.createElement('script');
	    script_tag.setAttribute("type","text/javascript");
	    script_tag.setAttribute("src","http://ajax.googleapis.com/ajax/libs/jquery/1.5.1/jquery.min.js");
	    script_tag.onload = scriptLoadHandler;
	    script_tag.onreadystatechange = function () { 
	        if (this.readyState == 'complete' || this.readyState == 'loaded') {
	            scriptLoadHandler();
	        }
	    };

	    // Try to find the head, otherwise default to the documentElement
	    (document.getElementsByTagName("head")[0] || document.documentElement).appendChild(script_tag);
	} else {
	    scriptLoadHandler();
	}
	
	
	// load polymaps, if needed
	if(window.org === undefined || window.org.polymaps === undefined){
		script_tag = document.createElement('script');
	    script_tag.setAttribute("type","text/javascript");
	    script_tag.setAttribute("src","http://polymaps.org/polymaps.min.js?2.4.0");
	    script_tag.onload = scriptLoadHandler;
	    script_tag.onreadystatechange = function () { 
	        if (this.readyState == 'complete' || this.readyState == 'loaded') {
	            scriptLoadHandler();
	        }
	    };

	    // Try to find the head, otherwise default to the documentElement
	    (document.getElementsByTagName("head")[0] || document.documentElement).appendChild(script_tag);
	} else {
	    scriptLoadHandler();
	}

	// keeps track if script counts
	function scriptLoadHandler() {
	   	script_count--;
		if(script_count == 0)scripts_all_loaded();
	}
	
	// scripts all done
	function scripts_all_loaded(){
		// Restore $ and window.jQuery to their previous values and store the
	    // new jQuery in our local jQuery variable
	    jQuery = window.jQuery.noConflict(true);
	    // Call our main function
	    main();
	}

	// name says it all
	function main() { 
		console.log("MAIN");
		// load widget styles
		var css_link = document.createElement('link');
		css_link.type = 'text/css';
		css_link.rel = 'stylesheet';
		css_link.href = 'dotspotting_embedwidget.css';
		(document.getElementsByTagName("head")[0] || document.documentElement).appendChild(css_link);
		
	    jQuery(document).ready(function($) { 
			var target = "dotspotting_embedwidget.js";
			var regpat = /(dotspotting_embedwidget\.js)/gi;
			jQuery('script').each(function(){
				var _p = $(this).attr("src");
				if(regpat.test(_p)){
					mapParent = $(this).parent();
					_p = _p.split("?");
					params = new Querystring(_p[1]);
				}
		
			});
			
			if(!mapParent)return;
			if(!params || !params.get)return;
			
			var userid = params.get("uid");
			var sheetid = params.get("sid");
			var _path_to_proxy = "http://dotpoop.appspot.com/?sid="+sheetid+"&uid="+userid;
			var mapdata = null;

			jQuery.ajax({
				url:  _path_to_proxy,
				dataType: "jsonp",
				success: function(d) {
					if(d.msg && d.msg == "error"){
						buildMap(mapParent,null);
					}else{
						mapdata = d;
						cleanDots(mapdata);
						//console.log(mapdata);
						buildMap(mapParent,mapdata.features);
					}
					
				},
				error:function(e){
					alert("Could not load data");
				}
			});

	    });
	}
	
	function cleanDots(obj){
		var arr=obj.features;
		for(var i=0;i<arr.length;i++){
			arr[i].geometry.coordinates = [Number(arr[i].geometry.coordinates[0]),Number(arr[i].geometry.coordinates[1])];
		}
	}
	
	function getExtent(arr){
		var swlat,swlon,nelat,nelon;
		for(var i=0;i<arr.length;i++){
			//0 = longitude,1 = latitude
			swlat = (swlat) ? Math.min(arr[i].geometry.coordinates[1],swlat) : arr[i].geometry.coordinates[1];
			swlon = (swlon) ? Math.min(arr[i].geometry.coordinates[0],swlon) : arr[i].geometry.coordinates[0];
			nelat = (nelat) ? Math.max(arr[i].geometry.coordinates[1],nelat) : arr[i].geometry.coordinates[1];
			nelon = (nelon) ? Math.max(arr[i].geometry.coordinates[0],nelon) : arr[i].geometry.coordinates[0];
		}
		
		return [{lat:swlat,lon:swlon},{lat:nelat,lon:nelon}]
	}
	
	function buildMap(parent,mapdata){
		var extent,coordinates;
		
		coordinates = params.coords();
		if(coordinates.length<1){
			extent = getExtent(mapdata);
		} 
		
		var userid = params.get("uid");
		var sheetid = params.get("sid");
		
		var mapwrapper = jQuery("<div/>").attr("id","map-wrapper");
		mapwrapper.append(jQuery("<div/>").attr("id","map"));
		parent.append(mapwrapper);
		
		// now map-a-ize
		var svg = org.polymaps.svg("svg");
		var parent = document.getElementById('map').appendChild(svg);

		var map = org.polymaps.map();
		map.container(parent);

		var tiles = org.polymaps.image();
		tiles.url('http://a2.acetate.geoiq.com/tiles/acetate-bg/{Z}/{X}/{Y}.png');

		map.add(tiles);
		
		//http://dotspotting.org/u/12/sheets/745/export.json
		// need a jsonp output

		//.url("http://dotpoop.appspot.com/?sid=745&uid=12&callback=?")
		if(mapdata){
			var dots = org.polymaps.geoJson()
				.features(mapdata)
		       	.on("load",dotsloaded);
			map.add(dots);
		}

		var controls = org.polymaps.interact();
		map.add(controls);

		// This is the "hash" control which will update the URL in the browser
		// with the maps center point (and zoom level) so that you can more easily
		// share specific parts of a map with people.

		var hash = org.polymaps.hash();
		map.add(hash);

		// This will add clickable "zoom" controls to the map. In Polymaps this
		// is called a "compass" and is actually two separate types of controls:
		// Panning and zooming. In this example, we'll specify a small zoom control
		// and disable the panning control entirely. IMPORTANT: compass controls
		// do not have any default styling so you will need to specify that in
		// your CSS (below).

		var compass = org.polymaps.compass();
		compass.zoom("small");
		compass.pan("none");
		map.add(compass);

		map.zoomRange([2, 17]);

		if(coordinates.length > 1){
			map.center({lat: coordinates[1], lon: coordinates[2]});
			map.zoom(coordinates[0]);
		}else{
			map.extent(extent);
		}
	}
	
	function dotsloaded(e){
		for (var i = 0; i < e.features.length; i++) {
			var f = e.features[i],
			c = jQuery(e.features[i].element),
			props = f.data.properties,
			elm = f.element,
			p = e.features[i].element.parentNode;
		
			if(elm){	

				elm.setAttribute('class', "dot");
				elm.setAttribute('r', 8);
				jQuery(elm).mouseover(function(e){
					
					jQuery(this)[0].setAttribute("class","dotHover");
					//infoDisplay($(this).attr("id") );
				});
				jQuery(elm).mouseout(function(e){
					//infoDisplay("" );
					jQuery(this)[0].setAttribute("class","dot");
				});
			
			}
		}
	}
	
	function Querystring(qs) { // optionally pass a querystring to parse
		this.params = {};

		if (qs == null || qs.length == 0) return;

		// Turn <plus> back to <space>
		// See: http://www.w3.org/TR/REC-html40/interact/forms.html#h-17.13.4.1
		qs = qs.replace(/\+/g, ' ');
		var args = qs.split('&'); // parse out name/value pairs separated via &

		// split out each name=value pair
		for (var i = 0; i < args.length; i++) {
			var pair = args[i].split('=');
			var name = decodeURIComponent(pair[0]);

			var value = (pair.length==2)
				? decodeURIComponent(pair[1])
				: name;

			this.params[name] = value;
		}

		/**/
		this.get = function(key, default_) {
			var value = this.params[key];
			return (value != null) ? value : default_;
		}

		this.contains = function(key) {
			var value = this.params[key];
			return (value != null);
		}
		
		this.coords = function(){
			var _coord = this.get("xyz");
			if(!_coord)return [];
			 var args = _coord.split("/").map(Number);
			 if (args.length < 3 || args.some(isNaN)){
					return [];//defaults = 15, 37.4, -121.98 ??
			 } else {
				return args;
			 }
			
		}
	}
	
	
	////
	/* fix for missing Array methods in IE */
	if (!Array.prototype.map) {
	    Array.prototype.map= function(mapper, that /*opt*/) {
	        var other= new Array(this.length);
	        for (var i= 0, n= this.length; i<n; i++)
	            if (i in this)
	                other[i]= mapper.call(that, this[i], i, this);
	        return other;
	    };
	}
	if (!Array.prototype.some) {
	    Array.prototype.some = function(tester, that /*opt*/) {
	        for (var i= 0, n= this.length; i<n; i++)
	            if (i in this && tester.call(that, this[i], i, this))
	                return true;
	        return false;
	    };
	}
	if (!Array.prototype.indexOf)
	{
	  Array.prototype.indexOf = function(searchElement /*, fromIndex */)
	  {
	    "use strict";

	    if (this === void 0 || this === null)
	      throw new TypeError();

	    var t = Object(this);
	    var len = t.length >>> 0;
	    if (len === 0)
	      return -1;

	    var n = 0;
	    if (arguments.length > 0)
	    {
	      n = Number(arguments[1]);
	      if (n !== n) // shortcut for verifying if it's NaN
	        n = 0;
	      else if (n !== 0 && n !== (1 / 0) && n !== -(1 / 0))
	        n = (n > 0 || -1) * Math.floor(Math.abs(n));
	    }

	    if (n >= len)
	      return -1;

	    var k = n >= 0
	          ? n
	          : Math.max(len - Math.abs(n), 0);

	    for (; k < len; k++)
	    {
	      if (k in t && t[k] === searchElement)
	        return k;
	    }
	    return -1;
	  };
	}

// end this
})();