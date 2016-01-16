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

class Customer extends REST_Controller{
	function __construct() {
        // Construct our parent class
        parent::__construct();
        // Configure limits on our controller methods. Ensure
        // you have created the 'limits' table and enabled 'limits'
        // within application/config/rest.php
        $this->methods['customer_get']['limit'] = 500; //500 requests per hour per user/key
        $this->methods['customer_post']['limit'] = 100; //100 requests per hour per user/key
        $this->methods['customer_delete']['limit'] = 50; //50 requests per hour per user/key
        $this->methods['customers_get']['limit'] = 500;
        $this->load->model('companies_model');
        $this->load->library('form_validation');
    }

    function customer_get (){
if( null !== $this->session->userdata('user_id')) {
           $data = ['error' => true,
        'message' => 'Unauthorized access'];

        $this->response($data,401);
        }

        if(!$this->get('id')){
           $data = array('error' => true,
            'message' => 'Missing Customer ID');

        $this->response($data,404);
        }

        $customer = $this->companies_model->getCompanyByID($this->get('id'));

        if($customer){
        $data = array( 'error' => false,
            'returned ' => $customer,
            'data' => $this->session->userdata('identity') );

        $this->response($data,200);
        }
        else {
        $data = array('error' => true,
            'message' => 'Customer Could not be found');

        $this->response($data,404);
        }
    }

    function customers_get (){
        $customers = $this->companies_model->getAllCustomerCompaniesApp();

        //print_r($customers);exit;
         
        if($customers){
            $data = array( 'error' => false,
            'returned ' => $customers );

        $this->response($data,200);
        }
 
        else{
            $data = array('error' => true,
                'message' => 'No customers Found');
            $this->response($data,404);
        }
    }
function customer_post (){
    $this->form_validation->set_rules('name','Customer Name','required|callback_alpha_only_space');
    $this->form_validation->set_rules('email', 'Customer Email', 'is_unique[companies.email]|required|valid_email');
    $this->form_validation->set_rules('customer_group','Customer Group','numeric|required');
    $this->form_validation->set_rules('customer_group_name', 'Customer Group Name','required|callback_alpha_only_space');
    $this->form_validation->set_rules('company', 'Company','required|callback_alpha_only_space');
    $this->form_validation->set_rules('address','Customer Address','required');
    $this->form_validation->set_rules('vat_no','VAT Number','trim');
    $this->form_validation->set_rules('city','City','required|callback_alpha_only_space');
    $this->form_validation->set_rules('state','State','trim|callback_alpha_only_space');
    $this->form_validation->set_rules('postal_code','Postal Code','trim');
    $this->form_validation->set_rules('phone', 'Phone Number', 'trim');
    $this->form_validation->set_rules('country','Country','trim|callback_alpha_only_space');

     if($this->form_validation->run() === FALSE){
         $name = form_error('name') ? form_error('name') : $this->input->post('name');
         $email = form_error('email') ? form_error('email') : $this->input->post('email');
         $customer_group = form_error('customer_group') ? form_error('customer_group') : $this->input->post('customer_group');
         $customer_group_name = form_error('customer_group_name') ? form_error('customer_group_name') : $this->input->post('customer_group_name');
         $company = form_error('company') ? form_error('company') : $this->input->post('company');
         $address = form_error('address') ? form_error('address') : $this->input->post('address');
         $vat_no = form_error('vat_no') ? form_error('vat_no') : $this->input->post('vat_no');
         $city = form_error('city') ? form_error('city') : $this->input->post('city');
         $state = form_error('state') ? form_error('state') : $this->input->post('state');
         $postal_code = form_error('postal_code') ? form_error('postal_code') : $this->input->post('postal_code');
         $phone = form_error('phone') ? form_error('phone') : $this->input->post('phone');
         $country = form_error('country') ? form_error('country') : $this->input->post('country');

         $response = array('error' => true,
        'message' => 'Minimum information required to process the request is missing/invalid',              
        'name' => strip_tags($name),
        'email' => strip_tags($email),
        'group_id' => '3',
        'group_name' => 'customer',
        'customer_group' => strip_tags($customer_group),
        'customer_group_name' => strip_tags($customer_group_name),
        'company' => strip_tags($company),
        'address' => strip_tags($address),
        'vat_no' => strip_tags($vat_no),
        'city' => strip_tags($city),
        'state' => strip_tags($state),
        'postal_code' => strip_tags($postal_code),
        'country' => strip_tags($country)
    );

    $this->response($response, 400);
    }
    else {
         $data = array('name' => $this->input->post('name'),
                'email' => $this->input->post('email'),
                'group_id' => '3',
                'group_name' => 'customer',
                'customer_group_id' => 1,
                'customer_group_name' => 'General',
                'company' => $this->input->post('company'),
                'address' => $this->input->post('address'),
                'vat_no' => $this->input->post('vat_no'),
                'city' => $this->input->post('city'),
                'state' => $this->input->post('state'),
                'postal_code' => $this->input->post('postal_code'),
                'country' => $this->input->post('country'),
                'phone' => $this->input->post('phone'),
                'warehouse_id' => $this->session->userdata('warehouse_id')
            );
    if($this->form_validation->run() == true && $this->companies_model->addCompany($data) == true) {
    $response = ['error' => false,
                'message' => 'The Customer added successfully'];
    $this->response($response,201);
    }
    else{
    $response = array('error' => true,
                'message' => 'Customer Could not be Added');

            $this->response($response,404);
    }
}
 
}

function alpha_only_space($str){
        if (!preg_match("/^([-a-z ])+$/i", $str))
        {
            $this->form_validation->set_message('alpha_only_space', 'The %s field must contain only alphabets or spaces');
            return FALSE;
        }
        else
        {
            return TRUE;
        }
    }

function test_post (){
    //$this->form_validation->set_error_delimiters('','');
    $this->form_validation->set_rules('id','ID','required|numeric');
    $this->form_validation->set_rules('name', 'Product Name', 'required|callback_alpha_only_space');
    $this->form_validation->set_rules("email",'Customer E-mail', 'is_unique[companies.email]');

    if($this->form_validation->run() === FALSE){
         $Id = form_error('id') ? form_error('id') : $this->post('id');
         $name = form_error('name') ? form_error('name') : $this->post('name');
         $email = form_error('email') ? form_error('email') : $this->post('email');

         $response = array('error' => true,
        'message' => 'Minimum information required to process the request is missing/invalid',              
        'id' => strip_tags($Id),
        'name' => strip_tags($name),
        'email' => strip_tags($email)
    );

    $this->response($response, 400);
    }else {

    $response = ['message' => 'I dont know what this is'];
    $this->response($response,201);
}
}

function test2_post (){
    $_POST = json_decode(file_get_contents("php://input"), true);
$this->form_validation->set_rules('id', 'id', 'required|numeric');

if($this->form_validation->run() == TRUE)
{   
    $id = $this->input->post('id');  
    echo $id;       
}
else
{   
    echo "false";        
}
}

}