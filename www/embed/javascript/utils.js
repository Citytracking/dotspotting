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
            marker._coord = marker.coord.clone();
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

