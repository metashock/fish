<?php

class Phish_Index
{

    /**
     *
     */
    protected $classes;

    

    protected function __construct() {

    }


    public static function load($filename = 'phish_info') {
        if(!is_file($filename)) {
            touch($filename);
        }
        $content = file_get_contents($filename);
        if(empty($content)) {
            $index = new Phish_Index();
        } else {
            $index = unserialize($content);
        }
        
        $index->filename($filename);
        return $index;
    }

    public function save($filename = '') {
        if(empty($filename)) {
            $filename = $this->filename;
        }
        file_put_contents($filename, serialize($this));
    }


    /**
     * Returns a class info by a class name
     */
    public function findclass($class) {
        if(isset($this->classes[$class])) {
            return $this->classes[$class];
        } else {
            return NULL;
        }
    }


     
    public function store($classname, $entry) {
        if(!is_array($this->classes)) {
            $this->classes = array();
        }
        $this->classes[$classname] = $entry;
        return $this;
    }


    public function remove($class, $file = '') {
        unset($this->classes[$class]);
    }


    public function removefile($file) {
        foreach($this->classes as $class => $f) {
            if($file === $f) {
                unset($this->classes[$class]);
            }
        }
    }


    public function filename($value) {
        Jm_Util_Checktype::check('string', $value);
        $this->filename = $value;
    }


    public function classes() {
        return $this->classes;
    }


    /**
     * *Magic Method* 
     * Declares the properties that should get serialized
     *
     * @return array
     */
    public function __sleep() {
        return array('classes', 'filename');
    }
}
