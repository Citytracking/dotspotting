<?php
/**
    NAME                            MERCATOR

    PURPOSE:	Transforms input longitude and latitude to Easting and
            Northing for the Mercator projection.  The
            longitude and latitude must be in radians.  The Easting
            and Northing values will be returned in meters.

    PROGRAMMER              DATE
    ----------              ----
    D. Steinwand, EROS      Nov, 1991
    T. Mittan		Mar, 1993

    ALGORITHM REFERENCES

    1.  Snyder, John P., "Map Projections--A Working Manual", 
        U.S. Geological
        Survey Professional Paper 1395 (Supersedes USGS Bulletin 1532),
        United State Government Printing Office, Washington D.C., 1987.

    2.  Snyder, John P. and Voxland, Philip M., "An Album of Map 
        Projections", U.S. Geological Survey Professional Paper 1453 , 
        United State Government
        Printing Office, Washington D.C., 1989.
**/

//static double r_major = a;		   /* major axis 				*/
//static double r_minor = b;		   /* minor axis 				*/
//static double lon_center = long0;	   /* Center longitude (projection center) */
//static double lat_origin =  lat0;	   /* center latitude			*/
//static double e,es;		           /* eccentricity constants		*/
//static double m1;		               /* small value m			*/
//static double false_northing = y0;   /* y offset in meters			*/
//static double false_easting = x0;	   /* x offset in meters			*/
//scale_fact = k0 
class Sourcemap_Proj_Transform_Merc extends Sourcemap_Proj_Transform {
    public function init() {
        $proj = $this->_proj;
        if($proj->lat_ts) {
            if($proj->sphere) {
                $proj->k0 = cos($proj->lat_ts);
            } else {
                $proj->k0 = Sourcemap_Proj::msfnz(
                    $proj->es, 
                    sin($proj->lat_ts), 
                    cos($proj->lat_ts)
                );
            }
        }
    }

/* Mercator forward equations--mapping lat,long to x,y
  --------------------------------------------------*/

    public function forward($pt) {	
        $lon = $pt->x;
        $lat = $pt->y;
        // convert to radians
        if($lat * Sourcemap_Proj::R2D > 90.0 && 
            $lat * Sourcemap_Proj::R2D < -90.0 && 
            $lon * Sourcemap_Proj::R2D > 180.0 && 
            $lon * Sourcemap_Proj::R2D < -180.0) {
            throw new Exception('Lat/Lon input out of range.');
        }

        if(abs(abs($lat) - Sourcemap_Proj::HALF_PI)  <= Sourcemap_Proj::EPSLN) {
            throw new Exception('Lat/Long at poles.');
        } else {
            if($this->_proj->sphere) {
                $x = $this->_proj->x0 + $this->_proj->a * $this->_proj->k0 * Sourcemap_Proj::adjust_lon($lon - $this->_proj->long0);
                $y = $this->_proj->y0 + $this->_proj->a * $this->_proj->k0 * log(tan(Sourcemap_Proj::FORTPI + 0.5*$lat));
            } else {
                $sinphi = sin($lat);
                $ts = Sourcemap_Proj::tsfnz($this->_proj->e, $lat, $sinphi);
                $x = $this->_proj->x0 + $this->_proj->a * $this->_proj->k0 * Sourcemap_Proj::adjust_lon($lon - $this->_proj->long0);
                $y = $this->_proj->y0 - $this->_proj->a * $this->_proj->k0 * log($ts);
            }
            $pt->x = $x; 
            $pt->y = $y;
            return $pt;
        }
    }


  /* Mercator inverse equations--mapping x,y to lat/long
  --------------------------------------------------*/
    public function inverse($pt) {	

        $x = $pt->x - $this->_proj->x0;
        $y = $pt->y - $this->_proj->y0;

        if($this->_proj->sphere) {
            $lat = Sourcemap_Proj::HALF_PI - 2.0 * atan(exp(-$y / $this->_proj->a * $this->_proj->k0));
        } else {
            $ts = exp(-$y / ($this->_proj->a * $this->_proj->k0));
            $lat = Sourcemap_Proj::phi2z($this->_proj->e, $ts);
            if($lat == -9999) {
                throw new Exception("Lat = -9999");
            }
        }
        $lon = Sourcemap_Proj::adjust_lon($this->_proj->long0 + $x / ($this->_proj->a * $this->_proj->k0));

        $pt->x = $lon;
        $pt->y = $lat;
        return $pt;
    }
}


