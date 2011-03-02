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

	function export_cache_path_for_sheet(&$sheet, &$more){

		if (! isset($more['filename'])){
			log_notice('export', 'missing filename for export path');
			return null;
		}

		$root = $GLOBALS['cfg']['export_cache_root'];

		$user_root = _export_cache_explode_id($sheet['user_id']);
		$sheet_root = _export_cache_explode_id($sheet['id']);

		$ymd = gmdate('Ymd', $sheet['created']);

		$fname = $more['filename'];

		$parts = array(
			$root,
			$user_root,
			$sheet_root,
			$fname,
		);

		return implode("/", $parts);
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

		return implode("/", $parts);
	}

	######################################################################
?>