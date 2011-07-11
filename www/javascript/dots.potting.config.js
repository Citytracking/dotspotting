////////////////////////////////////////////////////////
/////////////////////// CONFIG /////////////////////////
////////////////////////////////////////////////////////

if (typeof Dots === "undefined") Dots = {};
Dots.Config = function(more,theme) {
    // set theme
    this.theme = (theme == undefined) ? ds_chosen_theme : theme;
    if(!this.theme){
        this.theme = "default";
    }
    
    // pull in default options
    this.defaults = Dots.Config.defaultParams;
    
    // add more options if needed
    if(more){
        this.addExtraOptions(more);
    }
    
    // add handlers and dispatcher
    this.addHandlers();
    
    this.hasher = this.hashMe();
}



Dots.Config.defaultParams = {
    user:'',
    sheet:'',
    title:'',
    base:'',
    tm:'',
    tt:'',
    coords:''
}

Dots.Config.prototype = {
    selectors: {
        panel: "#config-options",
        container: "#config-content",
        map: "#config-map",
        extras: "#config-extras"
    },
    theme: 'default',
    defaults: null,
    isPreview: false,
    iframe_size: [400,400],
    init_coords: false,
    sheet_columns: [],
    configEvent:null,
    _begin:false,
    more:null,
    hasher:null,
    
    addExtraOptions: function(more){
        var _panel = $(this.selectors.extras);
        for(var i=0;i<more.length;i++){
            var item = more[i];
            var _insert = "";
            if(item.type == "heading"){
                _insert = "<hr/>";
            }else{
                _insert = "<span class='label'>"+item.label+"</span>";
                if (item.type == "text"){
                    _insert += "<input id='config_opt_"+item.id+"' value='"+item['default']+"'/>";
                }else if (item.type == "select"){
                    _insert += "<select id='config_opt_"+item.id+"'></select>";
                }
                
                if (item.helper){
                    _insert += "<span class='helper'>"+item.helper+"</span>";
                }
                
                // add new options to defaults object
                if(!this.defaults[item.id]){
                    this.defaults[item.id] = item['default'];
                }
            }
            if(_insert)_panel.append("<p>"+_insert+"</p>");
        }
        
        // store more
        this.more = more;
    },
    
    loadSheet: function(){
        this.getSheet($("#config_opt_url").val());
    },
    
    // straight from Polymaps
    dispatch: function(that) {
	  var types = {};

	  that.on = function(type, handler) {
	    var listeners = types[type] || (types[type] = []);
	    for (var i = 0; i < listeners.length; i++) {
	      if (listeners[i].handler == handler) return that; // already registered
	    }
	    listeners.push({handler: handler, on: true});
	    return that;
	  };

	  that.off = function(type, handler) {
	    var listeners = types[type];
	    if (listeners) for (var i = 0; i < listeners.length; i++) {
	      var l = listeners[i];
	      if (l.handler == handler) {
	        l.on = false;
	        listeners.splice(i, 1);
	        break;
	      }
	    }
	    return that;
	  };

	  return function(event) {
	    var listeners = types[event.type];
	    if (!listeners) return;
	    listeners = listeners.slice(); // defensive copy
	    for (var i = 0; i < listeners.length; i++) {
	      var l = listeners[i];
	      if (l.on) l.handler.call(that, event);
	    }
	  };
	},
	
    addHandlers: function(){
        var that = this;
   
        // preview handler
        $('#previewConfig').click(function(e) {
             e.preventDefault();
             that.doPreview();
        });
        
        // used for location img button
        $("#location_copier").hover(
            function(e){$(this).css("opacity",.4)},
            function(e){$(this).css("opacity",.7)}
        );
        
        // copies location to input in config panel
        $("#location_copier").click(function(e){
             e.preventDefault();
             $("#config_opt_coords").val($("#config-map input").val());
        });

        // process Sheet URL handler
        $("#startConfig").click(function(e){
            e.preventDefault();
            if($("#config_opt_url").val()){
                that.getSheet($("#config_opt_url").val());
            }else{
                $("#config_url_error").html("please enter a Dotspotting url").show();
            }
        });

        // update embed code handler
        $("#updateConfig").click(function(e){
            e.preventDefault();
            that.grabConfigSettings();
            that.setEmbed();
        });

        // select all text when embed code text area is clicked
        $("#example_text").click(function(e){
            e.preventDefault();
           $(this).focus();
           $(this).select();
        });
        
        this.configEvent = this.dispatch(this);
        
    },
    // utility function to read hash from map iframe
    hashMe: function(x){
        var that = this;

        
        return function(x){
            
            if(!that.defaults)return;
            if(!x)return;

            // strip # from coords
            x = x.replace("#","");

            // ok lazy coming...
            if(x == "0/0/0")return;

            // show the coordinate display
            if(!that.isPreview)$("#config-map p").show();

            //update coordinate box
            $("#config-map .label").html("Current Coordinates: ");
            $("#config-map input").val(x);
            if(!that.init_coords){
                $("#config_opt_coords").val(x);
                that.defaults['coords'] = x;

                $("#example_text").val( that.getEmbedCode(that.generateIframeSrc()) );
                that.init_coords = true;
            }
            
        }

    },
    // set fields from default object
    wipeConfigSettings: function(){
        for(i in this.defaults){
            this.defaults[i] = '';
        }

        this.iframe_size = [400,400];
        this.init_coords = false;
        this.setConfigSettings();
    },
    // set fields from default object
    setConfigSettings: function(){
        for(i in this.defaults){
            $("#config_opt_"+i).val(this.defaults[i]);
        };

        $("#config_opt_width").val(parseInt(this.iframe_size[0]));
        $("#config_opt_height").val(parseInt(this.iframe_size[1]));
    },

    // update defaults with fields
    grabConfigSettings: function(){
        for(i in this.defaults){
            this.defaults[i] = $("#config_opt_"+i).val();
        };

        this.iframe_size[0] = parseInt($("#config_opt_width").val());
        this.iframe_size[1] = parseInt($("#config_opt_height").val());
    },

    // process title for output
    processTitle: function(x){
        return x.replace(/\s/g, "+");
    },

    // adds dropdowns for tooltip fields
    processFields: function(x){
        $("#config_opt_tt").append('<option value=""></option>');
        $("#config_opt_tm").append('<option value=""></option>');
        for(f in x){
            $("#config_opt_tt").append('<option value="'+f+'">'+f+'</option>');
            $("#config_opt_tm").append('<option value="'+f+'">'+f+'</option>');
            this.sheet_columns.push(f);
        }
    },

    // outputs the url used in src for iframe
    generateIframeSrc: function(){
        var out = _dotspotting.abs_root_url+"embed/"+this.theme+"/map?";

        for(o in this.defaults){
            if(this.defaults[o]){
                if(o != "coords"){
                    if(o == 'base'){
                         out += o + "=" +this.defaults[o] + "&amp;";
                    }else{
                        out += o + "=" + ((o == "title") ? this.processTitle(this.defaults[o]):this.defaults[o]) + "&amp;";
                    }
                }
            }
        }


        if(out.slice(-5) == "&amp;"){
            out = out.slice(0,-5);
        }
        if(this.defaults['coords'])out += "#"+this.defaults['coords'];
        return out;
    },

    getEmbedCode: function(x){
        var iframe_pre = '<iframe type="text/html" width="'+this.iframe_size[0]+'" height="'+this.iframe_size[1]+'" src="';
         return iframe_pre + x + '"></iframe>';     
    },


    // set embed code textarea
    setEmbed:function(){
        var out = this.generateIframeSrc();
        var current_embed = $("#example_text").val();
        var new_embed = this.getEmbedCode(out);

        if(current_embed != new_embed){
            $("#example_text").val( new_embed );
            $("#example_iframe").attr('src',out);
        }
    },

    // parses a hash into key/values
    getParams: function(u){
        // gather page parameters from the query string
        var params = {},
            paramMatch = u.match(/(\w+)=([^&$]+)/g);
        if (paramMatch) {
            var len = paramMatch.length;
            for (var i = 0; i < len; i++) {
                var part = paramMatch[i].split("=");
                params[part[0]] = decodeURIComponent(part[1]).replace(/\+/g, " ");
            }
        }
        return params;
    },

    getSheet: function(u){
        // check for things
        if(!_dotspotting.abs_root_url && !u)return;

        // split on hash
        var _url = u.split("#");

        // check for the existence of something
        if(!_url[0])return;

        // chop trailing slash
        if(_url[0].slice(-1) == "/")_url[0] = _url[0].slice(0,-1);

        // if there are coordinates in hash, store them in default object
        if(_url[1]){
            var params = this.getParams(_url[1]);
            if(params['c'])this.defaults['coords'] = params['c'];
        }
        this.init_coords = false;

        // create url that fetches geojson for sheet
        var _export_url = _url[0]+"/export?format=json";
        
        var that = this;
        this.configEvent({type: "json_loading_begin"});
        
        // get sheet geojson
        $.ajax({
          url: _export_url,
          dataType:"jsonp",
          success: function(d){
            that.wipeConfigSettings();
            that.defaults['title'] = d['dotspotting:title'];
            if(d.features[0]['properties']['user_id'] && d.features[0]['properties']['sheet_id']){
                that.processFields(d.features[0]['properties']);
                that.defaults.user = parseInt(d.features[0]['properties']['user_id']);
                that.defaults.sheet = parseInt(d.features[0]['properties']['sheet_id']);
                that.setConfigSettings();
                that.setEmbed();
                $("#config_url_error").hide();
                $("#config-options").show();
                $("#config-message").html("Sheet #"+that.defaults['sheet'] + " is loaded.");
                that.configEvent({type: "json_loading_success"});
            }
          },
          error:function(){
            $("#config_url_error").html("ugh! check your URL and try again.").show();
            that.configEvent({type: "json_loading_error"});
          }
        });
    },

    // preview stuff
    /*
        @return array
    */
    doPreview: function(){
        if(this.isPreview)return;
        var that = this;
        this.isPreview = true;
        var vp = this.getViewPort();
        var content_offset = $(this.selectors.container).offset();

        var map_width = parseInt($("#config_opt_width").val());
        var map_height = parseInt($("#config_opt_height").val());

        var _offset_left = (((vp[0]  - map_width) / 2) - content_offset.left) ;
        var _offset_top = (((vp[1]  - map_height) / 2) - content_offset.top) - 10;

        var map = $(this.selectors.map);
        map.addClass('preview');
        map.css("left",_offset_left + "px");
        map.css("top",_offset_top + "px");
        $("#config-map p").hide();

        $("#close-preview-modal").show();

        this.autoResize('example_iframe',map_width,map_height);

        $('body').append('<div id="previewBackground"></div>');
        $("#previewBackground").show();
        $('#previewBackground').unbind('click').bind('click', function(e) {
            e.preventDefault();
            that.doClosePreview();
        });

        $("#close-preview-modal").unbind('click').bind('click', function(e) {
            e.preventDefault();
           that.doClosePreview();
        });
    },
    doClosePreview: function(){
        this.isPreview = false;
        $("#close-preview-modal").unbind('click');
        $('#previewBackground').unbind('click');
        $("#close-preview-modal").hide();
        this.autoResize('example_iframe',400,400);
        $("#config-map").removeClass('preview');
        $("#config-map").css("left","0px");
        $("#config-map p").show();
        $('#previewBackground').remove();
    },

    autoResize: function(id,w,h){
        var newheight = h;
        var newwidth = w;

        document.getElementById(id).style.height = (newheight) + "px";
        document.getElementById(id).style.width = (newwidth) + "px";
    },
    getViewPort: function(){
        /* from Andy Langton --> http://andylangton.co.uk/articles/javascript/get-viewport-size-javascript/ */

        var viewportwidth;
        var viewportheight;

        // the more standards compliant browsers (mozilla/netscape/opera/IE7) use window.innerWidth and window.innerHeight

        if (typeof window.innerWidth != 'undefined')
        {
         viewportwidth = window.innerWidth,
         viewportheight = window.innerHeight
        }

        // IE6 in standards compliant mode (i.e. with a valid doctype as the first line in the document)

        else if (typeof document.documentElement != 'undefined'
        && typeof document.documentElement.clientWidth !=
        'undefined' && document.documentElement.clientWidth != 0)
        {
          viewportwidth = document.documentElement.clientWidth,
          viewportheight = document.documentElement.clientHeight
        }

        // older versions of IE

        else
        {
          viewportwidth = document.getElementsByTagName('body')[0].clientWidth,
          viewportheight = document.getElementsByTagName('body')[0].clientHeight
        }
        return [viewportwidth,viewportheight];
    }

};