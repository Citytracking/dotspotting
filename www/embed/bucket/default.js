var pot,params,marker_props;

// style objects for dot layers
var over_style = {
	'fill' : 'rgb(11,189,255)',
	'fill-opacity' : 1,
	'stroke' : 'rgb(11,189,255)',
	'stroke-width' : 1,
	'stroke-opacity':0
};
var under_style = {
	'fill' : 'rgb(255,255,255)',
	'fill-opacity' : .8,
	'stroke' : 'rgb(207,207,207)',
	'stroke-width' : 1
};  

var colors,bucketColumn;
   	
$(function() {
    try{
        
        colors = d3.scale.category10();
        
        $("#map").css("height","100%");

        var mm = com.modestmaps,
        ds_tooltip = null;
        
        marker_props = {};

        params = parseQueryString(location.search);
        if (!params.baseURL) params.baseURL = baseURL;
        
        if(params.bucket)bucketColumn = params.bucket;

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
        
        // borough
        var typeSelector = new MenuSelector("#menu_types_wrapper","#menu_types", pot.dotsLayer);
        

         
        pot.makeDot = function(feature) {
            normalizeFeature(feature);
           var props = feature.properties,
           bucket_type = getBucketType(props),
           geom = (feature.geometry.type == 'GeometryCollection') ? feature.geometry.geometries : [ feature.geometry ];
           
           props.bucketType = bucket_type;
           props.__active = true;
           var coords = geom[0]['coordinates'];
           var pid = "dot_"+props.id;
           var more_front = {
               style: over_style,
               id:pid,
               radius:8,
               dotClass:"dott",
               type:bucket_type,
               props:props
           };
           

    	   var loc = new mm.Location(coords[1],coords[0]);
    	   props.__dt_coords = loc;

           var marker = more_front;

   
            // store props in key / value pairs
           marker_props[String(pid)] = props;  
           
           
           if (typeSelector) {
               var label = typeSelector.addLabel(props);
           }
           
  
          return marker;
        };


        var req = pot.load(null,function(){
            if (typeSelector) {
                
               typeSelector.selectorComplete();
                var markers = pot.dotsLayer.markers,
                len = markers.length,
                co = typeSelector.colorScheme;
                for(c in co){
                    for(i=0;i<len;i++){
                        if(c == markers[i].attrs['type']){
                            markers[i].attr("fill",co[c]);
                        }
                    }
                }

                
            }

        });
        
        // create tooltip
        // pass it the selector to listen for...
        // pulls rest of params from pot object
        // uses jQuery live
        ds_tooltip = new DotToolTip(".dott");
        
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

function getBucketType(props) {
    return props[bucketColumn] || "Unknown";
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


// borough
function MenuSelector(wrapper,selector, layer) {
    this.wrapper = $(wrapper);
    this.container = $(selector);
    this.canvas = Raphael("menu_types", this.menuWidth, 200);
    this.layer = layer;
    this.labelsByType = {};
    this.selectedTypes = {};
    this.buttonSets = {};
    this.colorScheme = {};
    this.labelStates = {};
    
    var that = this;
    this.show_all.click(function(e){
        e.preventDefault();
        for(t in that.labelStates){
            that.labelStates[t] = true;
            that.unselectButtons(t);
            that.showMarkers(t);
        }
        
    });
    this.hide_all.click(function(e){
        e.preventDefault();
        for(t in that.labelStates){
            that.labelStates[t] = false;
            that.selectButtons(t);
            that.hideMarkers(t);
        }
    });
}

MenuSelector.prototype = {
    container: null,
    canvas:null,
    layer: null,
    buttonSets: null,
    labelsByType: null,
    labelStates: null,
    buttonLength:0,
    selectedTypes: null,
    colorScheme: null,
    defaultTypeSelected: true,
    menuWidth:200,
    wrapper:null,
    show_all:$("#ct_show_all"),
    hide_all:$("#ct_hide_all"),

    
    getSortKey: function(data) {
        var indexes = {"violent": 1, "qol": 2, "property": 3};
        //return [indexes[data.group] || 9, data.label || data.type].join(":");
        return 1;
    },
    selectorComplete: function(){
        for(set in this.buttonSets){
            
            this.buttonSets[set][2].attr("width",this.menuWidth);
        }
    },
    
    addLabel: function(data) {
        var type = data.bucketType;
        
        if (this.labelsByType[type]) {
            var label = this.labelsByType[type];
            //label.data("count", label.data("count") + 1);
            return label;
        }
        var pos = this.buttonLength;
        var yPos = 10 + (pos * 25);
        var label = this.canvas.circle(10, yPos, 8);
        var txt = this.canvas.text(30, yPos, type);
        var clr = colors(pos);
        var _id = "c_"+type;
        this.colorScheme[type] = clr;
        label.attr({
            "fill":clr,
            "stroke-width":0
            });
        label.node.id = "c_"+type;
        txt.node.id = "t_"+type;
        txt.attr({
            "font-size":12,
            "font-weight":"bold",
            "text-anchor":"start"
        });
        
        var btn = this.canvas.rect(2,yPos - 10,190,20);
        btn.node.id = "b_"+type;
        btn.attr({
            "cursor":"pointer",
            "fill":"#000000",
            "fill-opacity":0,
            "stroke-width":0
        });
        var that = this;
        btn.node.onclick = function(e){
            var id = $(this).attr("id");
            id = id.slice(2);
            
            var state = !that.labelStates[id];
            
            if(state){
                that.unselectButtons(id);
                that.showMarkers(id);
            }else{
                that.selectButtons(id);
                that.hideMarkers(id);
            }
            that.labelStates[id] = state;
        }
        btn.node.onmouseover = function(e){
            var id = $(this).attr("id");
            btn.attr("fill-opacity",.3);
        }
        btn.node.onmouseout = function(e){
            btn.attr("fill-opacity",0);
           
        }

        this.labelsByType[type] = label;
        this.buttonSets[type] = [label,txt,btn];
        this.labelStates[type] = true;
        this.buttonLength ++;
        
        this.menuWidth = Math.max(this.menuWidth,(txt.node.clientWidth+50));

        this.canvas.setSize(this.menuWidth,yPos+20);
        //if(yPos > 200)this.canvas.attr("height",yPos+0);
        return label;
        
    },

    selectButtons: function(t){
        if(!this.buttonSets[t])return;
        var set = this.buttonSets[t];
        for(i=0;i<set.length-1;i++){
            set[i].attr("opacity",.4);
        }
    },
    
    unselectButtons: function(t){
        if(!this.buttonSets[t])return;
        var set = this.buttonSets[t];
        for(i=0;i<set.length-1;i++){
            set[i].attr("opacity",1);
        }
    },
 
    
    hideMarkers: function(t){
        var markers = this.layer.markers,
        len = markers.length;
        for(i=0;i<len;i++){
            if(t == markers[i].attrs['type']){
                markers[i].attrs['props']['__active'] = false;
                markers[i].attr("fill-opacity",0);
            }
        }
    },
    showMarkers: function(t){
        var markers = this.layer.markers,
        len = markers.length;
        for(i=0;i<len;i++){

            if(t == markers[i].attrs['type']){
                markers[i].attrs['props']['__active'] = true;
                markers[i].attr("fill-opacity",1);
            }
        }
    },
    
    
};



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
                var id = $(this).attr('id');
                if(!id)return;
                if(!marker_props[String(id)])return;
                if(!marker_props[String(id)]['__active'])return;
                /// proceed
                that.currentDot = this;
                that.currentProp = marker_props[String(id)];
                this.parentNode.appendChild(this);
                that.showTip();
            } else {
                that.dotRemoveClass(that.currentDot,"over_hover");
                that.hideTip();
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
        var _title = this.getTipTitle();
        var _desc = this.getTipDesc();

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

        this.dotAddClass(this.currentDot,"over_hover");
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


        var _point = this.map.locationPoint(this.currentProp.__dt_coords);
        var _h = this.tt.height();
        var _radius = this.currentDot.getAttribute('r');
        var _x = parseFloat(_point.x - 10);
        var _y = _point.y - (22+_h);

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
        this.cont_offset = this.container.offset();
        this.cont_width = this.container.width();
        this.cont_height = this.container.height();
    },
    
    dotHasClass: function(element, $class) {
        var pattern = new RegExp("(^| )" + $class + "( |$)");
        return pattern.test(element.className.baseVal) ? true : false;
    },

    dotAddClass: function(element, $class) {
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
    },

    dotRemoveClass: function(element, $class) {
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
    },
    
    unselectAllDots: function(){
        this.currentDot = this.currentProp = null;
    	$(this.listenFrom).each(function(){
    		dotRemoveClass($(this)[0],'over_hover');
    	});
    },
    
    getTipTitle: function(){
        return (this.tip_title.length && this.currentProp[this.tip_title]) ? this.currentProp[this.tip_title] : "";
    },

    getTipDesc: function(){

        if(this.tip_sentence){
            var txt = this.tip_sentence.struct;
            return txt.replace(this.tip_sentence.parts[0],this.currentProp[this.tip_sentence.parts[1]]);
        }else{
            return (this.tip_title.length && this.currentProp[this.tip_desc]) ? this.currentProp[this.tip_desc] : "";
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
        return isTip;
    }
}

