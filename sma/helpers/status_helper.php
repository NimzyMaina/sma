<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

if ( ! function_exists('label_this'))
{
	public function label_this($str){
    if($str == "true"){
        $r =  label_badge('success', 'True'); 
    } else if ($str == "false"){
       $r = label_badge('warning', 'False'); 
    }

    return $r;
}
}