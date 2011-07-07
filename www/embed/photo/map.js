var pot,params,marker_props;   	
   	
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

        pot.dotsLayer = new mm.MarkerLayer(pot.map);

        var dotTemplate = $("#dot").template();
        pot.makeDot = function(feature) {
           var props = feature.properties,
           geom = (feature.geometry.type == 'GeometryCollection') ? feature.geometry.geometries : [ feature.geometry ];
           
           
           var coords = geom[0]['coordinates'];
           var pid = "dot_"+props.id;
           var loc = new mm.Location(coords[1],coords[0]);
           props.__dt_coords = loc;
          
          
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
                 loadTheImage(marker[0],props['photo_url'],"","");
             }
 
        }

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
        //
        ///////////////////////////////////////// 
    
    }catch (e) {
        console.error("ERROR: ", e);
        pot.error("ERROR: " + e);
    }
});
function loadTheImage(elm,url,title,alt){
    var imgHolder = $("<div/>");
	imgHolder.attr("class","photoContainer");
	var thisNub = $('<div class="photo_nub"></div>');
	imgHolder.append(thisNub);
	$(elm).append(imgHolder);
	var img = new Image();
	
	/* events */
	$(elm).click(function(e){
	    e.preventDefault();
	    var id = $(this).attr('id');
	    if(prop = marker_props[String(id)]){
	        location.href = params.baseURL+"u/"+prop.user_id+"/dots/"+prop.id;
	    }
	});
	$(img).mouseover(function(e){
	    
	});
	$(img).mouseout(function(e){

	});
	

	// img loader
	$(img)
		.load(function () {

			// hide img by default    
			$(this).hide();

			// insert img into link
		    imgHolder.append(this);

			//set variables for resizing
			var maxWidth = 300; 	                        // Max width for the image ( 1/2 the available width )
			var maxHeight = 200;   	                		// Max height for the image
			var ratio = 0;                                  // Used for aspect ratio
			var width = $(this).width();    				// Current image width
			var height = $(this).height();  				// Current image height

            
            if ($(this).height() > $(this).width()) {
                var h = maxWidth;
                var w = Math.ceil($(this).width() / $(this).height() * maxWidth);
              } else {
                var w = maxWidth;
                var h = Math.ceil($(this).height() / $(this).width() * maxWidth);
              }

			
			$(this).show();
			$(imgHolder).css("top","-"+(imgHolder.innerHeight()+10)+"px");
			$(imgHolder).css("left",-(imgHolder.innerWidth()/2)+"px");
			$(thisNub).css("left",((imgHolder.innerWidth()/2)-10)+"px");
			
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
