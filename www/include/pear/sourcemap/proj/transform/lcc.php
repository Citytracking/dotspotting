<?php
/*******************************************************************************
NAME                            LAMBERT CONFORMAL CONIC

PURPOSE:	Transforms input longitude and latitude to Easting and
		Northing for the Lambert Conformal Conic projection.  The
		longitude and latitude must be in radians.  The Easting
		and Northing values will be returned in meters.


ALGORITHM REFERENCES

1.  Snyder, John P., "Map Projections--A Working Manual", U.S. Geological
    Survey Professional Paper 1395 (Supersedes USGS Bulletin 1532), United
    State Government Printing Office, Washington D.C., 1987.

2.  Snyder, John P. and Voxland, Philip M., "An Album of Map Projections",
    U.S. Geological Survey Professional Paper 1453 , United State Government
*******************************************************************************/


//<2104> +proj=lcc +lat_1=10.16666666666667 +lat_0=10.16666666666667 +lon_0=-71.60561777777777 +k_0=1 +x0=-17044 +x0=-23139.97 +ellps=intl +units=m +no_defs  no_defs

// Initialize the Lambert Conformal conic projection
// -----------------------------------------------------------------

//Proj4js.Proj.lcc = Class.create();
class Sourcemap_Proj_Transform_Lcc extends Sourcemap_Proj_Transform {
    public function init() {

        // array of:  r_maj,r_min,lat1,lat2,c_lon,c_lat,false_east,false_north
        //double c_lat;                   /* center latitude                      */
        //double c_lon;                   /* center longitude                     */
        //double lat1;                    /* first standard parallel              */
        //double lat2;                    /* second standard parallel             */
        //double r_maj;                   /* major axis                           */
        //double r_min;                   /* minor axis                           */
        //double false_east;              /* x offset in meters                   */
        //double false_north;             /* y offset in meters                   */

        if(!$this->_proj->lat2){ $this->_proj->lat2 = $this->_proj->lat0; }//if lat2 is not defined
        if(!$this->_proj->k0) $this->_proj->k0 = 1.0;

        // Standard Parallels cannot be equal and on opposite sides of the equator
        if (abs($this->_proj->lat1 + $this->_proj->lat2) < Sourcemap_Proj::EPSLN) {
            throw new Exception("Equal Latitudes");
        }

        $temp = $this->_proj->b / $this->_proj->a;
        $this->_proj->e = sqrt(1.0 - temp*temp);

        $sin1 = sin($this->_proj->lat1);
        $cos1 = cos($this->_proj->lat1);
        $ms1 = Sourcemap_Proj::msfnz($this->_proj->e, sin1, cos1);
        $ts1 = Sourcemap_Proj::tsfnz($this->_proj->e, $this->_proj->lat1, sin1);

        $sin2 = sin($this->_proj->lat2);
        $cos2 = cos($this->_proj->lat2);
        $ms2 = Sourcemap_Proj::msfnz($this->_proj->e, sin2, cos2);
        $ts2 = Sourcemap_Proj::tsfnz($this->_proj->e, $this->_proj->lat2, sin2);

        $ts0 = Sourcemap_Proj::tsfnz($this->_proj->e, $this->_proj->lat0, sin($this->_proj->lat0));

        if (abs($this->_proj->lat1 - $this->_proj->lat2) > Sourcemap_Proj::EPSLN) {
            $this->_proj->ns = log(ms1/ms2)/log(ts1/ts2);
        } else {
            $this->_proj->ns = sin1;
        }
        $this->_proj->f0 = ms1 / ($this->_proj->ns * pow(ts1, $this->_proj->ns));
        $this->_proj->rh = $this->_proj->a * $this->_proj->f0 * pow(ts0, $this->_proj->ns);
        if(!$this->_proj->title) $this->_proj->title = "Lambert Conformal Conic";
    }


    // Lambert Conformal conic forward equations--mapping lat,long to x,y
    // -----------------------------------------------------------------
    public function forward($pt) {

        $lon = $pt->x;
        $lat = $pt->y;

        // convert to radians
        if($lat <= 90.0 && $lat >= -90.0 && $lon <= 180.0 && $lon >= -180.0) {
            //lon = lon * Sourcemap_Proj::D2R;
            //lat = lat * Sourcemap_Proj::D2R;
            // holdover from proj4js
        } else {
            throw new Exception('Lat/Lon input out of range.');
        }

        $con = abs(abs($lat) - Sourcemap_Proj::HALF_PI);
        if($con > Sourcemap_Proj::EPSLN) {
            $ts = Sourcemap_Proj::tsfnz($this->_proj->e, $lat, sin($lat) );
            $rh1 = $this->_proj->a * $this->_proj->f0 * pow($ts, $this->_proj->ns);
        } else {
            $con = $lat * $this->_proj->ns;
            if($con <= 0) {
                throw new Exception('No projection.');
            }
            $rh1 = 0;
        }
        $theta = $this->_proj->ns * Sourcemap_Proj::adjust_lon($lon - $this->_proj->long0);
        $pt->x = $this->_proj->k0 * (rh1 * sin(theta)) + $this->_proj->x0;
        $pt->y = $this->_proj->k0 * ($this->_proj->rh - $rh1 * cos($theta)) + $this->_proj->y0;

        return $pt;
    }

  // Lambert Conformal Conic inverse equations--mapping x,y to lat/long
  // -----------------------------------------------------------------
    public function inverse($pt) {

        $x = ($pt->x - $this->_proj->x0) / $this->_proj->k0;
        $y = ($this->_proj->rh - ($pt->y - $this->_proj->y0) / $this->_proj->k0);
        if($this->_proj->ns > 0) {
            $rh1 = sqrt ($x * $x + $y * $y);
            $con = 1.0;
        } else {
            $rh1 = -sqrt (x * x + y * y);
            $con = -1.0;
        }
        $theta = 0.0;
        if($rh1 != 0) {
            $theta = atan2(($con * $x),($con * $y));
        }
        if(($rh1 != 0) || ($this->_proj->ns > 0.0)) {
            $con = 1.0/$this->_proj->ns;
            $ts = pow(($rh1/($this->_proj->a * $this->_proj->f0)), $con);
            $lat = Sourcemap_Proj::phi2z($this->_proj->e, $ts);
            if($lat == -9999) return null;
        } else {
            $lat = -Sourcemap_Proj::HALF_PI;
        }
        $lon = Sourcemap_Proj::adjust_lon($theta/$this->_proj->ns + $this->_proj->long0);

        $pt->x = $lon;
        $pt->y = $lat;
        return $pt;
  }
};




