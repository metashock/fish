<?php
/**
 * Phish
 *
 * Copyright (c) 2013, Thorsten Heymann <thorsten@metashock.de>.
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions
 * are met:
 *
 *   * Redistributions of source code must retain the above copyright
 *     notice, this list of conditions and the following disclaimer.
 *
 *   * Redistributions in binary form must reproduce the above copyright
 *     notice, this list of conditions and the following disclaimer in
 *     the documentation and/or other materials provided with the
 *     distribution.
 *
 *   * Neither the name Thorsten Heymann nor the names of his
 *     contributors may be used to endorse or promote products derived
 *     from this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS
 * FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE
 * COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT,
 * INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING,
 * BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER
 * CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT
 * LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN
 * ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 * POSSIBILITY OF SUCH DAMAGE.
 *
 * PHP Version >= 5.3.0
 *
 * @category  Util
 * @package   Phish
 * @author    Thorsten Heymann <thorsten@metashock.de>
 * @copyright 2013 Thorsten Heymann <thorsten@metashock.de>
 * @license   BSD-3 http://www.opensource.org/licenses/BSD-3-Clause
 * @version   GIT: $$GITVERSION$$
 * @link      http://www.metashock.de/
 * @since     0.1.0
 */
/**
 * A daemon that monitors inotify events including files of
 * given paths. Creates, updates and deletes `phish_info`
 * 
 * @category  Util
 * @package   Phish
 * @author    Thorsten Heymann <thorsten@metashock.de>
 * @copyright 2013 Thorsten Heymann <thorsten@metashock.de>
 * @license   BSD-3 http://www.opensource.org/licenses/BSD-3-Clause
 * @version   GIT: $$GITVERSION$$a
 * @link      http://www.metashock.de/
 * @since     0.1.0
 */
class Phish_Daemon extends Jm_Os_Daemon
implements Jm_Configurable
{

    /**
     * Indicates if SIGTERM has been sent to us or not
     *
     * @var boolean
     */
    protected $receivedSigterm;


    /**
     * Inotify queue 
     * 
     * @var Jm_Os_Inotify_Instance
     */
    protected $in;


    /**
     * Source file index as object. cache.
     *
     * @var Phish_Index
     */
    protected $index;


    /**
     * This method runs as a daemonized process. 
     * Will get called by start()
     *
     * @return void
     */
    protected function daemon() {
        pcntl_signal(SIGTERM, array($this, 'signalHandler'), TRUE);
        $this->receivedSigterm = FALSE;
        $in = Jm_Os_Inotify::init($this->log);
       
        $paths = $this->configuration->monitor->path;
        $options = IN_CLOSE_WRITE
            | IN_DELETE
            | IN_MOVED_TO
            | IN_MOVED_FROM;

        foreach($paths as $path) {
            if(is_dir($path->value)) {
                $options |= Jm_Os_Inotify::IN_X_RECURSIVE;
            }

            $in->watch($path->value, $options);
        }

        $this->index = Phish_Index::load('phish_info');
        $this->analyze($paths);

        // we need to poll as otherwise PHP would be 
        // able to deliver IPC signals to the userland anymore
        while($events = $in->wait(1)) {
            if($this->receivedSigterm === TRUE) {
                break;
            }
            foreach($events as $event) {
                // we are only interested in .php files
                $pinfo = pathinfo($event->fullpath());
                if(!isset($pinfo['extension'])
                  || $pinfo['extension'] !== 'php') {
                    continue;
                }

                $this->log('received event ' . $event->mask(),
                    Jm_Log_Level::DEBUG
                );
                switch (TRUE) {
                    case $event->mask()->contains(IN_DELETE):
                        $this->handleDelete($event);
                        break;
                    case $event->mask()->contains(IN_CLOSE_WRITE):
                        $this->handleCloseWrite($event);
                        break;
                    case $event->mask()->contains(IN_MOVED_FROM):
                        $this->handleMovedFrom($event);
                        break;
                    case $event->mask()->contains(IN_MOVED_TO):
                        $this->handleMovedTo($event);
                        break;
                    default :
                        $this->log('Unknown event mask ' . $mask,
                            Jm_Log_Level::ERROR
                        );
                }
            } 
        }
    }


    /**
     * Analyzes an array of trees containing source files
     * and rewrites the index.
     *
     * @param string $paths The path
     *
     * @return void
     */
    protected function analyze($paths) {
        $stack = array();
        $pattern = array();
        foreach($paths as $path) {
            $stack []= $path->value;
            $pattern []= preg_quote($path->value, '~');
        }

        $pattern = implode('|', $pattern);

        while(!empty($stack)) {
            $current = array_shift($stack);
            if(!is_dir($current)) {
                $pinfo = pathinfo($current);
                if($pinfo['ext'] === 'php') {
                    $this->log($current, Jm_Log_Level::ERROR);
                }
                continue;
            }
            
            foreach(scandir($current) as $entry) {

                if($this->receivedSigterm) {
                    break;
                }

                $filename = $current . '/' . $entry;
                if(is_dir($filename)) {
                    if($entry === '..' || $entry === '.') {
                        continue;
                    }
                    $stack []= $filename;
                } else {
                    $pinfo = pathinfo($filename);
                    if(isset($pinfo['extension'])
                        && $pinfo['extension'] === 'php'
                    ) {
                        $this->log($filename, Jm_Log_Level::ERROR);
                        $analyzer = new Phish_FileAnalyzer($filename);
                        $classes = $analyzer->classes();
                        foreach($classes as $class) {
                            $this->index->store($class, $filename);
                        }
                    }                   
                }
            }
        }

        foreach($this->index->classes() as $class => $filename) {
            if(!is_file($filename)
                || !preg_match('~^(' . $pattern . ')~', $filename)
            ) {
                $this->index->remove($class);
                $this->log('removing ' . $class . ' from index. ' .
                    'The source file has been deleted');
            }
        }

        // save the index
        $this->index->save();
    }


    /**
     * Terminates the process 
     *
     * @param string  $message Optional exit message
     * @param integer $code    Optional exit code. default: 1
     *
     * @return void
     */
    protected function terminate($message = '', $code = 1) {
        if(!empty($message)) {
            $this->log($message);
        }
        exit($code);
    }


    /**
     * Called if a file was written to and closed
     *
     * @param Jm_Os_Inotify_Event $e Filesystem event generated by inotify
     *
     * @return void
     */
    protected function handleCloseWrite(Jm_Os_Inotify_Event $e) {
        $this->log(sprintf(
                "File '%s' has been changed. Analyzing ... ",
                $e->fullpath()
            ),
            Jm_Log_Level::DEBUG
        );

        // we are only interested in .php files
        $pinfo = pathinfo($e->fullpath());
        if(!isset($pinfo['extension']) || $pinfo['extension'] !== 'php') {
            return;
        }

        // always check if the file exists before opening it
        // inotify may have thrown the event but it is not safe,
        // that the file still exists
        if(file_exists($e->fullpath())) {
            $info = new Phish_FileAnalyzer($e->fullpath());
            $classes = $info->classes();
            foreach($classes as $class) {
                $this->index->store($class, $e->fullpath());
                $this->log('- ' . $class);
            }
        } else {
            $this->log(sprintf(
                'Received event %s for %s but '
               .'the file doesn\'t exist anymore',
                $e->mask(), $e->fullpath()
                ), Jm_Log_Level::DEBUG
            );
        }
        $this->index->save();
    }


    /**
     * Called if a file was written to and closed
     *
     * @param Jm_Os_Inotify_Event $e Inotify filesystem event 
     *
     * @return Phish_Daemon
     */
    protected function handleDelete(Jm_Os_Inotify_Event $e) {
        $this->log(sprintf("File '%s' has been deleted", $e->fullpath()),
            Jm_Log_Level::NOTICE
        );
        // @TODO remove from index
        $this->index->removefile($e->fullpath());
        $this->index->save();
        return $this;
    }


    /**
     * Called if a file was moved into a watched folder
     *
     * @param Jm_Os_Inotify_Event $e Inotify filesystem event 
     *
     * @return Phish_Daemon
     */
    protected function handleMovedTo(Jm_Os_Inotify_Event $e) {
        $this->log(sprintf("File '%s' has been moved to", $e->fullpath()),
            Jm_Log_Level::NOTICE
        );
        return $this;
    }


    /**
     * Called if a file was moved out of a watched folder
     *
     * @param Jm_Os_Inotify_Event $e Inotify filesystem event 
     *
     * @return Phish_Daemon
     */
    protected function handleMovedFrom(Jm_Os_Inotify_Event $e) {
        $this->log(sprintf("File '%s' has been moved from", $e->fullpath()),
            Jm_Log_Level::NOTICE
        );
        return $this;
    }


    /**
     * Returns the location of the pid file
     *
     * @return string
     */
    public function getPidfileLocation() {
        return sys_get_temp_dir() . '/phishd.pid';
    }   


    /**
     * Configuration method.
     *
     * @param Jm_Configuration $configuration The configuration
     *
     * @return Phish_Daemon
     */
    public function configure($configuration) {
        parent::configure($configuration);
        $this->configuration->set('name', 'phishd');
        return $this; 
    }
}
