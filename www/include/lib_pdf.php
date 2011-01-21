<?php

	#
	# $Id$
	#

	# THIS NEEDS COMMENTS.

	loadpear("modestmaps/ModestMaps");
	loadpear("fpdf");

	loadlib("kmeans");

	#################################################################

	function pdf_export_dots(&$dots, &$more){

		# PLEASE FOR TO BE CACHING ME, OBIWAN...
		# (20110120/straup)

		$w = 11;
		$h = 8.5;

		$margin = .5;
		$dpi = 72;

		$header_h = .2;
		$row_h = .2;

		$col_width = 1.25;

		# Here we go...

		$pdf = new FPDF("P", "in", array($w, $h));
		$pdf->setMargins($margin, $margin);

		# The legend gets added below (once we've figured out what page
		# each dot is on) but we'll just declare it here.

		$legend = array();

		$count_legend_items = floor($h / ($row_h * 1.4));
		$count_clusters = ceil(count($dots) / $count_legend_items);

		# Just turn clusters off for now... the map rendering time
		# is still too long for multiple map images (20110120/straup)

		$count_clusters = 1;

		$clusters = array();

		if ($count_clusters == 1){
			$clusters = array($dots);
		}

		else {

			$points = array();
			$i = 0;

			foreach ($dots as $dot){

				$points[] = array(
					'x' => (float)$dot['longitude'],
					'y' => (float)$dot['latitude'],
					'id' => $dot['id'],
					'idx' => $i,
				);

				$i++;
			}

			$_clusters = kmeans_cluster($points, $count_clusters);

			foreach ($_clusters as $_cluster){

				$_dots = array();

				foreach ($_cluster as $_pt){
					$_dots[] = $dots[$pt['idx']];
				}

				$clusters[] = $_dots;
			}
		}

		#
		# First generate all the maps
		#

		$maps = array();

		foreach ($clusters as $dots){

			list($map, $map_img) = _pdf_export_dots_map($dots, ($h * $dpi), ($h * $dpi));
			$maps[] = $map_img;
		}

		# Now figure out the what is the what of the dots

		$columns = array();

		$cols_per_page = floor(($w - ($margin * 2)) / $col_width);
		$count_cols = count($more['columns']);

		$pages_per_row = ceil($count_cols / $cols_per_page);

		# See this? We're adding enough extra columns and re-counting
		# everything in order to ensure that every page for each row
		# has an 'id' column

		if ($pages_per_row > 1){
			$_count = $count_cols + ($pages_per_row - 1);
			$pages_per_row = ceil($_count / $cols_per_page);
		}

		# First, chunk out the header in (n) pages and measure the
		# height of the (header) row itself

		$_h = $header_h * 1.3;

		$pdf->SetFont('Helvetica', 'B', 10);

		for ($i = 0; $i < $count_cols; $i++){

			$col_name = $more['columns'][$i];

			$b = floor($i / $cols_per_page);

			if (! is_array($columns[$b])){
				$columns[] = array();
			}

			$columns[$b][] = $col_name;

			$str_width = ceil($pdf->GetStringWidth($more['columns'][$i]));

			if ($str_width > $col_width){
				$lines = ceil($str_width / $col_width);
				$_h = max($_h, ($lines * $header_h));
			}
		}

		$header_h = $_h;

		# make sure every page has an 'id' field
		# (see above)

		$count_columns = count($columns);

		for ($i = 0; $i < $count_columns; $i++){

			$cols = $columns[$i];

			if (! in_array('id', $cols)){
				array_unshift($cols, 'id');
				$columns[$i] = $cols;
			}

			# move stuff around so that we keep the pages nice and tidy

			if (count($columns[$i]) > $cols_per_page){

				$to_keep = array_slice($columns[$i], 0, $cols_per_page);
				$extra = array_slice($columns[$i], $cols_per_page);

				$columns[$i] = $to_keep;
				$columns[$i + 1] = $extra;
			}
		}

		# Now work out the height of each row of dots

		$row_heights = array();

		$pdf->SetFont('Helvetica', '', 10);

		foreach ($dots as $dot){

			$_h = $row_h;

			foreach ($dot as $key => $value){

				$str_width = ceil($pdf->GetStringWidth($value));

				if ($str_width > $col_width){
					$lines = ceil($str_width / $col_width);
					$_h = max($_h, ($lines * $row_h));
				}		
			}

			$row_heights[] = $_h * 1.1;
		}

		# Now sort everything in to pages

		$pages = array();
		$page = 0;

		$count_dots = count($dots);
		$dot_idx = 0;

		$y = $margin + $header_h;

		while ($dot_idx < $count_dots){

			$dot = $dots[$dot_idx];
			$row_height = $row_heights[$dot_idx];

			# will this row bleed off the current page ($page) ?

			$goto_nextpage = 0;

			if (($y + $row_height) > ($h - ($margin * 1.5))){
				$goto_nextpage = 1;
			}

			if ($goto_nextpage){
				$page += $pages_per_row;
				$y = $margin + $header_h;
			}

			$y += $row_height;

			# set up information for legend

			$legend[ $dot['id'] ] = array(
				'page' => $page + 2,	# account for a zero-based list + 1 (the map page)
				'id' => $dot['id'],
				'latitude' => $dot['latitude'],
				'longitude' => $dot['longitude'],
				'ymd' => gmdate('Y-m-d', strtotime($dot['created'])),
			);

			#

			$j = 0;

			foreach ($columns as $cols){

				$_row = array();

				foreach ($cols as $name){
					$_row[] = $dot[$name];
				}

				$page_idx = $page + $j;

				if (! is_array($pages[$page_idx])){

					$pages[$page_idx] = array(array(
						'row' => $cols,
						'bold' => 1,
						'height' => $header_h,
					));
				}

				$pages[ $page_idx ][] = array(
					'row' => $_row,
					'height' => $row_height,
				);

				$j ++;
			}

			$dot_idx++;
		}

		#
		# ZOMG... finally publish the thing...
		#

		# First, display all the maps and corresponding
		# legends

		function sort_by_lat($a, $b){

			if ($a['latitude'] == $b['latitude']) {
				return 0;
			}

			return ($a['latitude'] > $b['latitude']) ? -1 : 1;
		}

		$count_clusters = count($clusters);

		for ($i = 0; $i < $count_clusters; $i++){

			$dots = $clusters[$i];
			$_legend = array();

			$j = 0;

			foreach ($dots as $dot){
				$_legend[$dot['id']] = $legend[$dot['id']];
				$j ++;

				if ($j >= $count_legend_items){
					break;
				}
			}

			usort($_legend, "sort_by_lat");

			$pdf->AddPage();
			$pdf->Image($maps[$i], 0, 0, 0, 0, 'PNG');

			$pdf->SetFont('Helvetica', '', 10);

			$x = $h + $margin;
			$y = $margin;

			foreach ($_legend as $dot){

				$text = "{$dot['id']} / pg. {$dot['page']}";

				$pdf->SetXY($x, $y);
				$pdf->Cell(0, $row_h, $text);

				$loc = new MMaps_Location($dot['latitude'], $dot['longitude']);
				$pt = $map->locationPoint($loc);

				$x1 = $x - ($margin / 8);
				$y1 = $y + ($row_h / 2);

				$x2 = $pt->x / $dpi;
				$y2 = $pt->y / $dpi;

				$pdf->Line($x1, $y1, $x2, $y2);

				$y += $row_h * 1.1;
			}
		}

		# Now the rows (of dots)

		foreach ($pages as $page){

			$pdf->AddPage();

			$x = $margin;
			$y = $margin;

			$z = 0;
	
			foreach ($page as $data){

				$style = ($data['bold']) ? 'B' : '';

				$pdf->SetFont('Helvetica', $style, 10);

				$x_offset = $col_width * .1;
				$y_offset = $data['height'] * .1;

				$max_width = $col_width - ($x_offset * 3);

				$bg = ($z % 2) ? 255 : 205;
				$z ++;

				foreach ($data['row'] as $value){

					$value = trim($value);
					$width = $pdf->GetStringWidth($value);

					$pdf->SetFillColor($bg);
					$pdf->Rect($x, $y, $col_width, $data['height'], 'F');

					# Don't bother with MultiCell - it is behaving
					# badly (20110120/straup)

					if ($width < $max_width){
						$pdf->SetXY($x + $x_offset, $y + $y_offset);
						$pdf->Cell(0, $row_h, $value);
					}

					else {

						$_x = $x;
						$_y = $y;

						$lines = array();
						$buffer = '';

						foreach (str_split($value) as $char){

							if (($buffer == '') && ($char == ' ')){
								continue;
							}

							$buffer .= $char;
							$width = $pdf->GetStringWidth($buffer);

							if ($width >= $max_width){
								$lines[] = $buffer;
								$buffer = '';
							}
						}

						if (strlen($buffer)){
							$lines[] = $buffer;
							$buffer = '';
						}

						foreach ($lines as $ln){

							$pdf->SetXY($_x + $x_offset, $_y + $y_offset);
							$pdf->Cell(0, $row_h, $ln);
							$_y += $row_h * .8;
						}
					}

					$x += $col_width;
				}

				$x = $margin;
				$y += $data['height'];
			}
		}

		# Go!

		$pdf->Output();

		foreach ($maps as $map_img){
			unlink($map_img);
		}

	}

	#################################################################

	# See this: It is basically a clone of what's happening in lib_png.
	# Soon it will be time to reconcile the two. But not yet.
	# (20110113/straup)

	function _pdf_export_dots_map(&$dots, $w, $h){

		$dot_size = 15;

		$swlat = null;
		$swlon = null;
		$nelat = null;
		$nelon = null;

		foreach ($dots as $dot){
			$swlat = (! isset($swlat)) ? $dot['latitude'] : min($swlat, $dot['latitude']);
			$swlon = (! isset($swlon)) ? $dot['longitude'] : min($swlon, $dot['longitude']);
			$nelat = (! isset($nelat)) ? $dot['latitude'] : max($nelat, $dot['latitude']);
			$nelon = (! isset($nelon)) ? $dot['longitude'] : max($nelon, $dot['longitude']);
		}

		$template = $GLOBALS['cfg']['maptiles_template_url'];

		$hosts = $GLOBALS['cfg']['maptiles_template_hosts'];
		shuffle($hosts);
		$template = str_replace("{S}", $hosts[0], $template);

		$provider = new MMaps_Templated_Spherical_Mercator_Provider($template);

		$sw = new MMaps_Location($swlat, $swlon);
		$ne = new MMaps_Location($nelat, $nelon);

		$dims = new MMaps_Point($w, $h);

		$map = MMaps_mapByExtent($provider, $sw, $ne, $dims);
		$im = $map->draw();

		$points = array();

		$fill = imagecolorallocatealpha($im, 0, 17, 45, 96);
		$stroke = imagecolorallocate($im, 153, 204, 0);

		foreach ($dots as $dot){

			$loc = new MMaps_Location($dot['latitude'], $dot['longitude']);
			$pt = $map->locationPoint($loc);

			imagefilledellipse($im, $pt->x, $pt->y, $dot_size, $dot_size, $fill);

			imagesetthickness($im, 3);
			imagearc($im, $pt->x, $pt->y, $dot_size, $dot_size, 0, 359.9, $stroke);
		}

		$tmp = tempnam(sys_get_temp_dir(), "pdf") . ".png";

		imagepng($im, $tmp);
		imagedestroy($im);

		return array($map, $tmp);
	}

	#################################################################
?>