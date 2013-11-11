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
 * Executes commands
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
class Phish_CommandRunner
{

    /**
     * Trys to factory a command objec from $argv[1] and runs it's 
     * run() method.
     *
     * @param array $argv Copy of PHP's $argv
     *   
     * @return integer
     */
    public function run($argv) {

        if(!isset($argv[1])) {
            $this->usage();
            exit(1);
        }

        $command = $argv[1];

        try {
            $handler = Phish_Command::factory($command, $argv);
            return $handler->run();
        } catch (Exception $e) {
            Jm_Console::singleton()->writeln($e->getMessage(), 'red');
            return 1;
        }
    }


    /**
     * Displays usage information and prints all available commands 
     * and their usage information.
     *
     * @return Phish_CommandRunner
     */
    public function usage() {
        $console = Jm_Console::singleton();
        $console->write('Usage: ', 'bold');
        $console->writeln('phish COMMAND [ARGUMENTS]');
        $console->writeln();
        $console->writeln('COMMANDs:', 'bold');
        $console->writeln('');
        foreach(Phish_Command::names() as $command) {
            $console->write(lcfirst($command), 'blue,bold');
            $console->writeln();
            $classname = 'Phish_Command_' . ucfirst($command);
            $console->writeln(" " . $classname::shortdesc());
            $console->writeln();
        }
        $console->writeln();
        return $this;
    }
}

