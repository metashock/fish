<?php

class Phish_CommandRunner
{
    /**
     *
     */
    public function run($command, $argv) {

        $handler = Phish_Command::factory($command, $argv);
        $handler->run();
    }
}

