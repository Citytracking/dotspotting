<?php

	#
	# $Id$
	#

	include("include/init.php");

	loadlib("uploads");

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

		if (! uploads_is_valid_mimetype($_FILES['upload'])){

			$GLOBALS['error']['invalid_mimetype'] = 1;
			$smarty->display("page_upload.txt");
			exit();
		}

		# parse the file

		$rsp = uploads_process_file($_FILES['upload']);

		if (! $rsp['ok']){

			$GLOBALS['error']['parse_fail'] = 1;
			$smarty->display("page_upload.txt");
			exit();
		}

		# store the data

		$label = post_str('label');
		$private = (post_str('private')) ? 1 : 0;
		
		$more = array(
			'return_dots' => 0,
			'label' => $label,
			'mime_type' => $_FILES['upload']['type'],
			'mark_all_private' => $private,
		);

		$rsp = uploads_process_data($GLOBALS['cfg']['user'], $rsp['data'], $more);

		if (! $rsp['ok']){

			$GLOBALS['error']['process_fail'] = 1;
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