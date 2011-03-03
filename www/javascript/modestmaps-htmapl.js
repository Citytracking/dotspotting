(function(mm) {

 	var engine = mm.htmapl = {};

	var NULL_PROVIDER = new mm.MapProvider(function(c) { return null; });

	var id = 0;
 	engine.container = function() {
		// FIXME: this is kind of hacky
		var container = document.createElement("div");
		container.setAttribute("class", "modestmap");
		container.setAttribute("id", "mmap" + (++id));
		return container;
	};

	engine.map = function() {
		/**
		 * FIXME: we might need to defer initialization here, because it's
		 * non-trivial to add/remove event handlers after the Map instance has been
		 * created.
		 */
		// Our initial contianer is a detached <div>
		var container = document.createElement("div"),
				_map = new mm.Map(container, NULL_PROVIDER, null, []),
				map = {},
				// style attributes to pass along to new containers
				restyle = ["position", "padding", "overflow"];

		// just so we can make sure this doesn't stick around
		container.setAttribute("class", "dummy");

		// expose all of the normal stuff
		map.locationPoint = function(loc) { return _map.locationPoint(loc); };
		map.pointLocation = function(p) { return _map.pointLocation(p); };
		map.coordinatePoint = function(c) { return _map.coordinatePoint(c); };
		map.pointCoordinate = function(p) { return _map.pointCoordinate(p); };

		// container getter/setter; NOTE: this is tricky!
		map.container = function(c) {
			if (arguments.length) {
				// move all of the existing container's children to the new parent
				while (container.firstChild) {
					c.appendChild(container.firstChild);
				}
				// XXX: apply CSS from the old container
				for (var i = 0; i < restyle.length; i++) {
					var s = restyle[i];
					c.style[s] = container.style[s];
				}

				// then just hack the parent
				_map.parent = container = c;
				// XXX: this is a bit hacky, because we're using a nested container of
				// a relatively positioned parent. So we set its CSS width and height
				// to 100%, then set the map's size to its calculated dimensions.
				container.style.width = container.style.height = "100%";
				var $con = $(container),
						size = {x: $con.innerWidth(), y: $con.innerHeight()};
				map.size(size);

				return map;
			}
			return container;
		};

		// size getter/setter
		map.size = function(dims) {
			if (arguments.length) {
				_map.dimensions = dims;
				_map.draw();
				return map;
			} else {
				return _map.dimensions;
			}
		};

		map.center = function(x) {
			if (arguments.length) {
				_map.setCenter(x);
				return map;
			} else {
				return _map.getCenter();
			}
		};

		map.zoom = function(x) {
			if (arguments.length) {
				_map.setZoom(x);
				return map;
			} else {
				return _map.getZoom();
			}
		};

		map.zoomRange = function(range) {
			if (arguments.length) {
				_map.setMinZoom(range[0]);
				_map.setMaxZoom(range[1]);
				_map.draw();
				return map;
			} else {
				return [_map.minZoom, map.maxZoom];
			}
		};

		map.extent = function(e) {
			if (arguments.length) {
				_map.setExtent(e);
				return map;
			} else {
				return _map.getExtent();
			}
		};

		// add a layer
		map.add = function(layer) {
			layer.map(_map);
			return map;
		};

		// remove a layer
		map.remove = function(layer) {
			layer.map(null);
			return map;
		};

		var eMap = {
			move: "drawn"
		};
		// event dispatch wrappers
		map.on = function(e, handler) {
			_map.addCallback(eMap[e] || e, handler);
			return map;
		};
		map.off = function(e, handler) {
			_map.removeCallback(eMap[e] || e, handler);
			return map;
		};

		return map;
	};

	/**
	 * The image() generator wraps com.modestmaps.MapProvider with Polymaps-like
	 * functionality.
	 *
	 * FIXME: This should actually create slaved Map instances, to deal with
	 * ModestMaps' inability to manage multi-layer image providers.
	 *
	 * TODO: This also needs a po.dispatch()-like interface with "load" and
	 * "unload" event handlers.
	 */
	engine.image = function() {
		var template = "",
				provider = NULL_PROVIDER,
				image = {};

		image.url = function(x) {
			if (arguments.length) {
				if (typeof template == "function") {
					template = x;
				} else {
					var tmpl = x;
					template = function(c) {
						return $.fn.htmapl.templatize(tmpl, {Z: c.zoom, X: c.column, Y: c.row});
					};
				}
				provider = new mm.MapProvider(template);
				return image;
			} else {
				return template;
			}
		};

		image.map = function(m) {
			if (arguments.length) {
				m.setProvider(provider);
			} else {
				return null;
			}
		};

		return image;
	};

	/**
	 * This is a layer "generator" for ModestMaps event handlers.
	 *
	 * I know, this is lame.
	 */
	function handler(cls) {
		return function() {
			var wrapper = {},
					handler = new cls(),
					map = null;
			
			wrapper.map = function(x) {
				if (arguments.length) {
					// remove old event listeners if they have any
					if (map && typeof handler.teardown == "function") handler.teardown(map);
					map = x;
					// add new event listeners
					if (map && typeof handler.init == "function") handler.init(map);
					return wrapper;
				} else {
					return map;
				}
			};

			return wrapper;
		};
	}

	engine.drag = handler(mm.MouseHandler);
	// TODO: integrate some of Tom's other handlers, or write them here?
	// engine.arrow = handler(mm.KeyboardHandler);
	// engine.gesture = handler(mm.GestureHandler);

})(com.modestmaps);
