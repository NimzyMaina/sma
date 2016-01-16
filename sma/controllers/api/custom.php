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
         
        if($outlets){
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

    public function conversions_get(){
        $conversions = $this->custom_model->getConversions();

        if($conversions){
            $data =array('error' => false,
                'returned' => $conversions);

            $this->response($data,200);
        }
        else{
           $data = array('error' => true,
                'message' => 'Error: No Conversions Found');
            $this->response($data,404);
        }
    }

    public function conversion_get($id = NULL){
                if(!$this->get('id')){
           $data = array('error' => true,
            'message' => 'Missing Conversion ID');

        $this->response($data,404);
        }

        $conversion = $this->custom_model->getConversionByID($this->get('id'));

        if($conversion){
              $data =array('error' => false,
                'returned' => $conversion);

            $this->response($data,200);
        }
        else{
           $data = array('error' => true,
                'message' => 'Error: No Conversion Found');
            $this->response($data,404);
        }
    }

    public function targets_get(){
     $id = $this->session->userdata('user_id');
        $data  = array();
        $master =array();

       // echo $target = $this->custom_model->getMonthlyTarget($id);
       // echo $sales = $this->custom_model->getMonthlySales($id);
        $tags = $this->custom_model->getUserTargets($id);
        //print_r($tags);exit;
        if($tags){
            foreach($tags as $tag){
                $sales = $this->custom_model->getMonthlySales($id,$tag->category_id);
                //print_r($sales);exit;
                if($sales){
                 $data['sales'] = $sales;
                }else{
                 $data['sales'] = 0;
                }
                $data['targets'] = $tag->target;
                $data['month'] = $tag->month;
                $data['category'] = $tag->name;
                $data['variance'] = ceil(($data['sales']/$tag->target)*100)."%";
                array_push($master, $data);
            }
            $response = array('error' => false,
                'returned' => $master);

            $this->response($response,200);

        }else{
            $response = array('error' => true,
                'message' => 'Error: No Targets Found');
            $this->response($response,404);
        }
    }

    public function product_conversions_get(){
                $conversions = $this->custom_model->getProductConversions();

        if($conversions){
            $data =array('error' => false,
                'returned' => $conversions);

            $this->response($data,200);
        }
        else{
           $data = array('error' => true,
                'message' => 'Error: No Product Conversions Found');
            $this->response($data,404);
        }
    }

    public function types_get(){
        $types = $this->custom_model->getTypes();
        if($types){
            $data =array('error' => false,
                'returned' => $types);

            $this->response($data,200);
        }
        else{
           $data = array('error' => true,
                'message' => 'Error: No Types Found');
            $this->response($data,404);
        }
    }

}