<?php
/**
 *
 *	@package Phish
 */
/**
 *
 *	@package Phish
 */
class Phish_FileAnalyzer
{


    protected $namespaces;


    protected $classes;

  
    /**
     *  Retrives information about user defined classes.
     *
	 *	@param string $path
 	 *	@return array
     */
    public function __construct($path) {
        Jm_Util_Checktype::check('string', $path);

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
                        $classname =
                            '\\' . $currentNamespace . '\\' . $classname;
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


    public function getNamespaces() {
        return $this->namespaces;
    }


    public function classes() {
        return $this->classes;
    }


    public function __toString() {
        $str  = 'Namespaces:' . PHP_EOL;
        $str .= '  ' . implode(PHP_EOL . "  ", $this->getNamespaces());
        $str .= PHP_EOL;
        $str  = 'Classes:' . PHP_EOL;
        $str .= '  ' . implode(PHP_EOL . "  ", $this->classes());
        return $str;
    }
}

