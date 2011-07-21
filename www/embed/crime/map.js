var pot, params;
var tip_selected_type = null,
    tip_selected_elm = null;

$(function() {
try {
    var mm = com.modestmaps;

    params = parseQueryString(location.search);
    if (!params.base) params.base = "pale_dawn";
    // TODO: uncomment me?
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

    var dotTemplate = $("#dot").template();
    var tipTemplate = $.template("tipTemplate",  "<span>${crime_type}</span>${time}<br/>${day} ${date}");
    pot.makeDot = function(feature) {
        normalizeFeature(feature);
        var crime_type = getCrimeType(feature.properties),
            crime_group = getCrimeGroup(crime_type),
            data = {
                type: crime_type, 
                group: crime_group, 
                label: abbreviate(crime_type),
                desc: getCrimeDesc(feature.properties),
                props: feature.properties
            },
            marker = $.tmpl(dotTemplate, data);
            //feature.properties['crime_type'] = crime_type
            

        marker.data("feature", feature);
        marker.data("crime_type", crime_type);
        marker.data("crime_group", crime_group);
        
        
        feature.properties['tip_str'] = $.tmpl(tipTemplate, data.props);
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
    	
    	
    	marker.click(function(e) {
            dot_onclick(e,$(this));
            e.preventDefault();
        });
     
   

        if (typeSelector) {
            var label = typeSelector.addLabel(data);
            if (!label.data("selected")) {
                marker.css("display", "none");
            } else {
            }
        }
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
                
        
        var that = this;
        label.click(function(e) {
            that.onLabelClick($(this), e);
            e.preventDefault();
        });

        this.container.append(label);
        this.labelsByType[type] = label;
        this.sortLabels();

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
		
            close = $('<span/>').addClass('close').html('X')

    		cnt.append(close);
		
            bdy.html(props.content);

            cnt.append($('<div/>').addClass('nub'));
            cnt.append(bdy);
		
            close.click(function() {
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
        this.el
            .show()
            .css({left: this.props.left + 'px', top: this.props.top + 'px'});
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

