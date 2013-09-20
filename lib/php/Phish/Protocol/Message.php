<?php


class Phish_Protocol_Message
{

    protected $commands;


    /**
     * @throws Phish_Protocol_InvalidMessageException
     */
    public function __construct($string = '') {
        Jm_Util_Checktype::check('string', $string);
        $this->commands = new SplQueue();
        if(!empty($string)) {
            $this->parse($string);            
        }
    }


    /**
     *
     * @TODO
     * @throws Phish_Protocol_InvalidMessageException
     */
    protected function parse($string) {
        $doc = new DOMDocument();
        $doc->loadXML($string);
        $doc->preserveWhitespace = TRUE;

        $this->commands = new SplQueue();
        $factory = new Phish_Protocol_CommandFactory();

        foreach($doc->firstChild->childNodes as $cmd) {
            if($cmd->nodeType !== XML_ELEMENT_NODE) {
                continue;
            }
            
            $this->commands[]= $factory->createFromDOMNode($cmd);
        }
    }


    public function commands() {
        return $this->commands;
    }
}
