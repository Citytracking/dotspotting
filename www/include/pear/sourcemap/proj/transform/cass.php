<?php
/*******************************************************************************
NAME                            CASSINI

PURPOSE:	Transforms input longitude and latitude to Easting and
		Northing for the Cassini projection.  The
		longitude and latitude must be in radians.  The Easting
		and Northing values will be returned in meters.
    Ported from PROJ.4.


ALGORITHM REFERENCES

1.  Snyder, John P., "Map Projections--A Working Manual", U.S. Geological
    Survey Professional Paper 1395 (Supersedes USGS Bulletin 1532), United
    State Government Printing Office, Washington D.C., 1987.

2.  Snyder, John P. and Voxland, Philip M., "An Album of Map Projections",
    U.S. Geological Survey Professional Paper 1453 , United State Government
*******************************************************************************/


//Proj4js.defs["EPSG:28191"] = "+proj=cass +lat_0=31.73409694444445 +lon_0=35.21208055555556 +x_0=170251.555 +y_0=126867.909 +a=6378300.789 +b=6356566.435 +towgs84=-275.722,94.7824,340.894,-8.001,-4.42,-11.821,1 +units=m +no_defs";

// Initialize the Cassini projection
// -----------------------------------------------------------------
class Sourcemap_Proj_Transform_Cass {

    public $C1 = .16666666666666666666;
    public $C2 = .00833333333333333333;
    public $C3 = .04166666666666666666;
    public $C4 = .33333333333333333333;
    public $C5 = .06666666666666666666;

/* meridinal distance for ellipsoid and inverse
**	8th degree - accurate to < 1e-5 meters when used in conjuction
**		with typical major axis values.
**	Inverse determines phi to EPS (1e-11) radians, about 1e-6 seconds.
*/
    public $C00 = 1.0;
    public $C02 = .25;
    public $C04 = .046875;
    public $C06 = .01953125;
    public $C08 = .01068115234375;
    public $C22 = .75;
    public $C44 = .46875;
    public $C46 = .01302083333333333333;
    public $C48 = .00712076822916666666;
    public $C66 = .36458333333333333333;
    public $C68 = .00569661458333333333;
    public $C88 = .3076171875;



    public function init() {
        if (!$this->sphere) {
            $this->en = $this->pj_enfn($this->es);
            $this->m0 = $this->pj_mlfn($this->lat0, sin($this->lat0), cos($this->lat0), $this->en);
        }
    }



/* Cassini forward equations--mapping lat,long to x,y
  -----------------------------------------------------------------------*/
    public function forward($p) {

    /* Forward equations
      -----------------*/
        $lam = $p->x;
        $phi = $p->y;
        $lam = Sourcemap_Proj::adjust_lon($lam - $this->long0);

        if($this->sphere) {
            $x = asin(cos($phi) * sin($lam));
            $y = atan2(tan($phi) , cos($lam)) - $this->phi0;
        } else {
            //ellipsoid
            $this->n = sin($phi);
            $this->c = cos($phi);
            $y = $this->pj_mlfn($phi, $this->n, $this->c, $this->en);
            $this->n = 1.0/sqrt(1.0 - $this->es * $this->n * $this->n);
            $this->tn = tan($phi); 
            $this->t = $this->tn * $this->tn;
            $this->a1 = $lam * $this->c;
            $this->c *= $this->es * $this->c / (1 - $this->es);
            $this->a2 = $this->a1 * $this->a1;
            $x = $this->n * $this->a1 * (1. - $this->a2 * $this->t * ($this->C1 - (8. - $this->t + 8. * $this->c) * $this->a2 * $this->C2));
            $y -= $this->m0 - $this->n * $this->tn * $this->a2 * (.5 + (5. - $this->t + 6. * $this->c) * $this->a2 * $this->C3);
        }

        $p->x = $this->a*$x + $this->x0;
        $p->y = $this->a*$y + $this->y0;
        return $p;
    }

/* Inverse equations
  -----------------*/
    public function inverse($p) {
        $p->x -= $this->x0;
        $p->y -= $this->y0;
        $x = $p->x/$this->a;
        $y = $p->y/$this->a;

        if ($this->sphere) {
            $this->dd = $y + $this->lat0;
            $phi = asin(sin($this->dd) * cos($x));
            $lam = atan2(tan($x), cos($this->dd));
        } else {
            /* ellipsoid */
            $ph1 = $this->pj_inv_mlfn($this->m0 + $y, $this->es, $this->en);
            $this->tn = tan($ph1); 
            $this->t = $this->tn * $this->tn;
            $this->n = sin($ph1);
            $this->r = 1. / (1. - $this->es * $this->n * $this->n);
            $this->n = sqrt($this->r);
            $this->r *= (1. - $this->es) * $this->n;
            $this->dd = $x / $this->n;
            $this->d2 = $this->dd * $this->dd;
            $phi = $ph1 - ($this->n * $this->tn / $this->r) * $this->d2 * (.5 - (1.0 + 3.0 * $this->t) * $this->d2 * $this->C3);
            $lam = $this->dd * (1.0 + $this->t * $this->d2 * (-$this->C4 + (1.0 + 3.0 * $this->t) * $this->d2 * $this->C5)) / cos($ph1);
        }
        $p->x = Sourcemap_Proj::adjust_lon($this->long0+$lam);
        $p->y = $phi;
        return $p;
    }


  //code from the PROJ.4 pj_mlfn.c file;  this may be useful for other projections
    public function pj_enfn($es) {
        $en = array();
        $en[0] = $this->C00 - $es * ($this->C02 + $es * ($this->C04 + $es * ($this->C06 + $es * $this->C08)));
        $en[1] = $es * ($this->C22 - $es * ($this->C04 + $es * ($this->C06 + $es * $this->C08)));
        $t = $es * $es;
        $en[2] = $t * ($this->C44 - $es * ($this->C46 + $es * $this->C48));
        $t *= $es;
        $en[3] = $t * ($this->C66 - $es * $this->C68);
        $en[4] = $t * $es * $this->C88;
        return $en;
    }

    public function pj_mlfn($phi, $sphi, $cphi, $en) {
        $cphi *= $sphi;
        $sphi *= $sphi;
        return($en[0] * $phi - $cphi * ($en[1] + $sphi*($en[2]+ $sphi*($en[3] + $sphi*$en[4]))));
    }

    public function pj_inv_mlfn($arg, $es, $en) {
        $k = 1.0/(1.-$es);
        $phi = $arg;
        for($i = Sourcemap_Proj::MAX_ITER; $i ; --$i) { /* rarely goes over 2 iterations */
            $s = sin($phi);
            $t = 1.0 - $es * $s * $s;
            //t = $this->pj_mlfn(phi, s, cos(phi), en) - arg;
            //phi -= t * (t * sqrt(t)) * k;
            $t = ($this->pj_mlfn($phi, $s, cos($phi), $en) - $arg) * ($t * sqrt($t)) * $k;
            $phi -= $t;
            if(abs($t) < Sourcemap_Proj::EPSLN)
                return $phi;
        }
        throw new Exception("Convergence error.");
        return $phi;
    }

}
