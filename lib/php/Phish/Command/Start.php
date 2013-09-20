<?php

class Phish_Command_Start extends Phish_Command
{

    /**
     *
     */
    public function run() {
        $configuration = new Jm_Configuration_Inifile('.');
        $daemon = new Phish_Daemon($configuration);
        $daemon->start();
    }
}
