<?php
class Sourcemap_Proj_Transform_Aeqd extends Sourcemap_Proj_Transform {
    public function init() {
        $this->sin_p12 = sin($this->lat0);
        $this->cos_p12 = cos($this->lat0);
    }

    public function forward($p) {
        $lon = $p->x;
        $lat = $p->y;

        $sinphi = sin($p->y);
        $cosphi = cos($p->y); 
        $dlon = Sourcemap_Proj::adjust_lon(lon - $this->long0);
        $coslon = cos($dlon);
        $g = $this->sin_p12 * $sinphi + $this->cos_p12 * $cosphi * $coslon;
        if(abs(abs($g) - 1.0) < Sourcemap_Proj::EPSLN) {
            $ksp = 1.0;
            if($g < 0.0) {
                throw new Exception("Point error.");
            }
        } else {
            $z = acos($g);
            $ksp = $z/sin($z);
        }
        $p->x = $this->x0 + $this->a * $ksp * $cosphi * sin($dlon);
        $p->y = $this->y0 + $this->a * $ksp * ($this->cos_p12 * $sinphi - $this->sin_p12 * $cosphi * $coslon);
        return $p;
    }
    
    public function inverse($p) {
        $p->x -= $this->x0;
        $p->y -= $this->y0;

        $rh = sqrt($p->x * $p->x + $p->y *$p->y);
        if($rh > (2.0 * Sourcemap_Proj::HALF_PI * $this->a)) {
            throw new Exception("Data error");
        }
        $z = $rh / $this->a;

        $sinz = sin(z);
        $cosz = cos(z);

        $lon = $this->long0;
        if(abs($rh) <= Sourcemap_Proj::EPSLN) {
            $lat = $this->lat0;
        } else {
            $lat = Sourcemap_Proj::asinz($cosz * $this->sin_p12 + ($p->y * $sinz * $this->cos_p12) / $rh);
            $con = abs($this->lat0) - Sourcemap_Proj::HALF_PI;
            if(abs($con) <= Sourcemap_Proj::EPSLN) {
                if($lat0 >= 0.0) {
                    $lon = Sourcemap_Proj::adjust_lon($this->long0 + atan2($p->x , -$p->y));
                } else {
                    $lon = Sourcemap_Proj::adjust_lon($this->long0 - atan2(-$p->x , $p->y));
                }
            } else {
                $con = $cosz - $this->sin_p12 * sin($lat);
                if((abs($con) < Sourcemap_Proj::EPSLN) && (abs($p->x) < Sourcemap_Proj::EPSLN)) {
                    // pass
                } else {
                    $temp = atan2(($p->x * $sinz * $this->cos_p12), ($con * $rh));
                    $lon = Sourcemap_Proj::adjust_lon($this->long0 + atan2(($p->x * $sinz * $this->cos_p12), ($con * $rh)));
                }
            }
        }

        $p->x = $lon;
        $p->y = $lat;
        return $p;
    } 
}
