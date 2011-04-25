<?php

	loadlib("flickr");

	$GLOBALS['import_flickr_spr_extras'] = '';

	#################################################################

	function import_flickr_user($user_id, $more=array()){

		$method = 'flickr.photos.search';

		$args = array(
			'user_id' => $user_id,
			'extras' => $GLOBALS['import_flickr_spr_extras'],
		);

		# extra search params here

		return import_flickr_spr_paginate($method, $args, $more);
	}

	#################################################################

	function import_flickr_photoset($set_id){

		$method = 'flickr.photosets.getPhotos';

		$args = array(
			'photoset_id' => $set_id,
			'extras' => $GLOBALS['import_flickr_spr_extras'],
		);

		$more = array(
			'root' => 'photoset',
		);

		return import_flickr_spr_paginate($method, $args, $more);
	}

	#################################################################

	function import_flickr_group_pool($group_id, $more=array()){

		$method = 'flickr.groups.pools.getPhotos';

		$args = array(
			'group_id' => $group_id,
			'extras' => $GLOBALS['import_flickr_spr_extras'],
		);

		# extra API params here

		return import_flickr_spr_paginate($method, $args, $more);
	}

	#################################################################

	function import_flickr_spr_paginate($method, $args, $more=array()){

		$defaults = array(
			'root' => 'photos',
			'max_photos' => $GLOBALS['cfg']['import_max_records'],
		);

		$more = array_merge($defaults, $more);
		$root = $more['root'];

		$photos = array();

		$page = 1;
		$pages = null;

		while ((! isset($pages)) || ($page <= $pages)){

			$args['page'] = $page;
			$_rsp = flickr_api_call($method, $args);

			if ($_rsp['ok']){

				$rsp = $_rsp['rsp'];

				if (! isset($pages)){
					$pages = $rsp[$root]['pages'];
				}

				$photos = array_merge($photos, $rsp[$root]['photo']);

				if ((isset($more['max_photos'])) && (count($photos) >= $more['max_photos'])){
					$photos = array_slice($photos, 0, $more['max_photos']);
					break;
				}
			}

			$page += 1;
		}

		return $photos;
	}

	#################################################################
?>
