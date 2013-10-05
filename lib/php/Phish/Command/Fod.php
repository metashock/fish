<?php

class Phish_Command_Fod extends Phish_Command
{

    public function run() {
        $provider = new Phish_DocumentationProvider_PHPWebsite();
        $functions = get_defined_functions();
        $functions = $functions['internal'];
        $randomIndex = rand(0, count($functions) - 1);
        $function = $functions[$randomIndex];
        $description = $provider->getDocumentationForFunction($function);

        $console = Jm_Console::singleton();
        $renderer = new Phish_Renderer_Console();
        $renderer->renderFunction(new ReflectionFunction($function));
        $console->writeln();
        $console->writeln($description);
        $console->writeln(); 
    }


    public static function shortdesc() {
        return 'Displays a random php core function'
            . ' and it\'s short description';
    }


    public static function usage() {
        $console = Jm_Console::singleton();
        $console->write('USAGE: ', 'bold');
        $console->writeln('phish fod');
        $console->writeln();
        $console->writeln(self::shortdesc());
        $console->writeln();
    }
}


/**
 *
 */
class Phish_DocumentationProvider_PHPWebsite
{

    protected $baseurl;


    /**
     *
     */
    public function __construct($configuration = NULL) {
        $this->baseurl = 'http://php.net/manual/en/function.%s.php';
    }

    /**
     *
     */
    public function getDocumentationForFunction($function) {
        $f = str_replace('_', '-', strtolower($function));
        $content = file_get_contents(sprintf($this->baseurl, $f));
        $doc = new DOMDocument();
        @$doc->loadHTML($content);

        $selector = new DOMXPath($doc);
        foreach($selector->query('//p[@class="para rdfs-comment"]') as $node) {
            return str_replace(array("\n", '  '), ' ', trim($node->nodeValue));
        }
    }
}


