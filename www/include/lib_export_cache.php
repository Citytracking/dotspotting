<?php

	#
	# $Id$
	#

	######################################################################

	function export_cache_store_file($source_file, $cache_path){

		$dirname = dirname($cache_path);

		$ok = 1;

		if (! file_exists($dirname)){

			if (! mkdir($dirname, 0700, true)){

				return array(
					'ok' => 0,
					'error' => 'failed to create root dir'
				);
			}
		}

		if (! copy($source_file, $cache_path)){

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

	function export_cache_root_for_user(&$user){

		$root = $GLOBALS['cfg']['export_cache_root'];

		$user_root = _export_cache_explode_id($user['id']);

		$parts = array(
			$root,
			$user_root,
		);

		return implode( DIRECTORY_SEPARATOR, $parts);
	}

	######################################################################

	function export_cache_root_for_sheet(&$sheet){

		$user = users_get_by_id($sheet['user_id']);

		$user_root = export_cache_root_for_user($user);
		$sheet_root = _export_cache_explode_id($sheet['id']);

		$parts = array(
			$user_root,
			$sheet_root,
		);

		return implode( DIRECTORY_SEPARATOR, $parts);
	}

	######################################################################

	function export_cache_path_for_sheet(&$sheet, &$more){

		if (! isset($more['filename'])){
			log_notice('export', 'missing filename for export path');
			return null;
		}

		$root = export_cache_root_for_sheet($sheet);

		$parts = array(
			$root,
			$more['filename'],
		);

		return implode(DIRECTORY_SEPARATOR, $parts);
	}

	######################################################################

	function export_cache_purge_sheet(&$sheet){

		$root = export_cache_root_for_sheet($sheet);

		if (! is_dir($root)){
			return;
		}

		foreach (scandir($root) as $file){

			if (preg_match("/^\./", $file)){
				continue;
			}

			$path = implode(DIRECTORY_SEPARATOR, array($root, $file));
			unlink($path);
		}

		rmdir($root);
	}

	######################################################################

	function _export_cache_explode_id($uid){

		$tmp = sprintf("%09d", $uid);
		$parts = array();

		while (strlen($tmp) > 3){

			$parts[] = substr($tmp, 0, 3);
			$tmp = substr($tmp, 3);
		}

		if (strlen($tmp)){
			$parts[] = $tmp;
		}

		return implode(DIRECTORY_SEPARATOR, $parts);
	}

	######################################################################
?>