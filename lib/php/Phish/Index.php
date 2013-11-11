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
 * The "index" is a file containing serializes php data. This class provides
 * the functionality to serialize / unserialize the file content and to 
 * access, store and remove entries.
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
class Phish_Index
{

    /**
     * @var array
     */
    protected $classes;


    /**
     * Loads from file name
     *
     * @param string $filename The file name
     *
     * @return Phish_Index
     *
     * @throws InvalidArgumentException If $filename isn't a string
     */    
    public static function load($filename) {
        Jm_Util_Checktype::check('string', $filename);
        if(!is_file($filename)) {
            touch($filename);
        }
        $content = file_get_contents($filename);
        if(empty($content)) {
            $index = new Phish_Index();
        } else {
            $index = unserialize($content);
        }
        
        $index->filename($filename);
        return $index;
    }


    /** 
     * Saves the index back to a file.
     *
     * @param string $filename If omitted the file name where we had
     *                         loaded from will be use
     *
     * @return Phish_Index
     */
    public function save($filename='') {
        if(empty($filename)) {
            $filename = $this->filename;
        }
        file_put_contents($filename, serialize($this));
        return $this;
    }


    /**
     * Returns a class info by a class name
     *
     * @param string $class A fully qulified class name
     *
     * @return array|NULL
     */
    public function findclass($class) {
        if(isset($this->classes[$class])) {
            return $this->classes[$class];
        } else {
            return NULL;
        }
    }


    /**
     * Stores $entry for $classname.
     *
     * @param string $classname The class name
     * @param array  $entry     The entry
     *
     * @return Phish_Index
     */ 
    public function store($classname, $entry) {
        Jm_Util_Checktype::check('string', $classname);
        Jm_Util_Checktype::check('array', $entry);
        if(!is_array($this->classes)) {
            $this->classes = array();
        }
        $this->classes[$classname] = $entry;
        return $this;
    }


    /**
     * Remove entry for $classname.
     *
     * @param string $classname The classname
     *
     * @return Phish_Index
     */
    public function remove($classname) {
        unset($this->classes[$classname]);
        return $this;
    }


    /**
     * Removes one or more classes by file name
     * from index.
     *
     * @param string $filename The filename
     *
     * @return Phish_Index
     */
    public function removefile($filename) {
        foreach($this->classes as $class => $f) {
            if($filename === $f) {
                unset($this->classes[$class]);
            }
        }
        return $this;
    }


    /**
     * Sets the file name. Currently no getter is available.
     *
     * @param string $value The filename
     *
     * @return Phish_Index
     */
    protected function filename($value) {
        Jm_Util_Checktype::check('string', $value);
        $this->filename = $value;
        return $this;
    }


    /**
     * Returns the classes array.
     *
     * @return array $classes
     */
    public function classes() {
        return $this->classes;
    }


    /**
     * *Magic Method* 
     * Declares the properties that should get serialized
     *
     * @return array
     */
    public function __sleep() {
        return array('classes', 'filename');
    }
}
