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

class Sales extends REST_Controller {
	function __construct() {
        // Construct our parent class
        parent::__construct();
        
        // Configure limits on our controller methods. Ensure
        // you have created the 'limits' table and enabled 'limits'
        // within application/config/rest.php
        $this->methods['sale_get']['limit'] = 500; //500 requests per hour per user/key
        $this->methods['sale_post']['limit'] = 100; //100 requests per hour per user/key
        $this->methods['sale_delete']['limit'] = 50; //50 requests per hour per user/key
        $this->methods['sales_get']['limit'] = 500;
        $this->load->model('site');
        $this->load->model('sales_model');
        $this->lang->load('sales', $this->Settings->language);
        $this->load->library('form_validation');

        if(null === $this->session->userdata('identity')) {
           $data = ['error' => true,
           //'uid' => $this->session->userdata('user_id'),
        'message' => 'Unauthorized access'];

        $this->response($data,401);
        }
    }

    function sales2_get (){
        $sale = $this->site->getWarehouseByID($this->get('id'));
        if($sale){
            $data = array( 'error' => false,
            'returned ' => $sale );

        $this->response($data,200);
        }
 
        else{
            $data = array('error' => true,
            'message' => 'No Sales Found');
            $this->response($data,404);
        }
    }

    function sales_get($warehouse_id = NULL){
        // if($this->session->userdata('group_id') == 1) {
        //     $this->data['warehouses'] = $this->site->getAllWarehouses();
        //     $this->data['warehouse_id'] = $warehouse_id;
        //     $this->data['warehouse'] = $warehouse_id ? $this->site->getWarehouseByID($warehouse_id) : NULL;
        // } else {
        //     $this->data['warehouses'] = NULL;
        //     $this->data['warehouse_id'] = $this->session->userdata('warehouse_id');
        //     $this->data['warehouse'] = $this->session->userdata('warehouse_id') ? $this->site->getWarehouseByID($this->session->userdata('warehouse_id')) : NULL;
        // }

        // $this->sma->checkPermissions('index');

        // if($this->session->userdata('group_id') != 1) {
        //     $user = $this->site->getUser();
        //     $warehouse_id = $user->warehouse_id;
        //     $sales = $this->sales_model->testsales($warehouse_id);
        // }
        // else{
            $sales = $this->sales_model->getUserSales2();
        //}

        if($sales){
            $data = array( 'error' => false,
            'returned ' => $sales );

        $this->response($data,200);
        }
 
        else{
            $data = array('error' => true,
            'message' => 'No Sales Found');
            $this->response($data,404);
        }
    }
//     function sales_get (){
//         // if(!$this->Owner && !$warehouse_id) {
//         //     $user = $this->site->getUser();
//         //     $warehouse_id = $user->warehouse_id;
//         // }
//         // if($warehouse_id) {
//         // $this->db->select('id, date, reference_no, biller, customer, sale_status, grand_total, paid, (grand_total-paid) as balance, payment_status');
//         // $this->db->from('sales');
//         // $this->db->where('warehouse_id', $warehouse_id);
//         // }
//         // else{
//            $this->db->select('id, date, reference_no, biller, customer, sale_status, grand_total, paid, (grand_total-paid) as balance, payment_status');
//         $this->db->from('sales'); 
//         //}
//         $this->db->where('pos !=', 1);
//          $query = $this->db->get();
//         //$sales = $query->result();
//         if($query->num_rows()<1){
//             $response = array('error' => false,
//                 'message' => 'No Sales In Database');
//             $this->response($response,200);
//         }
//         if($query->num_rows() > 0){
//             foreach (($query->result()) as $row) {
//                 $data[] = $row;
//             }
//             $response = array( 'error' => false,
//             'returned ' => $data );

//         $this->response($response,200);
//     }
//     else{
//         $response = array('error'=> true,
//             'message' => 'Could Not execute Request');
//         $this->response($response,404);
//     }
// }
function ref_get(){
    echo $this->site->getReference('pay');
}
    function sale_get(){
        if(!$this->get('id')){
           $data = array('error' => true,
            'message' => 'Missing Sales ID');

        $this->response($data,404);
        }
         $this->db->select('id, date, reference_no, biller, customer, sale_status, grand_total, paid, (grand_total-paid) as balance, payment_status');
        $this->db->from('sales'); 
        //}
        $this->db->where('pos !=', 1);
        $this->db->where('id',$this->get('id'));
         $query = $this->db->get();
        //$sales = $query->result();
        if($query->num_rows()<1){
            $response = array('error' => false,
                'message' => 'No Sale Found');
            $this->response($response,200);
        }
        if($query->num_rows() > 0){
            foreach (($query->result()) as $row) {
                $data[] = $row;
            }
            $response = array( 'error' => false,
            'returned ' => $data );

        $this->response($response,200);
    }
    else{
        $response = array('error'=> true,
            'message' => 'Could Not execute Request');
        $this->response($response,404);
    }
    }

   

        function test_post(){
         //$this->sma->checkPermissions();
        
        //$this->form_validation->set_message('is_natural_no_zero', lang("no_zero_required"));
        //$this->form_validation->set_rules('reference_no', "Reference No", 'required');
        $this->form_validation->set_rules('customer', "Customer", 'required');
        $this->form_validation->set_rules('biller', "Biller", 'required');
        $this->form_validation->set_rules('sale_status', "Sale Status", 'required');
        $this->form_validation->set_rules('payment_status', "Payment Status", 'required');
        //$this->form_validation->set_rules('warehouse_id' , "Warehouse ID" , 'required');
        $this->form_validation->set_rules('order_tax' , "Order Tax" , 'required');
        $this->form_validation->set_rules('total_items' , "Total Items" , 'required');
        $this->form_validation->set_rules('amount-paid' , "Amount Paid" , 'required');
        $this->form_validation->set_rules('product_id[]' , "Product ID" , 'required');
        $this->form_validation->set_rules('product_code[]' , "Product Code" , 'required');
        $this->form_validation->set_rules('product_name[]' , "Product Name" , 'required');
        $this->form_validation->set_rules('product_type[]' , "Product Type" , 'required');
        $this->form_validation->set_rules('quantity[]' , "Quantity" , 'required');
        $this->form_validation->set_rules('product_tax[]' , "Product Tax" , 'required');
        $this->form_validation->set_rules('net_price[]' , "Net Price" , 'required');
        $this->form_validation->set_rules('route_id' , "Route ID" , 'required');
        $this->form_validation->set_rules('outlet_id' , "Outlet ID" , 'required');
        $this->form_validation->set_rules('type' , "Type" , 'required');
        $this->form_validation->set_rules('receipt_no' , "Receipt No" , 'required|is_unique[sales.receipt_no]');



        //$this->form_validation->set_rules('note', lang("note"), 'xss_clean');

        if($this->form_validation->run() === FALSE){
            //$reference_no = form_error('reference_no') ? form_error('reference_no') : $this->post('reference_no');
            $customer = form_error('customer') ? form_error('customer') : $this->post('customer');
            $biller = form_error('biller') ? form_error('biller') : $this->post('biller');
            $sale_status = form_error('sale_status') ? form_error('sale_status') : $this->post('sale_status');
            $payment_status = form_error('payment_status') ? form_error('payment_status') : $this->post('payment_status');
            //$warehouse_id = form_error('warehouse_id') ? form_error('warehouse_id') : $this->post('warehouse_id');
            $order_tax = form_error('order_tax') ? form_error('order_tax') : $this->post('order_tax');
            $total_items = form_error('total_items') ? form_error('total_items') : $this->post('total_items');
            $amount_paid = form_error('amount-paid') ? form_error('amount-paid') : $this->post('amount-paid');
            $product_id = form_error('product_id[]') ? form_error('product_id[]') : $this->post('product_id[]');
            $product_code = form_error('product_code[]') ? form_error('product_code[]') : $this->post('product_code[]');
            $product_name = form_error('product_name[]') ? form_error('product_name[]') : $this->post('product_name[]');
            $product_type = form_error('product_type[]') ? form_error('product_type[]') : $this->post('product_type[]');
            $product_tax = form_error('product_tax[]') ? form_error('product_tax[]') : $this->post('product_tax[]');
            $net_price = form_error('net_price[]') ? form_error('net_price[]') : $this->post('net_price[]');
             $paid_by = form_error('paid_by') ? form_error('paid_by') : $this->post('paid_by');
             $quantity = form_error('quantity[]') ? form_error('quantity[]') : $this->post('quantity[]');
             $route_id = form_error('route_id') ? form_error('route_id') : $this->post('route_id');
             $outlet_id = form_error('outlet_id') ? form_error('outlet_id') : $this->post('outlet_id');
             $type = form_error('type') ? form_error('type') : $this->post('type');
             $receipt_no = form_error('receipt_no') ? form_error('receipt_no') : $this->post('receipt_no');


            $_POST = array();

            $response = array('error' => true,
                //'reference_no' => strip_tags($reference_no),
                'customer' =>strip_tags ($customer),
                'biller' => strip_tags($biller),
                'sale_status'=> strip_tags($sale_status),
                'payment_status' => strip_tags($payment_status),
                'warehouse_id' => strip_tags($warehouse_id),
                'order_tax' => strip_tags($order_tax),
                'total_items' => strip_tags($total_items),
                'paid_by' => strip_tags($paid_by),
                'quantity' => strip_tags($quantity),
                'amount-paid' => strip_tags($amount_paid),
                'product_id[]' => strip_tags($product_id),
                'product_code[]' => strip_tags($product_code),
                'product_name[]' => strip_tags($product_name),
                'product_type[]' => strip_tags($product_type),
                'product_tax[]' => strip_tags($product_tax),
                'route_id' => strip_tags($route_id),
                'outlet_id' => strip_tags($outlet_id),
                'type' => strip_tags($type),
                'receipt_no' => strip_tags($receipt_no),
                'net_price' => strip_tags($net_price));

            $this->response($response, 400);
        }

        if($this->form_validation->run() == true) {
            $quantity = "quantity";
            $product = "product";
            $unit_cost = "unit_cost";
            $tax_rate = "tax_rate";
            $paying = $this->input->post('amount-paid');
            $reference = $this->site->getReference('so');
             $route_id = isset($_POST['route_id']) ? $_POST['route_id'] : NULL;
             $outlet_id = isset($_POST['outlet_id']) ? $_POST['outlet_id'] : NULL;
             $type = isset($_POST['type']) ? $_POST['type'] : NULL;
             $receipt_no = isset($_POST['receipt_no']) ? $_POST['receipt_no'] : NULL;
            //if($this->Owner || $this->Admin) {
              //  $date = $this->sma->fld(trim($this->input->post('date')));
            //} else {
                //$date = date('Y-m-d H:i:s');
                //(isset($_POST['date'])?$date = $_POST['date']:$date = date('Y-m-d H:i:s'));
                $date = (isset($_POST['date']) ? date('Y-m-d H:i:s',strtotime($_POST['date'])) : date('Y-m-d H:i:s'));
            //}
            $warehouse_id = $this->session->userdata('warehouse_id');
            $van_id = (isset($_POST['van_id']) ? $_POST['van_id'] : '');
            $customer_id = $this->input->post('customer');
            $biller_id = $this->input->post('biller');
            $total_items = $this->input->post('total_items');
            $sale_status = $this->input->post('sale_status');
            $payment_status = $this->input->post('payment_status');
            $payment_term = $this->input->post('payment_term');
            $due_date = $payment_term ? date('Y-m-d', strtotime('+' . $payment_term . ' days')) : NULL;
            $shipping = $this->input->post('shipping') ? $this->input->post('shipping') : 0;
            $customer_details = $this->site->getCompanyByID($customer_id);
            $customer = $customer_details->company ? $customer_details->company : $customer_details->name;
            $biller_details = $this->site->getCompanyByID($biller_id);
            $biller = $biller_details->company != '-' ? $biller_details->company : $biller_details->name;
            $note = $this->sma->clear_tags($this->input->post('note'));
            $staff_note = $this->sma->clear_tags($this->input->post('staff_note'));
            $quote_id = $this->input->post('quote_id') ? $this->input->post('quote_id') : NULL;

            $total = 0;
            $product_tax = 0;
            $order_tax = 0;
            $product_discount = 0;
            $order_discount = 0;
            $percentage = '%';
            $item_conversion = 0;
            $item_factor = 0;
            $i = isset($_POST['product_code']) ? sizeof($_POST['product_code']) : 0;
            for ($r = 0; $r < $i; $r++) {
                $item_id = $_POST['product_id'][$r];
                $item_type = $_POST['product_type'][$r];
                $item_code = $_POST['product_code'][$r];
                $item_name = $_POST['product_name'][$r];
                $item_option = isset($_POST['product_option'][$r]) ? $_POST['product_option'][$r] : NULL;
                $option_details = $this->sales_model->getProductOptionByID($item_option);
                $item_net_price = $this->sma->formatDecimal($_POST['net_price'][$r]);
                $item_quantity = $this->sma->formatDecimal($_POST['quantity'][$r]);
                $item_serial = isset($_POST['serial'][$r]) ? $_POST['serial'][$r] : '';
                $item_tax_rate = isset($_POST['product_tax'][$r]) ? $_POST['product_tax'][$r] : NULL;
                $item_discount = isset($_POST['product_discount'][$r]) ? $_POST['product_discount'][$r] : NULL;
                if(null != $_POST['conversion_qty'][$r]){
                $item_conversion = $_POST['conversion_qty'][$r];//isset($_POST['conversion_qty'][$r] ? $_POST['conversion_qty'][$r] : NULL);
                }
                if(null !=  $_POST['factor'][$r]){
                $item_factor = $_POST['factor'][$r];//isset($_POST['factor'][$r] ? $_POST['factor'][$r] : 1);
                }

                if(isset($item_code) && isset($item_net_price) && isset($item_quantity)) {
                    $product_details = $this->sales_model->getProductByCode($item_code);

                    if(isset($item_discount)) {
                        $discount = $item_discount;
                        $dpos = strpos($discount, $percentage);
                        if($dpos !== false) {
                            $pds = explode("%", $discount);
                            $pr_discount = (($this->sma->formatDecimal($item_net_price)) * (Float) ($pds[0])) / 100;
                        } else {
                            $pr_discount = $this->sma->formatDecimal($discount);
                        }
                    } else {
                        $pr_discount = 0;
                    }
                    $pr_item_discount = $this->sma->formatDecimal($pr_discount * $item_quantity);
                    $product_discount += $pr_item_discount;

                    if(isset($item_tax_rate) && $item_tax_rate != 0) {
                        $pr_tax = $item_tax_rate;
                        $tax_details = $this->site->getTaxRateByID($pr_tax);
                        if($tax_details->type == 1) {
                            if(!$product_details->tax_method) {
                                $item_tax = $this->sma->formatDecimal((($item_net_price-$pr_discount) * $tax_details->rate) / (100 + $tax_details->rate));
                                $tax = $tax_details->rate . "%";
                                $item_net_price -= $item_tax;
                            } else {
                                $item_tax = $this->sma->formatDecimal((($item_net_price-$pr_discount) * $tax_details->rate) / 100);
                                $tax = $tax_details->rate . "%";
                            }
                        } elseif($tax_details->type == 2) {

                            $item_tax = $this->sma->formatDecimal($tax_details->rate);
                            $tax = $tax_details->rate;

                        }
                        $pr_item_tax = $this->sma->formatDecimal($item_tax * $item_quantity);

                    } else {
                        $pr_tax = 0;
                        $pr_item_tax = 0;
                        $tax = "";
                    }
                    $product_tax += $pr_item_tax;

                    $subtotal = (($item_net_price * $item_quantity) + $pr_item_tax) - $pr_item_discount;

                    $products[] = array(
                        'product_id' => $item_id,
                        'product_code' => $item_code,
                        'product_name' => $item_name,
                        'product_type' => $item_type,
                        'option_id' => $item_option,
                        'net_unit_price' => $item_net_price,
                        'unit_price' => $this->sma->formatDecimal($item_net_price + $item_tax),
                        'quantity' => $item_quantity,
                        'warehouse_id' => $warehouse_id,
                        'van_id' => $van_id,
                        'item_tax' => $pr_item_tax,
                        'tax_rate_id' => $pr_tax,
                        'tax' => $tax,
                        'discount' => $item_discount,
                        'item_discount' => $pr_item_discount,
                        'subtotal' => $this->sma->formatDecimal($subtotal),
                        'serial_no' => $item_serial,
                        'conversion_qty' => $item_conversion,
                        'factor' => $item_factor
                        );

                    $total += $item_net_price * $item_quantity;
                }
            }
            if(empty($products)) {
                $this->form_validation->set_rules('product', lang("order_items"), 'required');
            } else {
                krsort($products);
            }

            if($this->input->post('order_discount')) {
                $order_discount_id = $this->input->post('order_discount');
                $opos = strpos($order_discount_id, $percentage);
                if($opos !== false) {
                    $ods = explode("%", $order_discount_id);
                    $order_discount = $this->sma->formatDecimal((($total + $product_tax) * (Float) ($ods[0])) / 100);
                } else {
                    $order_discount = $this->sma->formatDecimal($order_discount_id);
                }
            } else {
                $order_discount_id = NULL;
            }
            $total_discount = $this->sma->formatDecimal($order_discount + $product_discount);

            if($this->Settings->tax2) {
                $order_tax_id = $this->input->post('order_tax');
                if($order_tax_details = $this->site->getTaxRateByID($order_tax_id)) {
                    if($order_tax_details->type == 2) {
                        $order_tax = $this->sma->formatDecimal($order_tax_details->rate);
                    }
                    if($order_tax_details->type == 1) {
                        $order_tax = $this->sma->formatDecimal((($total + $product_tax - $order_discount) * $order_tax_details->rate) / 100);
                    }
                }
            } else {
                $order_tax_id = NULL;
            }

            $total_tax = $this->sma->formatDecimal($product_tax + $order_tax);
            $grand_total = $this->sma->formatDecimal($this->sma->formatDecimal($total) + $total_tax + $this->sma->formatDecimal($shipping) - $total_discount);
            $data = array('date' => $date,
                'reference_no' => $this->site->getReference('so'),
                'customer_id' => $customer_id,
                'customer' => $customer,
                'biller_id' => $biller_id,
                'biller' => $biller,
                'warehouse_id' => $warehouse_id,
                'note' => $note,
                'staff_note' => $staff_note,
                'total' => $this->sma->formatDecimal($total),
                'product_discount' => $this->sma->formatDecimal($product_discount),
                'order_discount_id' => $order_discount_id,
                'order_discount' => $order_discount,
                'total_discount' => $total_discount,
                'product_tax' => $this->sma->formatDecimal($product_tax),
                'order_tax_id' => $order_tax_id,
                'order_tax' => $order_tax,
                'total_tax' => $total_tax,
                'shipping' => $this->sma->formatDecimal($shipping),
                'grand_total' => $grand_total,
                'total_items' => $total_items,
                'sale_status' => $sale_status,
                'payment_status' => $payment_status,
                'payment_term' => $payment_term,
                'due_date' => $due_date,
                'paid' => $total,
                'created_by' => $this->session->userdata('user_id'),
                'route_id' => $route_id,
                'outlet_id' =>$outlet_id,
                'type' => $type,
                'receipt_no' => $receipt_no,
                'sold_by' => $this->session->userdata('username')
                );

if($payment_status == 'partial' || $payment_status == 'paid') {
    if($this->input->post('paid_by') == 'gift_card') {
        $gc = $this->site->getGiftCardByNO($this->input->post('gift_card_no'));
        $amount_paying = $grand_total >= $gc->balance ? $gc->balance : $grand_total;
        $gc_balance = $gc->balance - $amount_paying;
        $payment = array(
            'date' => $date,
            'reference_no' => $this->site->getReference('pay'),
            'amount' => $this->sma->formatDecimal($amount_paying),
            'paid_by' => $this->input->post('paid_by'),
            'cheque_no' => $this->input->post('cheque_no'),
            'cc_no' => $this->input->post('gift_card_no'),
            'cc_holder' => $this->input->post('pcc_holder'),
            'cc_month' => $this->input->post('pcc_month'),
            'cc_year' => $this->input->post('pcc_year'),
            'cc_type' => $this->input->post('pcc_type'),
            'created_by' => $this->session->userdata('user_id'),
            'note' => $this->input->post('payment_note'),
            'type' => 'received',
            'gc_balance' => $gc_balance
            );
    } else {
        $payment = array(
            'date' => $date,
            'reference_no' => $this->site->getReference('pay'),
            'amount' => $this->sma->formatDecimal($this->input->post('amount-paid')),
            'paid_by' => $this->input->post('paid_by'),
            'cheque_no' => $this->input->post('cheque_no'),
            'cc_no' => $this->input->post('pcc_no'),
            'cc_holder' => $this->input->post('pcc_holder'),
            'cc_month' => $this->input->post('pcc_month'),
            'cc_year' => $this->input->post('pcc_year'),
            'cc_type' => $this->input->post('pcc_type'),
            'created_by' =>$this->session->userdata('user_id'),
            'note' => $this->input->post('payment_note'),
            'type' => 'received'
            );
    }
} else {
    $payment = array();
}
//var_dump($this->session->all_userdata());
//$test = [$test,$biller_id,$this->site->getReference('pay')];
           // $this->sma->print_arrays($data, $products, $payment);exit;
}
//$_POST = array();

$csi = array(
    'reference_no' => $this->site->getReference('so'),
    'data' => serialize($data),
    'products' => serialize($products),
    'payment' => serialize($payment));
$this->db->insert('csi',$csi);

if($this->form_validation->run() == true && $this->sales_model->addSale($data, $products, $payment)) {
    $this->session->set_userdata('remove_slls', 1);
    if($quote_id) { $this->db->update('quotes', array('status' => 'completed'), array('id' => $quote_id)); }
    //$this->session->set_flashdata('message', lang("sale_added"));
    //redirect("sales");
    $_POST = array();
    $response = array(
        'error'=> false,
        'message' => 'Sales Added');
    $this->response($response,201);
} else {

    if($quote_id){
        $this->data['quote'] = $this->sales_model->getQuoteByID($quote_id);
        $items = $this->sales_model->getAllQuoteItems($quote_id);
        $c = rand(100000, 9999999);
        foreach ($items as $item) {
            $row = $this->site->getProductByID($item->product_id);
            if(!$row) { json_decode('{}'); $row->quantity = 0; }
            if(isset($row->details)) {unset($row->details); }
            if(isset($row->product_details)) {unset($row->product_details); }
            $row->id = $item->product_id;
            $row->code = $item->product_code;
            $row->name = $item->product_name;
            $row->type = $item->product_type;
            $row->qty = $item->quantity;
            $row->discount = $item->discount ? $item->discount : '0';
            $row->price = $item->net_unit_price;
            $row->tax_rate = $item->tax_rate_id;
            $row->serial = '';
            $row->tax_method = 1;
            $row->option = $item->option_id;
            $options = $this->sales_model->getProductOptions($row->id, $item->warehouse_id);
            if($options) {
                foreach ($options as $option) {
                    $option->quantity += $item->quantity;
                }
            }
            $combo_items = FALSE;
            $ri = $this->Settings->item_addition ? $row->id : $c;
            if($row->tax_rate) {
                $tax_rate = $this->site->getTaxRateByID($row->tax_rate);
                if($row->type == 'combo') {
                    $combo_items = $this->sales_model->getProductComboItems($row->id, $warehouse_id);
                } 
                $pr[$ri] = array('id' => $c, 'item_id' => $row->id, 'label' => $row->name . " (" . $row->code . ")", 'row' => $row, 'combo_items' => $combo_items, 'tax_rate' => $tax_rate, 'options' => $options);
            } else {
                $pr[$ri] = array('id' => $c, 'item_id' => $row->id, 'label' => $row->name . " (" . $row->code . ")", 'row' => $row, 'combo_items' => $combo_items, 'tax_rate' => false, 'options' => $options);
            }
            $c++;
        }
        $this->data['quote_items'] = json_encode($pr);
        $_POST = array();
        $response = array(
            'error' => true,
            'message' => 'Sale Not Added');

        $this->response($response,404);
    }

    //$this->data['error'] = (validation_errors() ? validation_errors() : $this->session->flashdata('error'));
    // $this->data['quote_id'] = $quote_id;
    // $this->data['billers'] = $this->site->getAllCompanies('biller');
    // $this->data['warehouses'] = $this->site->getAllWarehouses();
    // $this->data['tax_rates'] = $this->site->getAllTaxRates();
    //         //$this->data['currencies'] = $this->sales_model->getAllCurrencies();
    // $this->data['slnumber'] = $this->site->getReference('so');
    // $this->data['payment_ref'] = $this->site->getReference('pay');
   // $bc = array(array('link' => base_url(), 'page' => lang('home')), array('link' => site_url('sales'), 'page' => lang('sales')), array('link' => '#', 'page' => lang('add_sale')));
   // $meta = array('page_title' => lang('add_sale'), 'bc' => $bc);
    //$this->page_construct('sales/add', $meta, $this->data);

}
    }
}