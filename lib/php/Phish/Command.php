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

    public static function factory($command, array $argv) {
        $classname = 'Phish_Command_' . ucfirst($command);
        return new $classname($argv);
    }
}
