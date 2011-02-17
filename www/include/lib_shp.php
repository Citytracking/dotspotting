<?php
	#
	# $Id$
	#

	loadpear("ShapeFile");

	#################################################################

	function shp_parse_fh($fh, $more){

		# See also:
		# http://vis4.net/blog/de/2010/04/reading-esri-shapefiles-in-php/

		fclose($fh);

		$args = array(
			'noparts' => true,
		);

		$shp = new ShapeFile($more['file']['path'], $args);

		if (! $shp){

			return array(
				'ok' => 0,
				'error' => 'Failed to parse shapefile',
			);
		}

		$data = array();
		$errors = array();

		$record = 0;

		while ($record = $shp->getNext()){

			# This is mostly here if/when we break in $parts loop

			if (($more['max_records']) && ($record > $more['max_records'])){
				break;
			}

			# What to do about file specific metadata?

			$shp_data = $record->getShpData();

			$parts = (isset($shp_data['parts'])) ? $shp_data['parts'] : array($shp_data);

			foreach ($parts as $pt){

				$record ++;

				if (($more['max_records']) && ($record > $more['max_records'])){
					break;
				}

				$lat = $pt['y'];
				$lon = $pt['x'];

				if ((! $lat) || (! $lon)){
					continue;
				}

				# check $more for reprojection nonsense here

				# loadlib("geo_proj");
				# $from = 'EPSG:900913';

				# $pt = array('latitude' => $lat, 'longitude' => $lon);
				# $pt = geo_proj_transform($pt, $from, 'EPSG:4326');

				if (! geo_utils_is_valid_latitude($lat)){

					$errors[] = array(
						'record' => $record,
						'column' => 'latitude',
						'error' => 'Invalid latitude',
					);

					continue;
				}

				if (! geo_utils_is_valid_longitude($lon)){

					$errors[] = array(
						'record' => $record,
						'column' => 'longitude',
						'error' => 'Invalid longitude',
					);

					continue;
				}

				$tmp = array(
					'latitude' => $lat,
					'longitude' => $lon,
				);

				$data[] = $tmp;
			}
		}

		if (! count($data)){

			return array(
				'ok' => 0,
				'error' => '',
			);
		}

		return array(
			'ok' => 1,
			'errors' => &$errors,
			'data' => &$data,
		);
	}

	#################################################################
?>