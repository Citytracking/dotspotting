var pot,params,marker_props,ds_user_opts={},ds_kirby = false,mdict,ds_tooltip,backdict;

// style objects for dot layers
var over_style = {
	'fill' : 'rgb(11,189,255)',
	'fill-opacity' : 1,
	'stroke' : 'rgb(11,189,255)',
	'stroke-width' : 1
};
var under_style = {
	'fill' : 'rgb(255,255,255)',
	'fill-opacity' : .8,
	'stroke' : 'rgb(207,207,207)',
	'stroke-width' : 1
};
var hover_style = {
    'fill' : 'rgb(255,255,255)',
	'fill-opacity' : .8,
	'stroke' : '#666666',
	'stroke-width' : 2,
	'stroke-opacity':1
};  	
   	
$(function() {
    try{
        $("#map").css("height","100%");
        
        var mm = com.modestmaps;
        
        mdict = {},
        backdict = {},
        ds_tooltip = null,
        marker_props = {};
        
        params = parseQueryString(location.search);
        if (!params.baseURL) params.baseURL = baseURL;

        pot = new Dots.Potting(params);
        pot.setTitle();

        // map controls
        $(".zoom-in").click(function(e){
          e.preventDefault();
          pot.map.zoomIn(); 
        });
        $(".zoom-out").click(function(e){
          e.preventDefault();
          pot.map.zoomOut(); 
        });

        // adjust controls if title
        if (params.title) {
           $(".controls").css("top",($("#title").height()+20)+"px");
        }

        pot.dotsLayer = new mm.DotMarkerLayer(pot.map);

        pot.makeDot = function(feature) {
           var props = feature.properties,
           geom = (feature.geometry.type == 'GeometryCollection') ? feature.geometry.geometries : [ feature.geometry ];

           var coords = geom[0]['coordinates'],
           pid = "dot_"+props.id;
           
           var loc = new mm.Location(coords[1],coords[0]);
       	   props.__dt_coords = loc;
       	   props.__active = true;
       	   
           var more_front = {
               style: jQuery.extend({}, over_style),
               id:pid,
               radius:6,
               dotClass:"dott",
               props:props,
               _kirbyPos:"front"
           };

           var more_back = {
               style: jQuery.extend({}, under_style),
               _kirbyPos:"back",
               id:pid,
               radius:12
           };

    	   

           var marker = more_front;
   
           // Dots.Potting class only takes one marker, 
           // will manually add this one, for now, until I write a Kirby Dot markerLayer
           var c = pot.dotsLayer.addMarker(more_back,loc);
           c.toBack();
           
           backdict[pid] = c;
   
            // store props in key / value pairs
           marker_props[String(pid)] = props;  
  
          return marker;
        };


        var req = pot.load(null,function(){
            var markers = pot.dotsLayer.markers,
            len = markers.length;
            for(i=0;i<len;i++){
                
                if(markers[i].myAttrs['_kirbyPos'] == "front"){
                    var theID = markers[i].myAttrs.id;
                    if(!mdict[theID])mdict[theID] = markers[i];
                }
            }
        });
        
        // create tooltip
        // pass it the selector to listen for...
        // pulls rest of params from pot object
        // uses jQuery delegate
        if(typeof DotToolTip == 'function')ds_tooltip = new DotToolTip("[id*='dot_']");
        
        ////////////////////////////
        // ARE WE IN CONFIG MODE ////////////
        // SHould we do this .. this way?? //
        /////////////////////////////////////
        var _inConfig = null;
        try{ _inConfig = window.parent.ds_config.hasher; }catch(e){}
        /////////////////////////////////////////
        // used to update coordinates in config only
        function showhash(){
            if(ds_tooltip && ds_tooltip.active)ds_tooltip.updateSize();
            _inConfig(location.hash);
        }
        
        if((_inConfig) && (typeof _inConfig == 'function')){
            pot.map.addCallback("drawn", defer(showhash, 100));
        }
        ///////////////////////////////////////// 
    
    }catch (e) {
        console.error("ERROR: ", e);
        pot.error("ERROR: " + e);
    }
});