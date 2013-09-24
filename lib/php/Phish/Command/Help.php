<?php

class Phish_Command_Help extends Phish_Command
{

    public function run() {
        if(!isset($this->argv[2])) {
            self::usage();
            exit(1);
        }
        
        $command = $this->argv[2];
        $classname = 'Phish_Command_' . ucfirst($command);
        $classname::usage();
    }

    public static function shortdesc() {
        return 'Displays information about a command';
    }

    public static function usage() {
        $console = Jm_Console::singleton();
        $console->write('USAGE: ', 'bold');
        $console->writeln('help COMMAND');
        $console->writeln();
        $console->write(self::shortdesc() . '. For a list of commands '
            . 'just type ');
        $console->writeln('phish', 'bold');
        $console->writeln();
    }
}
