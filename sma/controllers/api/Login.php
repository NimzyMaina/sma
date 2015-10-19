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

class Login extends REST_Controller{
	function __construct() {
        // Construct our parent class
        parent::__construct();
        
        // Configure limits on our controller methods. Ensure
        // you have created the 'limits' table and enabled 'limits'
        // within application/config/rest.php
        
        $this->methods['login_post']['limit'] = 100; //100 requests per hour per user/key
        $this->load->model('auth_model');
        $this->load->model('companies_model');
        $this->load->library('form_validation');
        $this->load->library('ion_auth');
    }

    function login_post (){
        $this->form_validation->set_rules('email', 'Email', 'required|valid_email');
        $this->form_validation->set_rules('password','Password','required');

         if($this->form_validation->run() === FALSE){
         $password = form_error('password') ? form_error('password') : $this->post('password');
         $email = form_error('email') ? form_error('email') : $this->post('email');

         $response = array('error' => true,
        'message' => 'Minimum information required to process the request is missing/invalid',              
        'email' => strip_tags($email),
        'password' => strip_tags($password)
        );
           $this->response($response, 400);
        }
        else{
            if($this->form_validation->run() == true) {
                $result = $this->auth_model->loginApp($this->post('email'),$this->post('password'));
                if ( $result == false){
                    $response = array('error' => true,
                        'message' => 'Login Unsuccessfully');

                    $this->response($response,404);
                }
               // print_r($result);exit;
                //$warehouse = new stdClass;
                $w = $this->db->select('name')
                ->from('warehouses')
                ->where('id',$result->warehouse_id)
                ->get()
                ->result();
                foreach ($w as $a){
                    $name = $a->name;
                }

                $b = $this->db->select('name')
                ->from('companies')
                ->where('id',$result->biller_id)
                ->get()
                ->result();
                foreach ($b as $c){
                    $dist = $c->name;
                }
                

                $distributor = array('distributor' => $dist);
                
                $warehouse = array("warehouse_name" => $name);
                //echo $result->warehouse_id;exit;

                $result = get_object_vars($result);
                //print_r($result);exit;
                $result = array_merge($result, $warehouse);
                $result = array_merge($result, $distributor);
            $response = ['error' => false,
                        'message' => 'Login Successfully',
                        'data' => $result];
                        //$response['data'] = );
                        // $ar = array('warehouse_name' => 'eld');
                        // array_push($response['d'] ,$ar);


            $this->response($response,201);
            }
            else{
            $response = array('error' => true,
                        'message' => 'Login Unsuccessfully');

                    $this->response($response,404);
            }
        }
    }

    function login_get(){

           if(null === $this->session->userdata('identity')) {
           $data = ['error' => true,
           //'uid' => $this->session->userdata('user_id'),
        'message' => 'Unauthorized access'];

        $this->response($data,401);
        }

        $data = ['error' => false,
        'id' => $this->session->userdata('user_id')];

        $this->response($data,200);
    }

    function test_get (){
        if ($this->session->userdata('username') === null){
            $response = array(
                'error' => true,
                'message' => 'Unauthorized Access!!');
            $this->response($response,404);
        }
        $response = array(
            'username' => $this->session->userdata('username'));
        $this->response($response,200);
    }

    function logout_get (){
        $logout = $this->ion_auth->logout();
        $response = array(
                'error' => false,
                'message' => 'Logout Successful!!');
            $this->response($response,200);
    }
}