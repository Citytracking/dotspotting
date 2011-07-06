var pot,params,marker_props;

// style objects for dot layers
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
   	
   	
$(function() {
    try{
        $("#map").css("height","100%");

        var mm = com.modestmaps,
        ds_tooltip = null;
        
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

           var coords = geom[0]['coordinates'];
           var pid = "dot_"+props.id;
           var more_front = {
               style: over_style,
               id:pid,
               radius:6,
               dotClass:"dott"
           };

           var more_back = {
               style: under_style,
               radius:12
           };

    	   var loc = new mm.Location(coords[1],coords[0]);
    	   props.__dt_coords = loc;

           var marker = more_front;
   
           // Dots.Potting class only takes one marker, 
           // will manually add this one, for now, until I write a Kirby Dot markerLayer
           var c = pot.dotsLayer.addMarker(more_back,loc);
           c.toBack();
   
            // store props in key / value pairs
           marker_props[String(pid)] = props;  
  
          return marker;
        };


        var req = pot.load();
        
        // create tooltip
        // pass it the selector to listen for...
        // pulls rest of params from pot object
        // uses jQuery live
        ds_tooltip = new DotToolTip(".dott");

        /////////////////////////////////////////
        // used to update coordinates in config only
        function showhash(){
          if(typeof window.parent.hashMe == 'function') {
              if(ds_tooltip && ds_tooltip.active)ds_tooltip.updateSize();
              window.parent.hashMe(location.hash);
          }
        }

        if(typeof window.parent.hashMe == 'function') {
          pot.map.addCallback("drawn", defer(showhash, 100));
        }
        ///////////////////////////////////////// 
    
    }catch (e) {
        console.error("ERROR: ", e);
        pot.error("ERROR: " + e);
    }
});


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

