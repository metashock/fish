<?php



/**
 *
 */
class Phish_Renderer_Console extends Phish_Renderer 
{


    protected $console;


    public function __construct(){
        $this->console = Jm_Console::singleton();
    }


    /**
     *
     */
    public function renderClass(ReflectionClass $rc, $file = '') {
        $this->console->write('Class ', 'green,bold');
        $this->console->write($rc->name, 'green');
        $this->console->writeln(' (' . $file . ')', 'light'); 
        $this->renderInheritanceGraph($rc);
        $this->renderConstants($rc);   
        $this->renderProperties($rc);
        $this->renderConstructor($rc);
        $this->renderMethods($rc);
    }



    /**
     *
     */
    public function renderFunction($function) {

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
    }




    /**
     *
     *  @return string
     */
    public function linkToPhpNetFunctionDoc($function) {
        $link = 'http://www.php.net/en/function.' . str_replace('_', '-', $function->name);
        return $link;
    }


    /**
     *
     */
    public function renderInheritanceGraph($rc) {

        $string = '';

        $inhgraph = array();
        $padding = '  ';

        while($rc = $rc->getParentClass()) {
            $inhgraph []= sprintf("%s+ extends %s",
                $padding,
                $this->console->colorize($rc->name,
                    'green'));
            $padding .= '  ';
        };

        $ingraph []= sprintf("%s+ extends StdClass", $padding);

        echo $string . implode(PHP_EOL, $inhgraph) .
            PHP_EOL . PHP_EOL;
    }


    /**
     *  @return string
     */
    public function renderMethod($method) {

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
        return sprintf("%s\n - %s %s %s (%s)\n", 
            // $comment['text'] //,
            $this->renderModifiers($method),
            $this->console->colorize($meta['returnType'], 'cyan'),
            $this->console->colorize($method->name, 'white'),
            $n > 2 ? implode($delim, $params) 
                : implode($delim, $params)
        );

        return PHP_EOL;
    }
        
    /**
     *  @return string
     */
    public function renderProperty($property) {
        return sprintf(" - %s %s%s\n",
            $this->renderModifiers($property),
            $this->console->colorize('$', 'yellow'), 
            $this->console->colorize($property->name, 'cyan')
        );
    }


    /**
     *  @return string
     */
    public function renderConstant($constant) {
        return;

        return sprintf(" - %s %s%s\n",
            $this->renderModifiers($constant),
            $this->console->colorize('$',
                $this->console->YELLOW, $this->console->BOLD), 
            $this->console->colorize($constant->name,
                $this->console->GRAY, $this->console->BOLD)
        );
    }


    public function renderModifiers($r) {
        $mods = array();
        if($r->isPublic()) {
            $mods []= 'public';
        }
        if($r->isProtected()) {
            $mods []= 'protected';
        }
        if($r->isPrivate()) {
            $mods []= 'private';
        }
        if($r->isStatic()) {
            $mods []= 'static';
        }
      
        return $this->console->colorize(implode(' ', $mods), 'green');
    }


    /**
     *
     */
    public function renderMethods($rc) {
        $this->console->writeln('Methods', 'blue,bold');
        $map = array();
        foreach($rc->getMethods() as $m) {
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
            $this->console->writeln(sprintf("%s %s %s", PHP_EOL, $declaringClass, PHP_EOL), 'light');
            foreach ($methods as $m) {
                echo $this->renderMethod($m);
            }
        }
        $this->console->writeln();
    }


    /**
     *
     */
    public function renderConstructor($rc) {
        if(!is_null($rc->getConstructor())) {
            $this->console->writeln('Constructor:', 'blue,bold');
            echo $this->renderMethod($rc->getConstructor());
        }
        $this->console->writeln();
    }

    /**
     *
     */
    public function renderProperties($rc) {
        $this->console->writeln('Properties:', 'blue,bold');
        foreach ($rc->getProperties() as $p) {
            echo $this->renderProperty($p);
        }
        $this->console->writeln();
    }


    /**
     *
     */
    public function renderConstants($rc) {
        $this->console->writeln('Constants:', 'blue,bold');
        foreach ($rc->getConstants() as $c) {
            echo $this->renderConstant($c);
        }
        $this->console->writeln();
    }


    public function renderElementNotFound($search) {
        $this->console->writeln(
            'Class or function ' . $search . ' was not found', 'red'
        );
    }

}

