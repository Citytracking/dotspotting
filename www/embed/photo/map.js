var pot,params,marker_props;
var THUMB_MAX_WIDTH = 100,
    THUMB_MAX_HEIGHT = 70,
    THUMB_PADDING = null;
    
var tip_params = {};
tip_params.activate = true;
    
$(function() {
    try{
        $("#map").css("height","100%");

        var mm = com.modestmaps;
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
        
        if (params.ph_w){
            THUMB_MAX_WIDTH = Math.floor(Number(params.ph_w));
        }
        if (params.ph_h){
            THUMB_MAX_HEIGHT = Math.floor(Number(params.ph_h));
        }
        
        //checkForTooltipParams();

        pot.dotsLayer = new mm.MarkerLayer(pot.map);

        var dotTemplate = $("#dot").template();
        
        // get padding for later
        // this may not stick
        var get_kirby_padding = $("<div class='kirby_bottom'></div>").hide().appendTo("body");
        THUMB_PADDING = ($(get_kirby_padding).css("padding-top")) ? parseInt($(get_kirby_padding).css("padding-top").replace("px", "")) : 8;
        get_kirby_padding.remove();
        
        
        
        pot.makeDot = function(feature) {
            var props = feature.properties,
            geom = (feature.geometry.type == 'GeometryCollection') ? feature.geometry.geometries : [ feature.geometry ];


            var coords = geom[0]['coordinates'];
            var pid = "dot_"+props.id;
            var loc = new mm.Location(coords[1],coords[0]);
            props.__dt_coords = loc;
            props.__active = true;

            var data = {
                  photo_id: pid
              },
            marker = $.tmpl(dotTemplate, data);
            marker_props[String(pid)] = props;  
        
            // will look for flickr photo id or a 'photo_url' property
            if(props['flickr:id'] || props['photo_url']){
                if(props['flickr:id']){
                    getFlickrImg(
                        (function(e){
                     
                            return e;
                        })([props['flickr:id'],marker[0]])
                
                     ); 
                 }else{
                     var __title = "";
                     if(tip_params.activate && props[tip_params.tip_title]){
                         __title = props[tip_params.tip_title];
                         
                     }
                     
                     loadTheImage(marker[0],props['photo_url'],__title,"");
                 }
 
            }else{
                var __title = "";
                 if(tip_params.activate && props[tip_params.tip_title]){
                     __title = props[tip_params.tip_title];
                     
                 }
                 var photo_url = baseURL+"embed/photo/images/cameradot_30.png";
                 THUMB_MAX_WIDTH = 30;
                 THUMB_MAX_HEIGHT = 30;
                 loadTheImage(marker[0],photo_url,__title,"","photoNoNo");
            }
        
     /*
            var _hit = $("<div class='hit'></div>");
            pot.dotsLayer.addMarker(_hit[0],feature);
     */
               
          return marker[0];
        };

       
        var req = pot.load(null,function(){
        
            var markers = pot.dotsLayer.markers,
                len = markers.length;
            function latitude(marker) {
                return marker.location.lat;
            }
            
            markers = markers.sort(function(a, b) {
                var lata = latitude(a),
                    latb = latitude(b);
                return (lata > latb) ? -1 : (lata < latb) ? 1 : 0;
            });
            for (var i = 0; i < len; i++) {
                $(markers[i].parentNode).append(markers[i]);
            }
       
            
        },null);
        
        /////////////////////////////
        // ARE WE IN CONFIG MODE ////////////
        // SHould we do this .. this way?? //
        /////////////////////////////////////
        var _inConfig = null;
        try{ _inConfig = window.parent.ds_config.hasher; }catch(e){}
        /////////////////////////////////////////
        // used to update coordinates in config only
        function showhash(){
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


function checkForTooltipParams(){
    // look for tooltip parameters
    if(!params.tt && !params.tm){
      tip_params.activate = false;
    }else{
      tip_params.activate = true;
      if(params.tt){
          tip_params.tip_title = params.tt;
      }else{
          tip_params.tip_title = "id";
      }

      if(params.tm){

      //var re= new RegExp (/{(.*)}/gi);
      var re= new RegExp (/{([\w:]*)?\s?(.*?)}/gi);

      var m=re.exec(params.tm);

      if(m && m[1]){
          tip_params.tip_sentence = {
              "struct":params.tm,
              "parts":m
          };
      }
            
          tip_params.tip_desc = params.tm;
      }else{
          tip_params.tip_desc = "";
      }
    }

}



function hasBorderRadius() {
    //http://www.swedishfika.com/2010/03/19/rounded-corners-on-images-with-css3-2/
    var d = document.createElement("div").style;
    if (typeof d.borderRadius !== "undefined") return true;
    if (typeof d.WebkitBorderRadius !== "undefined") return true;
    if (typeof d.MozBorderRadius !== "undefined") return true;
    return false;
}

function loadTheImage(elm,url,title,alt,ughClass){
    var imgHolder = $("<div/>");
	imgHolder.attr("class","photoContainer");
	//var thisNub = $('<div class="photo_nub"></div>');
	//imgHolder.append(thisNub);
	$(elm).append(imgHolder);
	var img = new Image();
	
	var kirby = $(elm).find(".kirby_bottom");
	

	// img loader
	$(img)
		.load(function () {

			// hide img by default    
			$(this).hide();

			// insert img into link
		    imgHolder.append(this);

			//set variables for resizing
			var maxWidth = THUMB_MAX_WIDTH; 	            // Max width for the image ( 1/2 the available width )
			var maxHeight = THUMB_MAX_HEIGHT;        		// Max height for the image
			var ratio = 0;                                  // Used for aspect ratio
			var _width = $(this).width();    				// Current image width
			var _height = $(this).height();  				// Current image height
            var _h=THUMB_MAX_HEIGHT,
                _w=THUMB_MAX_WIDTH; 
            // scale thumbnail
            if (_height > _width) {
                _h = maxHeight;
                _w = Math.ceil(_width / _height * maxHeight);
              } else {
                _w = maxWidth;
                _h = Math.ceil(_height / _width * maxWidth);
              }
              
             
             $(this).width(_w);
             $(this).height(_h);

			// reposition parent to center on point
			var elm_offset = $(elm).offset();
			var padding = THUMB_PADDING;
			
            // adding in padding
			var new_elm_left = elm_offset.left - ((_w+(padding*2))/2);
			var new_elm_top = elm_offset.top - ((_h+(padding*2))/2);

			//$(elm).css("top",new_elm_top+"px");
		    //$(elm).css("left",new_elm_left+"px");
		    $(elm).css("margin-left",-((_w+(padding*2))/2) + "px")
		        .css("margin-top",-((_h+(padding*2))/2) + "px")

            /*
		    if (hasBorderRadius() && !ughClass) {
		        var imgSrc = $(this).attr("src");
		        $(imgHolder).addClass("roundedCorners");
        
		        $(imgHolder)
                      .css("background-image", "url(" + imgSrc + ")")
                      .css("background-repeat","no-repeat")
                      .css("height", _h + "px")
                      .css("width", _w + "px")
                      .css("left","5px") // adjust for padding
                      .css("top","5px"); // adjust for padding

                $(this).remove();
	        }else{
	            if(ughClass)$(imgHolder).addClass(ughClass);
   	            $(this).show();
	        }
	        */
	        
            if(ughClass)$(imgHolder).addClass(ughClass);
            $(imgHolder).css("left",padding-1+"px") // adjust for padding
                        .css("top",padding-1+"px") // adjust for padding
                        .css("height", _h + "px")
                        .css("width", _w + "px");
	        $(this).show();
	        
	        
	        
	        /* events */
        	$(elm).click(function(e){
        	    e.preventDefault();
        	    var id = $(this).attr('id');
        	    if(prop = marker_props[String(id)]){
        	        location.href = params.baseURL+"u/"+prop.user_id+"/dots/"+prop.id;
        	    }
        	});
        	
        	if(tip_params.activate){
            	/* tooltip */
            	$(function(){
            	    var that = elm;
            	 $(elm).tipTip({
                	    maxWidth: "auto", 
                	    edgeOffset: 1,
                	    delay:100,
                	    content:title,
                	    enter:function(e){$(that).addClass("photodot_over")},
                        exit:function(){$(that).removeClass("photodot_over")}
                	});
            	});
    	    }
			
		})

		// error handler
		.error(function () {
			// oops error
			console.log("ERROR");
		})

		// set attributes
		.attr('src', url)
		.attr('alt', alt)
		.attr('title', title);
}

function getFlickrImg(arr){
    var elm = arr[1];
    var url = "http://api.flickr.com/services/rest/?method=flickr.photos.getInfo&api_key="+apikey+"&photo_id="+arr[0]+"&format=json&jsoncallback=?";
    $.getJSON(url,function(rsp){
        if (rsp['stat'] != 'ok'){
			return;
		}
		
		var ph = rsp['photo'];

		//set size...
		var photoSize = "thumb";
		var ph_ending = (photoSize == 'thumb') ? '_t.jpg' : '.jpg';

		var ph_url =
			'http://farm' + encodeURIComponent(ph['farm']) +
			'.static.flickr.com/' + encodeURIComponent(ph['server']) +
			'/' + encodeURIComponent(ph['id']) +
			'_' + encodeURIComponent(ph['secret']) + ph_ending;

		var ph_page =
			'http://www.flickr.com/photos/' +
			encodeURIComponent(ph['owner']['nsid']) +
			'/' +
			encodeURIComponent(ph['id']);
			
	    var ph_title = htmlspecialchars(ph['title']['_content']);
		var ph_owner = htmlspecialchars(ph['owner']['username']);
		
		loadTheImage(elm,
		            ph_url,
		            ph_title + ' by ' + ph_owner,
		            'flickr photo: '+ph_title + ' by ' + ph_owner)
		
    });

}



function htmlspecialchars (string, quote_style, charset, double_encode) {
    // http://kevin.vanzonneveld.net
    // +   original by: Mirek Slugen
    // +   improved by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
    // +   bugfixed by: Nathan
    // +   bugfixed by: Arno
    // +    revised by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
    // +    bugfixed by: Brett Zamir (http://brett-zamir.me)
    // +      input by: Ratheous
    // +      input by: Mailfaker (http://www.weedem.fr/)
    // +      reimplemented by: Brett Zamir (http://brett-zamir.me)
    // +      input by: felix
    // +    bugfixed by: Brett Zamir (http://brett-zamir.me)
    // %        note 1: charset argument not supported
    // *     example 1: htmlspecialchars("<a href='test'>Test</a>", 'ENT_QUOTES');
    // *     returns 1: '&lt;a href=&#039;test&#039;&gt;Test&lt;/a&gt;'
    // *     example 2: htmlspecialchars("ab\"c'd", ['ENT_NOQUOTES', 'ENT_QUOTES']);
    // *     returns 2: 'ab"c&#039;d'
    // *     example 3: htmlspecialchars("my "&entity;" is still here", null, null, false);
    // *     returns 3: 'my &quot;&entity;&quot; is still here'

    var optTemp = 0, i = 0, noquotes= false;
    if (typeof quote_style === 'undefined' || quote_style === null) {
        quote_style = 2;
    }
    string = string.toString();
    if (double_encode !== false) { // Put this first to avoid double-encoding
        string = string.replace(/&/g, '&amp;');
    }
    string = string.replace(/</g, '&lt;').replace(/>/g, '&gt;');

    var OPTS = {
        'ENT_NOQUOTES': 0,
        'ENT_HTML_QUOTE_SINGLE' : 1,
        'ENT_HTML_QUOTE_DOUBLE' : 2,
        'ENT_COMPAT': 2,
        'ENT_QUOTES': 3,
        'ENT_IGNORE' : 4
    };
    if (quote_style === 0) {
        noquotes = true;
    }
    if (typeof quote_style !== 'number') { // Allow for a single string or an array of string flags
        quote_style = [].concat(quote_style);
        for (i=0; i < quote_style.length; i++) {
            // Resolve string input to bitwise e.g. 'PATHINFO_EXTENSION' becomes 4
            if (OPTS[quote_style[i]] === 0) {
                noquotes = true;
            }
            else if (OPTS[quote_style[i]]) {
                optTemp = optTemp | OPTS[quote_style[i]];
            }
        }
        quote_style = optTemp;
    }
    if (quote_style & OPTS.ENT_HTML_QUOTE_SINGLE) {
        string = string.replace(/'/g, '&#039;');
    }
    if (!noquotes) {
        string = string.replace(/"/g, '&quot;');
    }

    return string;
}
