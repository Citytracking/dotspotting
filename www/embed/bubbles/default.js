//define globals
var pot,params,colors,bucketColumn,mdict,ds_tooltip,backdict,masterNode,ds_user_opts={};

// style objects for dot
var over_style = {
	'fill' : 'rgb(11,189,255)',
	'fill-opacity' : .8,
	'stroke' : '#666666',
	'stroke-width' : 2,
	'stroke-opacity':1
}; 

var hover_style = {
    'fill' : 'rgb(255,255,255)',
	'fill-opacity' : .8,
	'stroke' : '#666666',
	'stroke-width' : 2,
	'stroke-opacity':1
};
var masterNode_style = {
    'fill' : '#FFFF00'
}



// go on then	
$(function() {
   
    
    try{
        mdict = {};
        backdict = {};
        colors = d3.scale.category10();
        var maxValue = 0,
        minValue = Infinity;
        
        // see colorbrewer.js for more info
        //colors = d3.scale.ordinal().range(colorbrewer.Spectral[9]);
        
        $("#map").css("height","100%");

        var mm = com.modestmaps,
        ds_tooltip = null;
        


        params = parseQueryString(location.search);
        if (!params.baseURL) params.baseURL = baseURL;
        
        if(params.provider)params.base = params.provider;
        if (!params.base)params.base = "acetate";
        



        pot = new Dots.Potting(params);
        
        pot.setTitle();
         
        pot.dotsLayer = new mm.DotMarkerLayer(pot.map);
         
        
        // adjust controls if title
        if (params.title) {
           $(".controls").css("top",($("#title").height()+20)+"px");
           var pos = ($("#title").length > 0) ? $("#title").innerHeight() : 0;
           $("#info_panel").css("top",pos+"px");
        }
        
        

        // map controls
        $(".zoom-in").click(function(e){
          e.preventDefault();
          pot.map.zoomIn(); 
        });
        
        $(".zoom-out").click(function(e){
          e.preventDefault();
          pot.map.zoomOut(); 
        });

        
        var bubbleSizeColumn = (params.valcol) ? params.valcol : "id";
        var maxSize = (params.max) ? parseFloat(params.max) : 50,
            minSize = (params.min) ? parseFloat(params.min) : 6,
            renderAsNodes = (params.nodes && params.nodes == 1) ? true : false,
            rollover_tmpl,
            misc,
            node_list,
            infoPanelText;
        
        function updateValues(val){

            maxValue = Math.max(maxValue,val);
            minValue = Math.min(minValue,val);
        
        }
        
        
        pot.makeDot = function(feature) {
            normalizeFeature(feature);
            var props = feature.properties,
            markerVal = (feature.properties[bubbleSizeColumn]) ? feature.properties[bubbleSizeColumn] : 0,
            geom = (feature.geometry.type == 'GeometryCollection') ? feature.geometry.geometries : [ feature.geometry ];
            
            if(props['__tooltip_config'] && !ds_user_opts['tooltip']){
                ds_user_opts['tooltip'] = props['__tooltip_config'];
            }
            
            if(props['__dot_style'] && !ds_user_opts['over_style']){
                ds_user_opts['over_style'] = parseJSON(props['__dot_style']);
                over_style = ds_user_opts['over_style'];
            }
            
            if(props['__dot_hover_style'] && !ds_user_opts['hover_style']){
                ds_user_opts['hover_style'] = parseJSON(props['__dot_hover_style']);
                hover_style = ds_user_opts['hover_style'];
            }
            
            coords = geom[0]['coordinates'],
            pid = "dot_"+props.id;
            
            
            props.__active = true,
            updateValues(markerVal);
            
           
            if(feature.properties.__rollover_message){
                
                feature.properties.__rollover_message = normalizeRolloverMessage(feature.properties.__rollover_message);
                if(!rollover_tmpl){
                    rollover_tmpl = "<span>"+feature.properties.__rollover_message+"</span>";
                    $.template( "rollover_tmpl", rollover_tmpl);
                    
                }
                //props.tipMessage = $.tmpl( "<span>"+feature.properties.__rollover_message+"</span>",props);
                props.tipMessage = $.tmpl( "rollover_tmpl",props);
            }else if(rollover_tmpl){
                props.tipMessage = $.tmpl( "rollover_tmpl",props);
            }
            

            if(!infoPanelText){
                if(feature.properties.__description_panel) infoPanelText = feature.properties.__description_panel;
            }
            
            
            var more_front = {
               style: over_style,
               id:pid,
               radius:6,
               markerSize:markerVal,
               dotClass:"dott",
               props:props,
               _kirbyPos:"front"
            };

            var loc = new mm.Location(coords[1],coords[0]);
           
            props.__dt_coords = loc;

            var marker = more_front;
            
           
            return marker;
        };
        
                
        function getMasterNode(prop,val,markers,len){
            for(i=0;i<len;i++){
               if(markers[i].myAttrs.props[prop] == val){
                   return markers[i];
               } 
            }
        }
        
        function getChildrenNodes(val,markers,len){
            var results = [];
            for(i=0;i<len;i++){
                var node_props = markers[i].myAttrs.props.nodes.split(":");
               if(node_props[1] == val){
                   results.push(markers[i]);
               } 
            }
            return results;
        }
        
        function gatherNodes(markers,len){
            node_list = {};
            // get masters
            
            for(i=0;i<len;i++){
                if(markers[i].myAttrs.props.nodes){
                    var node_props = markers[i].myAttrs.props.nodes.split(":");
                    
                    var nn = getMasterNode($.trim(node_props[0]),node_props[1],markers,len);
                    if(nn){
                        markers[i]['master']= true;
                        if(!node_list[node_props[1]]){
                            node_list[node_props[1]] = {
                                col: $.trim(node_props[0]),
                                master:nn,
                                children:[]
                            };
                        }
                    }
                }
            }
            
            
            for(obj in node_list){
                node_list[obj].children = getChildrenNodes(obj,markers,len);
            }
            
        }
        
        function repositionMisc(){
            createLines();
        }
        function createLines(){
            
            if(misc && misc.length){
                for(r=0;r<misc.length;r++){
                    misc[r].remove();
                }
            }
            misc=[];
            
            var markers = pot.dotsLayer.markers,
            len = markers.length;
            var canvas = pot.dotsLayer.canvas;

            if(!node_list){
                gatherNodes(markers,len);
            }
            
         
            
            for(obj in node_list){
                var pt1 = {x:node_list[obj].master.attrs.cx,y:node_list[obj].master.attrs.cy};                 
                node_list[obj].master.attr(masterNode_style);
                
                
                if(node_list[obj].children){
                    var len = node_list[obj].children.length;
                    for(i=0;i<len;i++){
                        var node = node_list[obj].children[i];
                        var pt2 = {x:node.attrs.cx,y:node.attrs.cy};
                        
                        var angle1 = Math.atan2(pt2.y - pt1.y, pt2.x - pt1.x);// * 180 / Math.PI;
                        var angle2 = Math.atan2(pt1.y - pt2.y, pt1.x - pt2.x);// * 180 / Math.PI;
                        
                        //
                        //angle1<0?angle1+=Math.PI*2:null;//correction for "negative" quadrants
                        //angle2<0?angle2+=Math.PI*2:null;//correction for "negative" quadrants
                        //
                        
                        var ox1 = node_list[obj].master.attrs.cx + node_list[obj].master.attrs.r * Math.cos(angle1);
                        var oy1 = node_list[obj].master.attrs.cy + node_list[obj].master.attrs.r * Math.sin(angle1);
                        var ox2 = node.attrs.cx + node.attrs.r * Math.cos(angle2);
                        var oy2 = node.attrs.cy + node.attrs.r * Math.sin(angle2);
                        
                        var line = "M"+ox1+","+oy1+"L" + ox2+","+oy2;
                                                
                        var c = canvas.path(line).attr({'stroke-width':4,'stroke':'#666666'});
                        c.coord = markers[i].coord;
                        misc.push(c);
                        c.toBack();
                    }
                }
                
            }
            
        }
        
        //load markers and do things when done
        
        var req = pot.load(null,function(){
  
            // create tooltip
            // pass it the selector to listen for...
            // pulls rest of params from pot object
            // uses jQuery delegate
            // because we are using Raphael we need to use the id as the selector
            if(typeof DotToolTip == 'function')ds_tooltip = new DotToolTip("[id*='dot_']");
            
            
            var markers = pot.dotsLayer.markers,
            len = markers.length;
            //var canvas = pot.dotsLayer.canvas;

            for(i=0;i<len;i++){
                if(markers[i].myAttrs._kirbyPos == "front"){
                    //var max = (markers[i].attrs.markerSize) ? (markers[i].attrs.markerSize / maxValue) * maxSize : minSize;
                    
                    var size = (markers[i].myAttrs.markerSize) ? (markers[i].myAttrs.markerSize) : minSize;
                    //var radius =  (((Math.sqrt(size)) / Math.sqrt(maxValue))) * maxSize;
                
                    
                    //http://blog.thematicmapping.org/2008/06/proportional-symbols-in-three.html
                    //var radius = (size / maxValue) * maxSize;
                    var radius = Math.pow(size/maxValue, 1/2) * maxSize;
                    
                    if(radius < minSize)radius = minSize;
                    
                    markers[i].attr("r",radius);
                    if(!mdict[markers[i].myAttrs.id])mdict[markers[i].myAttrs.id] = markers[i];
                    
                    
                    /*
                    var txt = canvas.text(markers[i].attrs.cx,markers[i].attrs.cy, "000");
                    txt.coord = markers[i].coord;
                    misc.push(txt);
                    */
                }
            }
            
            sortMarkers(markers,"desc");

            if(renderAsNodes){
                createLines();
                pot.map.addCallback("panned", repositionMisc);
                pot.map.addCallback("zoomed", repositionMisc);
                pot.map.addCallback("extentset", repositionMisc);
                pot.map.addCallback("resized", repositionMisc);
            }
        
            
            if(infoPanelText){
                $("#info_panel p").html(infoPanelText);
                $("#info_panel a").html("hide description");
                
                $("#info_panel a").click(function(e){
                    e.preventDefault();
                    if($("#info_panel p").is(":visible")){
                        $("#info_panel p").hide();
                         $(this).html("show description");
                    }else{
                        $(this).html("hide description");
                        var btnWidth = $(this).outerWidth() + 2;
                        $("#info_panel p").css('margin-right',btnWidth+"px");
                        $("#info_panel p").show();
                        
                        
                    }
                });
                
                $("#info_panel").show();
                $("#info_panel a").trigger('click');
            }else{
                $("#info_panel").remove();
            }
            
            // cluster markers
            pot.dotsLayer.cluster();
            
         
        });

       // this.map.addCallback("resized", defer(that.updateSize,100));
        

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

function sortMarkers(markers,dir){
    if(dir == undefined || dir == null){
        dir = "desc";
    }else{
        dir = (dir == "asc") ? "asc" : "desc";
    }

    function sortee(a, b) {
        var x = parseFloat(a.myAttrs.markerSize);
        var y = parseFloat(b.myAttrs.markerSize);
        return (dir == "asc") ? x - y : y - x;
    }
    var markers = markers || pot.dotsLayer.markers,
    len = markers.length;
    markers.sort(sortee);
    var len = markers.length;
    for(i=0;i<len;i++){
        mdict[markers[i].myAttrs.id].toFront();
    }
}

function normalizeFeature(feature) {
    var props = feature.properties;
    for (var p in props) {
        var norm = p.replace(/ /g, "_").toLowerCase();
        if (!props.hasOwnProperty(norm)) {
            props[norm] = props[p];
        }
    }
}

function parseJSON(str){ 
    var jData = JSON.parse(str, function (key, value) { 
        if(value){
            
            if (typeof value === 'string') {
                return String(value);  
            }else if(typeof value === 'number'){
                return Number(value);
            }
        }
        return value;
    });
    return jData;
}


