<?php

class Phish_Command_Restart extends Phish_Command
{

    /**
     *
     */
    public function run() {
        $configuration = new Jm_Configuration_Xmlfile('phish.xml');
        $daemon = new Phish_Daemon($configuration);
        $daemon->restart();
    }
}

