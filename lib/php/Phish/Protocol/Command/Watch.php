<?php

class Phish_Protocol_Command_Watch extends Phish_Protocol_Command
{

    protected $directory;


    public function __construct($directory) {
         $this->directory($directory);       
    }


    public static function createFromDOMNode(DOMNode $node) {
        return new Phish_Protocol_Command_Watch(
            $node->getAttribute('directory')
        );
    }


    /**
     *
     */
    public function directory($value = NULL) {
        if(is_null($value)) {
            return $this->directory;
        } else {
            Jm_Util_Checktype::check('string', $value);
            $this->directory = $value;
            return $this;
        }
    }
}

