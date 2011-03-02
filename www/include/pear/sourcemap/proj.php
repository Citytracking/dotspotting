<?php
class Sourcemap_Proj {

    const PI = 3.141592653589793238; //Math.PI;
    const HALF_PI = 1.570796326794896619; //Math.PI*0.5;
    const TWO_PI = 6.283185307179586477; //Math.PI*2;
    const FORTPI = 0.78539816339744833;
    const R2D = 57.29577951308232088;
    const D2R = 0.01745329251994329577;
    const SEC_TO_RAD = 4.84813681109535993589914102357e-6;
    const EPSLN = 1.0e-10;
    const MAX_ITER = 20;
    // following constants from geocent.c
    const COS_67P5 = 0.38268343236508977;
    const AD_C = 1.0026000;

    /* datum_type values */
    const PJD_UNKNOWN = 0;
    const PJD_3PARAM = 1;
    const PJD_7PARAM = 2;
    const PJD_GRIDSHIFT = 3;
    const PJD_WGS84 = 4;
    const PJD_NODATUM = 5;
    const SRS_WGS84_SEMIMAJOR = 6378137.0;

    // ellipsoid pj_set_ell.c
    const SIXTH = .1666666666666666667;
    const RA4 = .04722222222222222222;
    const RA6 = .02215608465608465608;
    const RV4 = .06944444444444444444;
    const RV6 = .04243827160493827160;

    public static $default_datum;

    public static $prime_meridian = array(
        "greenwich" => 0.0,               //"0dE",
        "lisbon" => -9.131906111111,   //"9d07'54.862\"W",
        "paris" => 2.337229166667,   //"2d20'14.025\"E",
        "bogota" => -74.080916666667,  //"74d04'51.3\"W",
        "madrid" => -3.687938888889,  //"3d41'16.58\"W",
        "rome" => 12.452333333333,  //"12d27'8.4\"E",
        "bern" => 7.439583333333,  //"7d26'22.5\"E",
        "jakarta" => 106.807719444444,  //"106d48'27.79\"E",
        "ferro" => -17.666666666667,  //"17d40'W",
        "brussels" => 4.367975,        //"4d22'4.71\"E",
        "stockholm" => 18.058277777778,  //"18d3'29.8\"E",
        "athens" => 23.7163375,       //"23d42'58.815\"E",
        "oslo" => 10.722916666667   //"10d43'22.5\"E"
    );

    public static $ellipsoid = array(
        "MERIT" => array('a' => 6378137.0, 'rf' => 298.257, 'ellipseName' => "MERIT 1983"),
        "SGS85" => array('a' => 6378136.0, 'rf' => 298.257, 'ellipseName' => "Soviet Geodetic System 85"),
        "GRS80" => array('a' => 6378137.0, 'rf' => 298.257222101, 'ellipseName' => "GRS 1980(IUGG, 1980)"),
        "IAU76" => array('a' => 6378140.0, 'rf' => 298.257, 'ellipseName' => "IAU 1976"),
        "airy" => array('a' => 6377563.396, 'b' => 6356256.910, 'ellipseName' => "Airy 1830"),
        "APL4." => array('a' => 6378137, 'rf' => 298.25, 'ellipseName' => "Appl. Physics. 1965"),
        "NWL9D" => array('a' => 6378145.0, 'rf' => 298.25, 'ellipseName' => "Naval Weapons Lab., 1965"),
        "mod_airy" => array('a' => 6377340.189, 'b' => 6356034.446, 'ellipseName' => "Modified Airy"),
        "andrae" => array('a' => 6377104.43, 'rf' => 300.0, 'ellipseName' => "Andrae 1876 (Den., Iclnd.)"),
        "aust_SA" => array('a' => 6378160.0, 'rf' => 298.25, 'ellipseName' => "Australian Natl & S. Amer. 1969"),
        "GRS67" => array('a' => 6378160.0, 'rf' => 298.2471674270, 'ellipseName' => "GRS 67(IUGG 1967)"),
        "bessel" => array('a' => 6377397.155, 'rf' => 299.1528128, 'ellipseName' => "Bessel 1841"),
        "bess_nam" => array('a' => 6377483.865, 'rf' => 299.1528128, 'ellipseName' => "Bessel 1841 (Namibia)"),
        "clrk66" => array('a' => 6378206.4, 'b' => 6356583.8, 'ellipseName' => "Clarke 1866"),
        "clrk80" => array('a' => 6378249.145, 'rf' => 293.4663, 'ellipseName' => "Clarke 1880 mod."),
        "CPM" => array('a' => 6375738.7, 'rf' => 334.29, 'ellipseName' => "Comm. des Poids et Mesures 1799"),
        "delmbr" => array('a' => 6376428.0, 'rf' => 311.5, 'ellipseName' => "Delambre 1810 (Belgium)"),
        "engelis" => array('a' => 6378136.05, 'rf' => 298.2566, 'ellipseName' => "Engelis 1985"),
        "evrst30" => array('a' => 6377276.345, 'rf' => 300.8017, 'ellipseName' => "Everest 1830"),
        "evrst48" => array('a' => 6377304.063, 'rf' => 300.8017, 'ellipseName' => "Everest 1948"),
        "evrst56" => array('a' => 6377301.243, 'rf' => 300.8017, 'ellipseName' => "Everest 1956"),
        "evrst69" => array('a' => 6377295.664, 'rf' => 300.8017, 'ellipseName' => "Everest 1969"),
        "evrstSS" => array('a' => 6377298.556, 'rf' => 300.8017, 'ellipseName' => "Everest (Sabah & Sarawak)"),
        "fschr60" => array('a' => 6378166.0, 'rf' => 298.3, 'ellipseName' => "Fischer (Mercury Datum) 1960"),
        "fschr60m" => array('a' => 6378155.0, 'rf' => 298.3, 'ellipseName' => "Fischer 1960"),
        "fschr68" => array('a' => 6378150.0, 'rf' => 298.3, 'ellipseName' => "Fischer 1968"),
        "helmert" => array('a' => 6378200.0, 'rf' => 298.3, 'ellipseName' => "Helmert 1906"),
        "hough" => array('a' => 6378270.0, 'rf' => 297.0, 'ellipseName' => "Hough"),
        "intl" => array('a' => 6378388.0, 'rf' => 297.0, 'ellipseName' => "International 1909 (Hayford)"),
        "kaula" => array('a' => 6378163.0, 'rf' => 298.24, 'ellipseName' => "Kaula 1961"),
        "lerch" => array('a' => 6378139.0, 'rf' => 298.257, 'ellipseName' => "Lerch 1979"),
        "mprts" => array('a' => 6397300.0, 'rf' => 191.0, 'ellipseName' => "Maupertius 1738"),
        "new_intl" => array('a' => 6378157.5, 'b' => 6356772.2, 'ellipseName' => "New International 1967"),
        "plessis" => array('a' => 6376523.0, 'rf' => 6355863.0, 'ellipseName' => "Plessis 1817 (France)"),
        "krass" => array('a' => 6378245.0, 'rf' => 298.3, 'ellipseName' => "Krassovsky, 1942"),
        "SEasia" => array('a' => 6378155.0, 'b' => 6356773.3205, 'ellipseName' => "Southeast Asia"),
        "walbeck" => array('a' => 6376896.0, 'b' => 6355834.8467, 'ellipseName' => "Walbeck"),
        "WGS60" => array('a' => 6378165.0, 'rf' => 298.3, 'ellipseName' => "WGS 60"),
        "WGS66" => array('a' => 6378145.0, 'rf' => 298.25, 'ellipseName' => "WGS 66"),
        "WGS72" => array('a' => 6378135.0, 'rf' => 298.26, 'ellipseName' => "WGS 72"),
        "WGS84" => array('a' => 6378137.0, 'rf' => 298.257223563, 'ellipseName' => "WGS 84"),
        "sphere" => array('a' => 6370997.0, 'b' => 6370997.0, 'ellipseName' => "Normal Sphere (r=6370997)")
    );

    public static $datum = array(
        "WGS84" => array('towgs84' => "0,0,0", 'ellipse' => "WGS84", 'datumName' => "WGS84"),
        "GGRS87" => array('towgs84' => "-199.87,74.79,246.62", 'ellipse' => "GRS80", 'datumName' => "Greek_Geodetic_Reference_System_1987"),
        "NAD83" => array('towgs84' => "0,0,0", 'ellipse' => "GRS80", 'datumName' => "North_American_Datum_1983"),
        "NAD27" => array('nadgrids' => "@conus,@alaska,@ntv2_0.gsb,@ntv1_can.dat", 'ellipse' => "clrk66", 'datumName' => "North_American_Datum_1927"),
        "potsdam" => array('towgs84' => "606.0,23.0,413.0", 'ellipse' => "bessel", 'datumName' => "Potsdam Rauenberg 1950 DHDN"),
        "carthage" => array('towgs84' => "-263.0,6.0,431.0", 'ellipse' => "clark80", 'datumName' => "Carthage 1934 Tunisia"),
        "hermannskogel" => array('towgs84' => "653.0,-212.0,449.0", 'ellipse' => "bessel", 'datumName' => "Hermannskogel"),
        "ire65" => array('towgs84' => "482.530,-130.596,564.557,-1.042,-0.214,-0.631,8.15", 'ellipse' => "mod_airy", 'datumName' => "Ireland 1965"),
        "nzgd49" => array('towgs84' => "59.47,-5.04,187.44,0.47,-0.1,1.024,-4.5993", 'ellipse' => "intl", 'datumName' => "New Zealand Geodetic Datum 1949"),
        "OSGB36" => array('towgs84' => "446.448,-125.157,542.060,0.1502,0.2470,0.8421,-20.4894", 'ellipse' => "airy", 'datumName' => "Airy 1830")
    );

    public static $defs = array(
        'WGS84' => "+title=long/lat:WGS84 +proj=longlat +ellps=WGS84 
            +datum=WGS84 +units=degrees",
        'EPSG:4326' => "+title=long/lat:WGS84 +proj=longlat +a=6378137.0 
            +b=6356752.31424518 +ellps=WGS84 +datum=WGS84 +units=degrees",
        'EPSG:4269' => "+title=long/lat:NAD83 +proj=longlat +a=6378137.0 
            +b=6356752.31414036 +ellps=GRS80 +datum=NAD83 +units=degrees",
        'EPSG:3785' => "+title= Google Mercator +proj=merc +a=6378137 
            +b=6378137 +lat_ts=0.0 +lon_0=0.0 +x_0=0.0 +y_0=0 +k=1.0 +units=m +nadgrids=@null +no_defs",
        'EPSG:3857' => "+title= Google Mercator +proj=merc +a=6378137 
            +b=6378137 +lat_ts=0.0 +lon_0=0.0 +x_0=0.0 +y_0=0 +k=1.0 +units=m +nadgrids=@null +no_defs",
        'GOOGLE' => "+title= Google Mercator +proj=merc +a=6378137 
            +b=6378137 +lat_ts=0.0 +lon_0=0.0 +x_0=0.0 +y_0=0 +k=1.0 +units=m +nadgrids=@null +no_defs",
        'EPSG:900913' => "+title= Google Mercator +proj=merc +a=6378137 
            +b=6378137 +lat_ts=0.0 +lon_0=0.0 +x_0=0.0 +y_0=0 +k=1.0 +units=m +nadgrids=@null +no_defs",
        'EPSG:102113' => "+title= Google Mercator +proj=merc +a=6378137 
            +b=6378137 +lat_ts=0.0 +lon_0=0.0 +x_0=0.0 +y_0=0 +k=1.0 +units=m +nadgrids=@null +no_defs"
    );
    
    // Function to compute the constant small m which is the radius of
    //   a parallel of latitude, phi, divided by the semimajor axis.
    // -----------------------------------------------------------------
    public static function msfnz($eccent, $sinphi, $cosphi) {
        $con = $eccent * $sinphi;
        return $cosphi/(sqrt(1.0 - $con * $con));
    }

    // Function to compute the constant small t for use in the forward
    //   computations in the Lambert Conformal Conic and the Polar
    //   Stereographic projections.
    // -----------------------------------------------------------------
    public static function tsfnz($eccent, $phi, $sinphi) {
        $con = $eccent * $sinphi;
        $com = .5 * $eccent;
        $con = pow(((1.0 - $con) / (1.0 + $con)), $com);
        return (tan(.5 * (self::HALF_PI - $phi))/$con);
    }

    // Function to compute the latitude angle, phi2, for the inverse of the
    //   Lambert Conformal Conic and Polar Stereographic projections.
    // ----------------------------------------------------------------
    public static function phi2z($eccent, $ts) {
        $eccnth = .5 * $eccent;
        $phi = (self::HALF_PI - 2) * atan($ts);
        for($i = 0; $i <= 15; $i++) {
          $con = $eccent * sin($phi);
          $dphi = self::HALF_PI - 2 * atan($ts * pow(((1.0 - $con)/(1.0 + $con)), $eccnth)) - $phi;
          $phi += $dphi;
          if (abs($dphi) <= .0000000001) return $phi;
        }
        throw new Exception("phi2z has NoConvergence");
        return (-9999);
    }

    /* Function to compute constant small q which is the radius of a 
       parallel of latitude, phi, divided by the semimajor axis. 
    ------------------------------------------------------------*/
    public static function qsfnz($eccent,$sinphi) {
        if ($eccent > 1.0e-7) {
          $con = $eccent * $sinphi;
          return (( 1.0- $eccent * $eccent) * ($sinphi /(1.0 - $con * $con) - (.5/$eccent)*log((1.0 - $con)/(1.0 + $con))));
        } else {
          return(2.0 * $sinphi);
        }
    }

    /* Function to eliminate roundoff errors in asin
    ----------------------------------------------*/
    public static function asinz($x) {
        if(abs($x) > 1.0) {
            $x = $x > 1.0 ? 1.0 : -1.0;
        }
        return asin($x);
    }

    // following functions from gctpc cproj.c for transverse mercator projections
    public static function e0fn($x) {
        return 1.0 - 0.25 * $x * (1.0 + $x/16.0 * (3.0 + 1.25 * $x));
    }
  
    public static function e1fn($x) {
        return 0.375 * $x * (1.0 + 0.25 * $x * (1.0 + 0.46875 * $x));
    }

    public static function e2fn($x) {
        return 0.05859375 * $x * $x * (1.0 + 0.75 * $x);
    }

    public static function e3fn($x) {
        return($x * $x * $x *(35.0/3072.0));
    }

    public static function mlfn($e0, $e1, $e2, $e3, $phi) {
        return $e0 * $phi - $e1 * sin(2.0 * $phi) + $e2 * sin(4.0 * $phi) - $e3 * sin(6.0 * $phi);
    }

    public static function srat($esinp, $exp) {
        return pow((1.0 - $esinp)/(1.0 + $esinp), $exp);
    }

    // Function to return the sign of an argument
    public static function sign($x) {
        return $x < 0.0 ? -1 : 1;
    }

    // Function to adjust longitude to -180 to 180; input in radians
    public static function adjust_lon($x) {
        $x = (abs($x) < self::PI) ? $x : ($x - (self::sign($x)*self::TWO_PI));
        return $x;
    }

    // IGNF - DGR : algorithms used by IGN France

    // Function to adjust latitude to -90 to 90; input in radians
    public static function adjust_lat($x) {
        $x = (abs($x) < self::HALF_PI) ? $x : ($x - (self::sign($x)*self::PI));
        return $x;
    }

    // Latitude Isometrique - close to tsfnz ...
    public static function latiso($eccent, $phi, $sinphi) {
        if(abs($phi) > self::HALF_PI) return NAN;
        if($phi == self::HALF_PI) return INF;
        if($phi == -1.0 * self::HALF_PI) return -INF;

        $con = $eccent * $sinphi;
        return log(tan((self::HALF_PI + $phi) / 2.0)) + $eccent * log((1.0 - $con) / (1.0 + $con)) / 2.0;
    }

    public static function fL($x, $L) {
        return 2.0 * atan($x * exp($L)) - self::HALF_PI;
    }

    // Inverse Latitude Isometrique - close to ph2z
    public static function invlatiso($eccent, $ts) {
        $phi = self::fL(1.0, $ts);
        $Iphi = 0.0;
        $con = 0.0;
        do {
          $Iphi = $phi;
          $con = $eccent * sin($Iphi);
          $phi = self::fL(exp($eccent * log((1.0 + $con) / (1.0 - $con)) / 2.0), $ts);
        } while(abs($phi - $Iphi) > 1.0e-12);
        return $phi;
    }

    // Grande Normale
    public static function gN($a, $e, $sinphi) {
        $temp = $e * $sinphi;
        return $a / sqrt(1.0 - $temp * $temp);
    }

    public static function transform($src, $dest, $pt) {
        if(!($src instanceof Sourcemap_Proj_Projection))
            $src = new Sourcemap_Proj_Projection($src);
        if(!($dest instanceof Sourcemap_Proj_Projection))
            $dest = new Sourcemap_Proj_Projection($dest);
        // Workaround for Spherical Mercator
        if(($src->srs_projnum == "900913" && $dest->datum_code != "WGS84") ||
            ($dest->srs_projnum == "900913" && $src->datum_code != "WGS84")) {
            $wgs84 = new Sourcemap_Proj_Projection('WGS84');
            self::transform($src, $wgs84, $pt);
            $src = wgs84;
        }

        // Transform source points to long/lat, if they aren't already.
        if($src->proj_name == "longlat") {
            $pt->x *= self::D2R;  // convert degrees to radians
            $pt->y *= self::D2R;
        } else {
            if(isset($src->to_meter) && $src->to_meter) {
                $pt->x *= $src->to_meter;
                $pt->y *= $src->to_meter;
            }
            $src->inverse($pt); // Convert Cartesian to longlat
        }

        // Adjust for the prime meridian if necessary
        if(isset($src->from_greenwich) && $src->from_greenwich) { 
            $pt->x += $src->from_greenwich; 
        }

        // Convert datums if needed, and if possible.
        $pt = self::datum_transform($src->datum, $dest->datum, $pt );

        // Adjust for the prime meridian if necessary
        if(isset($dest->from_greenwich) && $dest->from_greenwich) {
            $pt->x -= $dest->from_greenwich;
        }

        if($dest->proj_name == "longlat") {
            // convert radians to decimal degrees
            $pt->x *= self::R2D;
            $pt->y *= self::R2D;
        } else  { // else project
            $dest->forward($pt);
            if($dest->to_meter) {
                $pt->x /= $dest->to_meter;
                $pt->y /= $dest->to_meter;
            }
        }
        return $pt;
    }

    public static function datum_transform(Sourcemap_Proj_Datum $src, Sourcemap_Proj_Datum $dest, $pt) {
        // Short cut if the datums are identical.
        if(Sourcemap_Proj_Datum::cmp($src, $dest)) {
            return $pt;
        }

        // Explicitly skip datum transform by setting 'datum=none' as parameter for either source or dest
        if($src->datum_type == self::PJD_NODATUM
            || $dest->datum_type == self::PJD_NODATUM) {
            return $pt;
        }

        // If this datum requires grid shifts, then apply it to geodetic coordinates.
        if($src->datum_type == self::PJD_GRIDSHIFT ) {
            throw new Exception('Gridshift not implemented.');
        }

        if($dest->datum_type == self::PJD_GRIDSHIFT ) {
            throw new Exception('Gridshift not implemented.');
        }

        // Do we need to go through geocentric coordinates?
        if($src->es != $dest->es || $src->a != $dest->a
          || $src->datum_type == self::PJD_3PARAM
          || $src->datum_type == self::PJD_7PARAM
          || $dest->datum_type == self::PJD_3PARAM
          || $dest->datum_type == self::PJD_7PARAM){

            // Convert to geocentric coordinates.
            $src->geodetic_to_geocentric($pt);

            // Convert between datums
            if($src->datum_type == self::PJD_3PARAM || $src->datum_type == self::PJD_7PARAM) {
              $src->geocentric_to_wgs84($pt);
            }

            if( $dest->datum_type == self::PJD_3PARAM || $dest->datum_type == self::PJD_7PARAM) {
              $dest->geocentric_from_wgs84($pt);
            }

            // Convert back to geodetic coordinates
            $dest->geocentric_to_geodetic($pt);
        }

        // Apply grid shift to destination if required
        if( $dest->datum_type == self::PJD_GRIDSHIFT) {
            throw new Exception('Grid shift not implemented.');
        }
        return $pt;

    }
}
