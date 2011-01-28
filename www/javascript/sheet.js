
function sheet_export_visible(fmt){
    
    // Export only those visible dots in a sheet by checking to see
    // if the sheet has been filtered. If it hasn't just let the default
    // sheet_export code handle things.

    if (_dotspotting.datatables_query == ''){
	return true;
    }

    var dots = new Array();

    var visible = collect_dots();
    var features = visible.features;
    var count = features.length;

    for (var i = 0; i < count; i++){
	var id = features[i].properties.id;

	if (id){
	    dots.push(htmlspecialchars(id));
	}
    }

    if (dots.length == 0){

	alert("There's nothing to export!");
	return false;
    }

    var url = _dotspotting.abs_root_url + 'search/export/?ids=' + dots.join(',') + '&format=' + htmlspecialchars(fmt);
    location.href = url;
}