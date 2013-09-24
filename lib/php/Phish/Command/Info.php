<?php

class Phish_Command_Info extends Phish_Command
{

    /**
     * Runs the command
     */
    public function run() {
        if(!isset($this->argv[2])) {
            self::usage();
        }

        $search = $this->argv[2];
        $index = Phish_Index::load('phish_info');
        $entry = $index->findclass($search);

        $configuration = new Jm_Configuration_Xmlfile('phish.xml');

        foreach($configuration->monitor->path as $path) {
            if($path->has('prefix')) {
                $prefix = $path->prefix;
            } else {
                $prefix = '';
            }
            Jm_Autoloader::singleton()->addPath($path, $prefix);
        }

        if(!is_null($entry)) {
            require_once $entry;
        }

        $renderer = new Phish_Renderer_Console();
        if(class_exists($search)) {
            $renderer->renderClass(new ReflectionClass($search), $entry);
        } else if (function_exists($search)) {
            $renderer->renderFunction(new ReflectionFunction($search));
        } else { 
            $renderer->renderElementNotFound($search);
        }
    }


    public static function shortdesc() {
        return 'Displays reflection information about a class or function';
    }


    public static function usage() {
        $console = Jm_Console::singleton();
        $console->write('USAGE: ', 'bold');
        $console->writeln('phish info SEARCH');
        $console->writeln();
        $console->write('SEARCH ', 'bold');
        $console->writeln('can be a class name or a function name.');
        $console->writeln();
        exit(1);
    }
}
