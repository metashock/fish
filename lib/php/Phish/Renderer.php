<?php


abstract class Phish_Renderer {


    /**
     *
     */
    protected function parseComment($string) {

        //$returnPattern = "/^[ \t]*\*[ \t]*\@return[ \t]*[a-bA-B_]+[a-bA-B_0-9]?[ \t]?$/";

        $returnPattern = "/@return[ \t]*([a-zA-Z_]+[a-zA-Z0-9_]?)[ \t]?/";
        $paramPattern = "/@param[ \t]*([a-zA-Z_]+[a-zA-Z0-9_]?)[ \t]*.([a-zA-Z_]+[a-zA-Z0-9_]?)[ \t]?/";

        $meta = array(
            'returnType' => '?',
            'params' => array()
        );

        foreach(explode(PHP_EOL, $string) as $line) {
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

