/**
 * (Dots)Potting is a class for building embedded
 * Dotspotting maps.
 */

if (typeof Dots === "undefined") Dots = {};
Dots.Potting = function(params, selectors) {
    this.params = $.extend(Dots.Potting.defaultParams, this.parseParams(params));
    this.selectors = $.extend(this.selectors, selectors);

    this.mapContainer = $(this.selectors.map);
    this.outputContainer = $(this.selectors.output);

    this.createMap();
};

Dots.Potting.defaultParams = {
    base: "toner"
};

Dots.Potting.sourceAliases = (function() {
    var aliases = {};

    var OSM_TM = 'Map data &copy;<a href="https://www.openstreetmap.org/">OpenStreetMap</a> contributors, <a href="http://creativecommons.org/licenses/by-sa/2.0/">CCBYSA</a>; designed by <a href="http://www.stamen.com/">Stamen</a>',
        MAPQUEST_TM = 'Tiles courtesy of <a href="http://www.mapquest.com/" target="_blank">MapQuest</a> <img src="http://developer.mapquest.com/content/osm/mq_logo.png"/> Portions Courtesy NASA/JPL-Caltech and U.S. Depart. of Agriculture, Farm Service Agency',
        CLOUDMADE_TM = '&copy;{Y} <a href="http://www.cloudmade.com/">CloudMade</a>, <a href="http://www.openstreetmap.org/">OpenStreetMap</a> <a href="http://creativecommons.org/licenses/by-sa/2.0/">CCBYSA</a>',
        ACETATE_TM = 'Map data &copy;<a href="https://www.openstreetmap.org/">OpenStreetMap</a> contributors, <a href="http://creativecommons.org/licenses/by-sa/2.0/">CCBYSA</a>; designed by <a href="http://www.stamen.com/">Stamen</a> &amp; <a href="http://www.geoiq.com/">GeoIQ</a>',
        // FIXME: update MS copyright for each extent?
        MICROSOFT_TM = '&copy;{Y} Microsoft Corporation  &copy;2010 NAVTEQ';

    aliases.toner = {
        template: "http://spaceclaw.stamen.com/tiles/dotspotting/world/{Z}/{X}/{Y}.png",
        copyright: OSM_TM
    };

    aliases.pale_dawn = {
        template: "http://{S:a.,b.,c.,}tile.cloudmade.com/1a1b06b230af4efdbb989ea99e9841af/998/256/{Z}/{X}/{Y}.png",
        copyright: CLOUDMADE_TM
    };

    aliases.mapquest = {
        template: "http://otile{S:1,2,3,4}.mqcdn.com/tiles/1.0.0/osm/{Z}/{X}/{Y}.png",
        copyright: MAPQUEST_TM
    };

    aliases.acetate = {
        template: "http://acetate.geoiq.com/tiles/acetate/{Z}/{X}/{Y}.png",
        copyright: ACETATE_TM
    };

    aliases.bing = {
        template: "http://ecn.t{S:0,1,2}.tiles.virtualearth.net/tiles/r{Q}?g=689&mkt=en-us&lbl=l1&stl=h",
        copyright: MICROSOFT_TM
    };

    return aliases;
})();

Dots.Potting.prototype = {
    params: null,
    selectors: {
        map:        "#map",
        output:     "#output",
        title:      "#title",
        copyright:  "#copyright"
    },

    map: null,
    mapContainer: null,

    mapHash: null,
    alreadyCentered: false,

    dotsLayer: null,
    makeDot: function(feature) {
        throw "You must implement makeDot(feature)";
    },

    outputContainer: null,

    parseParams: function(params) {
        if (typeof params === "string") {
            return parseQueryString(params);
        } else {
            return params;
        }
    },

    getMapProvider: function(base) {
        var source = Dots.Potting.sourceAliases[base] || {
            template: base,
            copyright: this.params.copyright
        };
        var provider = source.provider || new com.modestmaps.TemplatedMapProvider(source.template, source.domains);
        provider.copyright = source.copyright;
        return provider;
    },

    createMap: function() {
        var provider = this.getMapProvider(this.params.base);
        this.map = new com.modestmaps.Map(this.mapContainer[0], provider);

        if (provider.copyright) {
            this.addCopyright(provider.copyright);
        }

        this.mapHash = new MapHash(this.map);
        if (location.hash.length > 1) {
            this.alreadyCentered = this.mapHash.read(location.hash);
        }
        this.mapHash.start();

        return this.map;
    },

    addCopyright: function(copy) {
        copy = copy.replace("{Y}", (new Date()).getFullYear());
        $(this.selectors.copyright).html(copy);
    },

    setTitle: function(title) {
        if (typeof title === "undefined") title = this.params.title;
        if (title) {
            $(this.selectors.title).text(title);
        } else {
            $(this.selectors.title).css("display", "none");
        }
    },

    output: function(message) {
        return $("<p/>").addClass("message").html(message).appendTo(this.outputContainer);
    },

    error: function(message) {
        return this.output(message).addClass("error");
    },

    getDotsURL: function() {
        var base = (this.params.baseURL || "http://dotspotting.org/"),
            url = base + "u/{user}/sheets/{sheet}/export",
            query = this.params;
        // FIXME: search disabled?
        if (this.params.search) {
            query = {
                e: params.search,
                u: params.user,
                s: params.sheet,
                format: "json",
                inline: 1,
                force: 1 // use response caching
            };
            url = base + "search/export";
        } else {
            if (!query.user) {
                this.error("You must provider a <strong>user</strong> parameter (<tt>?user={id}</tt>).");
                return false;
            }
            if (!query.sheet) {
                this.error("You must provider a <strong>sheet</strong> parameter (<tt>?sheet={id}</tt>).");
                return false;
            }

            url = url
                .replace("{user}", query.user)
                .replace("{sheet}", query.sheet);
            query = {
                format: "json",
                inline: 1
            };
        }
        return url + makeQueryString(query);
    },

    load: function(url, success, error) {
        if (!url) url = this.getDotsURL();
        if (url) {
            var that = this;
            return $.ajax(url, {
                dataType: "jsonp",
                success: function(collection) {
                    that.onDotsLoaded(collection);
                    if (success) success.call(that, collection);
                },
                error: function(e) {
                    that.onDotsError(e);
                    if (error) error.call(that, e);
                }
            });
        }
    },

    onDotsLoaded: function(collection) {
        if (this.dotsLayer) {
            this.addDots(collection.features, !this.alreadyCentered);
        }
    },


    addDots: function(features, updateExtent) {
        if (!this.dotsLayer) {
            this.error("We have no dots layer!");
            return false;
        }
        var len = features.length,
            locations = updateExtent ? [] : null,
            added = [];
        for (var i = 0; i < len; i++) {
            var feature = features[i],
                dot = this.makeDot(feature);
            if (dot) {
                var marker = this.dotsLayer.addMarker(dot, feature);
                if (updateExtent && marker.location && marker.location.lat && marker.location.lon) {
                    locations.push(marker.location);
                }
                added.push(marker);
            }
        }
        if (updateExtent && locations.length > 0) {
            this.map.setExtent(locations);
        }
        return added;
    },

    onDotsError: function(error) {
        this.error("Failed to load dots: <tt>" + error + "</tt>");
    }
<<<<<<< HEAD
};
=======
};
>>>>>>> 3db6d1f
