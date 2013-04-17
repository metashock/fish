<?php


class Phish_Daemon
{

    /**
     * @var integer
     */
    protected $daemonPid;


    /**
     *
     */
    public function start() {
        $pidfile = $this->getPidfileLocation();
        $file = fopen($pidfile, 'w+');

        if(!flock($file, LOCK_EX | LOCK_NB)) {
            die('phishd is already running. pid is ' . $phishpid);
        }

        // fork the current process
        $pid = pcntl_fork();

        switch($pid) {
            case -1 : 
                throw new Exception('Forking failed. Cannot spawn daemon');
            case  0 :
                $this->runDaemon();
            default:
                $this->daemonPid = $pid;
                return $this;
        }
    }


    /**
     *
     */
    protected function runDaemon() {
        $this->readConfiguration();
        $this->initializeInotifyWatches();
        $this->startControllInterface();

        while(TRUE) {
            $this->handleInotifyEvents();
        }
    }


    /**
     * Return the pid of the daemon
     *
     * @return integer
     */
    public function daemonPid() {
        return $this->daemonPid;
    }


    /**
     * Returns the location of the pid file
     *
     * @return string
     */
    public function getPidfileLocation() {
        return sys_get_tempdir() . '/phishd.pid';
    }    
}

