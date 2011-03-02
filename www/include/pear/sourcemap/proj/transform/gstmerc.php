<?php
class Sourcemap_Proj_Transform_Gstmerc extends Sourcemap_Proj_Transform {
    public function init() {
        // array of:  a, b, lon0, lat0, k0, x0, y0
        $temp = $this->b / $this->a;
        $this->e = sqrt(1.0 - $temp * $temp);
        $this->lc = $this->long0;
        $this->rs = sqrt(1.0 + $this->e * $this->e * pow(cos($this->lat0), 4.0) / (1.0 - $this->e * $this->e));
        $sinz = sin($this->lat0);
        $pc = asin($sinz / $this->rs);
        $sinzpc = sin($pc);
        $this->cp = Sourcemap_Proj::latiso(0.0, $pc, $sinzpc) - $this->rs * Sourcemap_Proj::latiso($this->e, $this->lat0, $sinz);
        $this->n2 = $this->k0 * $this->a * sqrt(1.0 - $this->e * $this->e) / (1.0 - $this->e * $this->e * $sinz * $sinz);
        $this->xs = $this->x0;
        $this->ys = $this->y0-$this->n2 * $pc;

        if(!$this->title) $this->title = "Gauss Schreiber transverse mercator";
    }


    # forward equations--mapping lat,long to x,y
    public function forward($p) {

        $lon = $p->x;
        $lat = $p->y;

        $L = $this->rs*($lon-$this->lc);
        $Ls = $this->cp + ($this->rs * Sourcemap_Proj::latiso($this->e, $lat, sin($lat)));
        $lat1 = asin(sin($L)/Sourcemap_Proj::cosh($Ls));
        $Ls1 = Sourcemap_Proj::latiso(0.0, $lat1, sin($lat1));
        $p->x = $this->xs + ($this->n2 * $Ls1);
        $p->y = $this->ys + ($this->n2 * atan(Sourcemap_Proj::sinh($Ls)/cos($L)));
        return $p;
    }

    # inverse equations--mapping x,y to lat/long
    public function inverse($p) {

        $x = $p->x;
        $y = $p->y;

        $L = atan(Sourcemap_Proj::sinh(($x-$this->xs) / $this->n2) / cos(($y - $this->ys) / $this->n2));
        $lat1 = asin(sin(($y - $this->ys) / $this->n2) / Sourcemap_Proj::cosh(($x - $this->xs) / $this->n2));
        $LC = Sourcemap_Proj::latiso(0.0, $lat1, sin($lat1));
        $p->x = $this->lc + $L / $this->rs;
        $p->y = Sourcemap_Proj::invlatiso($this->e, ($LC - $this->cp) / $this->rs);
        return $p;
    }

}
