function tilestache(template) {

    function pad(s, n, c) {
	var m = n - s.length;
	return (m < 1) ? s : new Array(m + 1).join(c) + s;
    }

    function format(i) {
	var s = pad(String(i), 6, "0");
	return s.substr(0, 3) + "/" + s.substr(3);
    }

    return function(c) {
	var max = 1 << c.zoom, column = c.column % max; // TODO assumes 256x256
	if (column < 0) column += max;
	return template.replace(/{(.)}/g, function(s, v) {
		switch (v) {
		case "Z": return c.zoom;
		case "X": return format(column);
		case "Y": return format(c.row);
		}
		return v;
	    });
    };
}