var pot, params;
var tip_selected_type = null,
    tip_selected_elm = null;
    
// custom icons
var custom_icon_base = "";


$(function() {
try {
    var mm = com.modestmaps;
    
    params = parseQueryString(location.search);
    if (!params.base) params.base = "pale_dawn";
    // TODO: uncomment me?
    if (!params.baseURL) params.baseURL = baseURL;
    
    
    // look for custom icon url base
    if(params.iconbase){
        custom_icon_base = params.iconbase;
        // wipe the angle brackets from a url
        custom_icon_base = custom_icon_base.replace(/[<>]/g,'');
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

    pot.dotsLayer = new mm.MarkerLayer(pot.map);

    if (params.ui == "1") {
        var typeSelector = new CrimeTypeSelector("#crime_types_wrapper","#crime_types", pot.dotsLayer);
        if (params.types) {
            typeSelector.defaultTypeSelected = false;
            typeSelector.selectTypes(params.types.split(","));
        }

        var pos = ($("#title").length > 0) ? $("#title").innerHeight() : 0;
        $("#crime_types_wrapper").css("top",pos+"px");
        
    } else {
        $("#crime_types_wrapper").remove();
    }

    function dot_onclick(e,elm){
        if(!elm)return;
        
        if(elm[0] == tip_selected_elm){
            //console.log("YOU HAVE SELECTED THE SAME TIP");  
        }else{
            $("#map").trigger('markerclick',elm);
        }
    }
    
    // clustering
    function doCluster(){
        clusterMarkers(pot.dotsLayer.markers);
        pot.map.setZoom(pot.map.getZoom());
    }
    
    var days_of_week = ["Sunday","Monday","Tuesday","Wednesday","Thursday","Friday","Saturday"];
    var months = ["January","February","March","April","May","June","July","August","September","October","November","December"];
    var monthsAPShort = ["Jan.","Feb.","March","April","May","June","July","Aug.","Sept.","Oct.","Nov.","Dec."];
    
    var dotTemplate = $("#dot").template();
    //var tipTemplate = $.template("tipTemplate", "<span>${description || crime_type}</span>${time}<br/>${date_formatted}");
    
    // properties for this, come from data object generated in makeDot function
    var tipTemplate = $.template("tipTemplate", "<span>${desc || type}</span>${time}<br/>${crime_date}");
    pot.makeDot = function(feature) {
        normalizeFeature(feature);
        var crime_type = getCrimeType(feature.properties),
            crime_group = getCrimeGroup(crime_type),
            data = {
                type: crime_type, 
                group: crime_group, 
                label: abbreviate(crime_type),
                desc: getCrimeDesc(feature.properties),
                props: feature.properties,
                crime_date: "?"
            },
            marker = $.tmpl(dotTemplate, data);
            //feature.properties['crime_type'] = crime_type
            
        //look for custom icon
        checkCustomIcon(marker,data);
        
        marker.data("feature", feature);
        marker.data("crime_type", crime_type);
        marker.data("crime_group", crime_group);
        
        // format date
        if(data.props.date){
            var dt = new Date(data.props.date);
            if(dt && dt.getMonth()){
                data.props.date_formatted = days_of_week[dt.getDay()] + ", " + monthsAPShort[dt.getMonth()] + " " + dt.getDate() + ", " + dt.getFullYear();
            }else{
                data.props.date_formatted = data.props.date;
            }
        }else{
            data.props.date_formatted = "?";
        }
        data.crime_date = data.props.date_formatted;
        
        feature.properties['tip_str'] = $.tmpl(tipTemplate, data);
        //tooltip
        /*
	    marker.tipTip({
	        activation:"hover",
    	    maxWidth: "300", 
    	    edgeOffset: 12,
    	    delay:200,
    	    content:tip_str,
    	    forcePosition:false,
    	    keepAlive:false,
    	    defaultPosition:"top",
    	    manualClose:false,
    	    closeContent:"",
    	    //addTo:pot.dotsLayer.parent,
    	    
    	    enter:function(e){
	            //var that = marker;
	            //pot.map.addCallback("drawn", function(){that.trigger('custom', ['Custom', 'Event'])} );
	            //$("#tiptip_holder").removeClass("classtype_tip")
    	    },
            exit:function(){
                //pot.map.removeCallback("drawn");
            }
    	});
    	*/
        
        // Marker Events
    	marker.click(function(e) {
            dot_onclick(e,$(this));
            e.preventDefault();
        });
        marker.mouseout(function(e){
            $(this).removeClass("marker_over"); /* slightly bounces marker */
        });
        marker.mouseover(function(e){
            $(this).addClass("marker_over");
        });
        
      
     
   

        if (typeSelector) {
            var label = typeSelector.addLabel(data);
            if (!label.data("selected")) {
                marker.css("display", "none");
            } else {
            }
        }
        
        
         defer(doCluster, 100)();
         
        return marker[0];
    };
    // need a callback on load to resize menu
    var req = pot.load(null, function(){   
        // map tooltip
    	utils_add_map_tooltip(pot.map,$("#map").parent(),"mm");
        if (typeSelector) {
            typeSelector.labelsAdded();
        }
    },null);
    
    ////////////////////////////
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

} catch (e) {
    console.error("ERROR: ", e);
    pot.error("ERROR: " + e);
}
});

/// Custom Icon 

function checkCustomIcon(marker,data){
    if(data.props.custom_icon || custom_icon_base.length){
        
        var url = (data.props.custom_icon) ? data.props.custom_icon.replace(/ /g, "_") : data.props.crime_type.replace(/ /g, "_");
        
        var extension = url.substr( (url.lastIndexOf('.') +1) );
        url = custom_icon_base + url;
        if(extension != "png" || extension != "jpg" || extension != "gif") url += ".png";
        
        var icon = $(marker).find(".group");
        var default_classes = ['violent','property','gol','unknown'];
        
        // check if icon image is out there
        // probably need to see if this will work in all browsers
        $('<img/>').attr('src', url).load(function() {
            // attach icon
            icon.css("backgroundImage", "url("+url +") !important");
            icon.css("width", "25px !important");
            icon.css("height", "25px !important");
            icon.html("");
            // remove old class
            var p = icon.parent();
            p.removeClass(default_classes.join(" "));

        });
        
        // store icon url in marker, for future reference
        marker.data("custom_icon", url);
    }
}

/// end custom icon

function CrimeTypeSelector(wrapper,selector, layer) {
    this.wrapper = $(wrapper);
    this.container = $(selector);
    this.layer = layer;
    this.labelsByType = {};
    this.selectedTypes = {};
    this.labels = [];
}

CrimeTypeSelector.prototype = {
    container: null,
    layer: null,
    labels: null,
    labelsByType: null,
    selectedTypes: null,
    defaultTypeSelected: true,
    wrapper:null,
    show_all:$("#ct_show_all"),
    hide_all:$("#ct_hide_all"),

    
    getSortKey: function(data) {
        var indexes = {"violent": 1, "qol": 2, "property": 3};
        return [indexes[data.group] || 9, data.label || data.type].join(":");
    },

    addLabel: function(data) {
        var type = data.type;

        if (this.labelsByType[type]) {
            var label = this.labelsByType[type];
            label.data("count", label.data("count") + 1);
            return  label;
        }
        

        var label = $("<li/>")
            .data("type", type)
            .data("sort", this.getSortKey(data))
            .data("count", 1)
            .data("data", data)
            .addClass(data.group)
            .append($('<span class="group"/>')
                    .text(data.label))
            .append($('<span class="title"/>')
                .text(data.title || data.type));
                
        //look for custom icon
        checkCustomIcon(label,data);
        
        var that = this;
        label.click(function(e) {
            e.preventDefault();
            that.onLabelClick($(this), e);
            
        });

        this.container.append(label);
        this.labelsByType[type] = label;
        this.sortLabels();

        // huh?
        if (this.selectedTypes.hasOwnProperty(data.label)) {
            this.selectedTypes[type] = this.selectedTypes[data.label];
        }
        
        var selected = this.selectedTypes[type];
        if (typeof selected == "undefined") {
            selected = this.selectedTypes[type] = this.defaultTypeSelected;
        }
        if (selected) {
            label.data("selected", true);
            this.selectType(type);
        } else {
            label.addClass("off");
            this.unselectType(type);
        }
        
        
        this.labels.push(label);
        return label;
        
    },

    onLabelClick: function(label, e) {
        /*
        var selected_type = label.data("type");
        var len = this.labels.length;
        var selected;
        for (var i = 0; i < len; i++) {
            var l = this.labels[i];
            var type = l.data("type");
            if(type == selected_type){
                selected = true;
                l.data("selected", selected);
                this.selectType(type);
            }else{
                selected = false;
                l.data("selected", selected);
                this.unselectType(type); 
            }
            l.toggleClass("off", !selected);
        }
        */
        
  
        var selected = !label.data("selected"),
            type = label.data("type");
        label.data("selected", selected);
        if (selected) {
            this.selectType(type);
        } else {
            this.unselectType(type);
        }
        label.toggleClass("off", !selected);
       
    },

    selectType: function(type) {
        var markers = this.layer.markers,
            len = markers.length;
        for (var i = 0; i < len; i++) {
            var marker = $(markers[i]);
            if (marker.data("crime_type") == type) {
                marker.css("display", "");
            }
        }
    },

    unselectType: function(type) {
        if(type == tip_selected_type)$("#map").trigger('tip_close_tip');
        var markers = this.layer.markers,
            len = markers.length;
        for (var i = 0; i < len; i++) {
            var marker = $(markers[i]);
            if (marker.data("crime_type") == type) {
                marker.css("display", "none");
            }
        }
    },

    selectTypes: function(types) {
        if (types) {
            for (var i = 0; i < types.length; i++) {
                this.selectedTypes[types[i]] = true;
            }
            for (var type in this.labelsByType) {
                var label = this.labelsByType[type],
                    selected = this.selectedTypes[type];
                label.data("selected", selected)
                    .toggleClass("off", !selected);
            }
        }
        var markers = this.layer.markers,
            len = markers.length;
        for (var i = 0; i < len; i++) {
            var marker = $(markers[i]),
                type = marker.data("crime_type"),
                label = marker.data("data").label,
                selected = this.selectedTypes[type] || this.selectedTypes[label];
            marker.css("display", selected ? "" : "none");
        }
    },

    sortLabels: function() {
        var labels = {};
        var sortables = this.container.children().toArray().map(function(el) {
            var label = $(el),
                key = label.data("sort");
            labels[key] = label;
            //console.log(label[0],key);
            return key;
        });
        sortables = sortables.sort(function(a, b) {
            return (b > a) ? -1 : (b < a) ? 1 : 0;
        });
        var len = sortables.length;
        for (var i = 0; i < len; i++) {
            labels[sortables[i]].appendTo(this.container);
        }
        
    }, 
    labelsAdded: function(){
        var that = this;
        var len = this.labels.length;

        // add alternating rows and tooltips
        var _items = this.container.find("li");
        _items.each(function(i){
    
            var label = $(this);
            if(i % 2 == 0){
                $(label).addClass("altrow");
            }
   
            var tip_str = $(label).find('.title').text() + "<br/>" + label.data('count') +" "+pluralize("report",label.data('count') );
            
            
        
             label.tipTip({
          	    maxWidth: "auto", 
          	    edgeOffset: 0,
          	    delay:100,
          	    defaultPosition:"left",
          	    forcePosition:true,
          	    content:tip_str,
          	    enter:function(){},
                exit:function(){}
          	});
  	
    
        });
        
        // resize crime type selector widget
        this.resize(); 
        
        // add show all & hide all events
        this.show_all.click(function(e){
            e.preventDefault();

            for (var i = 0; i < len; i++) {
            var label = that.labels[i];
            var selected = true,
                type = label.data("type");
            label.data("selected", selected);
            that.selectType(type);
            label.toggleClass("off", !selected);
            }
        });
        this.hide_all.click(function(e){
            e.preventDefault();
            
            for (var i = 0; i < len; i++) {
                var label = that.labels[i];
                var selected = false,
                type = label.data("type");
                label.data("selected", selected);
                that.unselectType(type);
                label.toggleClass("off", !selected);
            }

        });
    },
    
    resize: function(){
   
        var _parent = this.wrapper.parent();
        var _container_offset = this.wrapper.offset();

        if(_container_offset.top + this.wrapper.height() > _parent.height()){
           
            var _w = this.container.width();
            var _h = (_parent.height() - _container_offset.top) - 40;
            this.container.css("width",_w+25+"px").css("height",_h+"px").css("overflow","auto").css("padding-bottom","10px");
        }
    }
    
};

function getCrimeDesc(props) {
    return props["description"] || props["crime_description"] || "?";
}

function getCrimeType(props) {
    return props["crime_type"] || "Unknown";
}

function getCrimeGroup(crime_type) {
    switch (crime_type.toUpperCase()) {
        case "AGGRAVATED ASSAULT":
        case "MURDER": case "HOMICIDE":
        case "ROBBERY":
        case "SIMPLE ASSAULT":
        case "BATTERY":
        case "SUICIDE":
        case "DOMESTIC VIOLENCE":
            return "violent";
        case "DISTURBING THE PEACE":
        case "NARCOTICS": case "DRUGS":
        case "ALCOHOL":
        case "PROSTITUTION":
        case "SOLICITING A PROSTITUTE":
        case "ATTEMPTED BATTERY":
        case "CIVIL SIDEWALKS": case "SIT-LIE":
        case "CIVIL SIDEWALKS/SIT-LIE":
        case "DRUNK DRIVING":
            return "qol";
        case "THEFT":
        case "VEHICLE THEFT":
        case "VANDALISM":
        case "BURGLARY":
        case "ARSON":
        case "AUTO THEFT":
        case "BICYCLE THEFT":
        case "MOTORCYCLE THEFT":
        case "GRAFFITI":
        case "BURGLARY HOME":
        case "BURGLARY - HOME":
        case "BURGLARY COMMERCIAL":
        case "BURGLARY - COMMERCIAL":
        case "BURGLARY VEHICLE":
        case "BURGLARY - VEHICLE":
        case "FRAUD":
            return "property";
    }
    return "unknown";
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

function getDateTime(props) {
    if (props.hasOwnProperty("date") && props.hasOwnProperty("time")) {
        return " on " + props["date"] + " @ " + props["time"];
    } else if (props.hasOwnProperty("date_time")) {
        return " on " + props["date_time"];
    } else if (props.hasOwnProperty("date")) {
        return " on " + props["date"];
    } else {
        return " on " + props["created"];
    }
}

function abbreviate(group) {
    var words = group.split(" ");
    //console.log(group, group.indexOf(" "), words.concat());
    if (words.length > 1) {
        var first = words.shift();
        while (abbreviate.stopWords.indexOf(words[0].toLowerCase()) > -1) {
            words.shift();
        }
        var second = words.shift();
        return (first.charAt(0) + second.charAt(0)).toUpperCase();
    } else {
        return group ? capitalizeWord(group.substr(0, 2)) : "?";
    }
} 
abbreviate.stopWords = ["of", "the", "for", "and", "with", "-"];
/*
var tip_selected_type = null,
    tip_selected_elm = null;
*/   

function utils_add_map_tooltip(map,mapel,map_type){

    $(".maptip").each(function(){
    	$(this).remove();
    });
	
	
	
	$("#map").unbind('markerclick');
	$("#map").bind('markerclick', function(e,elm) {
    if(mapel.length == 0) return;
    var f = $(elm).data('feature');
    if(!f)return;
    var p = f.properties;

    // set elm & type for others
    tip_selected_elm = elm;
    tip_selected_type = p['crime_type'];

    // set property object that feeds into tip
    var props = new Object();
    props.location = elm.location,
    props.map = map,
    props.map_type = map_type,
    props.content = p['tip_str'];
    
    // create tip
     mapel.maptip(this)
      .data(props)
      .map(map)
      .location(props.location)
      .classNames(function(d) {
        return d.code
      })
      .top(function(tip) {
        var point = tip.props.map.locationPoint(this.props.location);
    
        return parseFloat(point.y);
      }).left(function(tip) {
        var offset = 18, 
            point = tip.props.map.locationPoint(this.props.location);

        return parseFloat(point.x + offset);
      }).content(function(d) {

    	var _tip = null;
	
        var self = this,
            props = d,
            cnt = $('<div/>'),
            bdy = $('<div id="maptip_bdy"/>'),
		
            close = $('<span/>').addClass('close').html('x')

    		cnt.append(close);
		
            bdy.html(props.content);

            cnt.append($('<div/>').addClass('nub'));
            cnt.append(bdy);
            
            
            
		
            close.unbind('click').bind('click',function() {
    			closeTip(self);
            });
          
            $("#map").unbind("tip_close_tip").bind("tip_close_tip",function(e){
                closeTip(self);
        	});

    		function closeTip(_tipRef){
    			if(!_tipRef)return;
    			_tipRef.hide();
    		}

        	return cnt;
      }).render()


    });

}

/**
 * MapTip - [Alpha Quality]
 * Flexible Polymaps Tooltips
 * Copyright (c) 2011 Justin Palmer
 * http://labratrevenge.com
 * Released under the MIT License
 */
;(function($) {
  function MapTip(el, target) {
    this.canvas = el;
    this.target = target;
    this.defaultClassName = 'maptip';
    this.el = $('<div />')
        .addClass(this.defaultClassName)
        .css('position', 'absolute');

    this.cnt = $('<div />').addClass(this.defaultClassName + '-cnt');
    this.el.append(this.cnt);
    this.props = {};
  }
  
  MapTip.prototype = {
    data: function(d) {
        this.props.data = d;
        return this;
    },
    
    map: function(el) {
        var self = this
        this.props.map = el;

        this.props.map.addCallback('drawn', function() { self.move() });
        this.props.map.addCallback('resized', function() { self.resize() });

        return this;
    },
    
    classNames: function(fn) {
        if($.isFunction(fn)) {
            this.props.classNames = fn.call(this, this.props.data);
        } else {
            this.props.classNames = fn;
        }
        this.el.attr('class', '');
        this.el
            .addClass(this.defaultClassName)
            .addClass(this.props.classNames)
        
        return this;
    },
    
    location: function(latlon) {
        this.props.location = latlon;
        return this;
    },
    
    left: function(fn) {
        if($.isFunction(fn)) {
            this.props.callbackLeft = fn;
            this.props.left = fn.call(this, this);
        } else {
            this.props.left = fn;
        }

        return this;
    },
    
    top: function(fn) {
        if($.isFunction(fn)) {
            this.props.callbackTop = fn;
            this.props.top = fn.call(this, this);
        } else {
            this.props.top = fn;
        }
        return this;
    },
    
    content: function(fn) {
        if($.isFunction(fn)) {
            this.props.content = fn.call(this, this.props.data);
            return this;
        }
        this.props.content = fn;
        return this;
    },
    
    className: function(fn) {
        if($.isFunction(fn)) {
            this.props.className = fn.call(this, this.props.data);
            return this;
        }

        this.props.className = fn;
        return this;
    },
    
    page: function(fn) {
        return this;
    },
    
    hide: function(fn) {
        this.props.map.removeCallback('drawn');
        this.props.map.removeCallback('resized');
        var el = this.el;
        this.el.fadeOut(function() {
            el.remove();
        });
    },
    
    move: function(event) {
        this.left(this.props.callbackLeft).top(this.props.callbackTop)
        var size = {x:this.props.map.parent.offsetWidth,y:this.props.map.parent.offsetHeight};

        if( (this.props.left > 0 && this.props.left < size.x) && (this.props.top > 0 && this.props.top < size.y) ){
            this.el.css({left: this.props.left + 'px', top: this.props.top + 'px'});
            this.el.css("visibility","");
        }else{
            this.el.css("visibility","hidden");
        }
        
        return this;
    },
    
    resize: function(event) {
        return this.move(event);
    },
    
    render: function() {
        this.cnt.html('').append(this.props.content)
        this.canvas.prepend(this.el)  
        this.el.show();
        
        // adjust width
        this.cnt.css("width","auto");
        if(this.cnt.width() > 200){
            this.cnt.css("width","200px");
        }else if(this.cnt.width() < 60){
            this.cnt.css("width","60px");
        }else{
            this.cnt.css("width",this.cnt.width()+"px");
        }
        
        this.el.css({left: this.props.left + 'px', top: this.props.top + 'px'});
            
        var that = this;
        var p1 = this.props.map.locationPoint(this.props.location);
        p1.x += (this.el.width() / 2);
        var p2 = this.props.map.locationPoint(this.props.map.getCenter()); //center

        var panx = p2.x - p1.x;
        var pany = p2.y - p1.y;
        
        // simple animater
        var step = 50;
        var that = this;
        for (i=0; i<step; i++) {
            var px = panx/step;
            var py = pany/step;
            setTimeout(function(){
              that.props.map.panBy(px,py);
            },5*(i+1));
        
        }
        
        
    }
    //end
  }
  
  $.fn.maptip = function(target) {
    var tip = $.data(this, 'maptip-callout');
    if(!tip) {
        tip = new MapTip(this, target);
        $.data(this, 'maptip-callout', tip);
    }

    return tip; 
  }
})(jQuery);

