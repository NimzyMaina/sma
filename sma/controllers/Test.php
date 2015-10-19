<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Test extends MY_Controller {

	function __construct() {
		parent::__construct();
		
	}


function index (){

$user = json_decode(
    file_get_contents('http://admin:1234@localhost/sma/api/product/product/id/1/')
);
 
echo $user->error;

}
}