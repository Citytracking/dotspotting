//define globals
var pot,params,colors,bucketColumn,mdict,ds_tooltip,backdict;

// style objects for dot
var over_style = {
	'fill' : 'rgb(11,189,255)',
	'fill-opacity' : .8,
	'stroke' : '#666666',
	'stroke-width' : 2,
	'stroke-opacity':1
}; 



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

        
        function doCluster(){
            //clusterMarkers(pot.dotsLayer.markers);
            //pot.map.setZoom(pot.map.getZoom());
        }
        
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
            coords = geom[0]['coordinates'],
            pid = "dot_"+props.id;
            
            
            props.__active = true,
            updateValues(markerVal);
            
            
            if(feature.properties.__rollover_message){
                if(!rollover_tmpl){
                    rollover_tmpl = "<span>"+feature.properties.__rollover_message+"</span>";
                    $.template( "rollover_tmpl", rollover_tmpl );
                }
                props.tipMessage = $.tmpl( "<span>"+feature.properties.__rollover_message+"</span>",props);
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
               if(markers[i].attrs.props[prop] == val){
                   return markers[i];
               } 
            }
        }
        
        function getChildrenNodes(val,markers,len){
            var results = [];
            for(i=0;i<len;i++){
                var node_props = markers[i].attrs.props.nodes.split(":");
               if(node_props[1] == val){
                   results.push(markers[i]);
               } 
            }
            return results
        }
        
        function gatherNodes(markers,len){
            node_list = {};
            // get masters
            
            for(i=0;i<len;i++){
                if(markers[i].attrs.props.nodes){
                    var node_props = markers[i].attrs.props.nodes.split(":");
                    
                    var nn = getMasterNode($.trim(node_props[0]),node_props[1],markers,len);
                    if(nn){
                        if(!node_list[node_props[1]]){
                            node_list[node_props[1]] = {
                                col: $.trim(node_props[0]),
                                master:nn,
                                children:[]
                            }
                        }
                    }
                }
            }
            
            
            for(obj in node_list){
                node_list[obj].children = getChildrenNodes(obj,markers,len)
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
                dotAddClass(node_list[obj].master.node,"node_master");
                
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
            // uses jQuery live
            ds_tooltip = new DotToolTip(".dott");
            
            
            var markers = pot.dotsLayer.markers,
            len = markers.length;
            //var canvas = pot.dotsLayer.canvas;

            for(i=0;i<len;i++){
                if(markers[i].attrs._kirbyPos == "front"){
                    //var max = (markers[i].attrs.markerSize) ? (markers[i].attrs.markerSize / maxValue) * maxSize : minSize;
                    var size = (markers[i].attrs.markerSize) ? (markers[i].attrs.markerSize) : minSize;
                    var radius =  (((Math.sqrt(Math.abs(size))) / Math.sqrt(maxValue))) * maxSize;
                    if(radius < minSize)radius = minSize;
                    markers[i].attr("r",radius);
                    if(!mdict[markers[i].attrs.id])mdict[markers[i].attrs.id] = markers[i];
                    
                    
                    /*
                    var txt = canvas.text(markers[i].attrs.cx,markers[i].attrs.cy, "000");
                    txt.coord = markers[i].coord;
                    misc.push(txt);
                    */
                }
            }
            
            sortMarkers(markers,"desc");
            if(renderAsNodes)createLines();
        
            
            
            if(infoPanelText){
                $("#info_panel").html("<p>" + infoPanelText + "</p>");
            }
            
            
             
            

            //doCluster();
            
         
        });
        if(renderAsNodes){
            pot.map.addCallback("panned", repositionMisc);
            pot.map.addCallback("zoomed", repositionMisc);
            pot.map.addCallback("extentset", repositionMisc);
            pot.map.addCallback("resized", repositionMisc);
        }
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
        var x = parseFloat(a.attrs.markerSize);
        var y = parseFloat(b.attrs.markerSize);
        return (dir == "asc") ? x - y : y - x;
    }
    var markers = markers || pot.dotsLayer.markers,
    len = markers.length;
    markers.sort(sortee);
    var len = markers.length;
    for(i=0;i<len;i++){
        mdict[markers[i].attrs.id].toFront();
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

function dotHasClass(element, $class) {
    if(!element.className.baseVal)return false;
    var pattern = new RegExp("(^| )" + $class + "( |$)");
    return pattern.test(element.className.baseVal) ? true : false;
};

function dotAddClass(element, $class) {
	if(!element)return;
    var i,newClass;
    //is the element array-like?
    if(element.length) {
        for (i = 0; i < element.length; i++) {

            if (!this.dotHasClass(element[i], $class)) {
				newClass = element[i].className.baseVal;
                newClass += element[i].className.baseVal === "" ? 
                $class : " "+$class;
				element.setAttribute('class', newClass);
            }
        }
    }
    else { //not array-like
        if (!this.dotHasClass(element, $class)) {
			newClass = element.className.baseVal;
            newClass += (element.className.baseVal === "") ? $class : " "+$class;
			element.setAttribute('class', newClass);
        }
    }
    return element;
};

function dotRemoveClass(element, $class) {
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
};





//////////////////////////
/////////////////////////

function DotToolTip(selector) {
    if(!pot){
        console.log("Needs a dotspotting pot object....");
        return;
    }
    
    if(!selector){
        pot.error("ERROR: needs a selector for DOM element(s) to listen for...");
        return;
    }
    
    this.container = $(pot.selectors.map);
 
    
    if(!this.container[0]){
        pot.error("ERROR: map DOM element seems to be missing.");
        return;
    }
    
    this.map = pot.map;
    this.listenFrom = selector;

    this.updateSize();
    this.createTip();
}

DotToolTip.prototype = {
    container: null,
    map: null,
    listenFrom:null,
    currentDot: null,
    currenProp:null,
    tt: $("#mm_tip"),
    tt_title: $("#mm_tip_title"),
    tt_desc: $("#mm_tip_desc"),
    tt_nub: $("#mm_tip_nub"),
    TT_WIDTH: 300,
    cont_offset: null,
    cont_width: null,
    cont_height: null,
    tip_title: null,
    tip_desc: null,
    tip_sentence:null,
    active:false,
    
    createTip: function(){
        if(this.checkParams()){
            this.active = true;
            this.addHandlers();
        }
    },
    addHandlers: function(){
        var that = this;
        that.removeHandlers();
        $(this.listenFrom).live("mouseover mouseout", function(event) {
            event.preventDefault();
            if ( event.type == "mouseover" ) {
                
                var id = String($(this).attr('id'));
                if(!id)return;
                if(!mdict[id])return;
                if(!mdict[id].attrs.props['__active'])return;
               
                mdict[id].attrs.props["__dt_coords"] = mdict[id].coord;
                /// proceed
                that.currentDot = this;
                that.currentProp = mdict[id].attrs.props;
                this.parentNode.appendChild(this);
                that.showTip();
            } else {
                //that.currentDot = that.currentProp = null
                var id = String($(this).attr('id'));
                if(!id)return;
                if(!mdict[id])return;
                
                //mdict[id].toBack();
                dotRemoveClass(that.currentDot,"over_hover");
                that.hideTip();
                sortMarkers(pot.dotsLayer.markers);//
            }
            return false;
        });
        this.map.addCallback("resized", defer(that.updateSize,100));

    },
    
    removeHandlers: function(){
        this.map.removeCallback("resized");
        $(this.listenFrom).die("mouseover mouseout");
    },
    
    destroy: function(){
        this.removeHandlers();
        this.hideTip();
        this.currentDot = null;
        this.currentProp = null;
        this.container = null;
        this.map = null;
        this.tt = null;
        this.tt_title = null;
        this.tt_desc = null;
        this.tt_nub = null;
    },
    
    hideTip: function(){
        if(this.tt)this.tt.hide();
    },
    
    showTip: function(){
        if(!this.currentProp)return;
        
        if(this.currentProp.tipMessage){
            this.tt_title.css("display","block");
            this.tt_title.html(this.currentProp.tipMessage);
            this.tt_desc.css("display","none");
        }else{
            var _title = this.getTipTitle();
            var _desc = this.getTipDesc();
            if(!_title.length && !_desc.length)return;
            if(_title){
                this.tt_title.css("display","block");
                this.tt_title.html(_title);
            }else{
                this.tt_title.css("display","none");
            }
            if(_desc){
                this.tt_desc.css("display","block");
                this.tt_desc.html(_desc);
            }else{
                this.tt_desc.css("display","none");
            }
        }
        
        dotAddClass(this.currentDot,"over_hover");
        this.initialTipPosition();
    },
    
    initialTipPosition: function(){
        
        this.tt.css("left","-9999px")
        this.tt.css("width","auto");
        var _w = (this.tt.width() < this.TT_WIDTH) ? this.tt.width() : this.TT_WIDTH;
        if(_w < 70)_w = 70;
        this.tt.css("width",_w+"px");
        this.tt.width(_w);
        //
        var _point = this.map.coordinatePoint(this.currentProp.__dt_coords);
        var _h = this.tt.height();
        var _radius = parseFloat(this.currentDot.getAttribute('r'));
        var _circleHeight = this.currentDot.getBBox().height;
        var _x = parseFloat(_point.x - 10);

     
        // y = Marker location - (tip box height + nub height + radius + border size)
        var _y = _point.y - (_h + 10 + _radius + 6); // 22

        /*
        if(_tc.left < 0 )_tc.left = 1;
        if(_tc.left > cont_width)_tc.left = cont_width-1;
        */

        var pos_pct = (_point.x / this.cont_width);

        var nub_pos = ((_w-20) * pos_pct);
        if(nub_pos<6)nub_pos = 6;
        

        this.tt_nub.css("left",nub_pos+"px");
        this.tt.css("margin-left", "-"+nub_pos+"px");
            
        this.tt.show();
        this.tt.css("left", _x).css("top", _y); 
    },
    
    updateSize: function(){
        this.container = this.container ||  $(pot.selectors.map);
        this.cont_offset = this.container.offset();
        this.cont_width = this.container.width();
        this.cont_height = this.container.height();
    },
    
    unselectAllDots: function(){
        this.currentDot = this.currentProp = null;
    	$(this.listenFrom).each(function(){
    		dotRemoveClass($(this)[0],'over_hover');
    	});
    },
    
    getTipTitle: function(){
        return (this.tip_title && this.tip_title.length && this.currentProp[this.tip_title]) ? this.currentProp[this.tip_title] : "";
    },

    getTipDesc: function(){

        if(this.tip_sentence){
            var txt = this.tip_sentence.struct;
            return txt.replace(this.tip_sentence.parts[0],this.currentProp[this.tip_sentence.parts[1]]);
        }else{
            return (this.tip_title && this.tip_title.length && this.currentProp[this.tip_desc]) ? this.currentProp[this.tip_desc] : "";
        }
    },
    
    checkParams: function(){
        // look for tooltip parameters
        if(!params.tt && !params.tm){
          isTip = false;
        }else{
          isTip = true;
          if(params.tt){
              this.tip_title = params.tt;
          }else{
              this.tip_title = "id";
          }

          if(params.tm){

          //var re= new RegExp (/{(.*)}/gi);
          var re= new RegExp (/{([\w:]*)?\s?(.*?)}/gi);

          var m=re.exec(params.tm);

          if(m && m[1]){
              this.tip_sentence = {
                  "struct":params.tm,
                  "parts":m
              };
          }
              this.tip_desc = params.tm;
          }else{
              this.tip_desc = "";
          }
        }
        isTip = true;
        //this.tip_title = "id";
        return isTip;
    }
}


