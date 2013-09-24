<?php

class Phish_Command_Start extends Phish_Command
{

    /**
     *
     */
    public function run() {
        $configuration = new Jm_Configuration_Xmlfile('phish.xml');
        $daemon = new Phish_Daemon($configuration);
        $daemon->start();
    }

    public static function shortdesc() {
        return 'Starts the phish monitor in the current directory';
    }

    public static function usage() {
        $console = Jm_Console::singleton();
        $console->write('USAGE: ', 'bold');
        $console->writeln('phish start');
        $console->writeln();
        $console->writeln(self::shortdesc());
        $console->writeln();
    }
}
