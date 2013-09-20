<?php

class Phish_Protocol_CommandFactory
{

    public function createfromDOMNode(DOMNode $node) {
        $classname  = 'Phish_Protocol_Command_';
        $classname .= ucfirst($node->nodeName);

        $cmd = $classname::createFromDOMNode($node);
        return $cmd;
    }
}
