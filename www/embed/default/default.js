var tip_title,
    tip_desc,
    isTip,
    tip_sentence;

var pot, params;

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
    // ensure map has height of 100%
    $("#map").css("height","100%");
    var marker_props = {};

    var ds_tooltip;
    var mm = com.modestmaps;
 
    params = parseQueryString(location.search);
    if (!params.baseURL) params.baseURL = baseURL;

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
       
       
       
        /* TOOLTIP FUNCTIONS */
           
           function TipController(){
               var tip = {};
               var container = $('#map');
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
                   if(_w < 70)_w = 70;
                   tt.css("width",_w+"px");
                   tt.width(_w);
                   //
                  
                  
                   var _point = pot.map.locationPoint(current_prop.__dt_coords);
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
                   var _title = getTipTitle();
                   var _desc = getTipDesc();
                   if(_title){
                       tt_title.css("display","block");
                       tt_title.html(_title);
                   }else{
                       tt_title.css("display","none");
                   }
                   if(_desc){
                       tt_desc.css("display","block");
                       tt_desc.html(_desc);
                   }else{
                       tt_desc.css("display","none");
                   }
                   
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
            
               tip.show = function(dot){
                   var id = $(dot).attr('id');
                   if(!id)return;
                   if(!marker_props[String(id)])return;
                   current_marker = dot;
                   current_prop = marker_props[String(id)];
                   dot.parentNode.appendChild(dot);
                   showTip(); 
               }
               tip.hide = function(dot){
                   dotRemoveClass(current_marker,"over_hover");
                   hideTip(); 
               }
               tip.destroy = function(){
                   //
               }
               
               pot.map.addCallback("resized", defer(updateContSize,100));
               return tip;
               
           }
         

       pot.dotsLayer = new mm.DotMarkerLayer(pot.map);
       if(isTip){
           ds_tooltip = new TipController();
           pot.dotsLayer.markerEventHandler = function(e){
               if(!this || !e)return;
               switch(e.type){
                   case "mouseover":
                    ds_tooltip.show(this);
                   break;
                   case "mouseout":
                    ds_tooltip.hide(this);
                   break;
                   case "click":
                    //
                   break;
               }
            }
        }

       
       
       
       pot.makeDot = function(feature) {
           var props = feature.properties,
           geom = (feature.geometry.type == 'GeometryCollection') ? feature.geometry.geometries : [ feature.geometry ];

           var coords = geom[0]['coordinates'];
           var pid = "dot_"+props.id;
           var more_front = {
               style: over_style,
               id:pid,
               radius:6,
               interactive:isTip
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
      
      // used to update coordinates in config
      function showhash(){
          if(typeof window.parent.hashMe == 'function') {
              window.parent.hashMe(location.hash);
          }
      }
      
      if(typeof window.parent.hashMe == 'function') {
          pot.map.addCallback("drawn", defer(showhash, 100));
      }
      
    
}catch (e) {
    console.error("ERROR: ", e);
    pot.error("ERROR: " + e);
}
});

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

