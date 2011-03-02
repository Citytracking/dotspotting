<?php
class Sourcemap_Proj_Datum {
    public $datum_type;

    public function __construct(Sourcemap_Proj_Projection $proj) {
        $this->datum_type = Sourcemap_Proj::PJD_WGS84;   //default setting
        if((isset($proj->datum_code) && $proj->datum_code === null) || !isset($proj->datum_code)) {
            $this->datum_type = Sourcemap_Proj::PJD_NODATUM;
        }
        if(isset($proj->datum_params) && $proj->datum_params) {
            for($i=0; $i<count($proj->datum_params); $i++) {
                $proj->datum_params[$i] = (float)$proj->datum_params[$i];
            }
            if(count($proj->datum_params) <= 3 && (
                $proj->datum_params[0] != 0 || $proj->datum_params[1] != 0 ||
                $proj->datum_params[2] != 0)) {
                $this->datum_type = Sourcemap_Proj::PJD_3PARAM;
            }
            if(count($proj->datum_params) > 3) {
                if($proj->datum_params[3] != 0 || $proj->datum_params[4] != 0 ||
                    $proj->datum_params[5] != 0 || $proj->datum_params[6] != 0) {
                    $this->datum_type = Sourcemap_Proj::PJD_7PARAM;
                    $proj->datum_params[3] *= Sourcemap_Proj::SEC_TO_RAD;
                    $proj->datum_params[4] *= Sourcemap_Proj::SEC_TO_RAD;
                    $proj->datum_params[5] *= Sourcemap_Proj::SEC_TO_RAD;
                    $proj->datum_params[6] = ($proj->datum_params[6]/1000000.0) + 1.0;
                }
            }
        }
        if($proj) {
            $this->a = $proj->a;    //datum object also uses these values
            $this->b = $proj->b;
            $this->es = $proj->es;
            $this->ep2 = $proj->ep2;
            $this->datum_params = isset($proj->datum_params) ? $proj->datum_params : null;
        }
    }

    public static function cmp(Sourcemap_Proj_Datum $a, Sourcemap_Proj_Datum $b) {
        if($a->datum_type != $b->datum_type) {
            return false; // false, datums are not equal
        } elseif($a->a != $b->a || abs($a->es - $b->es) > 0.000000000050) {
            // the tolerence for es is to ensure that GRS80 and WGS84
            // are considered identical
            return false;
        } elseif($a->datum_type == Sourcemap_Proj::PJD_3PARAM) {
            $eq = true;
            for($i=0; $i<3; $i++) {
                if($a->datum_params[$i] != $b->datum_params[$i]) {
                    $eq = false;
                }
            }
            return $eq;
        } elseif($a->datum_type == Sourcemap_Proj::PJD_7PARAM) {
            $eq = true;
            for($i=0; $i<7; $i++) {
                if($a->datum_params[$i] != $b->datum_params[$i]) {
                    $eq = false;
                    break;
                }
            }
            return $eq;
        } elseif($a->datum_type == Sourcemap_Proj::PJD_GRIDSHIFT) {
            throw new Exception('Gridshift not implemented.');
        } else {
            return true; // datums are equal
        }
    }

    public function geodetic_to_geocentric($pt) {
        $longitude = (float)$pt->x;
        $latitude = (float)$pt->y;
        $height = $pt->z ? (float)$pt->z : 0;   //Z value not always supplied
        /*
         ** Don't blow up if Latitude is just a little out of the value
         ** range as it may just be a rounding issue.  Also removed longitude
         ** test, it should be wrapped by Math.cos() and Math.sin().  NFW for PROJ.4, Sep/2001.
         */
        if($latitude < -Sourcemap_Proj::HALF_PI && $latitude > -1.001 * Sourcemap_Proj::HALF_PI ) {
            $latitude = -Sourcemap_Proj::HALF_PI;
        } elseif( $latitude > Sourcemap_Proj::HALF_PI && $latitude < 1.001 * Sourcemap_Proj::HALF_PI ) {
            $latitude = Sourcemap_Proj::HALF_PI;
        } elseif(($latitude < -Sourcemap_Proj::HALF_PI) || ($latitude > Sourcemap_Proj::HALF_PI)) {
            /* $latitude out of range */
            throw new Exception('Latitude out of range: '.$latitude);
        }

        if ($longitude > Sourcemap_Proj::PI) $longitude -= (2*Sourcemap_Proj::PI);
        $sin_lat = sin($latitude);
        $cos_lat = cos($latitude);
        $sin2_lat = $sin_lat * $sin_lat;
        $Rn = $this->a / (sqrt(1.0e0 - $this->es * $sin2_lat));
        $X = ($Rn + $height) * $cos_lat * cos($longitude);
        $Y = ($Rn + $height) * $cos_lat * sin($longitude);
        $Z = (($Rn * (1 - $this->es)) + $height) * $sin_lat;

        $pt->x = $X;
        $pt->y = $Y;
        $pt->z = $Z;
        return $this;
    } // cs_geodetic_to_geocentric()


    public function geocentric_to_geodetic($pt) {
        /* local defintions and variables */
        /* end-criterium of loop, accuracy of sin(Latitude) */
        $genau = 1.E-12;
        $genau2 = ($genau*$genau);
        $maxiter = 30;

#        $P;        /* distance between semi-minor axis and location */
#        $RR;       /* distance between center and location */
#        $CT;       /* sin of geocentric latitude */
#        $ST;       /* cos of geocentric latitude */
#        $RX;
#        $RK;
#        $RN;       /* Earth radius at location */
#        $CPHI0;    /* cos of start or old geodetic latitude in iterations */
#        $SPHI0;    /* sin of start or old geodetic latitude in iterations */
#        $CPHI;     /* cos of searched geodetic latitude */
#        $SPHI;     /* sin of searched geodetic latitude */
#        $SDPHI;    /* end-criterium: addition-theorem of sin(Latitude(iter)-Latitude(iter-1)) */
#        $At_Pole;     /* indicates location is in polar region */
#        $iter;        /* # of continous iteration, max. 30 is always enough (s.a.) */

        $x = $pt->x;
        $y = $pt->y;
        $z = $pt->z ? $pt->z : 0.0;   //Z value not always supplied

        $at_pole = false;
        $p = sqrt($x*$x+$y*$y);
        $rr = sqrt($x*$x+$y*$y+$z*$z);

        /*      special cases for latitude and longitude */
        if ($p/$this->a < $genau) {

            /*  special case, if P=0. (X=0., Y=0.) */
            $at_pole = true;
            $longitude = 0.0;

            /*  if (X,Y,Z)=(0.,0.,0.) then Height becomes semi-minor axis
             *  of ellipsoid (=center of mass), Latitude becomes PI/2 */
            if ($rr/$this->a < $genau) {
                $latitude = Sourcemap_Proj::HALF_PI;
                $height = -$this->b;
                return;
            }
        } else {
            /*  ellipsoidal (geodetic) longitude
             *  interval: -PI < Longitude <= +PI */
            $longitude = atan2($y, $x);
        }

        /* --------------------------------------------------------------
         * Following iterative algorithm was developped by
         * "Institut für Erdmessung", University of Hannover, July 1988.
         * Internet: www.ife.uni-hannover.de
         * Iterative computation of CPHI,SPHI and Height.
         * Iteration of CPHI and SPHI to 10**-12 radian resp.
         * 2*10**-7 arcsec.
         * --------------------------------------------------------------
         */
        $ct = $z/$rr;
        $st = $p/$rr;
        $rx = 1.0/sqrt(1.0-$this->es*(2.0-$this->es)*$st*$st);
        $cphi0 = $st*(1.0-$this->es)*$rx;
        $sphi0 = $ct*$rx;
        $iter = 0;

        /* loop to find sin(Latitude) resp. Latitude
         * until |sin(Latitude(iter)-Latitude(iter-1))| < genau */
        do
        {
            $iter++;
            $rn = $this->a/sqrt(1.0-$this->es*$sphi0*$sphi0);

            /*  ellipsoidal (geodetic) height */
            $height = $p*$cphi0+$z*$sphi0-$rn*(1.0-$this->es*$sphi0*$sphi0);

            $rk = $this->es*$rn/($rn+$height);
            $rx = 1.0/sqrt(1.0-$rk*(2.0-$rk)*$st*$st);
            $cphi = $st*(1.0-$rk)*$rx;
            $sphi = $ct*$rx;
            $sdphi = $sphi*$cphi0-$cphi*$sphi0;
            $cphi0 = $cphi;
            $sphi0 = $sphi;
        }
        while ($sdphi*$sdphi > $genau2 && $iter < $maxiter);

        /*      ellipsoidal (geodetic) latitude */
        $latitude = atan($sphi/abs($cphi));

        $pt->x = $longitude;
        $pt->y = $latitude;
        $pt->z = $height;
        return $p;
  } // cs_geocentric_to_geodetic()

  /** Convert_Geocentric_To_Geodetic
   * The method used here is derived from 'An Improved Algorithm for
   * Geocentric to Geodetic Coordinate Conversion', by Ralph Toms, Feb 1996
   */
    public static function geocentric_to_geodetic_noniter($pt) {
        $x = $pt->x;
        $y = $pt->y;
        $z = $pt->z ? $pt->z : 0;   //Z value not always supplied
        
#        var W;        /* distance from Z axis */
#        var W2;       /* square of distance from Z axis */
#        var T0;       /* initial estimate of vertical component */
#        var T1;       /* corrected estimate of vertical component */
#        var S0;       /* initial estimate of horizontal component */
#        var S1;       /* corrected estimate of horizontal component */
#        var Sin_B0;   /* Math.sin(B0), B0 is estimate of Bowring aux variable */
#        var Sin3_B0;  /* cube of Math.sin(B0) */
#        var Cos_B0;   /* Math.cos(B0) */
#        var Sin_p1;   /* Math.sin(phi1), phi1 is estimated latitude */
#        var Cos_p1;   /* Math.cos(phi1) */
#        var Rn;       /* Earth radius at location */
#        var Sum;      /* numerator of Math.cos(phi1) */
#        var At_Pole;  /* indicates location is in polar region */

        $x = (float)$x;
        $y = (float)$y;
        $z = (float)$z;

        $at_pole = false;
        if($x != 0.0) {
            $longitude = atan2($y, $x);
        } else {
            if($y > 0) {
                $longitude = Sourcemap_Proj::HALF_PI;
            } else if ($y < 0) {
                $longitude = -Sourcemap_Proj::HALF_PI;
            } else {
                $at_pole = true;
                $longitude = 0.0;
                if($z > 0.0) {  /* north pole */
                    $latitude = Sourcemap_Proj::HALF_PI;
                } elseif($z < 0.0) {  /* south pole */
                    $latitude = -Sourcemap_Proj::HALF_PI;
                } else {  /* center of earth */
                    $latitude = Sourcemap_Proj::HALF_PI;
                    $height = -$this->b;
                    return;
                }
            }
        }
        $w2 = $x*$x + $y*$y;
        $w = sqrt($w2);
        $t0 = $z * Sourcemap_Proj::AD_C;
        $s0 = sqrt($t0 * $t0 + $w2);
        $sin_b0 = $t0 / $s0;
        $cos_b0 = $w / $s0;
        $sin3_b0 = $sin_b0 * $sin_b0 * $sin_b0;
        $t1 = $z + $this->b * $this->ep2 * $sin3_b0;
        $sum = $w - $this->a * $this->es * $cos_b0 * $cos_b0 * $cos_b0;
        $s1 = sqrt($t1*$t1 + $sum * $sum);
        $sin_p1 = $t1 / $s1;
        $cos_p1 = $sum / $s1;
        $rn = $this->a / sqrt(1.0 - $this->es * $sin_p1 * $sin_p1);
        if ($cos_p1 >= Sourcemap_Proj::COS_67P5) {
            $height = $w / $cos_p1 - $rn;
        } elseif($cos_p1 <= -Sourcemap_Proj::COS_67P5) {
            $height = $w / -$cos_p1 - $rn;
        } else {
            $height = $z / $sin_p1 + $rn * ($this->es - 1.0);
        } if ($at_pole == false) {
            $latitude = atan($sin_p1 / $cos_p1);
        }

        $pt->x = $longitude;
        $pt->y = $latitude;
        $pt->z = $height;
        return $p;
  } // geocentric_to_geodetic_noniter()

  /****************************************************************/
  // pj_geocentic_to_wgs84( p )
  //  p = point to transform in geocentric coordinates (x,y,z)
    public static function geocentric_to_wgs84($pt) {

        if($this->datum_type == Sourcemap_Proj::PJD_3PARAM) {
            $pt->x += $this->datum_params[0];
            $pt->y += $this->datum_params[1];
            $pt->z += $this->datum_params[2];

        } elseif($this->datum_type == Sourcemap_Proj::PJD_7PARAM) {
            $dx_bf = $this->datum_params[0];
            $dy_bf = $this->datum_params[1];
            $dz_bf = $this->datum_params[2];
            $rx_bf = $this->datum_params[3];
            $ry_bf = $this->datum_params[4];
            $rz_bf = $this->datum_params[5];
            $m_bf  = $this->datum_params[6];
            $x_out = $m_bf*($pt->x - $rz_bf*$pt->y + $ry_bf*$pt->z) + $dx_bf;
            $y_out = $m_bf*( $rz_bf*$pt->x + $pt->y - $rx_bf*$pt->z) + $dy_bf;
            $z_out = $m_bf*(-$ry_bf*$pt->x + $rx_bf*$pt->y + $pt->z) + $dz_bf;
            $pt->x = $x_out;
            $pt->y = $y_out;
            $pt->z = $z_out;
        }
  } // cs_geocentric_to_wgs84

  /****************************************************************/
  // pj_geocentic_from_wgs84()
  //  coordinate system definition,
  //  point to transform in geocentric coordinates (x,y,z)
    public static function geocentric_from_wgs84($pt) {

        if(this.datum_type == Sourcemap_Proj::PJD_3PARAM) {
            $pt->x -= $this->datum_params[0];
            $pt->y -= $this->datum_params[1];
            $pt->z -= $this->datum_params[2];

        } elseif($this->datum_type == Sourcemap_Proj::PJD_7PARAM) {
            $Dx_bf =$this->datum_params[0];
            $Dy_bf =$this->datum_params[1];
            $Dz_bf =$this->datum_params[2];
            $Rx_bf =$this->datum_params[3];
            $Ry_bf =$this->datum_params[4];
            $Rz_bf =$this->datum_params[5];
            $m_bf  =$this->datum_params[6];
            $x_tmp = ($pt->x - $Dx_bf) / $m_bf;
            $y_tmp = ($pt->y - $Dy_bf) / $m_bf;
            $z_tmp = ($pt->z - $Dz_bf) / $m_bf;

            $pt->x = $x_tmp + $Rz_bf*$y_tmp - $Ry_bf*$z_tmp;
            $pt->y = -$Rz_bf*$x_tmp + $y_tmp + $Rx_bf*$z_tmp;
            $pt->z =  $Ry_bf*$x_tmp - $Rx_bf*$y_tmp + $z_tmp;
    } //cs_geocentric_from_wgs84()
  }

}
