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
 * Uses the tokenizer extension to parse source files.
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
class Phish_FileAnalyzer
{

    /**
     * @var array
     */
    protected $namespaces;


    /**
     * @var array
     */
    protected $classes;


    /**
     * Retrives information about user defined classes.
     *
     * @param string $path Path to source file
     *
     * @return array
     */
    public function __construct($path) {
        Jm_Util_Checktype::check('string', $path);
        $this->parse($path);
    }


    /**
     * Parses the file and populates $namespaces
     * and $classes.
     *
     * @param string $path Path to source file
     *
     * @return Phish_FileAnalyzer
     */
    protected function parse($path) {
        if(!file_exists($path)) {
            throw new Jm_Filesystem_FileNotFoundException(
                'The file ' . $path . ' was not found'
            );
        }

        $this->namespaces = array();
        $this->classes = array();

        $content = file_get_contents($path);

        // @TODO: document
        $commentTokens = array(T_COMMENT);

        if(defined('T_DOC_COMMENT')) {
            $commentTokens []= T_DOC_COMMENT;
        }
        if(defined('T_ML_COMMENT')) {
            $commentTokens []= T_ML_COMMENT;
        }

        $currentNamespace = '';

        $tokens = token_get_all($content);

        for($i = 0; $i < count($tokens); $i++) {
            $token = $tokens[$i];
            if(!is_array($token)) {
                continue;
            }

            switch($token[0]) {

                case T_STRING :
                    if($inNamespaceDeclaration) {
                        $currentNamespace .= '\\' . $token[1];
                    }
                    break;

                case T_NS_SEPARATOR:
                    break;

                case T_NAMESPACE :
                    if(!isset($tokens[$i+2])) {
                        continue;
                    }
                    //
                    $i = $i+2;
                    $currentNamespace = $tokens[$i][1];
                    // $this->namespaces []= $currentNamespace;
                    $inNamespaceDeclaration = TRUE;
                    break;

                case T_CLASS :
                    if(!isset($tokens[$i+2])) {
                        continue;
                    }
                    //
                    $classname = $tokens[$i+2][1];
                    if(!empty($currentNamespace)) {
                        $classname = join('\\', array(
                            '',
                            $currentNamespace
                            $classname
                        ));
                    }
                    $this->classes []= $classname;
                    $inNamespaceDeclaration = FALSE;
                    break;

                default:
                    $inNamespaceDeclaration = FALSE;
                    break;
            }
        }
    }


    /**
     * Returns the namespaces found in the file.
     *
     * @return array
     */
    public function getNamespaces() {
        return $this->namespaces;
    }


    /**
     * Returns the classes found in the file.
     *
     * @return array
     */
    public function classes() {
        return $this->classes;
    }


    /**
     * Useful for debugging.
     *
     * @return string
     */
    public function __toString() {
        $str  = 'Namespaces:' . PHP_EOL;
        $str .= '  ' . implode(PHP_EOL . "  ", $this->getNamespaces());
        $str .= PHP_EOL;
        $str  = 'Classes:' . PHP_EOL;
        $str .= '  ' . implode(PHP_EOL . "  ", $this->classes());
        return $str;
    }


    /**
     * Extracts meta information from method comments
     *
     * @param string $comment A method comment
     *
     * @return array
     */
    public static function parseComment($comment) {

        //$returnPattern  = "/^[ \t]*\*[ \t]*\@return[ \t]*";
        //$returnPattern .= "[a-bA-B_]+[a-bA-B_0-9]?[ \t]?$/";

        $returnPattern = "/@return[ \t]*([a-zA-Z_]+[a-zA-Z0-9_]?)[ \t]?/";
        $paramPattern  = "/@param[ \t]*([a-zA-Z_]+[a-zA-Z0-9_]?)[ \t]*.";
        $paramPattern .= "([a-zA-Z_]+[a-zA-Z0-9_]?)[ \t]?/";

        $meta = array(
            'returnType' => '?',
            'params' => array()
        );

        foreach(explode(PHP_EOL, $comment) as $line) {
            if(preg_match($returnPattern, $line, $matches)) {
                $meta['returnType'] = $matches[1];
                continue;
            }
            if(preg_match($paramPattern, $line, $matches)) {
                $meta['params'][$matches[2]] = array(
                    'type' => $matches[1],
                    'name' => $matches[2]
                );
            }
        }

        $meta['text'] = '';
        return $meta;
    }
}

