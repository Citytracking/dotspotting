// namespacing!
if (!com) {
    var com = {};
}
if (!com.modestmaps) {
    com.modestmaps = {};
}

(function(MM) {

    MM.GeoJSONProvider = function(template_provider, buildMarker) {
        MM.TilePaintingProvider.call(this, template_provider);
        this.preventer = new ClickPreventer(250);
        if (buildMarker) this.buildMarker = buildMarker;
    };

    MM.GeoJSONProvider.prototype = {
        preventer: null,

        // for remembering requests; FIXME: use the RequestManager?
        cache: {},
        useCache: false,

        tiled: true,

        // these aren't included in the 
        tileWidth: 256,
        tileHeight: 256,

        getFeatureLocation: function(feature) {
            var geom = feature.geometry;
            switch (geom.type) {
                case "Point":
                    return {lon: Number(geom.coordinates[0]), lat: Number(geom.coordinates[1])};
                case "Polygon":
                    // TODO: get the extent?
                    throw "Not yet implemented";
                default:
                    throw "Unsupported geometry type: " + geom.type;
            }
            return null;
        },

        positionPercentages: true,
        getLocalPosition: function(loc, coord) {
            var local = this.template_provider.locationCoordinate(loc).zoomTo(coord.zoom),
                pos = {};
            if (this.positionPercentages) {
                pos.x = 100 * (local.column - coord.column);
                pos.y = 100 * (local.row - coord.row);
                pos.left = (pos.x >> 0) + "%";
                pos.top = (pos.y >> 0) + "%";
            } else {
                pos.x = this.tileWidth * (local.column - coord.column);
                pos.y = this.tileHeight * (local.row - coord.row);
                pos.left = (pos.x >> 0) + "px";
                pos.top = (pos.y >> 0) + "px";
            }
            return pos;
        },

        // get a DOM node element for a given tile coordinate
        getTile: function(coord) {
            var key = coord.toKey();
            if (this.useCache && this.cache.hasOwnProperty(key)) {
                return this.cache[key].element;
            } else {
                // console.log("[gjp] create new item:", key, item);
                var url = this.template_provider.getTileUrl(coord),
                    tile = document.createElement("div");
                tile.coord = coord;
                tile.setAttribute("class", "tile");
                if (!url) {
                    tile.setAttribute("class", tile.getAttribute("class") + " empty");
                    return tile;
                }

                var item = {
                    coord: coord,
                    key: key,
                    url: url
                };
                // console.log("[gjp] get tile:", coord.toString(), tile, item.url);
                item.element = tile;

                var that = this;
                item.request = this.load(item.url, function(collection) {
                    if (collection && typeof collection.features !== "undefined") {
                        var features = collection.features,
                            len = features.length;
                        for (var i = 0; i < len; i++) {
                            var feature = features[i],
                                loc = that.getFeatureLocation(feature);
                            if (loc) {
                                feature.location = loc;
                                feature.position = that.getLocalPosition(loc, coord);
                            }
                        }
                        item.collection = collection;
                        that.dispatchCallback("load", {collection: collection, tile: tile, coord: coord});
                    }
                    delete item.request;
                });

                this.cache[key] = item;
                return tile;
            }
        },

        // TODO: remove the jQuery dependency here
        load: function(url, success, error) {
            throw "Not implemented";
        },

        // release the tile
        // TODO: dispatch event notifying removal of tile features
        releaseTile: function(coord) {
            var key = coord.toKey(),
                item = this.cache[key];
            if (item) {
                // console.log("[gjp] release cached item:", key, item);
                if (item.request) {
                    // console.log(item.request);
                    item.request.abort();
                }
                if (item.collection) {
                    this.dispatchCallback("unload", {collection: item.collection, tile: item.element, coord: coord});
                }
                delete this.cache[key];
                return true;
            } else {
                return false;
            }
        }
    };

    MM.extend(MM.GeoJSONProvider, MM.TilePaintingProvider);

    MM.MarkerLayer = function(map, provider, parent) {
        MM.Layer.call(this, map, provider, parent);

        this.map.addCallback("panned", this.getPanned());
        this.map.addCallback("zoomed", this.getZoomed());
        this.map.addCallback("extentset", this.getZoomed());
        this.map.addCallback("resized", this.getZoomed());

        this.markers = [];
        this.resetPosition();
    };

    MM.MarkerLayer.prototype = {
        markers: null,

        /*
        setProvider: function(provider) {
            if (this.template_provider instanceof MM.GeoJSONProvider) {
                this.template_provider.removeCallback("load", this.getLoadComplete());
                this.template_provider.removeCallback("unload", this.getUnload());
            }
            MM.Layer.prototype.setProvider.call(this, provider);
            if (this.template_provider instanceof MM.GeoJSONProvider) {
                this.template_provider.addCallback("load", this.getLoadComplete());
                this.template_provider.addCallback("unload", this.getUnload());
            }
        },
        */

        clear: function() {
            while (this.markers.length > 0) {
                this.removeMarker(this.markers[0]);
            }
        },

        getLocation: function(loc) {
            switch (typeof loc) {
                case "string": {
                    return MM.Location.fromString(loc);
                }
                case "object": {
                    // GeoJSON
                    if (typeof loc.geometry === "object") {
                        return this.getFeatureLocation(loc);
                    }
                }
            }
            return loc;
        },
        getFeatureLocation: MM.GeoJSONProvider.prototype.getFeatureLocation,

        addMarker: function(marker, location) {
            if (!marker || !location) {
                return null;
            }
            marker.style.position = "absolute";
            marker.location = this.getLocation(location);
            marker.coord = this.map.provider.locationCoordinate(marker.location);
            this.repositionMarker(marker);
            this.parent.appendChild(marker);
            this.markers.push(marker);
            return marker;
        },

        removeMarker: function(marker) {
            var index = this.markers.indexOf(marker);
            if (index > -1) {
                this.markers.splice(index, 1);
            }
            if (marker.parentNode == this.parent) {
                this.parent.removeChild(marker);
            }
            return marker;
        },

        position: null,
        resetPosition: function() {
            this.position = {x: 0, y: 0};
            this.parent.style.left = this.parent.style.top = "0px";
            this.parent.style.zIndex = this.map.getZoom() + 1;
        },

        getZoomed: function() {
            if (!this._onZoomed) {
                var that = this;
                this._onZoomed = function(map, offset) {
                    that.onZoomed(map, offset);
                };
            }
            return this._onZoomed;
        },
        _onZoomed: null,
        onZoomed: function(map, offset) {
            this.resetPosition();
            var len = this.markers.length;
            for (var i = 0; i < len; i++) {
                this.repositionMarker(this.markers[i]);
            }
        },

        repositionMarker: function(marker) {
            if (marker.coord) {
                var pos = this.map.coordinatePoint(marker.coord);
                marker.style.left = (pos.x >> 0) + "px";
                marker.style.top = (pos.y >> 0) + "px";
            }
        },

        getPanned: function() {
            if (!this._onPanned) {
                var that = this;
                this._onPanned = function(map, offset) {
                    that.onPanned(map, offset);
                };
            }
            return this._onPanned;
        },
        _onPanned: null,
        onPanned: function(map, offset) {
            this.position.x += offset[0];
            this.position.y += offset[1];
            this.parent.style.left = (this.position.x >> 0) + "px";
            this.parent.style.top = (this.position.y >> 0) + "px";
        }
    };

    MM.extend(MM.MarkerLayer, MM.Layer);

})(com.modestmaps);
