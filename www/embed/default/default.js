var tip_title,
    tip_desc,
    isTip,
    tip_sentence,
    okay = true,
    tile_template = null;


// display error message in overlay
function err(message) {
    if(console && console.error)console.error("::", message);
    $("#overlay").append($("<p class='error'/>")
        .html(message));
    okay = false;
}
    
// gather page parameters from the query string
var params = {},
    paramMatch = location.search.match(/(\w+)=([^&$]+)/g);
if (paramMatch) {
    var len = paramMatch.length;
    for (var i = 0; i < len; i++) {
        var part = paramMatch[i].split("=");
        params[part[0]] = decodeURIComponent(part[1]).replace(/\+/g, " ");
    }
}

// TODO: get the title from the JSON response here?
if (params.title) {
    $("#overlay .title a").text(params.title);
    $(".controls").css("top",($("#overlay .title a").height()+20)+"px");
} else {
    $("#overlay .title").css("display", "none");
}
if (params.href) {
    $("#overlay .title a").attr("href", params.title);
}


// look for tooltip parameters
if(!params.tt && !params.tm){
    isTip = false;
}else{
    isTip = true;
    if(params.tt){
        tip_title = params.tt;
    }else{
        tip_title = "id";
    }

    if(params.tm){
    
    //var re= new RegExp (/{(.*)}/gi);
    var re= new RegExp (/{([\w:]*)?\s?(.*?)}/gi);

    var m=re.exec(params.tm);
   
    if(m && m[1]){
        tip_sentence = {
            "struct":params.tm,
            "parts":m
        };
    }
        tip_desc = params.tm;
    }else{
        tip_desc = "";
    }
}

function processAttribution(x){
    var _copy = null;
    if(x.search(/http:\/\/oatile1.mqcdn.com/i) === 0){
        _copy = 'Tiles Courtesy of <a href="http://www.mapquest.com/" target="_blank">MapQuest</a> <img src="http://developer.mapquest.com/content/osm/mq_logo.png"> Portions Courtesy NASA/JPL-Caltech and U.S. Depart. of Agriculture, Farm Service Agency';
    }else if(x.search(/http:\/\/b.tile.cloudmade.com/i) === 0){
        _copy = '&copy; 2010 <a href="http://www.cloudmade.com/">CloudMade</a>, <a href="http://www.openstreetmap.org/">OpenStreetMap</a> <a href="http://creativecommons.org/licenses/by-sa/2.0/">CCBYSA</a>';
    }
    if(_copy)$("#copyright").html(_copy);  
}


// parseUri 1.2.2
// (c) Steven Levithan <stevenlevithan.com>
// MIT License

function parseUri (str) {
	var	o   = parseUri.options,
		m   = o.parser[o.strictMode ? "strict" : "loose"].exec(str),
		uri = {},
		i   = 14;

	while (i--) uri[o.key[i]] = m[i] || "";

	uri[o.q.name] = {};
	uri[o.key[12]].replace(o.q.parser, function ($0, $1, $2) {
		if ($1) uri[o.q.name][$1] = $2;
	});

	return uri;
};

parseUri.options = {
	strictMode: false,
	key: ["source","protocol","authority","userInfo","user","password","host","port","relative","path","directory","file","query","anchor"],
	q:   {
		name:   "queryKey",
		parser: /(?:^|&)([^&=]*)=?([^&]*)/g
	},
	parser: {
		strict: /^(?:([^:\/?#]+):)?(?:\/\/((?:(([^:@]*)(?::([^:@]*))?)?@)?([^:\/?#]*)(?::(\d*))?))?((((?:[^?#\/]*\/)*)([^?#]*))(?:\?([^#]*))?(?:#(.*))?)/,
		loose:  /^(?:(?![^:@]+:[^:@\/]*@)([^:\/?#.]+):)?(?:\/\/)?((?:(([^:@]*)(?::([^:@]*))?)?@)?([^:\/?#]*)(?::(\d*))?)(((\/(?:[^?#](?![^?#\/]*\.[^?#\/.]+(?:[?#]|$)))*\/?)?([^?#\/]*))(?:\?([^#]*))?(?:#(.*))?)/
	}
};


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

// check for a different base tile
if(params.base && (params.base = ensure_valid_url_template(params.base))){ 
    tile_template = params.base;
    processAttribution(params.base);
}else{
    tile_template = "http://spaceclaw.stamen.com/tiles/dotspotting/world/{Z}/{X}/{Y}.png";
}


/**
 * JSON-P callback support.
 */
var url;
if (params.search) {
    params = {
        e: params.search,
        u: params.user,
        s: params.sheet,
        format: "json",
        inline: 1,
        force: 1 // use response caching
    };
    // TODO: update with production URL once it's live
    url = "http://dotspotting.org/search/export";
} else {
    if (!params.user) {
        err("You must provider a <strong>user</strong> parameter (<tt>?user={id}</tt>).");
    }
    if (!params.sheet) {
        err("You must provider a <strong>sheet</strong> parameter (<tt>?sheet={id}</tt>).");
    }
    if (okay) {
        url = _dotspotting.abs_root_url+"u/{user}/sheets/{sheet}/export"
            .replace("{user}", params.user)
            .replace("{sheet}", params.sheet);
        if (!params.href) {
            var href = url.substr(0, url.lastIndexOf("/"));
            var duh = $("#overlay .title a").attr("href", href).length;
        }
        params = {
            format: "json",
            inline: 1
        };
    }
}


if (!url) {
    err("No URL. :(");
}

if (okay) {
    // update the URL
    var updated = false;
    for (var p in params) {
        if (typeof params[p] !== "undefined" && String(params[p]).length > 0) {
            url += (url.indexOf("?") > -1) ? "&" : "?";
            url += p + "=" + params[p];
            updated = true;
        }
    }
    
    /*
    if (updated) {
        $("#crimes").data("url", url);
    } else {
        // console.log("using default url:", $("#crimes").data("url"));
    }
    */
    
    $.ajax(url, {
        dataType: "jsonp",
        success: function(x){
            createMap(x);
        },
        error: function(x){
            err("Could not load data. :(");
        }
    });
    
}
    
    // Do all this stuff on load
function createMap(data){
        var mm = com.modestmaps;
        // problem w/ Safari not setting map height to 100%
        // not sure why, so this seems to fix problem for now...
        $("#map").css("height","100%");

        
        $("#map").each(function() {
        
            var dims = undefined;

        	var handlers = [
        			// how to disable the scroll wheel ?
        			new mm.MouseHandler()
        	];
        	
            var provider = new mm.TemplatedMapProvider(tile_template);
            var map = new mm.Map("map", provider, dims, handlers);
            
            var loc = new mm.Location(37.6,-114.6);
    		map.setCenterZoom(loc, 2);
    		
            
            var container = $(this).parent(),
                preventer = new ClickPreventer(),
                markers = [],
                ds_tooltip = null;
                
            /**
             * XXX: this is weird. When we're in an <iframe> the
             * CSS that sets html, body and #map to width and
             * height to 100% doesn't seem to have the desired
             * effect. So we set size(null), which does a
             * mm.Map.setupDimensions(), updating the dimensions
             * accordingly.
             */
             //map.setSize(null);
            
           
            /**
             * Read the center and zoom (#zoom/lat/lon) from the
             * fragment identifier, and keep reading it.
             */
            var alreadyCentered = false,
                hash = new MapHash(map);
            
            if (location.hash.length > 1) {
                alreadyCentered = hash.read(location.hash);
            }
            
             
            hash.start(); // udpate on pan/zoom

            
            function getHref(props, id) {
                return "http://dotspotting.org/u/{user_id}/dots/{id}/"
                    .replace("{user_id}", props.user_id)
                    .replace("{id}", id);
            }
            
            // map controls
            $(".zoom-in").click(function(e){
               e.preventDefault();
               map.zoomIn(); 
            });
            $(".zoom-out").click(function(e){
               e.preventDefault();
               map.zoomOut(); 
            });
            
           
           
            /* TOOLTIP FUNCTIONS */
            
            function TipController(){
                var tip = {};
                
                var tt = $("#mm_tip");
                var tt_title = $("#mm_tip_title");
                var tt_desc = $("#mm_tip_desc");
                var tt_nub = $("#mm_tip_nub");
                var TT_WIDTH = 300;
                var cont_offset = container.offset();
                var cont_width = container.width();
                var cont_height = container.height();
                var self = this;
                
                
                var current_prop = null,
                    current_marker,
                    nub_class = "left";
                
                // adjust tip for smaller displays
                if(tt.width()/container.width() > .5){
                    tt.width(container.width() * .5);
                }
               
                function getTipTitle(){
                    return (tip_title.length && current_prop[tip_title]) ? current_prop[tip_title] : "";
                }

                function getTipDesc(){

                    if(tip_sentence){
                        var txt = tip_sentence.struct;
                        return txt.replace(tip_sentence.parts[0],current_prop[tip_sentence.parts[1]]);
                    }else{
                        return (tip_title.length && current_prop[tip_desc]) ? current_prop[tip_desc] : "";
                    }
                }
                
                function initialTipPosition(){
                    tt.css("left","-9999px")
                    tt.css("width","auto");
                    var _w = (tt.width() < TT_WIDTH) ? tt.width() : TT_WIDTH;
                    tt.width(_w);
                    //
                    
                   // var _tc = $(current_marker).offset();
                    var _point = map.locationPoint(current_prop.__dt_coords);
                    var _h = tt.height();
                    var _radius = current_marker.getAttribute('r');
                    var _x = parseFloat(_point.x - 10);
                    var _y = _point.y - (22+_h);
                    
                    /*
                    if(_tc.left < 0 )_tc.left = 1;
                    if(_tc.left > cont_width)_tc.left = cont_width-1;
                    */
                    
                    var pos_pct = (_point.x / cont_width);
                    
                    var nub_pos = ((_w-20) * pos_pct);
                    if(nub_pos<6)nub_pos = 6;
                    
                    tt_nub.css("left",nub_pos+"px");
                    tt.css("margin-left", "-"+nub_pos+"px");
                                     
                         
                    
                    tt.show();
                    tt.css("left", _x).css("top", _y);
                    
                   
                }

                function showTip(){
                    if(!current_prop)return;
                    
                    tt_title.html(getTipTitle());
                    tt_desc.html(getTipDesc());
                    dotAddClass(current_marker,"over_hover");
                    initialTipPosition();
                }

                function hideTip(){
                    if(tt)tt.hide();
                }
                
                function updateContSize(){
                    cont_offset = container.offset();
                    cont_width = container.width();
                    cont_height = container.height();
                }
             
                tip.show = function(){
                    var id = $(this).attr('id');
                    if(!id)return;
                    if(!marker_props[String(id)])return;
                    current_marker = this;
                    current_prop = marker_props[String(id)];
                    this.parentNode.appendChild(this);
                    showTip(); 
                }
                tip.hide = function(){
                    dotRemoveClass(current_marker,"over_hover");
                    hideTip(); 
                }
                tip.destroy = function(){
                    //
                }
                
                map.addCallback("resized", defer(updateContSize,100));
                return tip;
                
            }

           if(isTip)ds_tooltip = new TipController();
           

            function updateFinally() {
                try {
                    updateExtent();
                    
                } catch (e) {
                    // console.warn("ERROR updateFinally():", e);
                }
            }
            
            /**
             * The layer template is a function that takes a GeoJSON
             * feature and returns a DOM element marker.
             */
             var marker_props = {};
             
            var markerLayer = new mm.DotMarkerLayer(map, provider);            
            
            if(data){
                var features = data.features,
                len = features.length;
                
                 var over_style = {
                   		'fill' : 'rgb(11,189,255)',
                   		'fill-opacity' : 1,
                   		'stroke' : 'rgb(11,189,255)',
                   		'stroke-width' : 1
                   	};
                   	var under_style = {
                   		'fill' : 'rgb(10,10,10)',
                   		'fill-opacity' : 1,
                   		'stroke' : 'rgb(10,10,10)',
                   		'stroke-width' : 1
                   	};
                
                for(i=0;i<len;i++){
                    var feature = features[i],
                    props = feature.properties,
                    href = getHref(props, feature.id),
                    desc = props["request"] || props["description"] || "",
                    geom = (feature.geometry.type == 'GeometryCollection') ? feature.geometry.geometries : [ feature.geometry ];
                    
                    var coords = geom[0]['coordinates'];
                    var pid = "dot_"+props.id;
                    var more_front = {
                        style: over_style,
                        id:pid,
                        radius:6
                    };
                    
                    var more_back = {
                        style: under_style,
                        radius:12
                    };
                            			
        			var loc = new mm.Location(coords[1],coords[0]);
        			props.__dt_coords = loc;
                   
                    var marker = markerLayer.addMarker(more_front,loc);
                    var c = markerLayer.addMarker(more_back,loc);
                    c.toBack();
                    // interaction handlers
                    
      
                    if(ds_tooltip){
                         marker.node.onmouseover = ds_tooltip.show;
                         marker.node.onmouseout = ds_tooltip.hide;
                    }

                    // remember it for iteration later
                    markers.push(marker);

                    marker_props[String(pid)] = props
                    // defer a final update for a while so we can
                    // cluster and set the initial map extent
                    defer(updateFinally, 100)();

                    // prevent clicks if we dragged on this marker
                    preventer.prevent(marker);
                    
                }
            }


            // Quantize a number by a divisor
            // quantize(x, prec)
            function quantize(n, q) {
                return Math.round(n / q) * q;
            }
            
            
            function showhash(){
                if(typeof window.parent.hashMe == 'function') {
                    window.parent.hashMe(location.hash);
                }
            }
            
            if(typeof window.parent.hashMe == 'function') {
                map.addCallback("drawn", defer(showhash, 100));
            }
            

            /**
             * Here we grab all of the locations and set the map extent.
             */
            function updateExtent() {
             
                var locations = [],
                    len = markers.length;
                    
                
                for (var i = 0; i < len; i++) {
                    var marker = markers[i],
                        loc = marker.location;
                    if (loc.lat != 0 && loc.lon != 0) {
                        locations.push(loc);
                    }
                }
                if (locations.length && !alreadyCentered) {
                    map.setExtent(locations);
                }
                
            }
            

        });
}



function capitalize(word) {
    return word.charAt(0).toUpperCase() + word.substr(1).toLowerCase();
}

function abbreviate(group) {
    var words = group.split(" ");
    if (words.length > 1) {
        return (words[0].charAt(0) + words[1].charAt(0)).toUpperCase();
    } else {
        return group ? capitalize(group.substr(0, 2)) : "?";
    }
} 

/* ------------------------------------------------------------------------*/
/* Series of helper classes to manage add and deleting classes on SVG dots */
/* ------------------------------------------------------------------------*/

function dotHasClass(element, $class) {
    var pattern = new RegExp("(^| )" + $class + "( |$)");
    return pattern.test(element.className.baseVal) ? true : false;
}


function dotAddClass(element, $class) {
	if(!element)return;
    var i,newClass;
    //is the element array-like?
    if(element.length) {
        for (i = 0; i < element.length; i++) {
			
            if (!dotHasClass(element[i], $class)) {
				newClass = element[i].className.baseVal;
                newClass += element[i].className.baseVal === "" ? 
                $class : " "+$class;
				element.setAttribute('class', newClass);
            }
        }
    }
    else { //not array-like
        if (!dotHasClass(element, $class)) {
			newClass = element.className.baseVal;
            newClass += (element.className.baseVal === "") ? $class : " "+$class;
			element.setAttribute('class', newClass);
        }
    }
    return element;
}

function dotRemoveClass (element, $class) {
	if(!element)return;

    var pattern = new RegExp("(^| )" + $class + "( |$)");
    var i,newClass;

    //is element array-like?
    if(element.length) {
        for (i = 0; i < element.length; i++) {
			newClass = element[i].className.baseVal;
            newClass = newClass.replace(pattern, "$1");
            newClass = newClass.replace(/ $/, "");  
			element.setAttribute('class', newClass);          
        }
    }
    else { //nope
		newClass = element.className.baseVal;
        newClass = newClass.replace(pattern, "$1");
        newClass = newClass.replace(/ $/, ""); 
		element.setAttribute('class', newClass); 
    }
    return element;
}

/*
// it does as the name says it does
function unselectAllDots(){
	$(".dot").each(function(){
		dotRemoveClass($(this)[0],'over_hover');
	});
}

// generic function to reset dot styles
function dot_unselect(dotid){
	$("#"+"dot_"+dotid).each(function(){
		dotRemoveClass($(this)[0],'over_hover');
	});
}
*/