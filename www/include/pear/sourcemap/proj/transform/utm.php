<?php
class Sourcemap_Proj_Transform_Utm extends Sourcemap_Proj_Transform_Tmerc {
/*******************************************************************************
NAME                            TRANSVERSE MERCATOR

PURPOSE:	Transforms input longitude and latitude to Easting and
		Northing for the Transverse Mercator projection.  The
		longitude and latitude must be in radians.  The Easting
		and Northing values will be returned in meters.

ALGORITHM REFERENCES

1.  Snyder, John P., "Map Projections--A Working Manual", U.S. Geological
    Survey Professional Paper 1395 (Supersedes USGS Bulletin 1532), United
    State Government Printing Office, Washington D.C., 1987.

2.  Snyder, John P. and Voxland, Philip M., "An Album of Map Projections",
    U.S. Geological Survey Professional Paper 1453 , United State Government
    Printing Office, Washington D.C., 1989.
*******************************************************************************/


    #Initialize Transverse Mercator projection
    public function init() {
        if(!$this->zone) {
            throw new Exception("Zone must be specified for UTM");
        }
        $this->lat0 = 0.0;
        $this->long0 = ((6 * abs($this->zone)) - 183) * Sourcemap_Proj::D2R;
        $this->x0 = 500000.0;
        $this->y0 = $this->utmSouth ? 10000000.0 : 0.0;
        $this->k0 = 0.9996;

        parent::init();
    }
}
