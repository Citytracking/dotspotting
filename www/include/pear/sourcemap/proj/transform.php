<?php
class Sourcemap_Proj_Transform {
    
    protected $_proj = null;

    public function __construct(Sourcemap_Proj_Projection $proj) {
        foreach($proj as $k => $v) {
            $this->{$k} = $v;
        }
        $this->_proj = $proj;
        $this->init();
    }

    public function init() {}
    public function forward($pt) { return $pt; }
    public function inverse($pt) { return $pt; }
}
