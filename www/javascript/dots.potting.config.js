////////////////////////////////////////////////////////
/////////////////////// CONFIG /////////////////////////
////////////////////////////////////////////////////////

if (typeof Dots === "undefined") Dots = {};
Dots.Config = function(more,theme,base) {
    // set theme
    this.theme = (theme == undefined) ? ds_chosen_theme : theme;
    if(!this.theme){
        this.theme = "default";
    }
    if(base){
        Dots.Config.defaultParams['base']=base;
    }

    this.setThemeSelection();

    // add more options if needed
    if(more){
        this.addExtraOptions(more);
    }

    // clone base config object
    this.defaults = jQuery.extend({}, Dots.Config.defaultParams);

    // add handlers and dispatcher
    this.addHandlers();


    this.hasher = this.hashMe();
}



Dots.Config.defaultParams = {
    user:'',
    sheet:'',
    title:'',
    base:'',
    coords:''
}

Dots.Config.defaultSizes = {
    large:[940,500],
    medium:[600,450],
    small:[400,300]
}

Dots.Config.prototype = {
    selectors: {
        panel: "#config-options",
        container: "#config-content",
        map: "#config-map",
        extras: "#config-extras",
        theme_selector: "#config_opt_theme"
    },
    theme: 'default',
    defaults: null,
    isPreview: false,
    iframe_size: [940,500],
    init_coords: false,
    sheet_columns: [],
    configEvent:null,
    _begin:false,
    more:null,
    hasher:null,
    currentSize: 'large',
    isUpdating: false,
    current_iframe_src: "",
    current_embed_code: "",

    setThemeSelection: function(){
        $(this.selectors.theme_selector).val(this.theme);
    },

    addExtraOptions: function(more){
        if(!more && !more.length)return;
        var len = more.length;
        var _panel = $(this.selectors.extras);
        for(var i=0;i<len;i++){
            var item = more[i];
            var _insert = "";
            if(item.type == "heading"){
                _insert = "<hr/>";
            }else{
                _insert = "<span class='label'>"+item.label+"</span>";
                if (item.type == "text"){
                    _insert += "<input id='config_opt_"+item.id+"' value='"+item['default']+"' class='autoUpdate'/>";
                }else if (item.type == "select"){
                    _insert += "<select id='config_opt_"+item.id+"' class='autoUpdate'></select>";
                }else if(item.type == "checkbox"){
                    _insert += "<input type='checkbox' id='config_opt_"+item.id+"' class='autoUpdate'/>";
                }

                if (item.helper){
                    _insert += "<span class='helper'>"+item.helper+"</span>";
                }

                // add new options to base config object
                if(!Dots.Config.defaultParams[item.id]){
                    Dots.Config.defaultParams[item.id] = item['default'];
                }
            }

            if(_insert){
                _panel.append("<div class='grid_3'>"+_insert+"</div>");
            }
        }
         _panel.append("<div class='clear'>&nbsp;</div>");

        // store more
        this.more = more;
    },

    loadSheet: function(){
        if(!incoming_sheet)return;
        this.getSheet(incoming_sheet);
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
            function(e){$(this).css("opacity",.5)},
            function(e){$(this).css("opacity",1)}
        );

        // copies location to input in config panel
        $("#location_copier").click(function(e){
             e.preventDefault();
             var _new = $("#config_opt_hash").val();
             $("#config_opt_coords").val(_new);
             that.defaults['coords'] = _new;
             that.setEmbed(false);
        });

        $("#config_opt_mapsize").change(function(e){
            var val = $(this).val();
            if(val != "custom"){
                $("#custom_map_fields").hide();
                this.currentSize = $(this).val();
                this.iframe_size = Dots.Config.defaultSizes[this.currentSize];
                $("#config_opt_width").val(parseInt(this.iframe_size[0]));
                $("#config_opt_height").val(parseInt(this.iframe_size[1]));
            }else{
                $("#custom_map_fields").css("display","block");
            }
        });

        $("#config_opt_theme").change(function(e){
            var _val = $(this).val();
            if(ds_chosen_theme != _val){
                var _url = _dotspotting.abs_root_url+"embed/"+_val+"?oid="+that.defaults.user+"&sid="+that.defaults.sheet;
                location.href = _url;
            }
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
            that.setEmbed(true);
        });

        // select all text when embed code text area is clicked
        $("#example_text").click(function(e){
            e.preventDefault();
           $(this).focus();
           $(this).select();
        });

        $(".autoUpdate").change(function(e){
             e.preventDefault();
             if(!that.isUpdating){
                 that.isUpdating = true;
                 that.grabConfigSettings();
                 that.setEmbed(true);
             }
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

            //update coordinate box
            $("#config_opt_hash").val(x);
            if(!that.init_coords){
                $("#config_opt_coords").val(x);
                that.defaults['coords'] = x;
                that.setEmbed(false);
                //$("#example_text").val( that.getEmbedCode(that.generateIframeSrc()) );
                that.init_coords = true;
            }
        }
    },
    // set fields from default object
    wipeConfigSettings: function(){
        // clone original static object
        this.defaults = jQuery.extend({}, Dots.Config.defaultParams);

        this.iframe_size = [940,500];
        this.init_coords = false;
        this.setConfigSettings();
    },
    // set fields from default object
    setConfigSettings: function(){
        for(i in this.defaults){
            var elm = $("#config_opt_"+i);
            if(elm.is(":checkbox")){
                if(this.defaults[i]=="1"){
                    elm.attr('checked','checked');
                }else{
                   // do nothing because this should only be called in beginning
                }
            }else{
                //console.log(this.defaults[i]);
                elm.val(this.defaults[i]);
            }

        };

        $("#config_opt_width").val(parseInt(this.iframe_size[0]));
        $("#config_opt_height").val(parseInt(this.iframe_size[1]));
    },

    // update defaults with fields
    grabConfigSettings: function(){
        for(i in this.defaults){
            var elm = $("#config_opt_"+i);
            if(elm.is(":checkbox")){
                this.defaults[i] = (elm.is(':checked')) ? "1":"0";
            }else{
                this.defaults[i] = elm.val();
            }
        };

        this.iframe_size[0] = parseInt($("#config_opt_width").val());
        this.iframe_size[1] = parseInt($("#config_opt_height").val());
    },

    // process title for output
    processTitle: function(x){
        return x.replace(/\s/g, "+");
    },

    // process column fields
    processFields: function(x){
        for(f in x){
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
                         if(this.defaults[o] != Dots.Config.defaultParams['base'])out += o + "=" +this.defaults[o] + "&";
                    }else{
                        out += o + "=" + ((o == "title") ? this.processTitle(this.defaults[o]):this.defaults[o]) + "&";
                    }
                }
            }
        }

        if(out.slice(-1) == "&"){
            out = out.slice(0,-1);
        }
        if(this.defaults['coords'])out += "#"+this.defaults['coords'];
        return out;
    },

    getLinkBack: function(){
        var pre_out = "";
        if(this.defaults.user && this.defaults.sheet){
            var sheet_link = _dotspotting.abs_root_url + "u/"+this.defaults.user+"/sheets/"+this.defaults.sheet;
            var sheet_title = (this.defaults.title) ? this.defaults.title : "Sheet #"+this.defaults.sheet;
            pre_out = "<a href='"+sheet_link+"'>"+sheet_title+"</a> on ";
        }else{
            pre_out = "This map is a product of "
        }

        return  "<p>"+pre_out + "<a href='"+_dotspotting.abs_root_url+"'>Dotspotting</a>"+"</p>";


    },

    getEmbedCode: function(){
        var iframe_val = '<iframe type="text/html" width="'+this.iframe_size[0]+'" height="'+this.iframe_size[1]+'" src="'+ this.current_iframe_src + '" frameborder="0"></iframe>';
        return iframe_val;
    },

    updateIframeSize: function(){
        // resize iframe
        var map_width = parseInt($("#config_opt_width").val());
        var map_height = parseInt($("#config_opt_height").val());
        this.autoResize('example_iframe',map_width,map_height);

        // reposition map
        var parent_width = $("#config-map").width();
        var parent_height = $("#config-map").height();
        var leftPos = (parent_width - map_width) / 2;
        var topPos = (parent_height - map_height) / 2;
        if(topPos < 0)topPos = 0;

        $("#example_iframe").css("left",leftPos + "px");
        $("#example_iframe").css("top",topPos + "px");

    },

    // set embed code textarea
    // @param force iframe reload
    setEmbed:function(updateIframe){
        if (updateIframe == undefined || updateIframe == null)updateIframe = false;
        var that = this;
        var old_iframe_src = this.current_iframe_src;

        this.current_iframe_src = this.generateIframeSrc();
        this.current_embed_code = this.getEmbedCode();

        // set embed test
        $("#example_text").val( this.current_embed_code + this.getLinkBack());
        //reload iframe?
        if(updateIframe){
             // dump the old iframe
            $("#example_iframe").remove();
            // create a new one
            var _i = $(this.current_embed_code);
            _i.attr("id","example_iframe");
            _i.attr("width",this.iframe_size[0]);
            _i.attr("height",this.iframe_size[1]);

            /* check for iframe load before allowing to update the iframe again*/
            _i.unbind('load').bind('load',function(e){
                        that.isUpdating = false;
            });

            $("#config-map").append(_i); // append it to load it
            that.updateIframeSize(); // update iframe size
            this.isUpdating = false; //  i lie
        }else{
            this.isUpdating = false;
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
            // uncomment if allowing user to resubmit a URL
            //that.wipeConfigSettings();
            that.defaults['title'] = d['dotspotting:title'];
            if(d.features[0]['properties']['user_id'] && d.features[0]['properties']['sheet_id']){
                that.processFields(d.features[0]['properties']);
                that.defaults.user = parseInt(d.features[0]['properties']['user_id'], 10);
                that.defaults.sheet = parseInt(d.features[0]['properties']['sheet_id'], 10);
                that.setConfigSettings();
                that.setEmbed(true);
                $("#config_url_error").hide();
                $("#config-options").show();
                $("#config-message").html("<a href='"+incoming_sheet+"'>Sheet #"+that.defaults['sheet'] + "</a> is loaded.");
                that.configEvent({type: "json_loading_success"});
            } else {
                that.configEvent({type: "json_loading_error"});
            }
          },
          error:function(e){

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