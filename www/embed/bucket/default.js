//define globals
var pot,params,colors,bucketColumn,mdict,ds_tooltip,backdict;

// style objects for dot
var over_style = {
	'fill' : 'rgb(11,189,255)',
	'fill-opacity' : 1,
	'stroke' : '#666666',
	'stroke-width' : 1,
	'stroke-opacity':0
}; 

var under_style = {
	'fill' : 'rgb(33,33,33)',
	'fill-opacity' : .8,
	'stroke' : 'rgb(66,66,66)',
	'stroke-width' : 1
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


        pot = new Dots.Potting(params);
        pot.setTitle();
        pot.dotsLayer = new mm.DotMarkerLayer(pot.map);
        
        params.ui = "1";
        if (params.ui == "1") {
            var typeSelector = new MenuSelector("#menu_types_wrapper","#menu_types", pot.dotsLayer);
            
            if (params.types) {
               // typeSelector.defaultTypeSelected = false;
               // typeSelector.selectTypes(params.types.split(","));
            }
            var pos = ($("#title").length > 0) ? $("#title").innerHeight() : 0;
            $("#menu_wrapper").css("top",pos+"px");
            if(bucketColumn){
                $("#menu_wrapper_title").html(bucketColumn);
            }
        }else{
            $("#menu_wrapper").remove();
        }
        
        // adjust controls if title
        if (params.title) {
           $(".controls").css("top",($("#title").height()+20)+"px");
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
            useTemplate=false,
            infoPanelText = null,
            bucketPrep = {},
            bucketCount = 0,
            bucketList = [];
            
        function normalizeRolloverMessage(msg){
            var msgParts = msg.split(/(\${.+?\})/gi),
            len = msgParts.length;
            if(!len)return msg;
            var newMsg = "";
            for(i=0;i<len;i++){
                if(msgParts[i].indexOf("$") == 0){
                   msgParts[i] = msgParts[i].replace(/ /g, "_").toLowerCase();
                }
                newMsg += msgParts[i];
            }
            
            return newMsg;
            
            
        }
        pot.makeDot = function(feature) {
            normalizeFeature(feature);
            var props = feature.properties,
            bucket_type = getBucketType(props),
            geom = (feature.geometry.type == 'GeometryCollection') ? feature.geometry.geometries : [ feature.geometry ];

            props.bucketType = bucket_type;
            props.__active = true;
            var coords = geom[0]['coordinates'];
            var pid = "dot_"+props.id;
            
            
            if(feature.properties.__rollover_message){
                useTemplate = true;
                feature.properties.__rollover_message = normalizeRolloverMessage(feature.properties.__rollover_message);
                if(!rollover_tmpl){
                    rollover_tmpl = "<span>"+feature.properties.__rollover_message+"</span>";  
                   $.template( "rollover_tmpl", rollover_tmpl );
                }
                props.tipMessage = $.tmpl( "<span>"+feature.properties.__rollover_message+"</span>",props);
            }else if(rollover_tmpl){
                props.tipMessage = $.tmpl( "rollover_tmpl",props);
            }
            
            if(!infoPanelText){
                if(feature.properties.__description_panel && feature.properties.__description_panel.length > 1) infoPanelText = feature.properties.__description_panel;
            }
            
            var more_front = {
               style: over_style,
               id:pid,
               radius:6,
               dotClass:"dott",
               type:bucket_type,
               props:props
            };
            
            var more_back = {
                   style: under_style,
                   radius:12
               };


            var loc = new mm.Location(coords[1],coords[0]);
            props.__dt_coords = loc;

            var marker = more_front;
            
            // Dots.Potting class only takes one marker, 
            // will manually add this one, for now, until a Kirby Dot markerLayer exsists
            var c = pot.dotsLayer.addMarker(more_back,loc);
            c.toBack();
            backdict[pid] = c;
            
            if(!bucketPrep[bucket_type]){
               bucketCount++; 
               bucketPrep[bucket_type] = props;
            }
            bucketList.push(props);
 

            return marker;
        };
        
        var infoPanelContentElm,infoPanelElm,infoPanelCloseElm,infoPanelCloseTxtElm;
        //load markers and do things when done
        var req = pot.load(null,function(){
            // create tooltip
            // pass it the selector to listen for...
            // pulls rest of params from pot object
            // uses jQuery live
            ds_tooltip = new DotToolTip(".dott",useTemplate);
            
            if (typeSelector) {
                
                if(params.cs && colorbrewer){
                    if(colorbrewer[params.cs]){
                        if(bucketCount <= 3){
                            colors = d3.scale.ordinal().range(colorbrewer[params.cs][3]); 
                        }else if(bucketCount >= 9){
                            colors = d3.scale.ordinal().range(colorbrewer[params.cs][9]); 
                        }else{
                            colors = d3.scale.ordinal().range(colorbrewer[params.cs][bucketCount]);
                        }
                    }
                }
                if(!colors)colors = d3.scale.category10();
                
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
                        if(c == markers[i].attrs['type']){
                            markers[i].attr("fill",co[c]);
                            //markers[i].attr("fill-opacity",1);
                            if(!mdict[markers[i].attrs.id])mdict[markers[i].attrs.id] = markers[i];
                        }
                    }
                }

                
            }
            
            if(infoPanelText){
                infoPanelContentElm = $("#info_panel p"),
                infoPanelElm = $("#info_panel"),
                infoPanelCloseElm = $("#info_panel a"),
                infoPanelCloseTxtElm = $("#info_panel a span");
                
                var pos = ($("#title").length > 0) ? $("#title").innerHeight() : 0;
                
                infoPanelElm.css("top",pos+"px").show();
                infoPanelContentElm.html( infoPanelText );
                infoPanelCloseElm.css("height",infoPanelElm.innerHeight() + "px");
                var infopos = (infoPanelElm.length > 0) ? infoPanelElm.offset().top + infoPanelElm.innerHeight() + 20 : 0;
                
                $("#menu_wrapper").css("top",infopos+"px");
                
                infoPanelCloseElm.click(function(e){
                    e.preventDefault();
                    if(infoPanelContentElm.is(':visible')){
                        infoPanelContentElm.hide();
                        infoPanelCloseTxtElm.html("&laquo;");
                        infoPanelElm.css("width",$(this).innerWidth()+"px");
                    }else{
                        infoPanelContentElm.show();
                        infoPanelCloseTxtElm.html("&raquo;");
                        infoPanelElm.css("width","50%");
                        adjustPanelSizes();
                    }
                    
                });
                
                $(window).resize(function(e){
                    adjustPanelSizes();
                });
                
                function adjustPanelSizes(){
                    infoPanelCloseElm.css("height", "auto");
                    var newsize = infoPanelElm.innerHeight() + "px";
                    infoPanelCloseElm.css("height", newsize);
                    
                    infopos = (infoPanelElm.length > 0) ? infoPanelElm.offset().top + infoPanelElm.innerHeight() + 20 : 0;
                    $("#menu_wrapper").css("top",infopos+"px");
                }
            }else{
                $("#info_panel").remove();
            }
            
            doCluster();
            
         
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

function dotHasClass(element, $class) {
    if(!element.className.baseVal)return false;
    var pattern = new RegExp("(^| )" + $class + "( |$)");
    return pattern.test(element.className.baseVal) ? true : false;
};

function dotAddClass(element, $class) {
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
};

function dotRemoveClass(element, $class) {
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
};



// borough
function MenuSelector(wrapper,selector, layer) {
    this.wrapper = $(wrapper);
    this.container = $(selector);
    this.canvas = Raphael("menu_types", this.menuWidth, this.menuHeight);
    this.layer = layer;
    this.labelsByType = {};
    this.selectedTypes = {};
    this.buttonSets = {};
    this.colorScheme = {};
    this.labelStates = {};
    this.labelCounts = {};
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
    menuWidth:200,
    menuHeight:200,
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
        $(this.container).mouseleave(function(){
            if (that.unAllReal.timeout) clearTimeout(that.unAllReal.timeout);
            that.unAll(10);
        });
        
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
        label.attr({
            "fill":clr,
            "fill-opacity":1,
            'stroke' : '#666666',
        	'stroke-width' : 1,
        	'stroke-opacity':1
            });
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
            "stroke-width":0
        });
        
        // events
        btn.node.onclick = function(e){
            var id = $(this).attr("id");
            id = id.slice(2);
            
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
        }
        btn.node.onmouseover = function(e){
            
            var id = $(this).attr("id");
            id = id.slice(2);
            btn.attr("fill-opacity",.3);
            if(that.labelStates[id])that.highlightMarkers(id);
        }
        btn.node.onmouseout = function(e){
            var id = $(this).attr("id");
            id = id.slice(2);
            btn.attr("fill-opacity",0);
            //if(that.labelStates[id])that.unhighlightMarkers(id);
            that.unAll(800);
           
        }
        
        // housekeeping
        this.labelsByType[type] = label;
        this.buttonSets[type] = [label,txt,btn];
        this.labelStates[type] = true;
        this.labelCounts[type] = 1;
        this.buttonLength ++;
        
        // adjust menu container
        // todo: check for container height
        this.menuWidth = Math.max(this.menuWidth,(txt.node.clientWidth+50));
        this.menuHeight = Math.max(this.menuHeight,yPos+20);
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
        var markers = this.layer.markers,
        len = markers.length;
        for(i=0;i<len;i++){
            if(markers[i].attrs.id){
                if(t == markers[i].attrs['type']){
                    //if(!dotHasClass(markers[i].node,"over_hover"))dotAddClass(markers[i].node,"over_hover");
                    markers[i].toFront();
                    backdict[markers[i].attrs.id].attr("opacity",1);
                    markers[i].attr("opacity",1);
                }else{
                    if( markers[i].attrs['props']['__active']){
                        backdict[markers[i].attrs.id].attr("opacity",.2);
                        markers[i].attr("opacity",0);
                    }
                }
            }

        }
    },
    unhighlightMarkers: function(t){
        var markers = this.layer.markers,
        len = markers.length;
        for(i=0;i<len;i++){
            if(markers[i].attrs.id){
                if(t == markers[i].attrs['type']){
                    //dotRemoveClass(markers[i].node,"over_hover");
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
            if(markers[i].attrs.id){
                if(t == markers[i].attrs['type']){
                    markers[i].attrs['props']['__active'] = false;
                    markers[i].attr("fill-opacity",0);
                    backdict[markers[i].attrs.id].attr("opacity",0);
                    //if(dotHasClass(markers[i].node,"over_hover"))dotRemoveClass(markers[i].node,"over_hover");
                }
            }
        }
    },
    showMarkers: function(t){
        var markers = this.layer.markers,
        len = markers.length;
        for(i=0;i<len;i++){
            if(markers[i].attrs.id){
                if(t == markers[i].attrs['type']){
                    markers[i].attrs['props']['__active'] = true;
                    markers[i].attr("fill-opacity",1);
                    backdict[markers[i].attrs.id].attr("opacity",1);
                }
            }
        }
    },
    /// delay for unhighlighting markers ???
    unAllReal: function(){
           var markers = this.layer.markers,
           len = markers.length;
           for(i=0;i<len;i++){
               if(markers[i].attrs.id){
                  if( markers[i].attrs['props']['__active']){
                      //backdict[markers[i].attrs.id].attr("opacity",1);
                      //markers[i].attr("opacity",1);
                      this.animate(backdict[markers[i].attrs.id],1,200);
                      this.animate(markers[i],1,200);
                  }

               }
           }
       },
       unAll: function(ms){
           if(!ms)ms = 400;
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
           
       },
    
    
};

//////////////////////////
/////////////////////////

function DotToolTip(selector,useTemplate) {
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
    
    if(useTemplate != null || useTemplate != undefined)this.useTemplate = useTemplate;
    
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
    useTemplate:false,
    
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
                
                var id = String($(this).attr('id'));
                if(!id)return;
                if(!mdict[id])return;
                if(!mdict[id].attrs.props['__active'])return;
               
                mdict[id].attrs.props["__dt_coords"] = mdict[id].coord;
                /// proceed
                that.currentDot = this;
                that.currentProp = mdict[id].attrs.props;
                this.parentNode.appendChild(this);
                that.showTip();
            } else {
                dotRemoveClass(that.currentDot,"over_hover");
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
        if(this.currentProp.tipMessage){
            this.tt_desc.css("display","block");
            this.tt_desc.html(this.currentProp.tipMessage);
            this.tt_title.css("display","none");
        }else{
            var _title = this.getTipTitle();
            var _desc = this.getTipDesc();
            if(!_title.length && !_desc.length)return;
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
        }
       

        dotAddClass(this.currentDot,"over_hover");
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
        var _point = this.map.coordinatePoint(this.currentProp.__dt_coords);
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
        this.container = this.container ||  $(pot.selectors.map);
        this.cont_offset = this.container.offset();
        this.cont_width = this.container.width();
        this.cont_height = this.container.height();
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
        if(this.useTemplate)return true;
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


