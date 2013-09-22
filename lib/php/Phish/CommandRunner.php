<?php

class Phish_CommandRunner
{
    /**
     *
     */
    public function run($command, $argv) {
        try {
            $handler = Phish_Command::factory($command, $argv);
            $handler->run();
        } catch (Exception $e) {
            Jm_Console::singleton()->writeln($e->getMessage(), 'red');
            exit(1);
        }
    }
}

