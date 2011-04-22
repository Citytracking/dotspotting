<?php

	#
	# $Id$
	#

	include("include/init.php");

	error_404();

	loadlib("import");
	loadlib("formats");

	#################################################################

	login_ensure_loggedin("{$GLOBALS['cfg']['abs_root_url']}upload");

	$crumb_key = 'split';
	$crumb_ok = crumb_check($crumb_key);

	$GLOBALS['smarty']->assign("crumb_key", $crumb_key);

	#
	# First grab the file and do some basic validation
	#

	# Ideally the front end should remove the 'upload' parameter but
	# just in case...

	if (($crumb_ok) && ($_FILES['upload']) && (! post_str('url'))){

		$ok = 1;

		if ($_FILES['upload']['error']){

			$GLOBALS['error']['upload_error'] = 1;
			$GLOBALS['error']['upload_error_msg'] = $_FILES['upload']['error'];
			$ok = 0;
		}

		if ($ok){

			$more = array();

			if ($mime_type){
				$more['assume_mime_type'] = $mime_type;
			}

			if (! import_is_valid_mimetype($_FILES['upload'], $more)){
				$GLOBALS['error']['invalid_mimetype'] = 1;
				$ok = 0;
			}

			# okay. try to pre-process the data

			else {

				$_FILES['upload']['path'] = $_FILES['upload']['tmp_name'];

				$pre_process = import_process_file($_FILES['upload'], $more);
			}
		}
	}

	# Upload by URL

	else if (($crumb_ok) && (post_str('url'))){
		# Do this later...
	}

	else {}

	$import_formats = formats_valid_import_map('key by extension');
	$GLOBALS['smarty']->assign_by_ref("import_formats", $import_formats);

	$import_formats_pretty = formats_pretty_import_names_map();
	$GLOBALS['smarty']->assign_by_ref("import_formats_pretty", $import_formats_pretty);

	$smarty->display("page_split_file.txt");
	exit();
?>
