//define globals
var pot,params,colors,bucketColumn,mdict,ds_tooltip,backdict,ds_user_opts={},ds_kirby = false;

// style objects for dot
var over_style = {
	'fill' : 'rgb(11,189,255)',
	'fill-opacity' : 1,
	'stroke' : '#333333',
	'stroke-width' : 1,
	'stroke-opacity':1
};

var under_style = {
	'fill' : 'rgb(33,33,33)',
	'fill-opacity' : .8,
	'stroke' : 'rgb(66,66,66)',
	'stroke-width' : 1
};
var hover_style = {
    'fill' : 'rgb(255,255,255)',
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

        $("#map").css("height","100%");

        var mm = com.modestmaps,
        ds_tooltip = null;

        params = parseQueryString(location.search);

        if (!params.baseURL) params.baseURL = baseURL;


        if(params.provider)params.base = params.provider;
        if (!params.base)params.base = "acetate";
        if(params.bucket)bucketColumn = params.bucket;

        if (params.darktheme) {
            $('body').addClass('dark-theme');
        }

        params.autofit = (params.autofit && parseInt(params.autofit, 10) === 1) ? true : false;

        if (params.messagetmpl) params.tm = decodeURIComponent(params.messagetmpl);
        //messagetmpl=%7Bsquare%20feet%7D%20sq%20ft

        var maxValue = 0,
            minValue = Infinity;

        var bubbleSizeColumn = (params['bucketsize']) ? params['bucketsize'] : null;
        var maxSize = 6,
            minSize = 6;

        if (bubbleSizeColumn) {
            maxSize = (params.max) ? parseFloat(params.max) : 50;
            minSize = (params.min) ? parseFloat(params.min) : 6;
        }

        pot = new Dots.Potting(params);
        pot.setTitle();
        pot.dotsLayer = new mm.DotMarkerLayer(pot.map);

        params.ui = "1";
        var typeSelector;
        if (params.ui == "1") {


            if (params.types) {
               // typeSelector.defaultTypeSelected = false;
               // typeSelector.selectTypes(params.types.split(","));
            }
            var pos = ($("#title").length > 0) ? $("#title").innerHeight() : 0;
            $("#menu_wrapper").css("top", pos+"px");
            if(bucketColumn){
                $("#menu_wrapper_title").html(bucketColumn);
            }

            typeSelector = new MenuSelector("#menu_types_wrapper","#menu_types", pot.dotsLayer,$("#menu_wrapper").innerWidth() + 10);
        }else{
            $("#menu_wrapper").remove();
        }

        // make legend
        var legend;
        function makeLegend(){
            if (!typeSelector) return;
            typeSelector.makeLegend('#map', 'br', radiusMaker, bubbleSizeColumn);
        }

        var radiusMaker = d3.scale.sqrt();
        radiusMaker.rangeRound([minSize, maxSize]);


        // adjust controls if title
        if (params.title) {
           $(".controls").css("top",($("#title").height() + 20) +"px");
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

        var rollover_tmpl,
            useTemplate = false,
            infoPanelText = null,
            bucketPrep = {},
            bucketCount = 0,
            bucketList = [];


        function updateValues(val){
            maxValue = Math.max(maxValue,val);
            minValue = Math.min(minValue,val);
        }

        function sortMarkers(markers,dir){
            if(dir == undefined || dir == null){
                dir = "desc";
            }else{
                dir = (dir == "asc") ? "asc" : "desc";
            }

            function sortee(a, b) {
                var x = parseFloat(a.myAttrs.markerSize);
                var y = parseFloat(b.myAttrs.markerSize);
                return (dir == "asc") ? x - y : y - x;
            }
            var markers = markers || pot.dotsLayer.markers,
            len = markers.length;
            markers.sort(sortee);
            var len = markers.length;
            for(i=0;i<len;i++){
                mdict[markers[i].myAttrs.id].toFront();
                markers[i].myAttrs.__zindex = i;
            }
        }

        pot.makeDot = function(feature) {

            normalizeFeature(feature);
            var props = feature.properties,
                markerVal =  6,
                bucket_type = getBucketType(props),
                geom = (feature.geometry.type == 'GeometryCollection') ? feature.geometry.geometries : [ feature.geometry ];

            if (bubbleSizeColumn && feature.properties.hasOwnProperty(bubbleSizeColumn)) {
                markerVal = feature.properties[bubbleSizeColumn];
            }

            props.bucketType = bucket_type;
            props.__active = true;
            var coords = geom[0]['coordinates'];
            var pid = "dot_"+props.id;

            //
            //
            //addCommas
            if(feature.properties.__rollover_message){
                useTemplate = true;
                feature.properties.__rollover_message = normalizeRolloverMessage(feature.properties.__rollover_message);
                if(!rollover_tmpl){
                    //console.log(feature.properties.__rollover_message)
                    rollover_tmpl = "<span>"+feature.properties.__rollover_message+"</span>";
                    $.template( "rollover_tmpl", rollover_tmpl );
                }
                //console.log(rollover_tmpl)
                props.tipMessage = $.tmpl("rollover_tmpl", props);
            }else if(rollover_tmpl){

                props.tipMessage = $.tmpl("rollover_tmpl", props);
            }


            if(!infoPanelText){
                if(feature.properties.__description_panel && feature.properties.__description_panel.length > 1) infoPanelText = feature.properties.__description_panel;
            }


            var loc = new mm.Location(coords[1],coords[0]);
            props.__dt_coords = loc;

            updateValues(markerVal);


            var more_front = {
               style: jQuery.extend({}, over_style),
               id:pid,
               radius:6,
               markerSize:markerVal,
               dotClass:"dott",
               type:bucket_type,
               props:props,
               _kirbyPos:"front"
            };

            var marker = more_front;


            // add a background dot ????
            if(ds_kirby){
                var more_back = {
                       style: jQuery.extend({}, under_style),
                       radius:12,
                       _kirbyPos:"back",
                       props:props,
                       id:pid
                   };

                // Dots.Potting class only takes one marker,
                // will manually add this one, for now, until a Kirby Dot markerLayer exsists
                var c = pot.dotsLayer.addMarker(more_back,loc);
                c.toBack();
                backdict[pid] = c;
            }


            if(!bucketPrep[bucket_type]){
               bucketCount++;
               bucketPrep[bucket_type] = props;
            }
            bucketList.push(props);

            return marker;
        };



        //load markers and do things when done
        var req = pot.load(null,function(){

            if (typeSelector) {

                if(params.cs && colorbrewer){
                    if(colorbrewer[params.cs]){
                        if(bucketCount > 3 && bucketCount <= 9 ){
                            colors = d3.scale.ordinal().range(colorbrewer[params.cs][bucketCount]);
                        }else{
                            colors = d3.scale.ordinal().domain([0,bucketCount-1]).range([colorbrewer[params.cs][9][0],colorbrewer[params.cs][9][colorbrewer[params.cs][9].length-1]]);
                        }
                    }
                }
                if(!colors){
                    if(bucketCount <= 10){
                        colors = d3.scale.category10();
                    }else{
                        colors = d3.scale.category20();
                    }
                }

                for(i=0;i<bucketList.length;i++){
                    var label = typeSelector.addLabel(bucketList[i]);
                }
                bucketPrep = {};
                bucketList = [];


                typeSelector.selectorComplete();

                var markers = pot.dotsLayer.markers,
                    len = markers.length,
                    co = typeSelector.colorScheme;
                for(c in co){
                    for(i=0;i<len;i++){
                        if(c == markers[i].myAttrs['type']){
                            markers[i].attr("fill",co[c]);
                            markers[i].myAttrs['style']['fill'] = co[c];
                            if(!mdict[markers[i].myAttrs.id])mdict[markers[i].myAttrs.id] = markers[i];
                        }
                    }
                }

                radiusMaker.domain([minValue, maxValue]);
                var locations = [];

                for(i=0;i<len;i++){
                    if (markers[i].hasOwnProperty('location'))locations.push(markers[i].location);
                    if(markers[i].myAttrs._kirbyPos == "front"){
                        //var max = (markers[i].attrs.markerSize) ? (markers[i].attrs.markerSize / maxValue) * maxSize : minSize;

                        var size = (markers[i].myAttrs.markerSize) ? (markers[i].myAttrs.markerSize) : minSize;
                        //var radius =  (((Math.sqrt(size)) / Math.sqrt(maxValue))) * maxSize;

                        //http://blog.thematicmapping.org/2008/06/proportional-symbols-in-three.html
                        //var radius = (size / maxValue) * maxSize;
                        //var radius = Math.pow(size/maxValue, 1/2) * maxSize;

                        var radius = radiusMaker(size);
                        if(radius < minSize) radius = minSize;

                        markers[i].attr("r",radius);
                        if(!mdict[markers[i].myAttrs.id])mdict[markers[i].myAttrs.id] = markers[i];


                        /*
                        var txt = canvas.text(markers[i].attrs.cx,markers[i].attrs.cy, "000");
                        txt.coord = markers[i].coord;
                        misc.push(txt);
                        */
                    }
                }

                sortMarkers(markers,"desc");
            }

            // autofit
            if (locations.length && params.autofit) pot.map.setExtent(locations);

            // cluster markers
            pot.dotsLayer.cluster();

            if(infoPanelText){
                $("#info_panel p").html(infoPanelText);
                $("#info_panel a").html("show description");

                $("#info_panel a").click(function(e){
                    e.preventDefault();
                    if($("#info_panel p").is(":visible")){
                        $("#info_panel p").hide();
                         $(this).html("show description");
                    }else{
                        $(this).html("hide description");
                        var btnWidth = $(this).outerWidth() + 2;
                        $("#info_panel p").css('margin-right',btnWidth+"px");
                        $("#info_panel p").show();


                    }
                });


                $("#info_panel").show();
                if($("#title").is(":visible")){
                    $("#info_panel").css("top",(($("#title").length > 0) ? $("#title").innerHeight() : 0)+"px");
                }
                $("#menu_wrapper").css("top",($("#info_panel a").innerHeight() + $("#info_panel").offset().top + 5)+"px");
                if(typeSelector){
                    if($("#info_panel a").width() > typeSelector.menuWidth){
                        typeSelector.canvas.setSize($("#info_panel a").width(),typeSelector.menuHeight);
                    }
                }


                //$("#info_panel a").trigger('click');

            }else{
                $("#info_panel").remove();
            }

            if (bubbleSizeColumn) makeLegend();

            // create tooltip
            // pass it the selector to listen for...
            // pulls rest of params from pot object
            // uses jQuery delegate
            // because we are using Raphael we need to use the id as the selector
            if(typeof DotToolTip == 'function')ds_tooltip = new DotToolTip("[id*='dot_']");


        });



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
        //console.error("ERROR: ", e);
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
function MenuSelector(wrapper, selector, layer, initWidth) {
    this.wrapper = $(wrapper);
    this.container = $(selector);
    if(initWidth !== "undefined" || initWidth !== null)this.menuWidth = initWidth;

    this.canvas = Raphael("menu_types", this.menuWidth, this.menuHeight);
    this.layer = layer;
    this.labelsByType = {};
    this.selectedTypes = {};
    this.buttonSets = {};
    this.colorScheme = {};
    this.labelStates = {};
    this.labelCounts = {};
    this.pools = {};
}

MenuSelector.prototype = {
    container: null,
    canvas:null,
    layer: null,
    buttonSets: null,
    labelsByType: null,
    labelStates: null,
    labelCounts:null,
    buttonLength:0,
    selectedTypes: null,
    colorScheme: null,
    defaultTypeSelected: true,
    menuWidth:20,
    menuHeight:200,
    pools:null,
    wrapper:null,
    show_all:$("#ct_show_all"),
    hide_all:$("#ct_hide_all"),
    init:false,

    // sort labels function
    sortLabels: function(arr){
        arr = arr.sort(function(a, b) {
            return (b > a) ? -1 : (b < a) ? 1 : 0;
        });
        var len = arr.length;
        for(i=0;i<len;i++){
            var yPos = 10 + (i * 25);
            this.buttonSets[arr[i]][0].attr("cy",yPos);
            this.buttonSets[arr[i]][1].attr("y",yPos);
            this.buttonSets[arr[i]][2].attr("y",yPos - 10);
        }
    },

    // onComplete function
    selectorComplete: function(){
        if(this.init)return;
        this.init = true;

        // temporary array for sorting
        var s = [];
        // context
        var that = this;

        // adjust size of buttons to canvas size
        // set tooltips for buttons
        for(set in this.buttonSets){
            // resize
            this.buttonSets[set][2].attr("width",this.menuWidth);
            // set tips
            var ts = " " + this.labelCounts[set] + " ";
            $(this.buttonSets[set][2].node).tipTip({
        	    maxWidth: "auto",
        	    edgeOffset: 0,
        	    delay:100,
        	    defaultPosition:"left",
        	    forcePosition:true,
        	    content:ts,
        	    enter:function(){},
                exit:function(){}
        	});

            // store label in temp array
        	s.push(set);
        }

        // sort labels by name
        this.sortLabels(s);

        // show all dots after mouse leaves container

        /*
        $(this.container).mouseleave(function(){
            if (that.unAllReal.timeout) clearTimeout(that.unAllReal.timeout);
            that.unAll(10);
        });
        */

        this.show_all.click(function(e){
               e.preventDefault();
               for(t in that.labelStates){
                   that.labelStates[t] = true;
                   that.unselectButtons(t);
                   that.showMarkers(t);
               }
               that.resetZindex();

           });

       this.hide_all.click(function(e){
           e.preventDefault();
           for(t in that.labelStates){
               that.labelStates[t] = false;
               that.selectButtons(t);
               that.hideMarkers(t);
           }
       });

    },

    getID: function(selectorID) {
        return selectorID.slice(2);
    },

    // adds button elements
    // todo: create custom method to wrap the three button elements
    addLabel: function(data) {
        var type = data.bucketType;
        var that = this;

        // check for label
        if (this.labelsByType[type]) {
            var label = this.labelsByType[type];
            this.labelCounts[type]++;
            return label;
        }

        // create label
        var pos = this.buttonLength;
        var yPos = 10 + (pos * 25);
        var clr = colors(pos);
        this.colorScheme[type] = clr;

        // draw circle
        var label = this.canvas.circle(10, yPos, 8);

        label.attr(over_style);
        label.attr("fill",clr);
        label.node.id = "c_"+type;

        // make text
        var txt = this.canvas.text(30, yPos, type);
        txt.node.id = "t_"+type;
        txt.attr({
            "font-size":12,
            "font-weight":"bold",
            "text-anchor":"start"
        });

        // make button area
        var btn = this.canvas.rect(2,yPos - 10,190,20);
        btn.node.id = "b_"+type;
        btn.attr({
            "cursor":"pointer",
            "fill":"#000000",
            "fill-opacity":0,
            "stroke-opacity":0
        });

        // events
        btn.node.onclick = function(e){
            var id = that.getID(this.id);

            var state = !that.labelStates[id];

            if(state){
                that.unselectButtons(id);
                that.showMarkers(id);
                that.highlightMarkers(id);
            }else{
                that.selectButtons(id);
                that.hideMarkers(id);
            }

            that.labelStates[id] = state;
        };

        btn.node.onmouseover = function(e){
            var id = that.getID(this.id);
            btn.attr("fill-opacity", 0.3);
            that.highlightMarkers(id);
            //if(that.labelStates[id]) that.highlightMarkers(id);
        };

        btn.node.onmouseout = function(e){
            var id = that.getID(this.id);
            btn.attr("fill-opacity", 0);
            //if(that.labelStates[id])that.unhighlightMarkers(id);
            that.unAll(400);

        };

        // housekeeping
        this.labelsByType[type] = label;
        this.buttonSets[type] = [label,txt,btn];
        this.labelStates[type] = true;
        this.labelCounts[type] = 1;
        this.buttonLength ++;

        // adjust menu container
        // todo: check for container height

        var txtWidth = txt.getBBox().width || txt.node.clientWidth;

        this.menuWidth = Math.max(this.menuWidth,(parseFloat(txtWidth) + 55));
        this.menuHeight = Math.max(this.menuHeight, yPos + 20);

        this.canvas.setSize(this.menuWidth,this.menuHeight);

        // returns the dot
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

    highlightMarkers: function(t){
        if (this.unAllReal.timeout) clearTimeout(this.unAllReal.timeout);
        this.unAllReal.timeout = null;

        var markers = this.layer.markers,
        len = markers.length;

        for(i=0;i<len;i++){
            if(markers[i].myAttrs.id){
                var op = markers[i].myAttrs['props']['__active'] ? 1 : 0.4;
                if(t == markers[i].myAttrs['type']){
                    markers[i].toFront();
                    if(ds_kirby)backdict[markers[i].myAttrs.id].attr("opacity", 1);
                    markers[i].attr("opacity", 1);
                    markers[i].attr("stroke-opacity", op);
                    markers[i].attr("fill-opacity", op);
                    //this.pools[t].push(markers[i]);
                }else{
                    if( markers[i].myAttrs['props']['__active']){
                        if(ds_kirby)backdict[markers[i].myAttrs.id].attr("opacity",.2);
                        markers[i].attr("opacity",0);

                    } else {
                        markers[i].attr("stroke-opacity", 0.4);
                        markers[i].attr("fill-opacity", 0);
                    }
                }
            }

        }
    },

    unhighlightMarkers: function(t){
        var markers = this.layer.markers,
        len = markers.length;
        for(i=0;i<len;i++){
            if(markers[i].myAttrs.id){
                if(t == markers[i].myAttrs['type']){

                }else{
                    //backdict[markers[i].attrs.id].attr("opacity",1);
                    //markers[i].attr("opacity",1);
                }
            }
        }
    },

    hideMarkers: function(t){
        var markers = this.layer.markers,
        len = markers.length;
        for(i=0;i<len;i++){
            if(markers[i].myAttrs.id){
                if(t == markers[i].myAttrs['type']){
                    markers[i].myAttrs['props']['__active'] = false;
                    markers[i].toBack();
                    markers[i].attr("fill-opacity",0);
                    markers[i].attr("stroke-opacity",0.4);
                    if(ds_kirby)backdict[markers[i].myAttrs.id].attr("opacity",0);
                }
            }
        }
    },

    resetZindex: function() {
        var markers = this.layer.markers,
        len = markers.length;
        for(i=0;i<len;i++){
            if (markers[i].myAttrs['props']['__active']) {
                markers[i].toFront();
            }

        }
    },

    showMarkers: function(t){
        var markers = this.layer.markers,
        len = markers.length;
        for(i=0;i<len;i++){
            if(markers[i].myAttrs.id){
                if(t == markers[i].myAttrs['type']){
                    markers[i].myAttrs['props']['__active'] = true;
                    markers[i].attr("fill-opacity",1);
                    markers[i].attr("stroke-opacity",1);
                    if(ds_kirby)backdict[markers[i].myAttrs.id].attr("opacity",1);
                }
            }
        }
    },
    /// delay for unhighlighting markers ???
    unAllReal: function(){
        this.unAllReal.timeout = null;

           var markers = this.layer.markers,
           len = markers.length;
           for(i=0;i<len;i++){
               if(markers[i].myAttrs.id){
                  if( markers[i].myAttrs['props']['__active']){
                      if(ds_kirby)backdict[markers[i].myAttrs.id].attr("opacity",1);
                      markers[i].attr("opacity",1);
                      markers[i].attr("fill-opacity",1);
                        markers[i].attr("stroke-opacity",1);

                      //this.animate(backdict[markers[i].myAttrs.id],1,200);
                      //this.animate(markers[i],1,200);
                  } else {

                    markers[i].attr("fill-opacity",0);
                    markers[i].attr("stroke-opacity",0.4);
                  }

               }
           }
           this.resetZindex();
       },
       unAll: function(ms){
            ms = ms || 400;
            var that = this;
            if (this.unAllReal.timeout) clearTimeout(this.unAllReal.timeout);
            this.unAllReal.timeout = setTimeout(function() {
                that.unAllReal.apply(that);
            }, ms);
       },
       animate: function(elm,val,t){
           elm.animate({"opacity":val},t);
       },
       stageCheck: function(){
           /* write me */
       },
       makeLegend: function(appendTo, position, scale, titleString) {
            var that = this;
            var script = document.createElement("script");

            script.onload = function() {
                legend = DS.extras.CircleLegend('#menu_wrapper', position, scale, titleString);
                document.body.style.minHeight = $('#menu_wrapper').height() + "px";
            };
            script.src = "../javascript/ds.legend.js";
            script.type = "text/javascript";
            document.getElementsByTagName("head")[0].appendChild(script);
       }


};