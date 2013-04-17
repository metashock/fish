<?php

require_once 'Jm/Autoloader.php';
require_once 'Phish_PhpFileInfo.php';


class Phish_WatchFolder
{


    /**
     *
     */
    protected $fd;


    protected $watches;



    /**
     *
     */
    public function __construct($path, $debug = TRUE) {
        $this->fd = inotify_init();
        $wd = inotify_add_watch($this->fd, $path, $this->inotifyOptions() | IN_DELETE_SELF);

        $this->watches = array (
            $wd => __DIR__
        );

        $this->initRecursively($path);
    }

    
    protected function initRecursively ($path) {

        // search for subdirs recursively and add watches for them
        // @see http://stackoverflow.com/a/7149477/171318
        $directoryIterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($path),
            RecursiveIteratorIterator::SELF_FIRST
        );

        foreach($directoryIterator as $item) {
            if( $item->getFilename() === '..' 
             || $item->getFilename() === '.'
             || realpath($item->getPathname()) === $path
            ) {
                continue;
            }

            if($item->isDir()) {
                $name = $item->getPathname();
                $wd = inotify_add_watch($this->fd, $name, $this->inotifyOptions());
                $this->watches[$wd] = $name;
                $this->log(sprintf('Watching directory %s', $name));
            }
        }
    }


    public function log($message) {
        echo $message . PHP_EOL;
    }


    /**
     *
     *
     */ 
    public function watch() {
        while(TRUE) {
            $events = inotify_read($this->fd);
            if(!$events) {
                continue;
            }

            foreach($events as $event) {
                $mask = $event['mask'];
                $name = $event['name'];

                if (($mask & IN_ATTRIB) === IN_ATTRIB) {
                    if(!preg_match('/^[A-Za-z]*\.php$/', $name)) {
                        continue;
                    }
                    printf("The attributes of %s were modified\n", $name);
                }

                if (($mask & IN_CLOSE_WRITE) === IN_CLOSE_WRITE) {

                    if(!preg_match('/^[A-Za-z]*\.php$/', $name)) {
                        continue;
                    }

                    // get the full path to the new file

                    // build path to new directory
                    $filename = $this->pathByEvent($event) . '/' . $name;

                    $this->log(printf("File '%s' has been changed. Analyze:", $filename));
                    $info = new Phish_PhpFileInfo($filename);
                    $this->log($info);
                }


                // IN_CREATE will be triggered if a file or a directory was created within
                // a watched directory.
                if (($mask & IN_CREATE) === IN_CREATE) {

                    // check if it is a file or a directory
                    if(($mask & IN_ISDIR) === IN_ISDIR) {

                        // if a new directory was created we add a new watch for it

                        // check for the entry in the lookup table
                        if(!isset($this->watches[$event['wd']])) {
                            throw new Exception('Lookup for wd(' . $event['wd'] . ') failed');
                        }

                        // build path to new directory
                        $name = $this->watches[$event['wd']] . '/' . $name;
                        printf("Folder %s was created. Add watch to it\n", $name);

                        $wd = inotify_add_watch($fd, $name, $this->inotifyOptions());
                        $this->watches[$wd] = $name;
                    } 
                }

                // IN_DELETE will be triggered if a file or a directory will be 
                // deleted within a watched directory
                if (($mask & IN_DELETE) === IN_DELETE) {

                    // check if it is a file or a directory
                    if(($mask & IN_ISDIR) === IN_ISDIR) {
                        // if a new directory was created we add a new watch for it

                        // check for the entry in the lookup table
                        if(!isset($this->watches[$event['wd']])) {
                            throw new Exception('Lookup for wd(' . $event['wd'] . ') failed');
                        }

                        // build path to new directory
                        $name = $this->watches[$event['wd']] . '/' . $name;
                        printf("Folder '%s' was deleted. Remove watch for it\n", $name);

                        $wd = inotify_rm_watch($fd, $event['wd']);
                        $this->watches[$wd] = $name;

                    } else {
                        if(!preg_match('/^[A-Za-z]*\.php$/', $name)) {
                            continue;
                        }
                        printf("File '%s' was deleted\n", $name);
                    }
                }

                if (($mask & IN_DELETE_SELF) === IN_DELETE_SELF) {
                    printf("The watched file '%s' itself was deleted\n", $name);
                }
                if (($mask & IN_MOVE_SELF) === IN_MOVE_SELF) {
                    printf("The watched file '%s' itself was moved\n", $name);
                }
            }
        }
    }


    /**
     *
     * @return string
     *
     * @throws Exception
     */
    protected function pathByEvent(array $event) {
        // check for the entry in the lookup table
        if(!isset($this->watches[$event['wd']])) {
            throw new Exception('Lookup for wd(' . $event['wd'] . ') failed');
        }
        return $this->watches[$event['wd']];
    }


   /**
    *
    * @return integer
    */
    public function inotifyOptions() {
        return IN_ISDIR  
            | IN_CLOSE_WRITE 
            | IN_DELETE 
            | IN_CREATE 
//            | IN_MOVED_FROM 
//            | IN_MOVED_TO 
            | IN_MOVE_SELF 
//            | IN_ATTRIB
        ;
    }

}

$w = new Phish_WatchFolder(__DIR__);
$w->watch();
