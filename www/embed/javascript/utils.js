// define the dummy console interface if it doesn't exist
if (typeof console == "undefined") console = {};
if (typeof console.log == "undefined") console.log = function() {};

function defer(fn, ms, context) {

    if (!ms) ms = 10;
    return function() {
        var args = arguments, that = context || this;
        if (fn.timeout) clearTimeout(fn.timeout);
        return fn.timeout = setTimeout(function() {
            if (typeof fn === "function") {

                fn.apply(that, args);
            }
        }, ms);
    };
}

function bind(that, fn) {
    return function() {
        return fn.apply(that, arguments);
    };
}

function capitalize(str, ignore) {
    return str.split(" ").map(function(word) {
        if (!ignore || ignore.indexOf(word) == -1) {
            return word.charAt(0).toUpperCase() + word.substr(1).toLowerCase();
        } else {
            return word;
        }
    }).join(" ");
}

function pluralize(word,num,plural){
    return (num == 1)? word : plural || word+"s";
}

function addCommas(nStr)
{
	nStr += '';
	x = nStr.split('.');
	x1 = x[0];
	x2 = x.length > 1 ? '.' + x[1] : '';
	var rgx = /(\d+)(\d{3})/;
	while (rgx.test(x1)) {
		x1 = x1.replace(rgx, '$1' + ',' + '$2');
	}
	return x1 + x2;
}

function capitalizeWord(word) {
    return word.charAt(0).toUpperCase() + word.substr(1).toLowerCase();
}

function parseQueryString(str) {
    // gather page parameters from the query string
    var params = {},
        paramMatch = str.match(/(\w+)=([^&$]+)/g);
    if (paramMatch) {
        var len = paramMatch.length;
        for (var i = 0; i < len; i++) {
            var part = paramMatch[i].split("=");
            params[part[0]] = decodeURIComponent(part[1]).replace(/\+/g, " ");
        }
    }
    return params;
}

function makeQueryString(params) {
    var out = "";
    for (var p in params) {
        if (typeof params[p] !== "undefined" && String(params[p]).length > 0) {
            out += (out.indexOf("?") > -1) ? "&" : "?";
            out += p + "=" + String(params[p]).replace(/ /g, "+");
        }
    }
    return out;
}

var MapHash = function(map) {
    var hash = {};

    hash.parse = function(fragment) {
        if (fragment.charAt(0) == "#") {
            var parts = fragment.substr(1).split("/").map(parseFloat);
            return {zoom: parts[0], center: {lat: parts[1], lon: parts[2]}};
        } else {
            return false;
        }
    };

    hash.apply = function() {
        var center = map.getCenter(),
            zoom = map.getZoom(),
            precision = Math.max(0, Math.ceil(Math.log(zoom) / Math.LN2));
        if (!isNaN(center.lon) && !isNaN(center.lat) && !isNaN(zoom)) {
            location.hash = "#" + [zoom, center.lat.toFixed(precision), center.lon.toFixed(precision)].join("/");
            return true;
        } else {
            return false;
        }
    };

    hash.read = function(fragment) {
        var loc = hash.parse(fragment);
        if (!isNaN(loc.zoom) && !isNaN(loc.center.lat) && !isNaN(loc.center.lon)) {
            map.setCenterZoom(loc.center, loc.zoom);
            return true;
        } else {
            return false;
        }
    };

    var update = defer(hash.apply, 100);
    hash.start = function() {
        map.addCallback("drawn", update);
        return hash;
    };
    hash.stop = function() {
        map.removeCallback("drawn", update);
        return hash;
    };

    return hash;
};

var ClickPreventer = function(ms) {
    var preventer = {},
        maxTime = ms || 250;

    function down(e) {
        $(this).data("downtime", +new Date());
    }

    function up(e) {
        $(this).data("uptime", +new Date());
    }

    function click(e) {
        var d = $(this).data("downtime"),
            u = $(this).data("uptime");
        if (d && u && (u - d) > maxTime) {
            e.preventDefault();
            return false;
        }
        return true;
    }

    preventer.prevent = function(link) {
        return $(link).mousedown(down).mouseup(up).click(click);
    };

    preventer.allow = function(link) {
        return $(link).mousedown(down, false).mouseup(up, false).click(click, false);
    };

    return preventer;
};

var MapControls = function(map, container) {
    var controls = {container: container};

    // container = $(container).appendTo(map.parent);

    controls.addButton = function(text, action, context) {
        return $("<button/>").text(text)
                .click(function(e) {
            action.call(context || this);
            e.preventDefault();
            return false;
        }).addClass("rounded").appendTo(container);
    };

    return controls;
};

var ExtentSetter = function(map) {
    var setter = {},
        enabled = true,
        active = false,
        box = $("<div/>").addClass("extent");

    box.css({position: "absolute", border: "1px dotted red", background: "rgba(0,0,0,.1)"});

    setter.enable = function() {
        enabled = true;
    };

    setter.disable = function() {
        enabled = false;
    };

    var topLeft = null,
        bottomRight = null;
    function update() {
        box.css({
            left: Math.round(Math.min(topLeft.x, bottomRight.x)) + "px",
            top: Math.round(Math.min(topLeft.y, bottomRight.y)) + "px",
            width: Math.round(Math.abs(bottomRight.x - topLeft.x)) + "px",
            height: Math.round(Math.abs(bottomRight.y - topLeft.y)) + "px"
        });
    }

    $(map.parent)
        .mousedown(function(e) {
            if (enabled && e.shiftKey) {
                box.appendTo(this);
                topLeft = {x: e.clientX, y: e.clientY};
                active = true;
                e.preventDefault();
                return false;
            }
        })
        .mousemove(function(e) {
            if (enabled && active && e.shiftKey) {
                bottomRight = {x: e.clientX, y: e.clientY};
                update();
                e.preventDefault();
                return false;
            }
        })
        .mouseup(function(e) {
            if (enabled && active) {
                var northWest = map.pointLocation(topLeft),
                    southEast = map.pointLocation(bottomRight);
                map.setExtent([northWest, southEast]);
            }
            if (active) {
                box.css({width: "0px", height: "0px"}).remove();
                topLeft = bottomRight = null;
                active = false;
            }
        });

    return setter;
};

// parseUri 1.2.2
// (c) Steven Levithan <stevenlevithan.com>
// MIT License
function parseUri(str) {
    var	o = parseUri.options,
        m = o.parser[o.strictMode ? "strict" : "loose"].exec(str),
        uri = {},
        i = 14;

    while (i--) uri[o.key[i]] = m[i] || "";

    uri[o.q.name] = {};
    uri[o.key[12]].replace(o.q.parser, function ($0, $1, $2) {
        if ($1) uri[o.q.name][$1] = $2;
    });

    return uri;
};
parseUri.options = {
    strictMode: false,
    key: ["source","protocol","authority","userInfo","user","password","host","port","relative","path","directory","file","query","anchor"],
    q:   {
        name:   "queryKey",
        parser: /(?:^|&)([^&=]*)=?([^&]*)/g
    },
    parser: {
        strict: /^(?:([^:\/?#]+):)?(?:\/\/((?:(([^:@]*)(?::([^:@]*))?)?@)?([^:\/?#]*)(?::(\d*))?))?((((?:[^?#\/]*\/)*)([^?#]*))(?:\?([^#]*))?(?:#(.*))?)/,
        loose:  /^(?:(?![^:@]+:[^:@\/]*@)([^:\/?#.]+):)?(?:\/\/)?((?:(([^:@]*)(?::([^:@]*))?)?@)?([^:\/?#]*)(?::(\d*))?)(((\/(?:[^?#](?![^?#\/]*\.[^?#\/.]+(?:[?#]|$)))*\/?)?([^?#\/]*))(?:\?([^#]*))?(?:#(.*))?)/
    }
};

/**
 * Here we group all of the markers by their "corner"
 * (quantized location, see getCorner() above) and
 * distribute overlapping markers in a circle around the
 * center of the first one in the cluster.
 */
function clusterMarkers(markers) {

    // Quantize a number by a divisor
    function quantize(n, q) {
        return Math.round(n / q) * q;
    }

    /**
     * Quantize the location of the marker to determine its "corner".
     * Note: we should probably avoid offsetting markers with
     * more explicit locations.
     */
    function getCorner(marker) {
        var loc = marker.location,
            prec = .001,
            x = Number(loc.lon),
            y = Number(loc.lat);

        try {
            return quantize(x, prec)+ "," + quantize(y, prec);
        } catch (e) {
            return "bad";
        }
    }

    var corners = {},
        len = markers.length;
    for (var i = 0; i < len; i++) {
        var marker = markers[i],
            loc = marker.location,
            corner = getCorner(marker);
        if (loc.lat != 0 && loc.lon != 0) {

            marker._coord = marker.coord.copy();

            if (corner in corners) {
                corners[corner].push(marker);
            } else {
                corners[corner] = [marker];
            }
        }
    }


    for (var corner in corners) {
        var m = corners[corner];
        if (m.length > 1) {
            //.0000004,
            var r = .0000004,
                // TODO: use the center instead?
                c = m[0]._coord,
                a = Math.PI / 40,
                step = Math.PI * 2 / m.length;
            for (var i = 0; i < m.length; i++) {
                var mark = m[i],
                    offset = {
                        row: Math.cos(a) * r,
                        col: Math.sin(a) * r
                    };

                mark.coord.row += offset.row;
                mark.coord.column += offset.col;
                a += step;
            }
        }
    }
}


function normalizeRolloverMessage(msg){
    var msgParts = msg.split(/(\$\{.+?\})/gi);
    var len = msgParts.length;
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



/// Array utilties...
// Production steps of ECMA-262, Edition 5, 15.4.4.19
// Reference: http://es5.github.com/#x15.4.4.19
if (!Array.prototype.map) {
  Array.prototype.map = function(callback, thisArg) {

    var T, A, k;

    if (this == null) {
      throw new TypeError(" this is null or not defined");
    }

    // 1. Let O be the result of calling ToObject passing the |this| value as the argument.
    var O = Object(this);

    // 2. Let lenValue be the result of calling the Get internal method of O with the argument "length".
    // 3. Let len be ToUint32(lenValue).
    var len = O.length >>> 0;

    // 4. If IsCallable(callback) is false, throw a TypeError exception.
    // See: http://es5.github.com/#x9.11
    if ({}.toString.call(callback) != "[object Function]") {
      throw new TypeError(callback + " is not a function");
    }

    // 5. If thisArg was supplied, let T be thisArg; else let T be undefined.
    if (thisArg) {
      T = thisArg;
    }

    // 6. Let A be a new array created as if by the expression new Array(len) where Array is
    // the standard built-in constructor with that name and len is the value of len.
    A = new Array(len);

    // 7. Let k be 0
    k = 0;

    // 8. Repeat, while k < len
    while(k < len) {

      var kValue, mappedValue;

      // a. Let Pk be ToString(k).
      //   This is implicit for LHS operands of the in operator
      // b. Let kPresent be the result of calling the HasProperty internal method of O with argument Pk.
      //   This step can be combined with c
      // c. If kPresent is true, then
      if (k in O) {

        // i. Let kValue be the result of calling the Get internal method of O with argument Pk.
        kValue = O[ k ];

        // ii. Let mappedValue be the result of calling the Call internal method of callback
        // with T as the this value and argument list containing kValue, k, and O.
        mappedValue = callback.call(T, kValue, k, O);

        // iii. Call the DefineOwnProperty internal method of A with arguments
        // Pk, Property Descriptor {Value: mappedValue, Writable: true, Enumerable: true, Configurable: true},
        // and false.

        // In browsers that support Object.defineProperty, use the following:
        // Object.defineProperty(A, Pk, { value: mappedValue, writable: true, enumerable: true, configurable: true });

        // For best browser support, use the following:
        A[ k ] = mappedValue;
      }
      // d. Increase k by 1.
      k++;
    }

    // 9. return A
    return A;
  };
}

if (!Array.prototype.indexOf) {
    Array.prototype.indexOf = function (searchElement /*, fromIndex */ ) {
        "use strict";
        if (this === void 0 || this === null) {
            throw new TypeError();
        }
        var t = Object(this);
        var len = t.length >>> 0;
        if (len === 0) {
            return -1;
        }
        var n = 0;
        if (arguments.length > 0) {
            n = Number(arguments[1]);
            if (n !== n) { // shortcut for verifying if it's NaN
                n = 0;
            } else if (n !== 0 && n !== Infinity && n !== -Infinity) {
                n = (n > 0 || -1) * Math.floor(Math.abs(n));
            }
        }
        if (n >= len) {
            return -1;
        }
        var k = n >= 0 ? n : Math.max(len - Math.abs(n), 0);
        for (; k < len; k++) {
            if (k in t && t[k] === searchElement) {
                return k;
            }
        }
        return -1;
    }
}


/* Cross-Browser Split 1.0.1
(c) Steven Levithan <stevenlevithan.com>; MIT License
An ECMA-compliant, uniform cross-browser split method */

var cbSplit;

// avoid running twice, which would break `cbSplit._nativeSplit`'s reference to the native `split`
if (!cbSplit) {

cbSplit = function (str, separator, limit) {
    // if `separator` is not a regex, use the native `split`
    if (Object.prototype.toString.call(separator) !== "[object RegExp]") {
        return cbSplit._nativeSplit.call(str, separator, limit);
    }

    var output = [],
        lastLastIndex = 0,
        flags = (separator.ignoreCase ? "i" : "") +
                (separator.multiline  ? "m" : "") +
                (separator.sticky     ? "y" : ""),
        separator = RegExp(separator.source, flags + "g"), // make `global` and avoid `lastIndex` issues by working with a copy
        separator2, match, lastIndex, lastLength;

    str = str + ""; // type conversion
    if (!cbSplit._compliantExecNpcg) {
        separator2 = RegExp("^" + separator.source + "$(?!\\s)", flags); // doesn't need /g or /y, but they don't hurt
    }

    /* behavior for `limit`: if it's...
    - `undefined`: no limit.
    - `NaN` or zero: return an empty array.
    - a positive number: use `Math.floor(limit)`.
    - a negative number: no limit.
    - other: type-convert, then use the above rules. */
    if (limit === undefined || +limit < 0) {
        limit = Infinity;
    } else {
        limit = Math.floor(+limit);
        if (!limit) {
            return [];
        }
    }

    while (match = separator.exec(str)) {
        lastIndex = match.index + match[0].length; // `separator.lastIndex` is not reliable cross-browser

        if (lastIndex > lastLastIndex) {
            output.push(str.slice(lastLastIndex, match.index));

            // fix browsers whose `exec` methods don't consistently return `undefined` for nonparticipating capturing groups
            if (!cbSplit._compliantExecNpcg && match.length > 1) {
                match[0].replace(separator2, function () {
                    for (var i = 1; i < arguments.length - 2; i++) {
                        if (arguments[i] === undefined) {
                            match[i] = undefined;
                        }
                    }
                });
            }

            if (match.length > 1 && match.index < str.length) {
                Array.prototype.push.apply(output, match.slice(1));
            }

            lastLength = match[0].length;
            lastLastIndex = lastIndex;

            if (output.length >= limit) {
                break;
            }
        }

        if (separator.lastIndex === match.index) {
            separator.lastIndex++; // avoid an infinite loop
        }
    }

    if (lastLastIndex === str.length) {
        if (lastLength || !separator.test("")) {
            output.push("");
        }
    } else {
        output.push(str.slice(lastLastIndex));
    }

    return output.length > limit ? output.slice(0, limit) : output;
};

cbSplit._compliantExecNpcg = /()??/.exec("")[1] === undefined; // NPCG: nonparticipating capturing group
cbSplit._nativeSplit = String.prototype.split;

} // end `if (!cbSplit)`

// for convenience...
String.prototype.split = function (separator, limit) {
    return cbSplit(this, separator, limit);
};

/**
    Tooltip for Dots
    has alot of dependencies like:
        - requires access to pot & mdict object
        - expecting tooltip markup in a certain way
        - use at your own risk...
**/
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
    tt: null,
    tt_title: null,
    tt_desc: null,
    tt_nub: null,
    TT_WIDTH: 300,
    cont_offset: null,
    cont_width: null,
    cont_height: null,
    tip_title: null,
    tip_desc: null,
    tip_sentence:null,
    active:false,
    currentRalfObj:null,

    createTip: function(){

        if(this.checkParams()){
            this.tt = $("#mm_tip"),
            this.tt_title = $("#mm_tip_title"),
            this.tt_desc = $("#mm_tip_desc"),
            this.tt_nub = $("#mm_tip_nub"),
            this.active = true;

            this.addHandlers();
        }

    },
    addHandlers: function(){
        var that = this;
        that.removeHandlers();
        $(this.container).delegate(this.listenFrom, 'mouseover mouseout', function(event) {

            event.preventDefault();
            if ( event.type == "mouseover" ) {

                var id = String($(this).attr('id'));
                if(!id)return;
                if(!mdict[id])return;
                if(!mdict[id].myAttrs.props['__active'])return;
                if(mdict[id] == that.currentRalfObj)return;

                mdict[id].myAttrs.props["__dt_coords"] = mdict[id].coord;

                that.currentRalfObj = mdict[id];
                /// proceed
                that.currentDot = this;
                that.currentProp = mdict[id].myAttrs.props;

                that.showTip();
            } else {

                //that.currentDot = that.currentProp = null
                if(!that.currentRalfObj)return;

                //that.currentRalfObj.attr(over_style);
                that.currentRalfObj.attr(that.currentRalfObj.myAttrs['style']);

                //that.currentRalfObj.toBack();

                that.currentRalfObj = null;

                that.hideTip();
            }
            return false;
        });
        this.map.addCallback("resized", defer(that.updateSize,100));
    },

    removeHandlers: function(){
        this.map.removeCallback("resized");
        $(this.container).undelegate(this.listenFrom, "mouseover mouseout");
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
            this.tt_title.css("display","none");
            this.tt_desc.css("display","block")
            this.tt_desc.html(this.currentProp.tipMessage);
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

        this.currentRalfObj.attr(hover_style);
        this.initialTipPosition();
    },

    initialTipPosition: function(){

        this.tt.css("left","-9999px");
        this.tt.css("width","auto");
        var _w = (this.tt.width() < this.TT_WIDTH) ? this.tt.width() : this.TT_WIDTH;
        if(_w < 70)_w = 70;
        this.tt.css("width",_w+"px");
        this.tt.width(_w);
        //
        var _point = this.map.coordinatePoint(this.currentProp.__dt_coords);
        var _h = this.tt.height();
        var _radius = parseFloat(this.currentRalfObj.attr('r'));
        var _circleHeight = this.currentRalfObj.getBBox().height;
        var _x = parseFloat(_point.x - 10);


        // y = Marker location - (tip box height + nub height + radius + border size)
        var _y = _point.y - (_h + 10 + _radius + 6); // 22


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
        this.currentDot = this.currentProp = this.currentRalfObj = null;

        for(o in mdict){
            mdict[o].attr(over_style);
        }

    },

    getTipTitle: function(){
        var t;
        if (this.tip_title && this.tip_title.length && this.currentProp[this.tip_title]) {
            t = this.currentProp[this.tip_title];
            if (!isNaN(t)) t = addCommas(t);
            return t;
        }

        return "";
    },

    getTipDesc: function(){
        var t;
        if(this.tip_sentence){
            var txt = this.tip_sentence.struct;
            t = this.currentProp[this.tip_sentence.parts[1]];
            if (!isNaN(t)) t = addCommas(t);
            return txt.replace(this.tip_sentence.parts[0],t);
        }else{
            if (this.tip_title && this.tip_title.length && this.currentProp[this.tip_desc]) {
                t = this.currentProp[this.tip_desc];
                if (!isNaN(t)) t = addCommas(t);
                return t;
            }
        }

        return "";
    },

    checkParams: function(){
        // user tip styles

        if(ds_user_opts['tooltip']){

            var userTipObj = parseJSON(ds_user_opts['tooltip']);


            if(userTipObj){
                for(prop in userTipObj){


                    switch(prop){
                        case "background":
                            this.tt.css("background-color",userTipObj[prop]);
                            this.tt_nub.css("border-top-color",userTipObj[prop] );
                        break;
                        case "font-color":
                            this.tt.css("color",userTipObj[prop]);
                        break;
                        case "font-size":
                            this.tt_title.css("fontSize",userTipObj[prop]);
                        break;
                        case "font-family":
                            this.tt_title.css("fontFamily",userTipObj[prop]);
                        break;
                        case "font-weight":
                            this.tt_title.css("fontWeight",userTipObj[prop]);
                        break;
                    }


                }
            }

        }
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
          //var re= new RegExp (/{([\w:]*)?\s?(.*?)}/gi);
          var re = new RegExp(/\{?\{\s*(.*?)\s*\}\}?/g);
          var m = re.exec(params.tm);

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
        isTip = true;
        //this.tip_title = "id";
        return isTip;
    }
}