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
 * Base class for all native phish commands.
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
abstract class Phish_Command
{

    /**
     * Copy of PHP's $argv
     *
     * @var array
     */
    protected $argv;

    /**
     * Constructor
     *
     * @param array $argv Copy of PHP's $argv
     */
    public function __construct(array $argv) {
        $this->argv = $argv;
    }

    /**
     * Called by Phish_CommandRunner. Sub classes must implement
     * their main functionality in this method.
     *
     * @return integer The return code
     */
    abstract public function run();

    /**
     * Provides a short description to show in help mode.
     *
     * @return string
     */
    public static function shortdesc() {
        return '';
    }

    /**
     * Returns a string that provides usage information of 
     * the command.
     *
     * @return string
     */
    public static function usage() {
        return '';
    }


    /**
     * Creates a command from command line arguments
     *
     * @param string $command The command name
     * @param array  $argv    Copy of PHP's $argv
     *
     * @return Phish_Command
     */
    public static function factory($command, array $argv) {
        $classname = 'Phish_Command_' . ucfirst($command);
        if(!Jm_Autoloader::singleton()->autoload($classname)) {
            throw new Exception('Command ' . $command . ' not found');
        } 
        return new $classname($argv);
    }



    /**
     * The method is currently used for the usage info of the phish
     * main executable.
     *
     * @return array Available command names
     * @experimental
     */
    public static function names() {
        $result = array();
        foreach(glob(__DIR__ . '/Command/*.php') as $file) {
            $classname = 'Phish_Command_'
                . str_replace('.php', '', basename($file));
            if(class_exists($classname, TRUE)
            && is_a($classname, 'Phish_Command', TRUE)
// @TODO:
//            && !is_abstract($classname)
            ) {
                $result []= str_replace('.php', '', basename($file));
            }
        }
        return $result;
    }
}

