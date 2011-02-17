<?php
class Sourcemap_Proj_Projection {
    public $title = null;
    public $proj_name = null;
    public $units = null;
    public $datum = null;
    public $x0 = 0;
    public $y0 = 0;

    public $srs_code = null;
    public $srs_auth = null;
    public $srs_projnum = null;

    public $lat_ts = null;
    public $lat2 = null;
    public $lat1 = null;
    public $lat0 = null;
    public $k0 = null;
    public $to_meter = null;
    public $sphere = null;

    public function __construct($srs_code) {
        $this->setSrsCode($srs_code);
        $def = self::load_proj_def($srs_code);
        if(!$def) throw new Exception('Def not found for proj "'.$srs_code.'".');
        $this->init(self::parse_def($def));
        $this->transformer = self::load_proj_transform($this->proj_name);
    }

    public function load_proj_def($srs_code) {
        $c = 'Sourcemap_Proj_Def_'.ucfirst(preg_replace('/\W+/', '', $srs_code));
        $rc = new ReflectionClass($c);
        return $rc->getStaticPropertyValue('def_data');
    }

    public function load_proj_transform($proj_name) {
        if($proj_name == 'longlat') $c = 'Sourcemap_Proj_Transform';
        else $c = 'Sourcemap_Proj_Transform_'.ucfirst($proj_name);
        $rc = new ReflectionClass($c);
        $transform = $rc->newInstance($this);
        return $transform;
    }

    public function init($params) {
        foreach($params as $k => $v) $this->{$k} = $v;
        // derive constants.
        if(isset($this->nagrids) && $this->nagrids == '@null') $this->datum_code = 'none';
        if(isset($this->datum_code) && $this->datum_code && $this->datum_code != 'none') {
            $datum_def = Sourcemap_Proj::$datum[$this->datum_code];
            if($datum_def) {
                $this->datum_params = $datum_def['towgs84'] ? explode(',', $datum_def['towgs84']) : null;
                $this->ellps = $datum_def['ellipse'];
                $this->datum_name = $datum_def['datumName'] ? $datum_def['datumName'] : $this->datum_code;
            }
        }
        if(!isset($this->a) || !$this->a) {    // do we have an ellipsoid?
            $ellipse = Sourcemap_Proj::$ellipsoid[$this->ellps] ? Sourcemap_Proj::$ellipsoid[$this->ellps] : Sourcemap_Proj::$ellipsoid['WGS84'];
            foreach($ellipse as $k => $v)
            $this->{$k} = $v;
        }
        if((isset($this->rf) && $this->rf) && (!isset($this->b) || !$this->b)) 
            $this->b = (1.0 - 1.0/$this->rf) * $this->a;
        if(abs($this->a - $this->b) < Sourcemap_Proj::EPSLN) {
            $this->sphere = true;
            $this->b = $this->a;
        }
        $this->a2 = $this->a * $this->a;          // used in geocentric
        $this->b2 = $this->b * $this->b;          // used in geocentric
        $this->es = ($this->a2 - $this->b2) / $this->a2;  // e ^ 2
        $this->e = sqrt($this->es);        // eccentricity
        if(isset($this->R_A) && $this->R_A) {
            $this->a *= 1.0 - $this->es * (Sourcemap_Proj::SIXTH + $this->es * (Sourcemap_Proj::RA4 + $this->es * Sourcemap_Proj::RA6));
            $this->a2 = $this->a * $this->a;
            $this->b2 = $this->b * $this->b;
            $this->es = 0.0;
        }
        $this->ep2 = ($this->a2 - $this->b2) / $this->b2; // used in geocentric
        if(!isset($this->k0) || !$this->k0) $this->k0 = 1.0;    //default value

        $this->datum = new Sourcemap_Proj_Datum($this);

        return $this;
    }

    public function setSrsCode($srs_code) {
        list($code, $auth, $projnum) = self::parse_srs_code($srs_code);
        $this->srs_code = $code;
        $this->srs_auth = $auth;
        $this->srs_projnum = $projnum;
        return $this;
    }

    public static function parse_srs_code($srs_code) {
        $parsed = array();
        if(strpos($srs_code, 'EPSG') === 0) {
            $parsed[] = $srs_code;
            $parsed[] = 'epsg';
            $parsed[] = substr($srs_code, 5);
        } elseif(strpos($srs_code, 'IGNF') === 0) {
            $parsed[] = $srs_code;
            $parsed[] = 'IGNF';
            $parsed[] = substr($srs_code, 5);
        } elseif(strpos($srs_code, 'CRS') === 0) {
            $parsed[] = $srs_code;
            $parsed[] = 'CRS';
            $parsed[] = substr($srs_code, 4);
        } else {
            $parsed[] = null;
            $parsed[] = '';
            $parsed[] = $srs_code;
        }
        return $parsed;
    }
    
    public function parse_def($def) {
        if(!$def) return $def;
        $params = explode('+', $def);
        $parsed = array();
        for($pi=0; $pi<count($params); $pi++) {
            if(!$params[$pi]) continue;
            if(strstr($params[$pi], '=')) {
                list($pkey, $pval) = explode('=', $params[$pi]);
                $pkey = strtolower(trim($pkey));
                $pval = trim($pval);
            } else {
                $pkey = $params[$pi];
                $pval = null;
            }
            switch($pkey) {
                case '';
                    break;
                case 'x_0':
                case 'y_0':
                case 'k_0':
                case 'k':
                    $pkey = substr($pkey, 0, 1).'0';
                case 'a':
                case 'b':
                case 'rf':
                case 'to_meter':
                    $parsed[$pkey] = (float)$pval;
                    break;
                case 'lat_0':
                case 'lat_1':
                case 'lat_2':
                    $parsed[strtr($pkey, '_', '')] = (float)$pval * Sourcemap_Proj::D2R;
                    break;
                case 'lat_ts':
                case 'lon_0':
                    $parsed['long0'] = (float)$pval * Sourcemap_Proj::D2R;
                    break;
                case 'lonc':
                    $pkey = 'longc';
                case 'alpha':
                case 'from_greenwich':
                    $parsed[$pkey] = (float)$pval * Sourcemap_Proj::D2R;
                    break;
                case 'proj':
                    $parsed['proj_name'] = $pval;
                    break;
                case 'datum':
                    $parsed['datum_code'] = $pval;
                    break;
                case 'no_defs':
                    break;
                case 'zone':
                    $parsed[$pkey] = (int)$pval;
                    break;
                case 'south':
                    $parsed['utm_south'] = true;
                    break;
                case 'towgs84':
                    $parsed['datum_params'] = explode(',', $pval);
                default:
                    $parsed[$pkey] = $pval;
                    break;
            }
        }
        return $parsed;
    }

    public function forward($pt) {
        return $this->transformer->forward($pt);
    }

    public function inverse($pt) {
        return $this->transformer->inverse($pt);
    }
}
