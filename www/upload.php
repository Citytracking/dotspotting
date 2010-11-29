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
			$private = (post_str('private')) ? 1 : 0;
		
			$more = array(
				'return_dots' => 0,
				'label' => $label,
				'mime_type' => $_FILES['upload']['type'],
				'mark_all_private' => $private,
			);

			$_FILES['upload']['path'] = $_FILES['upload']['tmp_name'];

			$rsp = import_import_file($GLOBALS['cfg']['user'], $_FILES['upload'], $more);

			$smarty->assign("upload_complete", 1);
			$smarty->assign_by_ref("rsp", $rsp);
		}

		else {
			# ...
		}
	}

	$smarty->display("page_upload.txt");
	exit();
?>