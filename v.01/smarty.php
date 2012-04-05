<?php
/*********************************************************
 * smarty extend class
 * 
 *	liuyang
 *	liuyang5@staff.sina.com.cn
**********************************************************/

require_once("Smarty/Smarty.class.php");

class smarty_client extends Smarty { 
   function __construct() { 

       $this->template_dir = $_SERVER['DOCUMENT_ROOT'] . "/neutrogena/templates/";
       $compile_dir = $_SERVER['SINASRV_CACHE_DIR']."/neutrogena/";
		if (!is_dir($compile_dir))
		{
			mkdir($compile_dir, 0777);
		}
		$this->left_delimiter = "<{";
	    $this->right_delimiter = "}>";
        $this->compile_dir  = $compile_dir;
        $this->compile_check  = true;
		$this->force_compile = true;
//		$this->cache_dir    = $compile_dir;
		$this->caching = false;
		$this->debugging = false;
   }
}
