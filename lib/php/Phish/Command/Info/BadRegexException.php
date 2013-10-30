<?php

class Phish_Command_Info_BadRegexException extends Exception
{

    public function __construct($message, $code, $regex) {
    
        switch($code) {
            case PREG_NO_ERROR:
                $message = 'Success';
                break;

            case PREG_INTERNAL_ERROR:
                $message = 'Internal Error';
                break;

            case PREG_BACKTRACK_LIMIT_ERROR:
                $message = 'Backtrack Limit has been reached';
                break;

            case PREG_RECURSION_LIMIT_ERROR:
                $message = 'Recursion limit has been reached';
                break;

            case PREG_BAD_UTF8_ERROR:
                $message = 'Bad UTF-8';
                break;

            case PREG_BAD_UTF8_OFFSET_ERROR:
                $message = 'Bad UTF-8 offset';
                break;

            // custom error code.
            // @see http://akrabat.com/php/preg_last_error-returns-no-error-on-preg_match-failure/
            case -1 :
                // message will come from caller
                break;
                

            default :
                $message = 'Unknown error';
        }

        $message .= ' (' . $regex . ')';
        return parent::__construct($message, $code);
    }

}

