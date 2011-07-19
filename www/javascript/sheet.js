function sheet_ready_function(){
    var _theme = "default";
    // set embed / export toggler
    // embed props are taken care of in inc_dots_js.txt
    $("#embedexportToggler").toggle(
        function(e){
            $(this).removeClass('taller').addClass('shorter');
            e.preventDefault(); 
            set_embed_code(_theme);
            $("#embed_map_box").show();
            
        },
        function(e){
            e.preventDefault(); 
            $("#embed_map_box").hide();
            $(this).removeClass('shorter').addClass('taller');
            clear_embed_code();
        }
    );
    
    $("#embed_ta").click(function(e){
        e.preventDefault();
       $(this).focus();
       $(this).select();
    });
    $("#embed_perma").click(function(e){
        e.preventDefault();
       $(this).focus();
       $(this).select();
    });
    
    var theme_link = $("#theme_more_config");
    $("#ds_theme_options").change(function(e){
        if(_dotspotting.embed_props && _dotspotting.embed_props.uid && _dotspotting.embed_props.sid){
            var _val = $(this).val();
            var _href = _dotspotting.abs_root_url+"embed/"+_val+"?oid="+_dotspotting.embed_props.uid+"&sid="+_dotspotting.embed_props.sid;
            theme_link.attr('href',_href);
            set_embed_code(_val);
        }
    });
    
    
}

function clear_embed_code(){
    $("#embed_ta").val("");
}

function set_embed_code(_theme){
    var src = "sorry could not generate an embed code...";
    var pre = '<iframe type="text/html" width="400" height="400" src="';
    var post = '" frameborder="0"></iframe>';
    var sheet_link,sheet_title,link_back;
    //
    if(_dotspotting.embed_props && _dotspotting.embed_props.uid && _dotspotting.embed_props.sid){
        link_back = "";
        // minimum
        src = _dotspotting.abs_root_url+"embed/"+_theme+"/map?user="+_dotspotting.embed_props.uid+"&amp;sheet="+_dotspotting.embed_props.sid;
        sheet_link = _dotspotting.abs_root_url + "u/"+_dotspotting.embed_props.uid+"/sheets/"+_dotspotting.embed_props.sid;
        // label
        if(_dotspotting.embed_props.label){
            src += "&amp;title="+_dotspotting.embed_props.label;
            sheet_title = _dotspotting.embed_props.label;
        }else{
            sheet_title = "Sheet #" + _dotspotting.embed_props.sid;
        }
        // coords
        if(_dotspotting.embed_props.c){
    	    src += "#"+_dotspotting.embed_props.c;
    	}
    	link_back = "<p><a href='"+sheet_link+"'>"+sheet_title+"</a> on <a href='"+_dotspotting.abs_root_url+"'>Dotspotting</a>"+"</p>"; 
        // oh good
        $("#embed_ta").val(pre+src+post+link_back);
    }else{
        // oh bad
        $("#embed_ta").val(src);
    }
    
    if(location.href)$("#embed_perma").html(location.href);	
}

function prep_sheet_export(selObj){

	if(selObj.selectedIndex){ // index 0 = header, so do nothing on it
		sheet_export_visible(selObj.options[selObj.selectedIndex].title,selObj.options[selObj.selectedIndex].value);
	}else{
		return false;
	}
    
}
function sheet_export_visible(fmt, export_all){

    // Export only those visible dots in a sheet by checking to see
    // if the sheet has been filtered. If it hasn't just let the default
    // sheet_export code handle things.

    if ((_dotspotting.datatables_query == undefined) || (_dotspotting.datatables_query == '')){
	location.href = export_all;
	return;
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