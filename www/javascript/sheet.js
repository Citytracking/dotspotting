function sheet_export_url(sheet_id){

    // this assumes DataTables is being used:
    // http://www.datatables.net/

    var table = $("#user_dots").dataTable();
    var settings = table.fnSettings();

    var columns = settings.aoColumns;
    var sort = settings.aaSorting[0];

    var url = _abs_root_url + 'search/?s=' + encodeURIComponent(sheet_id) + '&export=1';

    if ((sort) && (sort[0] != 0)){

	var col = columns[sort[0]].sTitle;
	col = col.toLowerCase();

	ctx = 'search';
	url += '&_s=' + encodeURIComponent(col) + '&_o=' + encodeURIComponent(sort[1]);
    }

    // url += '&debug=1&inline=1';
    return url;
}