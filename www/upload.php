<?php

	#
	# $Id$
	#

	include("include/init.php");

	loadlib("import");

	#################################################################

	login_ensure_loggedin("{$GLOBALS['cfg']['abs_root_url']}upload");

	if (! $GLOBALS['cfg']['enable_feature_import']){

		$GLOBALS['error']['uploads_disabled'] = 1;
		$smarty->display("page_upload.txt");
		exit();
	}

	#################################################################

	$crumb_key = 'upload';
	$smarty->assign("crumb_key", $crumb_key);

	if (($_FILES['upload']) && (crumb_check($crumb_key))){

		if (! $_FILES['upload']['error']){

			$label = filter_strict(post_str('label'));
			$dots_index_on = filter_strict(post_str('dots_index_on'));
			$private = (post_str('private')) ? 1 : 0;
		
			$more = array(
				'return_dots' => 0,
				'label' => $label,
				'mime_type' => $_FILES['upload']['type'],
				'mark_all_private' => $private,
			);

			if ($mime_type = post_str('mime_type')){

				$more['assume_mime_type'] = $mime_type;
			}

			$_FILES['upload']['path'] = $_FILES['upload']['tmp_name'];

			$rsp = import_import_file($GLOBALS['cfg']['user'], $_FILES['upload'], $more);

			$smarty->assign("upload_complete", 1);
			$smarty->assign_by_ref("rsp", $rsp);
		}

		else {
			# ...
		}
	}

	$import_formats = formats_valid_import_map('key by extension');
	$GLOBALS['smarty']->assign_by_ref("import_formats", $import_formats);

	$GLOBALS['smarty']->display("page_upload.txt");
	exit();
?>