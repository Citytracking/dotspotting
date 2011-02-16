<?php
	#
	# $Id$
	#

	#################################################################

	function shp_parse_fh($fh, $more){

		# See also:
		# http://vis4.net/blog/de/2010/04/reading-esri-shapefiles-in-php/

		loadpear("ShapeFile");

		fclose($fh);

		$args = array(
			'noparts' => true,
		);

		$shp = new ShapeFile($more['file']['path'], $args);

		if (! $shp){

		}

		$data = array();
		$errors = array();

		while ($record = $shp->getNext()){

			$shp_data = $record->getShpData();

			$parts = (isset($shp_data['parts'])) ? $shp_data['parts'] : array($shp_data);

			foreach ($parts as $pt){

				$lat = $pt['y'];
				$lon = $pt['x'];

				# sudo do better error handling here

				if ((! $lat) || (! $lon)){
					continue;
				}

				# check $more for reprojection nonsense here

				$tmp = array(
					'latitude' => $lat,
					'longitude' => $lon,
				);

				$data[] = $tmp;
			}
		}

		return array(
			'ok' => 1,
			'errors' => &$errors,
			'data' => &$data,
		);
	}

	#################################################################
?>