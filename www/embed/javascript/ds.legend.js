(function(exports){
    "use static";

    var DS = exports.DS || (exports.DS = {});
    var extras = DS.extras || (DS.extras = {});

    function makeSVG(tag, attrs) {
        attrs = attrs || {};

        var elm = document.createElementNS("http://www.w3.org/2000/svg", tag);
        for(var attr in attrs) {
            elm.setAttributeNS(null, attr, attrs[attr]);
        }
        return elm;
    }

    extras.CircleLegend = function(appendTo, position, scale, titleString) {
        var __ = {};

        if (!document.querySelector) return;

        var size = scale.range();
        var values = scale.domain();
        var padding = 10;
        var width = (scale(values[1]) * 2) + padding;

        var root = document.createElement("div");
        root.className = "circle-legend position-" + position;

        var svg = makeSVG('svg', {width:width, height: (size[1] * 2) + padding});
        var wrapper = makeSVG('g', {transform: "translate(" + padding/2 + "," + (padding/2 + size[1]*2) + ")"});

        var radius;
        var fmt = d3.format(".1s");
        var sizes = [15, size[1] * .60, size[1] * .95];
        while (sizes.length) {
            var s = sizes.pop();
            var val = scale.invert(s);//step * range;
            radius = scale(val);

            var circle = makeSVG("circle", {id:"circle-" + s, cx: "50", cy: -radius, r: radius});
            var text = makeSVG("text", {x: size[1], y: -2 * radius, dy: "1.3em", 'text-anchor': 'middle'});
            text.textContent = fmt(val);
            wrapper.appendChild(circle);
            wrapper.appendChild(text);
        }

        svg.appendChild(wrapper);
        root.appendChild(svg);

        var title = document.createElement('p');
        title.innerText = titleString;
        root.appendChild(title);

        document.querySelector(appendTo).appendChild(root);


        return __;
    };


})(this);