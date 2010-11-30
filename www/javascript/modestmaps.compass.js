// This is Carden's original MapControls thingy tweaked just enough to look
// like the Polymaps compass thingy. Something about this code does not work
// with current versions of Raphael. Specifically:
// "pathArray[0] is undefined  [Break On This Error] if (pathArray[0][0] == "M") { raphael.js ln 648"
// So we're using an older version (0.7.3) at least until we decide how/what
// we're using for a JS mapping layer rather then going down the rabbit hole
// of debugging third-party JS libraries.
// (20101130/straup)

// namespacing!

if (!com){
    var com = {};

    if (!com.modestmaps){
        com.modestmaps = {};
    }
}

com.modestmaps.Compass = function(map){

    // get your div on
    
    this.div = document.createElement('div');
    this.div.style.position = 'absolute';
    this.div.style.left = '0px';
    this.div.style.top = '0px';
    map.parent.appendChild(this.div);

    this.canvas = Raphael(this.div, 200, 100);

    // zoom in (background and "+" symbol)

    var zin = this.canvas.path({fill: "#ccc", stroke: "white", 'stroke-width': 3}, "M-12,0V-12A12,12 0 1,1 12,-12V0Z").translate(25, 36);
    var zina = this.canvas.path({stroke: "white", 'stroke-width': 2}, "M -5 0 L 5 0 M 0 -5 L 0 5").translate(25, 25);

    // zoom out (background and "-" symbol)

    var zout = this.canvas.path({fill: "#ccc", stroke: "white", 'stroke-width': 3}, "M-12,0V-12A12,12 0 1,1 12,-12V0Z").translate(25, 65).rotate(180);
    var zouta = this.canvas.path({stroke: "white", 'stroke-width': 2}, "M -5 0 L 5 0").translate(25, 52);

    zina.node.onclick = zin.node.onclick = function() { map.zoomIn() };
    zouta.node.onclick = zout.node.onclick = function() { map.zoomOut() };
};

com.modestmaps.Compass.prototype = {
    div: null,
    canvas: null
};