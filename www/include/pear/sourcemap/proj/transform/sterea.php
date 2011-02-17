<?php
class Sourcemap_Proj_Transform_Sterea extends Sourcemap_Proj_Transform_Gauss {
    
    public function init() {
        parent::init();
        if (!$this->rc) {
            throw new Exception("No 'rc'.");
        }
        $this->sinc0 = sin($this->phic0);
        $this->cosc0 = cos($this->phic0);
        $this->R2 = 2.0 * $this->rc;
        if(!$this->title) $this->title = "Oblique Stereographic Alternative";
    }

    public function forward($p) {
        $p->x = Sourcemap_Proj::adjust_lon($p->x-$this->long0); /* adjust del longitude */
        $p = parent::forward($p);
        $sinc = sin($p->y);
        $cosc = cos($p->y);
        $cosl = cos($p->x);
        $k = $this->k0 * $this->R2 / (1.0 + $this->sinc0 * $sinc + $this->cosc0 * $cosc * $cosl);
        $p->x = $k * $cosc * sin($p->x);
        $p->y = $k * ($this->cosc0 * $sinc - $this->sinc0 * $cosc * $cosl);
        $p->x = $this->a * $p->x + $this->x0;
        $p->y = $this->a * $p->y + $this->y0;
        return $p;
    }

    public function inverse($p) {
        $p->x = ($p->x - $this->x0) / $this->a; /* descale and de-offset */
        $p->y = ($p->y - $this->y0) / $this->a;

        $p->x /= $this->k0;
        $p->y /= $this->k0;
        if(($rho = sqrt($p->x * $p->x + $p->y * $p->y)) ) {
            $c = 2.0 * atan2($rho, $this->R2);
            $sinc = sin($c);
            $cosc = cos($c);
            $lat = asin($cosc * $this->sinc0 + $p->y * $sinc * $this->cosc0 / $rho);
            $lon = atan2($p->x * $sinc, $rho * $this->cosc0 * $cosc - $p->y * $this->sinc0 * $sinc);
        } else {
            $lat = $this->phic0;
            $lon = 0.;
        }

        $p->x = $lon;
        $p->y = $lat;
        $p = parent::inverse($p);
        $p->x = Sourcemap_Proj::adjust_lon($p->x + $this->long0); /* adjust longitude to CM */
        return $p;
    }
}

