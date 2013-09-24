<?php

class Phish_Command_Stop extends Phish_Command
{
    /**
     *
     */
    public function run() {
        $configuration = new Jm_Configuration_Xmlfile('phish.xml');
        $daemon = new Phish_Daemon($configuration);
        $daemon->stop();
    }

    public static function shortdesc() {
        return 'Stops the phish monitor';
    }


    public static function usage() {
        $console = Jm_Console::singleton();
        $console->write('USAGE: ', 'bold');
        $console->writeln('phish stop');
        $console->writeln();
        $console->writeln(self::shortdesc());
        $console->writeln();
    }
}
