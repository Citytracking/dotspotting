/* ======================================================================
    querystring.js
   ====================================================================== */

/* Client-side access to querystring name=value pairs
	Version 1.3
	28 May 2008
	
	License (Simplified BSD):
	http://adamv.com/dev/javascript/qslicense.txt
*/
function Querystring(qs) { // optionally pass a querystring to parse
	this.params = {};
	
	if (qs == null) qs = location.search.substring(1, location.search.length);
	if (qs.length == 0) return;

// Turn <plus> back to <space>
// See: http://www.w3.org/TR/REC-html40/interact/forms.html#h-17.13.4.1
	qs = qs.replace(/\+/g, ' ');
	var args = qs.split('&'); // parse out name/value pairs separated via &
	
// split out each name=value pair
	for (var i = 0; i < args.length; i++) {
		var pair = args[i].split('=');
		var name = decodeURIComponent(pair[0]);
		
		var value = (pair.length==2)
			? decodeURIComponent(pair[1])
			: name;
		
		this.params[name] = value;
	}
}

Querystring.prototype.get = function(key, default_) {
	var value = this.params[key];
	return (value != null) ? value : default_;
}

Querystring.prototype.contains = function(key) {
	var value = this.params[key];
	return (value != null);
}
/* ======================================================================
    uri.src.js
   ====================================================================== */

/*

info.aaronland.URI library v1.0
Copyright (c) 2009 Aaron Straup Cope

This is free software. You may redistribute it and/or modify it under
the same terms as Perl Artistic License.

http://en.wikipedia.org/wiki/Artistic_License

*/

if (! info){
    var info = {};
}

if (! info.aaronland){
    info.aaronland = {};
}

if (! info.aaronland.URI){
    info.aaronland.URI = {};
}

info.aaronland.URI = function(){
    this.querystring;
    this.query;

    this.init();
};

info.aaronland.URI.prototype = window.location;

info.aaronland.URI.prototype.init = function(){

    if (window.location.hash != ''){
        this.querystring = window.location.hash.substring(1);
    }
    
    else {
        this.querystring = window.location.search.substring(1);
    }

    this.query = new Querystring(this.querystring);
};

// -*-java-*-
