<?php
class Sourcemap_Proj_Transform_Vandg extends Sourcemap_Proj_Transform {
/*******************************************************************************
NAME                    VAN DER GRINTEN 

PURPOSE:	Transforms input Easting and Northing to longitude and
		latitude for the Van der Grinten projection.  The
		Easting and Northing must be in meters.  The longitude
		and latitude values will be returned in radians.

PROGRAMMER              DATE            
----------              ----           
T. Mittan		March, 1993

This function was adapted from the Van Der Grinten projection code
(FORTRAN) in the General Cartographic Transformation Package software
which is available from the U.S. Geological Survey National Mapping Division.
 
ALGORITHM REFERENCES

1.  "New Equal-Area Map Projections for Noncircular Regions", John P. Snyder,
    The American Cartographer, Vol 15, No. 4, October 1988, p$p-> 341-355.

2.  Snyder, John P., "Map Projections--A Working Manual", U.S. Geological
    Survey Professional Paper 1395 (Supersedes USGS Bulletin 1532), United
    State Government Printing Office, Washington D.C., 1987.

3.  "Software Documentation for GCTP General Cartographic Transformation
    Package", U.S. Geological Survey National Mapping Division, May 1982.
*******************************************************************************/


    # Initialize the Van Der Grinten projection
    public function init() {
        $this->R = 6370997.0; //Radius of earth
	}

    public function forward($p) {

        $lon = $p->x;
		$lat = $p->y;	

		/* Forward equations
		-----------------*/
		$dlon = Sourcemap_Proj::adjust_lon($lon - $this->long0);

		if(abs($lat) <= Sourcemap_Proj::EPSLN) {
			$x = $this->x0  + $this->R * $dlon;
			$y = $this->y0;
		}
		$theta = Sourcemap_Proj::asinz(2.0 * abs($lat / Sourcemap_Proj::PI));
		if((abs($dlon) <= Sourcemap_Proj::EPSLN) || (abs(abs(lat) - Sourcemap_Proj::HALF_PI) <= Sourcemap_Proj::EPSLN)) {
			$x = $this->x0;
			if($lat >= 0) {
				$y = $this->y0 + Sourcemap_Proj::PI * $this->R * tan(.5 * $theta);
			} else {
				$y = $this->y0 + Sourcemap_Proj::PI * $this->R * - tan(.5 * $theta);
			}
		}
		$al = 0.5 * abs((Sourcemap_Proj::PI / $dlon) - ($dlon / Sourcemap_Proj::PI));
		$asq = $al * $al;
		$sinth = sin($theta);
		$costh = cos($theta);

		$g = $costh / ($sinth + $costh - 1.0);
		$gsq = $g * $g;
		$m = $g * (2.0 / $sinth - 1.0);
		$msq = $m * $m;
		$con = Sourcemap_Proj::PI * $this->R * ($al * ($g - $msq) + sqrt($asq * ($g - $msq) * ($g - $msq) - ($msq + $asq) * ($gsq - $msq))) / ($msq + $asq);
		if($dlon < 0) {
		    $con = -$con;
		}
		$x = $this->x0 + $con;
		$con = abs($con / (Sourcemap_Proj::PI * $this->R));
		if($lat >= 0) {
		    $y = $this->y0 + Sourcemap_Proj::PI * $this->R * sqrt(1.0 - $con * $con - 2.0 * $al * $con);
		} else {
		    $y = $this->y0 - Sourcemap_Proj::PI * $this->R * sqrt(1.0 - $con * $con - 2.0 * $al * $con);
		}
		$p->x = $x;
		$p->y = $y;
		return $p;
	}

    # Van Der Grinten inverse equations--mapping x,y to lat/long
    public function inverse($p) {

		/* inverse equations
		-----------------*/
		$p->x -= $this->x0;
		$p->y -= $this->y0;
		$con = Sourcemap_Proj::PI * $this->R;
		$xx = $p->x / $con;
		$yy =$p->y / $con;
		$xys = $xx * $xx + $yy * $yy;
		$c1 = -abs($yy) * (1.0 + $xys);
		$c2 = $c1 - 2.0 * $yy * $yy + $xx * $xx;
		$c3 = -2.0 * $c1 + 1.0 + 2.0 * $yy * $yy + $xys * $xys;
		$d = $yy * $yy / $c3 + (2.0 * $c2 * $c2 * $c2 / $c3 / $c3 / $c3 - 9.0 * $c1 * $c2 / $c3 / $c3) / 27.0; // seriously?
        $a1 = ($c1 - $c2 * $c2 / 3.0 / $c3) / $c3;
        $m1 = 2.0 * sqrt(-$a1 / 3.0);
		$con = ((3.0 * $d) / $a1) / $m1;
		if(abs($con) > 1.0) {
			if($con >= 0.0) {
				$con = 1.0;
			} else {
				$con = -1.0;
			}
		}
		$th1 = acos($con) / 3.0;
		if ($p->y >= 0) {
			$lat = (-$m1 *cos($th1 + Sourcemap_Proj::PI / 3.0) - $c2 / 3.0 / $c3) * Sourcemap_Proj::PI;
		} else {
			$lat = -(-$m1 * cos($th1 + PI / 3.0) - $c2 / 3.0 / $c3) * Sourcemap_Proj::PI;
		}

		if (abs($xx) < Sourcemap_Proj::EPSLN) {
			$lon = $this->long0;
		}
		$lon = Sourcemap_Proj::adjust_lon($this->long0 + Sourcemap_Proj::PI * ($xys - 1.0 + sqrt(1.0 + 2.0 * ($xx * $xx - $yy * $yy) + $xys * $xys)) / 2.0 / $xx);

		$p->x = $lon;
		$p->y = $lat;
		return $p;
	}
}
