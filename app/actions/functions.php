<?php 
/**
 * Global functions available to all actions pages
 * 
 * @author Ubani Balogun <ubani@sas.upenn.edu>
 * @package HECTOR
 */



/**
 * Add a js file to the header of a page (admin_headers.tpl.php)
 * @param String $filename The name of the js file to add
 */
function hector_add_js($filename){
	global $testscripts;
	
	if ($filename != '' && in_hector_jsroot($filename)){
		$script = "<script type='text/javascript' src='js/$filename'></script>";
		if (!in_array($script, $testscripts)){
			$testscripts[] = $script;
		}
	}
}


/**
 * Checks if a javascript file is in HECTOR's javascript directory
 * 
 * @param String $filename The javascript file to check for
 */

function in_hector_jsroot($filename){
	global $jsroot;
	if ($filename !=''){
		$filepath = $jsroot . '/' . $filename;
		$exists = file_exists($filepath);
		$in_root = ($jsroot == dirname($filepath));
		if ($exists && $in_root){
			return true;
		}	
	}
	return false;
}
?>