<?php

	loadlib("flickr");

	# A comma-delimited list of extra information to fetch for each returned record.
	# Currently supported fields are: description, license, date_upload, date_taken,
	# owner_name, icon_server, original_format, last_update, geo, tags, machine_tags,
	# o_dims, views, media, path_alias, url_sq, url_t, url_s, url_m, url_z, url_l,
	# url_o (http://www.flickr.com/services/api/flickr.photos.search.htm)

	$GLOBALS['import_flickr_spr_extras'] = 'geo,description,date_taken,owner_name,tags';

	#################################################################

	function import_flickr_url($url, $more=array()){

		$empty = array();

		# photosets

		if (preg_match("!/sets/(\d+)/?$!", $url, $m)){
			return import_flickr_photoset($m[1], $more);
		}

		# groups

		if (preg_match("!/groups/([^/]+)(?:/pool)?/?$!", $url, $m)){

			$group_id = $m[1];

			if (! preg_match("!\@N\d+$!", $group_id)){
				$group_id = flickr_lookup_group_id_by_url($url);
			}

			if (! $group_id){
				return $empty;
			}

			return import_flickr_group_pool($group_id, $more);
		}

		# individual users

		if (preg_match("!/photos/([^/]+)/?!", $url, $m)){

			$user_id = $m[1];

			if (! preg_match("!\@N\d+$!", $user_id)){
				$user_id = flickr_lookup_user_id_by_url($url);
			}

			if (! $user_id){
				return $empty;
			}

			return import_flickr_user($user_id, $more);

		}

		if ($feed_url = flickr_get_georss_feed($url)){

			$more = array(
				'assume_mime_type' => 'application/rss+xml'
			);

			return import_fetch_uri($url, $more);
		}

		# yahoo says no

		return $empty;
	}

	#################################################################

	function import_flickr_user($user_id, $more=array()){

		$method = 'flickr.photos.search';

		$args = array(
			'user_id' => $user_id,
			'has_geo' => 1,
			'extras' => $GLOBALS['import_flickr_spr_extras'],
		);

		# Note the order of precedence

		if (is_array($more['filter'])){
			$args = array_merge($more['filter'], $args);
		}

		return import_flickr_spr_paginate($method, $args, $more);
	}

	#################################################################

	function import_flickr_photoset($set_id, $more=array()){

		$method = 'flickr.photosets.getPhotos';

		$args = array(
			'photoset_id' => $set_id,
			'extras' => $GLOBALS['import_flickr_spr_extras'],
		);

		# Note the order of precedence

		if (is_array($more['filter'])){
			$args = array_merge($more['filter'], $args);
		}

		# I don't know why we did this... (20110427/straup)
		$more['root'] = 'photoset';

		return import_flickr_spr_paginate($method, $args, $more);
	}

	#################################################################

	function import_flickr_group_pool($group_id, $more=array()){

		$method = 'flickr.photos.search';

		$args = array(
			'group_id' => $group_id,
			'has_geo' => 1,
			'extras' => $GLOBALS['import_flickr_spr_extras'],
		);

		# Note the order of precedence

		if (is_array($more['filter'])){
			$args = array_merge($more['filter'], $args);
		}

		return import_flickr_spr_paginate($method, $args, $more);
	}

	#################################################################

	function import_flickr_spr_paginate($method, $args, $more=array()){

		$defaults = array(
			'root' => 'photos',
			'max_photos' => $GLOBALS['cfg']['import_max_records'],
			'ensure_geo' => 0,
		);

		$more = array_merge($defaults, $more);
		$root = $more['root'];

		$photos = array();
		$count_photos = 0;

		$to_remove = array(
			'secret',
			'server',
			'farm',
			'isprimary',
			'place_id',
			'geo_is_family',
			'geo_is_friend',
			'geo_is_contact',
			'geo_is_public',
			'datetakengranularity',
		);

		$page = 1;
		$pages = null;

		while ((! isset($pages)) || ($page <= $pages)){

			$args['per_page'] = 500;
			$args['page'] = $page;

			$_rsp = flickr_api_call($method, $args);

			if (! $_rsp['ok']){
				break;
			}

			$rsp = $_rsp['rsp'];

			if (! isset($pages)){
				$pages = $rsp[$root]['pages'];
			}

			foreach ($rsp[$root]['photo'] as $ph){

				# why didn't we just add a "has_geo" attribute
				# to the API responses... (20110425/straup)

				if ($ph['accuracy'] == 0){
					continue;
				}

				foreach ($to_remove as $key){
					if (isset($ph[$key])){
						unset($ph[$key]);
					}
				}

				$ph['description'] = $ph['description']['_content'];
				$photos[] = $ph;
				$count_photos += 1;

				if ((isset($more['max_photos'])) && ($count_photos >= $more['max_photos'])){
					break;
				}
			}

			if ((isset($more['max_photos'])) && ($count_photos >= $more['max_photos'])){
				break;
			}

			$page += 1;
		}

		return $photos;
	}

	#################################################################
?>
