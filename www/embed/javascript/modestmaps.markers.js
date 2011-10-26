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

        MM.Layer.call(this, map, provider || map.provider, parent);
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

    /**
        Uses Raphael to draw dots
    **/
    MM.DotMarkerLayer = function(map, provider, parent) {
        MM.MarkerLayer.call(this, map, provider, parent);
       
        this.canvas = Raphael(this.parent, this.map.dimensions.x * 3, this.map.dimensions.y * 3 );
        
        this.dotAttrs = {"fill": "#f0f"};
        
        var that = this;
        this.map.addCallback("panned", that.getRedraw());//defer(this.getRedraw(), 200)
    };
    
    MM.DotMarkerLayer.prototype = {
        canvas: null,
        dotRadius: 6,
        dotAttrs: null,
        frontMarkers:{},
        backMarkers:{},
        
        clear: function() {
           this.canvas.clear();
        },
        
        buildMarker: function(attrs) {
            var radius = (attrs.radius) ? attrs.radius : this.dotRadius;
            // position is set in repositionMarker function
            var dot = this.canvas.circle(0, 0, radius);
            dot.attr(this.dotAttrs);
            if (attrs.style) {
                dot.attr(attrs.style);
            }
            if (attrs.id) {
                dot.node.id = attrs.id;
            }
            if(attrs.dotClass){
                dot.node.setAttribute('class', attrs.dotClass);
            }


            return dot;
        },
        
        addMarker: function(attrs, location) {
            // if only location is provided, ignore attrs
            if (arguments.length == 1) location = arguments[0];
            // create a dot with the provided attrs
            var dot = this.buildMarker(attrs);
            dot.location = this.getLocation(location);
            // stash the projected coordinate for later use
            dot.coord = this.map.provider.locationCoordinate(dot.location);
            dot.attrs = attrs;
            // set its initial position
            this.repositionMarker(dot);
            this.markers.push(dot);
            
            if(dot.attrs['_kirbyPos'] == "back"){
                this.backMarkers[dot.attrs.id] = dot;
            }else{
                this.frontMarkers[dot.attrs.id] = dot;
            }
            
            return dot;
        },
        
        removeMarker: function(marker) {
            var index = this.markers.indexOf(marker);
            if (index > -1) {
                this.markers.splice(index, 1);
            }
            if(typeof marker.remove == "function"){
                marker.remove();
            }
            return marker;
        },
        
        resetPosition: function() {
            this.position = {x: -this.map.dimensions.x, y: -this.map.dimensions.y};
            this.parent.style.left = this.position.x + "px";
            this.parent.style.top = this.position.y + "px";
            this.parent.style.zIndex = this.map.getZoom() + 1;
        },
        
        repositionMarker: function(marker) {
            if (marker.coord) {
                var pos = this.map.coordinatePoint(marker.coord);
                // TODO: check to see if this works in IE
                
                marker.attr("cx", pos.x - this.position.x);
                marker.attr("cy", pos.y - this.position.y);
            }
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
            this.canvas.setSize(map.dimensions.x * 3, map.dimensions.y * 3);            
            this.onRedraw(map, offset);
        },

        getRedraw: function() {
            if (!this._onRedraw) {
                var that = this;
                this._onRedraw = function(map, offset) {
                    that.onRedraw(map, offset);
                };
            }
            return this._onRedraw;
        },
        _onRedraw: null,
        onRedraw: function(map, offset) {
            this.resetPosition();
            var len = this.markers.length;
            for (var i = 0; i < len; i++) {
                this.repositionMarker(this.markers[i]);
            }
        },
        
        cluster: function(){
            // Quantize a number by a divisor
            function quantize(n, q) {
                return Math.round(n / q) * q;
            }

            /**
             * Quantize the location of the marker to determine its "corner".
             * Note: we should probably avoid offsetting markers with
             * more explicit locations.
             */
            function getCorner(marker,loc) {
                var prec = .001,
                    x = Number(loc.lon),
                    y = Number(loc.lat);

                try {
                    return quantize(x, prec)+ "," + quantize(y, prec);
                } catch (e) {
                    return "bad";
                }
            }
            
            
            var corners = {};
            
            for (mark in this.frontMarkers) {
                var marker = this.frontMarkers[mark],
                    loc = marker.location,
                    corner = getCorner(marker,loc);
                
                if(loc.lat != 0 && loc.lon !=0){
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
                        this.repositionMarker(mark);
                        
                        if(this.backMarkers[mark.attrs.id]){
                            this.backMarkers[mark.attrs.id].coord.row = mark.coord.row;
                            this.backMarkers[mark.attrs.id].coord.column = mark.coord.column;
                            this.repositionMarker(this.backMarkers[mark.attrs.id]);
                        }
                        
                        a += step;
                    }
                }
            }
        }
        
    };
    
    MM.extend(MM.DotMarkerLayer, MM.MarkerLayer);
    
    /* NOT WORKING */
    MM.GeoJsonLayer = function(map, provider, parent) {
        MM.DotMarkerLayer.call(this, map, provider, parent);
    };
    
    MM.GeoJsonLayer.prototype = {
        drawPoint: function(attrs){
   
            var radius = (attrs.radius) ? attrs.radius : this.dotRadius;
            // position is set in repositionMarker function
            var feature = this.canvas.circle(0, 0, radius);
            
            thing.attr(this.dotAttrs);
            if (attrs.style) {
                feature.attr(attrs.style);
            }
            if (attrs.id) {
                feature.node.id = attrs.id;
            }
            if(attrs.dotClass){
                feature.node.setAttribute('class', attrs.dotClass);
            }
            
            return feature;
            
        },
        drawPolygon: function(attrs){
            
        },
        
        buildMarker: function(attrs) {
            
            var feature;
            if(attrs.type == "Point"){
                feature = this.drawPoint(attrs);

            }else if((attrs.type == 'Polygon') || (attrs.type == 'MultiPolygon')){
                feature = this.drawPolygon(attrs);
            }


            return feature;
            
        },
         addMarker: function(attrs, location) {
            // if only location is provided, ignore attrs
            if (arguments.length == 1) location = arguments[0];
            // create a dot with the provided attrs
            var feature = this.buildMarker(attrs);
            feature.location = this.getLocation(location);
            // stash the projected coordinate for later use
            feature.coord = this.map.provider.locationCoordinate(dot.location);
            feature.attrs = attrs;
            // set its initial position
            this.repositionMarker(dot);
            this.markers.push(dot);

            return dot;
        },
        repositionMarker: function(marker) {
            if (marker.coord && marker.attrs.type == "Point") {
                var pos = this.map.coordinatePoint(marker.coord);
                // TODO: check to see if this works in IE
                marker.attr("cx", pos.x - this.position.x);
                marker.attr("cy", pos.y - this.position.y);
            }
        },
    };
    
    MM.extend(MM.GeoJsonLayer, MM.DotMarkerLayer);

})(com.modestmaps);