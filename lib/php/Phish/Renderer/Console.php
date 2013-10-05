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
 * @package   Phish\Renderer
 * @author    Thorsten Heymann <thorsten@metashock.de>
 * @copyright 2013 Thorsten Heymann <thorsten@metashock.de>
 * @license   BSD-3 http://www.opensource.org/licenses/BSD-3-Clause
 * @version   GIT: $$GITVERSION$$
 * @link      http://www.metashock.de/
 * @since     0.1.0
 */
/**
 * Renderer for the console output of the info command. As the info command
 * was the first command in phish this class is much too specialized and
 * needs to be refactored in a way that rendering functionality can be
 * introduced by classes that will be delivered together with commands -
 * to keep things flexible.
 *
 * @category  Util
 * @package   Phish\Renderer
 * @author    Thorsten Heymann <thorsten@metashock.de>
 * @copyright 2013 Thorsten Heymann <thorsten@metashock.de>
 * @license   BSD-3 http://www.opensource.org/licenses/BSD-3-Clause
 * @version   GIT: $$GITVERSION$$a
 * @link      http://www.metashock.de/
 * @since     0.1.0
 */
class Phish_Renderer_Console extends Phish_Renderer 
{

    /**
     * Points the a Jm_Console instance which is used for printing
     *
     * @var Jm_Console
     */
    protected $console;


    /** 
     * Constructor
     *
     * Initializes the console instance.
     *
     * @return Phish_Renderer_Console
     */
    public function __construct() {
        $this->console = Jm_Console::singleton();
    }


    /**
     * Displays informantion about a class.
     *
     * @param ReflectionFunction $class The class
     * @param string             $file  Optional source file name
     *
     * @return Phish_Renderer_Console
     */
    public function displayClass(ReflectionClass $class, $file = '') {
        $this->console->write($this->renderModifiers($class), 'yellow');
        $this->console->write('class ', 'green,bold');
        $this->console->write($class->name, 'green');
        $this->console->writeln(' (' . $file . ')', 'light'); 
        $this->displayInheritanceGraph($class);
        $this->displayConstants($class);   
        $this->displayProperties($class);
        $this->displayConstructor($class);
        $this->displayMethods($class);

        return $this;
    }


    /**
     * Displays a function.
     *
     * @param ReflectionFunction $function The function
     *
     * @return Phish_Renderer_Console
     */
    public function displayFunction(ReflectionFunction $function) {
        $str = $this->console->colorize($function->getDocComment(), 'yellow');

        if($function->isInternal()) {
            $str = $this->console->colorize("/**\n * @internal " .  
                $this->linkToPhpNetFunctionDoc($function) . "\n */",
            'yellow');
        } 

        $str .= PHP_EOL;
        $meta = $this->parseComment($function->getDocComment());

        $params = array();
        foreach($function->getParameters() as $p) {
            $typehint = '';
            if(isset($meta['params'][$p->name])) {
                $typehint = $meta['params'][$p->name]['type'] . ' ';
            }
            $params []= $typehint . '$' . $p->name;
        }

        $str .= sprintf("%s %s %s (%s)\n", 
            //        $comment['text'],//
            $this->console->colorize($meta['returnType'], 'cyan'),
            $this->console->colorize('function', 'purple'),
            $this->console->colorize($function->name, 'white,bold'),
            implode(", ", $params)
        );
        if(!$function->isInternal()) {
            $str .= sprintf("\nFile: %s:%s\n",
                $function->getFileName(),
                $function->getStartLine()
            );
        }

        $this->console->write($str);
        return $this;
    }


    /**
     * Helper method. Returns the php.net/manual url for a certain function
     *
     * @param string $function The function name
     *
     * @return string
     */
    public function linkToPhpNetFunctionDoc($function) {
        $fmt = 'http://www.php.net/en/function.%s';
        // PHP manual controllers using '-' instead of '_'
        // Don't ask me why!
        return sprintf($fmt, str_replace('_', '-', $function->name));
    }


    /**
     * Displays parent classes of $class as a graph like:
     *
     *     DateTime
     *       + StdClass
     *
     * @param ReflectionClass $class The class
     *
     * @return Phish_Renderer
     */
    public function displayInheritanceGraph(ReflectionClass $class) {
        $string, $padding = '';
        $inhgraph = array();
        $indent = '  ';

        while($class = $class->getParentClass()) {
            $inhgraph []= sprintf("%s+ extends %s",
                $padding,
                $this->console->colorize($class->name,
                    'green'));
            $padding .= $indent;
        };

        $ingraph []= sprintf("%s+ extends StdClass", $padding);

        echo $string . implode(PHP_EOL, $inhgraph) .
            PHP_EOL . PHP_EOL;
    }


    /**
     * Renders a method.
     *
     * @param ReflectionMethod $method The method
     *
     * @return string
     */
    public function renderMethod(ReflectionMethod $method) {
        $meta = $this->parseComment($method->getDocComment());
        $params = array();

        $delim = ',';

        $n = count($method->getParameters());
        if($n > 2) {
            $delim .= "\n    ";
        } else {
            $delim .= " ";
        }

        foreach($method->getParameters() as $p) {
            $typehint = '';
            if(isset($meta['params'][$p->name])) {
                $typehint = $meta['params'][$p->name]['type'] . ' ';
            }
            $params []= $typehint . 
                $this->console->colorize('$', 'yellow') . 
                $this->console->colorize($p->name, 'cyan');
        }
        return sprintf("\n - %s %s %s (%s)\n", 
            $this->renderModifiers($method),
            $this->console->colorize($meta['returnType'], 'cyan'),
            $this->console->colorize($method->name, 'white'),
            $n > 2 ? implode($delim, $params) 
                : implode($delim, $params)
        );
    }

        
    /**
     * Renders a class property.
     *  
     * @param ReflectionProperty $property The property
     *
     * @return string
     */
    public function renderProperty(ReflectionProperty $property) {
        return sprintf(" - %s %s%s\n",
            $this->renderModifiers($property),
            $this->console->colorize('$', 'yellow'), 
            $this->console->colorize($property->name, 'cyan')
        );
    }


    /**
     * Renders a class constant. Note that there is no ReflectionConstant
     * class. That's why $key and $value being passed.
     *
     * @param string $constname The constant's name
     * @param mixed  $value     The constant's value
     *
     * @return string
     */
    public function renderConstant($constname, $value) {
        return sprintf(' - %s = "%s";%s',
            $this->console->colorize($constname, 'white'),
            $this->console->colorize($value, 'yellow'),
            PHP_EOL
        );
    }


    /**
     * Renders modifiers (public, private, .., static, ..)
     * for a method or property.
     *
     * @param Reflector $reflector Any object which's type inherits Reflector
     * @param string    $style     Optional style attribute. Defaults to green
     *
     * @return string
     */
    public function renderModifiers(Reflector $reflector, $style = 'green') {
        if(method_exists($reflector, 'getModifiers')) {
            $mods = $reflector->getModifiers();
        } else {
            $mods = 0;
        }

        return $this->console->colorize(
            implode(' ', Reflection::getModifierNames($mods)),
            $style
        );
    }


    /**
     * Displays class methods.
     *
     * @param ReflectionClass $class The class
     *
     * @return Phish_Renderer_Console
     */
    public function displayMethods(ReflectionClass $class) {
        $this->console->writeln('Methods', 'blue,bold');
        $map = array();
        foreach($class->getMethods() as $m) {
            if($m->isConstructor()) {
                continue;
            }
            if(!isset($map[$m->getDeclaringClass()->getName()])) {
                $map[$m->getDeclaringClass()->getName()]
                    = array();
            } 
            $map[$m->getDeclaringClass()->getName()]
                []= $m;
        }

        foreach($map as $declaringClass => $methods) {
            $this->console->writeln(sprintf("%s %s %s",
                PHP_EOL, $declaringClass, PHP_EOL
            ), 'light');
            foreach ($methods as $m) {
                echo $this->renderMethod($m);
            }
        }
        $this->console->writeln();
        return $this;
    }


    /**
     * Displays a class constructor.
     *
     * @param ReflectionClass $class The class
     *
     * @return Phish_Renderer_Console
     */
    public function displayConstructor(ReflectionClass $class) {
        if(!is_null($class->getConstructor())) {
            $this->console->writeln('Constructor:', 'blue,bold');
            echo $this->renderMethod($class->getConstructor());
        }
        $this->console->writeln();
        return $this;
    }


    /**
     * Displays class properties.
     *
     * @param ReflectionClass $class The class
     *
     * @return Phish_Renderer_Console
     */
    public function displayProperties(ReflectionClass $class) {
        $this->console->writeln('Properties:', 'blue,bold');
        foreach ($class->getProperties() as $p) {
            echo $this->renderProperty($p);
        }
        $this->console->writeln();
        return $this;
    }


    /**
     * Displays class constants.
     *
     * @param ReflectionClass $class The class
     *
     * @return Phish_Renderer_Console
     */
    public function displayConstants(ReflectionClass $class) {
        $this->console->writeln('Constants:', 'blue,bold');
        foreach ($class->getConstants() as $key => $value) {
            echo $this->renderConstant($key, $value);
        }
        $this->console->writeln();
        return $this;
    }


    /** 
     * Displays a notice if a class or function wasn't found.
     *
     * @param string $search The search string that gave no results
     *
     * @return Phish_Renderer_Console
     */
    public function displayElementNotFound($search) {
        $this->console->writeln(
            'Class or function ' . $search . ' was not found', 'red'
        );
        return $this;
    }
}
