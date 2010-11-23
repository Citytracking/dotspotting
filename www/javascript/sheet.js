function sheet_export_url(url){

    // this assumes DataTables is being used:
    // http://www.datatables.net/

    var table = $("#user_dots").dataTable();
    var settings = table.fnSettings();

    var columns = settings.aoColumns;
    var sort = settings.aaSorting[0];

    if ((sort) && (sort[0] != 0)){

	var col = columns[sort[0]].sTitle;
	col = col.toLowerCase();

	url += '?sort=' + encodeURIComponent(col) + '&sortorder=' + encodeURIComponent(sort[1]);
    }

    return url;
}