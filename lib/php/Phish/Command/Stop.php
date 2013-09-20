<?php

class Phish_Command_Stop extends Phish_Command
{
    /**
     *
     */
    public function run() {
        $configuration = new Jm_Configuration_Inifile('.');
        $daemon = new Phish_Daemon($configuration);
        $daemon->stop();
    }
}
