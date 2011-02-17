<?php
class Sourcemap_Proj_Transform_Gauss extends Sourcemap_Proj_Transform {

    public function init() {
        $sphi = sin($this->lat0);
        $cphi = cos($this->lat0);  
        $cphi *= $cphi;
        $this->rc = sqrt(1.0 - $this->es) / (1.0 - $this->es * $sphi * $sphi);
        $this->C = sqrt(1.0 + $this->es * $cphi * $cphi / (1.0 - $this->es));
        $this->phic0 = asin($sphi / $this->C);
        $this->ratexp = 0.5 * $this->C * $this->e;
        $this->K = tan(0.5 * $this->phic0 + Sourcemap_Proj::FORTPI) / (pow(tan(0.5*$this->lat0 + Sourcemap_Proj::FORTPI), $this->C) * Sourcemap_Proj::srat($this->e*sphi, $this->ratexp));
    }

    public function forward($p) {
        $lon = $p->x;
        $lat = $p->y;

        $p->y = 2.0 * atan( $this->K * pow(tan(0.5 * lat + Sourcemap_Proj::FORTPI), $this->C) * Sourcemap_Proj::srat($this->e * sin(lat), $this->ratexp) ) - Sourcemap_Proj::HALF_PI;
        $p->x = $this->C * lon;
        return $p;
    }

    public function inverse($p) {
        $DEL_TOL = 1e-14;
        $lon = $p->x / $this->C;
        $lat = $p->y;
        $num = pow(tan(0.5 * $lat + Sourcemap_Proj::FORTPI)/$this->K, 1.0/$this->C);
        for ($i = Sourcemap_Proj::MAX_ITER; $i>0; --$i) {
            $lat = 2.0 * atan($num * Sourcemap_Proj::srat($this->e * sin($p->y), -0.5 * $this->e)) - Sourcemap_Proj::HALF_PI;
            if (abs($lat - $p->y) < $DEL_TOL) break;
            $p->y = $lat;
        }	
        /* convergence failed */
        if (!$i) {
            throw new Exception("Convergence failed.");
        }
        $p->x = $lon;
        $p->y = $lat;
        return $p;
    }
}
