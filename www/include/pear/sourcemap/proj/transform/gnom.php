<?php
class Sourcemap_Proj_Transform_Gnom extends Sourcemap_Proj_Transform {
/*****************************************************************************
NAME                             GNOMONIC

PURPOSE:	Transforms input longitude and latitude to Easting and
		Northing for the Gnomonic Projection.
                Implementation based on the existing sterea and ortho
                implementations.

PROGRAMMER              DATE
----------              ----
Richard Marsden         November 2009

ALGORITHM REFERENCES

1.  Snyder, John P., "Flattening the Earth - Two Thousand Years of Map 
    Projections", University of Chicago Press 1993

2.  Wolfram Mathworld "Gnomonic Projection"
    http://mathworld.wolfram.com/GnomonicProjection.html
    Accessed: 12th November 2009
******************************************************************************/

    # Initialize the Gnomonic projection
    public function init() {
        /* Place parameters in static storage for common use
           -------------------------------------------------*/
        $this->sin_p14 = sin($this->lat0);
        $this->cos_p14 = cos($this->lat0);
        // Approximation for projecting points to the horizon (infinity)
        $this->infinity_dist = 1000 * $this->a;
    }


    # Gnomonic forward equations--mapping lat,long to x,y
    public function forward($p) {
        #$sinphi, $cosphi;	/* sin and cos value				*/
        #$dlon;		/* delta longitude value			*/
        #$coslon;		/* cos of longitude				*/
        #$ksp;		/* scale factor					*/
        #$g;		
        $lon = $p->x;
        $lat= $p->y;	
        /* Forward equations
           -----------------*/
        $dlon = Sourcemap_Proj::adjust_lon($lon - $this->long0);

        $sinphi = sin($lat);
        $cosphi = cos($lat);	

        $coslon = cos($dlon);
        $g = $this->sin_p14 * $sinphi + $this->cos_p14 * $cosphi * $coslon;
        $ksp = 1.0;
        if(($g > 0) || (abs($g) <= Sourcemap_Proj::EPSLN)) {
            $x = $this->x0 + $this->a * $ksp * $cosphi * sin($dlon) / $g;
            $y = $this->y0 + $this->a * $ksp * ($this->cos_p14 * $sinphi - $this->sin_p14 * $cosphi * $coslon) / $g;
        } else {
            throw new Exception("Point error.");

            // Point is in the opposing hemisphere and is unprojectable
            // We still need to return a reasonable point, so we project 
            // to infinity, on a bearing 
            // equivalent to the northern hemisphere equivalent
            // This is a reasonable approximation for short shapes and lines that 
            // straddle the horizon.

            #$x = $this->x0 + $this->infinity_dist * $cosphi * sin($dlon);
            #$y = $this->y0 + $this->infinity_dist * ($this->cos_p14 * $sinphi - $this->sin_p14 * $cosphi * $coslon);

        }
        $p->x = $x;
        $p->y = $y;
        return $p;
    }


    public function inverse($p) {
        #$rh;		/* Rho */
        #$z;		/* angle */
        #$sinc, $cosc;
        #$c;
        #$lon , $lat;

        /* Inverse equations
           -----------------*/
        $p->x = ($p->x - $this->x0) / $this->a;
        $p->y = ($p->y - $this->y0) / $this->a;

        $p->x /= $this->k0;
        $p->y /= $this->k0;

        if(($rh = sqrt($p->x * $p->x + $p->y * $p->y)) ) {
            $c = atan2($rh, $this->rc);
            $sinc = sin($c);
            $cosc = cos($c);

            $lat = Sourcemap_Proj::asinz($cosc * $this->sin_p14 + ($p->y * $sinc * $this->cos_p14) / $rh);
            $lon = atan2($p->x * $sinc, $rh * $this->cos_p14 * $cosc - $p->y * $this->sin_p14 * $sinc);
            $lon = Sourcemap_Proj::adjust_lon($this->long0+lon);
        } else {
            $lat = $this->phic0;
            $lon = 0.0;
        }

        $p->x = $lon;
        $p->y = $lat;
        return p;
    }
}
