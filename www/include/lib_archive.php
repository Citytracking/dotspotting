<?php

	#
	# $Id$
	#

	loadlib("formats");

	######################################################################

	function archive_store_file(&$file, &$sheet){

		if (! is_dir($GLOBALS['cfg']['import_archive_root'])){

			return array(
				'ok' => 0,
				'error' => 'archive root is not a directory',
			);
		}

		$archived_path = archive_path_for_sheet($sheet);
		$dirname = dirname($archived_path);

		$ok = 1;

		if (! file_exists($dirname)){

			if (! mkdir($dirname, 0700, true)){

				return array(
					'ok' => 0,
					'error' => 'failed to create root dir'
				);
			}
		}

		if (! copy($file['path'], $archived_path)){

			return array(
				'ok' => 0,
				'error' => 'failed to copy file'
			);
		}

		return array(
			'ok' => 1,
			'path' => $archived_path,
		);
	}

	######################################################################

	function archive_path_for_sheet(&$sheet){

		$root = $GLOBALS['cfg']['import_archive_root'];
		$user_root = _archive_explode_id($sheet['user_id']);

		$ymd = gmdate('Ymd', $sheet['created']);

		$map = formats_valid_import_map();
		$ext = $map[$sheet['mime_type']];

		$fname = "{$sheet['id']}.{$ext}";

		$parts = array(
			$root,
			$user_root,
			$ymd,
			$fname,
		);

		return implode("/", $parts);
	}

	######################################################################

	function _archive_explode_id($uid){

		$tmp = $uid;
		$parts = array();

		while (strlen($tmp) > 3){

			$parts[] = substr($tmp, 0, 3);
			$tmp = substr($tmp, 3);
		}

		if (strlen($tmp)){
			$parts[] = $tmp;
		}

		return implode("/", $parts);
	}

	######################################################################
?>