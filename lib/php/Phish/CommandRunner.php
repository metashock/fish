<?php

class Phish_CommandRunner
{
    /**
     *
     */
    public function run($argv) {

        if(!isset($argv[1])) {
            $this->usage();
            exit(1);
        }

        $command = $argv[1];

        try {
            $handler = Phish_Command::factory($command, $argv);
            $handler->run();
        } catch (Exception $e) {
            Jm_Console::singleton()->writeln($e->getMessage(), 'red');
            exit(1);
        }
    }


    public function usage() {
        $console = Jm_Console::singleton();
        $console->write('Usage: ', 'bold');
        $console->writeln('phish COMMAND [ARGUMENTS]');
        $console->writeln();
        $console->writeln('COMMANDs:', 'bold');
        $console->writeln('');
        foreach(Phish_Command::names() as $command) {
            $console->write('    - ' . lcfirst($command), 'blue,bold');
            $classname = 'Phish_Command_' . ucfirst($command);
            $console->writeln("\t\t" . $classname::shortdesc());
        }
        $console->writeln();
    }
}

