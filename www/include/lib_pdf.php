<?php

	#
	# $Id$
	#

	# HEY LOOK! This isn't finished. It mostly works but there's a lot
	# of layout work left to do... (20110119/straup)

	loadpear("modestmaps/ModestMaps");
	loadpear("fpdf");

	#################################################################

	function pdf_export_dots(&$dots, &$more){

		$w = 11;
		$h = 8.5;

		$margin = .5;
		$dpi = 72;

		$pdf = new FPDF("P", "in", array($w, $h));
		$pdf->setMargins($margin, $margin);

		# First, add the map

		$pdf->addPage();

		$map_img = _pdf_export_dots_map($dots, ($h * $dpi), ($h * $dpi));
		$pdf->Image($map_img, 0, 0, 0, 0, 'PNG');

		# Now add the dots

		$header_buckets = array();

		#

		$header_h = .2;
		$row_h = .2;

		$col_width = 1.25;

		#

		$cols_per_page = floor(($w - ($margin * 2)) / $col_width);

		$count_cols = count($more['columns']);

		$pages_per_row = ceil($count_cols / $cols_per_page);

		# See this? We're adding enough extra columns and re-counting
		# everything in order to ensure that every page for each row
		# has an 'id' column

		if ($pages_per_row > 1){
			$pages_per_row = ceil(($count_cols + ($pages_per_row - 1)) / $cols_per_page);
		}

		# First, chunk out the header in (n) pages and measure the
		# height of the (header) row itself

		$_h = $header_h;

		$pdf->SetFont('Helvetica', 'B', 10);

		for ($i = 0; $i < $count_cols; $i++){

			$b = floor($i / $cols_per_page);

			if (! is_array($header_buckets[$b])){
				$header_buckets[] = array();
			}

			$header_buckets[$b][] = $more['columns'][$i];

			$str_width = ceil($pdf->GetStringWidth($more['columns'][$i]));

			if ($str_width > $col_width){
				$lines = ceil($str_width / $col_width);
				$_h = max($_h, ($lines * $header_h));
			}
		}

		$header_h = $_h;

		# make sure every page has an 'id' field
		# (see above)

		$count_buckets = count($header_buckets);

		for ($i = 0; $i < $count_buckets; $i++){

			$cols = $header_buckets[$i];

			if (! in_array('id', $cols)){
				array_unshift($cols, 'id');
				$header_buckets[$i] = $cols;
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

			$row_heights[] = $_h;
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

			if (($y + $row_height) > ($h - ($margin * 2))){
				$page += $pages_per_row;
				$y = $margin + $header_h;
			}

			$y += $row_height;

			$j = 0;

			foreach ($header_buckets as $cols){

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

		# ZOMG... finally publish the thing...

		foreach ($pages as $page){

			$pdf->AddPage();

			$x = $margin;
			$y = $margin;

			foreach ($page as $data){

				$style = ($data['bold']) ? 'B' : '';

				$pdf->SetFont('Helvetica', $style, 8);

				$max_width = floor($col_width * .9);

				foreach ($data['row'] as $value){

					$value = trim($value);
					$width = $pdf->GetStringWidth($value);

					$pdf->Rect($x, $y, $col_width, $data['height']);

					# Don't bother with MultiCell - it is behaving
					# badly (20110120/straup)

					if ($width < $max_width){
						$pdf->SetXY($x, $y);
						$pdf->Cell(0, $row_h, $value);
					}

					else {

						$_x = $x;
						$_y = $y;

						$buffer = '';

						foreach (str_split($value) as $char){

							$buffer .= $char;
							$width = $pdf->GetStringWidth($buffer);

							if ($width < $max_width){
								continue;
							}

							$pdf->SetXY($_x, $_y);
							$pdf->Cell(0, $row_h, $buffer);
							$buffer = '';

							$_y += $row_h * .8;
						}

						if (strlen($buffer)){

							$pdf->SetXY($_x, $_y);
							$pdf->Cell(0, $row_h, $buffer);
							$buffer = '';
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
		unlink($map_img);
	}

	#################################################################

	# See this: It is basically a clone of what's happening in lib_png.
	# Soon it will be time to reconcile the two. But not yet.
	# (20110113/straup)

	function _pdf_export_dots_map(&$dots, $w, $h){

		$dot_size = 20;

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

		return $tmp;
	}

	#################################################################
?>