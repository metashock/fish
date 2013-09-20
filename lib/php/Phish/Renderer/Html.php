<?php



/**
 *
 */
class Renderer_Html extends Renderer_Console {

   
    /**
     *
     */ 
    public function __construct () {
        echo join("\r\n", array(
            '<html>',
            '    <head>',
            '    </head>',
            '    <body>'
        ));
    }


    /**
     *
     *  
     */
    public function __destruct() {
        echo join("\r\n", array(
            '    </body>',
            '</html>'
        ));
    }

}




