<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Example
 *
 * This is an example of a few basic user interaction methods you could use
 * all done with a hardcoded array.
 *
 * @package     CodeIgniter
 * @subpackage  Rest Server
 * @category    Controller
 * @author      Phil Sturgeon
 * @link        http://philsturgeon.co.uk/code/
*/

// This can be removed if you use __autoload() in config.php OR use Modular Extensions
require APPPATH.'/libraries/REST_Controller.php';

class Product extends REST_Controller{
    function __construct() {
        // Construct our parent class
        parent::__construct();
        
        // Configure limits on our controller methods. Ensure
        // you have created the 'limits' table and enabled 'limits'
        // within application/config/rest.php
        $this->methods['product_get']['limit'] = 500; //500 requests per hour per user/key
        $this->methods['product_post']['limit'] = 100; //100 requests per hour per user/key
        $this->methods['product_delete']['limit'] = 50; //50 requests per hour per user/key
        $this->methods['products_get']['limit'] = 500;
        $this->load->model('products_model');
        $this->load->model('site');
    }

    function product_get (){
        if(!$this->get('id')){
           $data = array('error' => true,
            'message' => 'Missing Product ID');

        $this->response($data,404);
        }

        $product = $this->products_model->getProductByID($this->get('id'));

        if($product){
        $data = array( 'error' => false,
            'returned ' => $product );

        $this->response($data,200);
        }
        else {
        $data = array('error' => true,
            'message' => 'Product Could not be found');

        $this->response($data,404);
        }
    }

    function product_post (){
        $data = array('returned: '. $this->post('id'));
        $this->response($data);
    }

    function product_put (){
        $data = array('returned: '. $this->put('id'));
        $this->response($data);
    }

    function product_delete (){
        $data = array('returned: '. $this->delete('id'));
        $this->response($data);
    }

    // function products_get (){
    //  $products = $this->products_model->getAllProducts();
         
 //        if($products){
 //         $data = array( 'error' => false,
    //      'returned ' => $products );

 //        $this->response($data,200);
 //        }
 
 //        else{
 //         $data = array('error' => true,
 //            'message' => 'No Product Found');
 //            $this->response($data,404);
 //        }
    // }

    function products_get (){
        // $this->sma->checkPermissions('index');

        // if(null !== $this->session->userdata('warehouse_id')) {
        //     $user = $this->site->getUser();
        //     $warehouse_id = $this->session->userdata('warehouse_id');
        //     $products = $this->products_model->test($warehouse_id);
        // }
        // else{
            $products = $this->products_model->test();
        //}


        if($products){
            $data = array( 'error' => false,
            'returned ' => $products );

        $this->response($data,200);
        }
 
        else{
            $data = array('error' => true,
            'message' => 'No Product Found');
            $this->response($data,404);
        }

    }

    function session_get (){
        print_r( $this->session->userdata());
    }
}

// $this->form_validation->set_rules('email', 'Email', 'required|valid_email');
//         $this->form_validation->set_rules('password','Password','required');

//          if($this->form_validation->run() === FALSE){
//          $password = form_error('password') ? form_error('password') : $this->post('password');
//          $email = form_error('email') ? form_error('email') : $this->post('email');

//          $response = array('error' => true,
//         'message' => 'Minimum information required to process the request is missing/invalid',              
//         'password' => strip_tags($password),
//         'email' => strip_tags($email)
//         );
//            $this->response($response, 400);
//         }
//         else{
//             if($this->form_validation->run() == true && $this->auth_model->login($this->post('email').$this->post('password')) == true) {
//             $response = ['error' => false,
//                         'message' => 'Login Successfully'];
//             $this->response($response,201);
//             }
//             else{
//             $response = array('error' => true,
//                         'message' => 'Login Unsuccessfully');

//                     $this->response($data,404);
//             }
//         }