<?php


abstract class Phish_Command
{

    /**
     * @var array
     */
    protected $argv;

    public function __construct(array $argv) {
        $this->argv = $argv;
    }

    abstract public function run();

    public static function shortdesc() {
        return '';
    }

    public static function usage() {
        return '';
    }


    public static function factory($command, array $argv) {
        $classname = 'Phish_Command_' . ucfirst($command);
        if(!Jm_Autoloader::singleton()->autoload($classname)) {
            throw new Exception('Command ' . $command . ' not found');
        } 
        return new $classname($argv);
    }


    public static function names() {
        $result = array();
        foreach(glob(__DIR__ . '/Command/*.php') as $file) {
            $classname = 'Phish_Command_'
                . str_replace('.php', '', basename($file));
            require $file;
            if(class_exists($classname)
            && is_a($classname, 'Phish_Command', TRUE)
// @TODO:
//            && !is_abstract($classname)
            ) {
                $result []= str_replace('.php', '', basename($file));
            }
        }
        return $result;
    }
}

