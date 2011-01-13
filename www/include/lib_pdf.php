<?php

	#
	# $Id$
	#

	# HEY LOOK! THIS DOESN'T WORK.

	loadpear("modestmaps/ModestMaps");
	loadpear("fpdf");

	#################################################################

	function pdf_export_dots(&$dots, $fh){

		$w = 8.5;
		$h = 11;
		$dpi = 150;

		$pdf = new FPDF("P", "in", array($w, $h));
		$pdf->setMargins(.5, .5);
		$pdf->addPage();

		$map = _pdf_export_dots_map($dots, ($w * $dpi), ($h * $dpi));

		$pdf->Image($map, null, null, 1, 1);
		exit;
	}

	#################################################################

	function _pdf_export_dots_map(&$dots, $w, $h){


	}

	#################################################################
?>