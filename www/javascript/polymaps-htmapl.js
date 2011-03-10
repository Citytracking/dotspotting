(function(po) {

 	var engine = po;

	engine.container = function() {
		return po.svg("svg");
	};

	engine.anchor = function() {
		return po.svg("a");
	};

})(org.polymaps);
