<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Example
 *
 * This is an example of a few basic user interaction methods you could use
 * all done with a hardcoded array.
 *
 * @package		CodeIgniter
 * @subpackage	Rest Server
 * @category	Controller
 * @author		Phil Sturgeon
 * @link		http://philsturgeon.co.uk/code/
*/

// This can be removed if you use __autoload() in config.php OR use Modular Extensions
require APPPATH.'/libraries/REST_Controller.php';

class Custom extends REST_Controller{
	function __construct() {
        // Construct our parent class
        parent::__construct();
        $this->load->model('custom_model');
    }

    public function routes_get (){
    	$routes = $this->custom_model->getRoutes();
         
        if($routes){
            $data = array( 'error' => false,
            'returned ' => $routes);

        $this->response($data,200);
        }
 
        else{
            $data = array('error' => true,
            	'message' => 'Error: No Routes Found');
            $this->response($data,404);
        }
    }

	public function outlets_get (){
    	$outlets = $this->custom_model->getOutlets();
         
        if($routes){
            $data = array( 'error' => false,
            'returned ' => $outlets);

        $this->response($data,200);
        }
 
        else{
           $data = array('error' => true,
            	'message' => 'Error: No Outlets Found');
            $this->response($data,404);
        }
    }    


}