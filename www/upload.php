<?php

	#
	# $Id$
	#

	include("include/init.php");

	loadlib("import");

	#################################################################

	login_ensure_loggedin("/upload");

	if (! $GLOBALS['cfg']['enable_feature_uploads']){

		$GLOBALS['error']['uploads_disabled'] = 1;
		$smarty->display("page_upload.txt");
		exit();
	}

	#################################################################

	$crumb_key = 'upload';
	$smarty->assign("crumb_key", $crumb_key);

	if (($_FILES['upload']) && (crumb_check($crumb_key))){

		if (! import_is_valid_mimetype($_FILES['upload'])){

			$GLOBALS['error']['invalid_mimetype'] = 1;
			$smarty->display("page_upload.txt");
			exit();
		}

		# parse the file

		$rsp = import_process_file($_FILES['upload']);

		if (! $rsp['ok']){

			$GLOBALS['error']['parse_fail'] = 1;
			$smarty->display("page_upload.txt");
			exit();
		}

		# store the data

		$label = filter_strict(post_str('label'));
		$private = (post_str('private')) ? 1 : 0;
		
		$more = array(
			'return_dots' => 0,
			'label' => $label,
			'mime_type' => $_FILES['upload']['type'],
			'mark_all_private' => $private,
		);

		$rsp = import_process_data($GLOBALS['cfg']['user'], $rsp['data'], $more);

		if (! $rsp['ok']){

			$GLOBALS['error']['process_fail'] = 1;

			$smarty->assign_by_ref("upload_errors", $rsp['errors']);
			$smarty->display("page_upload.txt");
			exit();
		}

		# Happy happy!

		$smarty->assign("upload_complete", 1);
		$smarty->assign_by_ref("rsp", $rsp);
	}

	$smarty->display("page_upload.txt");
	exit();
?>