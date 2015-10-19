<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Sales extends MY_Controller {

    public $check = false;

    function __construct() {
        parent::__construct();

        if(!$this->loggedIn) {
            $this->session->set_userdata('requested_page', $this->uri->uri_string());
            redirect('login');
        }
        if($this->Supplier) {
            $this->session->set_flashdata('warning', lang('access_denied'));
            redirect($_SERVER["HTTP_REFERER"]);
        }
        $this->lang->load('sales', $this->Settings->language);
        $this->load->library('form_validation'); 
        $this->load->model('sales_model');
        $this->load->model('custom_model');
        $this->digital_upload_path = 'files/';
        $this->upload_path = 'assets/uploads/';
        $this->thumbs_path = 'assets/uploads/thumbs/';
        $this->image_types = 'gif|jpg|jpeg|png|tif';
        $this->digital_file_types = 'zip|psd|ai|rar|pdf|doc|docx|xls|xlsx|ppt|pptx|gif|jpg|jpeg|png|tif';
        $this->allowed_file_size = '1024';
        $this->data['logo'] = true;
    }

    function index($warehouse_id = NULL) {
        $this->sma->checkPermissions();

        $this->data['error'] = (validation_errors()) ? validation_errors() : $this->session->flashdata('error');
        if($this->Owner) {
            $this->data['warehouses'] = $this->site->getAllWarehouses();
            $this->data['warehouse_id'] = $warehouse_id;
            $this->data['warehouse'] = $warehouse_id ? $this->site->getWarehouseByID($warehouse_id) : NULL;
        } else {
            $this->data['warehouses'] = NULL;
            $this->data['warehouse_id'] = $this->session->userdata('warehouse_id');
            $this->data['warehouse'] = $this->session->userdata('warehouse_id') ? $this->site->getWarehouseByID($this->session->userdata('warehouse_id')) : NULL;
        }

        $bc = array(array('link' => base_url(), 'page' => lang('home')), array('link' => '#', 'page' => lang('sales')));
        $meta = array('page_title' => lang('sales'), 'bc' => $bc);
        $this->page_construct('sales/index', $meta, $this->data);
    }

    function getSales($warehouse_id = NULL) {
        $this->sma->checkPermissions('index');
        
        if(!$this->Owner && !$warehouse_id) {
            $user = $this->site->getUser();
            $warehouse_id = $user->warehouse_id;
        }
        $detail_link = anchor('sales/view/$1', '<i class="fa fa-file-text-o"></i> ' . lang('sale_details'));
        $payments_link = anchor('sales/payments/$1', '<i class="fa fa-money"></i> ' . lang('view_payments'), 'data-toggle="modal" data-target="#myModal"');
        $add_payment_link = anchor('sales/add_payment/$1', '<i class="fa fa-money"></i> ' . lang('add_payment'), 'data-toggle="modal" data-target="#myModal"');
        $add_delivery_link = anchor('sales/add_delivery/$1', '<i class="fa fa-truck"></i> ' . lang('add_delivery'), 'data-toggle="modal" data-target="#myModal"');
        $email_link = anchor('sales/email/$1', '<i class="fa fa-envelope"></i> ' . lang('email_sale'), 'data-toggle="modal" data-target="#myModal"');
        $edit_link = anchor('sales/edit/$1', '<i class="fa fa-edit"></i> ' . lang('edit_sale'), 'class="sledit"');
        $pdf_link = anchor('sales/pdf/$1', '<i class="fa fa-file-pdf-o"></i> ' . lang('download_pdf'));
        $return_link = anchor('sales/return_sale/$1', '<i class="fa fa-angle-double-left"></i> ' . lang('return_sale'));
        $push_link = anchor('sales/push/$1', '<i class="fa fa-angle-double-right"></i> ' . lang('push'));
        $delete_link = "<a href='#' class='po' title='<b>" . lang("delete_sale") . "</b>' data-content=\"<p>"
        . lang('r_u_sure') . "</p><a class='btn btn-danger po-delete' href='" . site_url('sales/delete/$1') . "'>"
        . lang('i_m_sure') . "</a> <button class='btn po-close'>" . lang('no') . "</button>\"  rel='popover'><i class=\"fa fa-trash-o\"></i> "
        . lang('delete_sale') . "</a>";
        $action = '<div class="text-center"><div class="btn-group text-left">'
        . '<button type="button" class="btn btn-default btn-xs btn-primary dropdown-toggle" data-toggle="dropdown">'
        . lang('actions') . ' <span class="caret"></span></button>
        <ul class="dropdown-menu pull-right" role="menu">
            <li>' . $detail_link . '</li>
            <li>' . $payments_link . '</li>
            <li>' . $add_payment_link . '</li>
            <li>' . $add_delivery_link . '</li>';
            // if($this->Owner){
            // $action.= '<li>' . $edit_link . '</li>
            
            // <li>' . $push_link . '</li>';
            // }
           $action.= '<li>' . $pdf_link . '</li>
            <li>' . $email_link . '</li>
            <li>' . $return_link . '</li>
            <li>' . $delete_link . '</li>
        </ul>
    </div></div>';
        //$action = '<div class="text-center">' . $detail_link . ' ' . $edit_link . ' ' . $email_link . ' ' . $delete_link . '</div>';

    $this->load->library('datatables');
   if($warehouse_id) {
        $this->datatables
        ->select("id, date, reference_no, biller, customer,sold_by, REPLACE(REPLACE(push,0,'false'),1,'true') AS push, sale_status, grand_total, paid, (grand_total-paid) as balance, payment_status")
        ->from('sales')
        ->where('warehouse_id', $warehouse_id)
        //->where('push',0)
        ;
    } else {
        $this->datatables
        ->select("id, date, reference_no, biller, customer,sold_by, REPLACE(REPLACE(push,0,'false'),1,'true') AS push, sale_status, grand_total, paid, (grand_total-paid) as balance, payment_status")
        ->from('sales')
        //->where('push',0)
        ;
    }
    $this->datatables->where('pos !=', 1);
    if(!$this->Customer && !$this->Supplier && !$this->Owner && !$this->Admin) {
        $this->datatables->where('created_by', $this->session->userdata('user_id'));
    } elseif($this->Customer) {
        $this->datatables->where('customer_id', $this->session->userdata('user_id'));
    }
    $this->datatables->add_column("Actions", $action, "id");
    //$this->load->helper('status_helper');
    //$this->datatables->edit_column('push','$1','label_this(true)');
    echo $this->datatables->generate();
}

function ref(){
    echo $this->site->getReference('pay');
}

function return_sales($warehouse_id = NULL) {
    $this->sma->checkPermissions();

    $this->data['error'] = (validation_errors()) ? validation_errors() : $this->session->flashdata('error');
    if($this->Owner) {
        $this->data['warehouses'] = $this->site->getAllWarehouses();
        $this->data['warehouse_id'] = $warehouse_id;
        $this->data['warehouse'] = $warehouse_id ? $this->site->getWarehouseByID($warehouse_id) : NULL;
    } else {
        $user = $this->site->getUser();
        $this->data['warehouses'] = NULL;
        $this->data['warehouse_id'] = $user->warehouse_id;
        $this->data['warehouse'] = $user->warehouse_id ? $this->site->getWarehouseByID($user->warehouse_id) : NULL;
    }

    $bc = array(array('link' => base_url(), 'page' => lang('home')), array('link' => '#', 'page' => lang('return_sales')));
    $meta = array('page_title' => lang('return_sales'), 'bc' => $bc);
    $this->page_construct('sales/return_sales', $meta, $this->data);
}

function getReturns($warehouse_id = NULL) {
    $this->sma->checkPermissions('return_sales', FALSE, TRUE);

    if(!$this->Owner && !$warehouse_id) {
        $user = $this->site->getUser();
        $warehouse_id = $user->warehouse_id;
    }
    $detail_link = anchor('sales/view/$1', '<i class="fa fa-file-text-o"></i>');
    $edit_link = ''; //anchor('sales/edit/$1', '<i class="fa fa-edit"></i>', 'class="reedit"');
    $delete_link = "<a href='#' class='po' title='<b>" . lang("delete_return_sale") . "</b>' data-content=\"<p>"
    . lang('r_u_sure') . "</p><a class='btn btn-danger po-delete' href='" . site_url('sales/delete_return/$1') . "'>"
    . lang('i_m_sure') . "</a> <button class='btn po-close'>" . lang('no') . "</button>\"  rel='popover'><i class=\"fa fa-trash-o\"></i></a>";
    $action = '<div class="text-center">'.$detail_link .' '. $edit_link .' '. $delete_link.'</div>';
        //$action = '<div class="text-center">' . $detail_link . ' ' . $edit_link . ' ' . $email_link . ' ' . $delete_link . '</div>';
    
    $this->load->library('datatables');
    if($warehouse_id) {
        $this->datatables
        ->select($this->db->dbprefix('return_sales').".date as date, ".$this->db->dbprefix('return_sales').".reference_no as ref, ".$this->db->dbprefix('sales').".reference_no as sal_ref, ".$this->db->dbprefix('return_sales').".biller, ".$this->db->dbprefix('return_sales').".customer, ".$this->db->dbprefix('return_sales').".surcharge, ".$this->db->dbprefix('return_sales').".grand_total, ".$this->db->dbprefix('return_sales').".id as id")
        ->join('sales', 'sales.id=return_sales.sale_id', 'left')
        ->from('return_sales')
        ->group_by('return_sales.id')
        ->where('return_sales.warehouse_id', $warehouse_id);
    } else {
        $this->datatables
        ->select($this->db->dbprefix('return_sales').".date as date, ".$this->db->dbprefix('return_sales').".reference_no as ref, ".$this->db->dbprefix('sales').".reference_no as sal_ref, ".$this->db->dbprefix('return_sales').".biller, ".$this->db->dbprefix('return_sales').".customer, ".$this->db->dbprefix('return_sales').".surcharge, ".$this->db->dbprefix('return_sales').".grand_total, ".$this->db->dbprefix('return_sales').".id as id")
        ->join('sales', 'sales.id=return_sales.sale_id', 'left')
        ->from('return_sales')
        ->group_by('return_sales.id');
    }
    if(!$this->Customer && !$this->Supplier && !$this->Owner && !$this->Admin) {
        $this->datatables->where('return_sales.created_by', $this->session->userdata('user_id'));
    } elseif($this->Customer) {
        $this->datatables->where('return_sales.customer_id', $this->session->userdata('customer_id'));
    }
    $this->datatables->add_column("Actions", $action, "id");
    echo $this->datatables->generate();
}

function view($id = NULL) {
    $this->sma->checkPermissions('index');

    if($this->input->get('id')) {
        $id = $this->input->get('id');
    }
    $this->data['error'] = (validation_errors()) ? validation_errors() : $this->session->flashdata('error');
    $inv = $this->sales_model->getInvoiceByID($id);
    $this->sma->view_rights($inv->created_by);
    $this->data['barcode'] = "<img src='" . site_url('products/gen_barcode/' . $inv->reference_no) . "' alt='" . $inv->reference_no . "' class='pull-left' />";
    $this->data['customer'] = $this->site->getCompanyByID($inv->customer_id);
    $this->data['payments'] = $this->sales_model->getPaymentsForSale($id);
    $this->data['biller'] = $this->site->getCompanyByID($inv->biller_id);
    $this->data['created_by'] = $this->site->getUser($inv->created_by);
    $this->data['updated_by'] = $inv->updated_by ? $this->site->getUser($inv->updated_by) : NULL;
    $this->data['warehouse'] = $this->site->getWarehouseByID($inv->warehouse_id);
    $this->data['inv'] = $inv;
    $return = $this->sales_model->getReturnBySID($id);
    $this->data['return_sale'] = $return;
    $this->data['rows'] = $this->sales_model->getAllInvoiceItems($id);
    //$this->data['return_items'] = $return ? $this->sales_model->getAllReturnItems($return->id) : NULL;
    $this->data['paypal'] = $this->sales_model->getPaypalSettings();
    $this->data['skrill'] = $this->sales_model->getSkrillSettings();

    $bc = array(array('link' => base_url(), 'page' => lang('home')), array('link' => site_url('sales'), 'page' => lang('sales')), array('link' => '#', 'page' => lang('view')));
    $meta = array('page_title' => lang('view_sales_details'), 'bc' => $bc);
    $this->page_construct('sales/view', $meta, $this->data);
}

function view_return($id = NULL) {
    $this->sma->checkPermissions('return_sales');

    if($this->input->get('id')) {
        $id = $this->input->get('id');
    }
    $this->data['error'] = (validation_errors()) ? validation_errors() : $this->session->flashdata('error');
    $inv = $this->sales_model->getReturnByID($id);
    $this->sma->view_rights($inv->created_by);
    $this->data['barcode'] = "<img src='" . site_url('products/gen_barcode/' . $inv->reference_no) . "' alt='" . $inv->reference_no . "' class='pull-left' />";
    $this->data['customer'] = $this->site->getCompanyByID($inv->customer_id);
    $this->data['payments'] = $this->sales_model->getPaymentsForSale($id);
    $this->data['biller'] = $this->site->getCompanyByID($inv->biller_id);
    $this->data['user'] = $this->site->getUser($inv->created_by);
    $this->data['warehouse'] = $this->site->getWarehouseByID($inv->warehouse_id);
    $this->data['inv'] = $inv;
    $this->data['rows'] = $this->sales_model->getAllReturnItems($id);
    $this->data['sale'] = $this->sales_model->getInvoiceByID($inv->sale_id);
    $bc = array(array('link' => base_url(), 'page' => lang('home')), array('link' => site_url('sales'), 'page' => lang('sales')), array('link' => '#', 'page' => lang('view_return')));
    $meta = array('page_title' => lang('view_return_details'), 'bc' => $bc);
    $this->page_construct('sales/view_return', $meta, $this->data);
}

function pdf($id = NULL, $view = NULL, $save_bufffer = NULL) {
    $this->sma->checkPermissions();

    if($this->input->get('id')) {
        $id = $this->input->get('id');
    }
    $this->data['error'] = (validation_errors()) ? validation_errors() : $this->session->flashdata('error');
    $inv = $this->sales_model->getInvoiceByID($id);
    $this->sma->view_rights($inv->created_by);
    $this->data['barcode'] = "<img src='" . site_url('products/gen_barcode/' . $inv->reference_no) . "' alt='" . $inv->reference_no . "' class='pull-left' />";
    $this->data['customer'] = $this->site->getCompanyByID($inv->customer_id);
    $this->data['payments'] = $this->sales_model->getPaymentsForSale($id);
    $this->data['biller'] = $this->site->getCompanyByID($inv->biller_id);
    $this->data['user'] = $this->site->getUser($inv->created_by);
    $this->data['warehouse'] = $this->site->getWarehouseByID($inv->warehouse_id);
    $this->data['inv'] = $inv;
    $return = $this->sales_model->getReturnBySID($id);
    $this->data['return_sale'] = $return;
    $this->data['rows'] = $this->sales_model->getAllInvoiceItems($id);
    $this->data['return_items'] = $return ? $this->sales_model->getAllReturnItems($return->id) : NULL;
    //$this->data['paypal'] = $this->sales_model->getPaypalSettings();
    //$this->data['skrill'] = $this->sales_model->getSkrillSettings();

    $name = lang("sale") . "_" . str_replace('/', '_', $inv->reference_no) . ".pdf";
    $html = $this->load->view($this->theme . 'sales/pdf', $this->data, TRUE);
    if($view) {
        $this->load->view($this->theme . 'sales/pdf', $this->data);
    } elseif($save_bufffer) {
        return $this->sma->generate_pdf($html, $name, $save_bufffer, $this->data['biller']->invoice_footer);
    } else {
        $this->sma->generate_pdf($html, $name, FALSE, $this->data['biller']->invoice_footer);
    }
}

function email($id = NULL) {
    $this->sma->checkPermissions(false, true);

    if($this->input->get('id')) {
        $id = $this->input->get('id');
    }
    $inv = $this->sales_model->getInvoiceByID($id);
    $this->form_validation->set_rules('to', lang("to") . " " . lang("email"), 'trim|required|valid_email');
    $this->form_validation->set_rules('subject', lang("subject"), 'trim|required');
    $this->form_validation->set_rules('cc', lang("cc"), 'trim');
    $this->form_validation->set_rules('bcc', lang("bcc"), 'trim');
    $this->form_validation->set_rules('note', lang("message"), 'trim');

    if($this->form_validation->run() == true) {
        $this->sma->view_rights($inv->created_by);
        $to = $this->input->post('to');
        $subject = $this->input->post('subject');
        if($this->input->post('cc')) {
            $cc = $this->input->post('cc');
        } else {
            $cc = NULL;
        }
        if($this->input->post('bcc')) {
            $bcc = $this->input->post('bcc');
        } else {
            $bcc = NULL;
        }
        $customer = $this->site->getCompanyByID($inv->customer_id);
        $this->load->library('parser');
        $parse_data = array(
            'reference_number' => $inv->reference_no,
            'contact_person' => $customer->name,
            'company' => $customer->company,
            'site_link' => base_url(),
            'site_name' => $this->Settings->site_name,
            'logo' => '<img src="' . base_url() . 'assets/uploads/logos/' . $this->Settings->logo . '" alt="' . $this->Settings->site_name . '"/>'
            );
        $msg = $this->input->post('note');
        $message = $this->parser->parse_string($msg, $parse_data);

        $biller = $this->site->getCompanyByID($inv->biller_id);
        $paypal = $this->sales_model->getPaypalSettings();
        $skrill = $this->sales_model->getSkrillSettings();
        $btn_code = '<div id="payment_buttons" class="text-center margin010">';
        if($paypal->active == "1" && $inv->grand_total != "0.00" ){ 
            if(trim(strtolower($customer->country)) == $biller->country) {
                $paypal_fee = $paypal->fixed_charges+($inv->grand_total*$paypal->extra_charges_my/100);
            } else {
                $paypal_fee = $paypal->fixed_charges+($inv->grand_total*$paypal->extra_charges_other/100);
            }
            $btn_code .=  '<a href="https://www.paypal.com/cgi-bin/webscr?cmd=_xclick&business='.$paypal->account_email.'&item_name='.$inv->reference_no.'&item_number='.$inv->id.'&image_url='.base_url() . 'assets/uploads/logos/' . $this->Settings->logo.'&amount='.(($inv->grand_total-$inv->paid)+$paypal_fee).'&no_shipping=1&no_note=1&currency_code='.$this->default_currency->code.'&bn=FC-BuyNow&rm=2&return='.site_url('sales/view/'.$inv->id).'&cancel_return='.site_url('sales/view/'.$inv->id).'&notify_url='.site_url('payments/paypalipn').'&custom='.$inv->reference_no.'__'.($inv->grand_total-$inv->paid).'__'.$paypal_fee.'"><img src="'.base_url('assets/images/btn-paypal.png').'" alt="Pay by PayPal"></a> ';

        }
        if($skrill->active == "1" && $inv->grand_total != "0.00" ){ 
            if(trim(strtolower($customer->country)) == $biller->country) {
                $skrill_fee = $skrill->fixed_charges+($inv->grand_total*$skrill->extra_charges_my/100);
            } else {
                $skrill_fee = $skrill->fixed_charges+($inv->grand_total*$skrill->extra_charges_other/100);
            }
            $btn_code .= ' <a href="https://www.moneybookers.com/app/payment.pl?method=get&pay_to_email='.$skrill->account_email.'&language=EN&merchant_fields=item_name,item_number&item_name='.$inv->reference_no.'&item_number='.$inv->id.'&logo_url='.base_url() . 'assets/uploads/logos/' . $this->Settings->logo.'&amount='.(($inv->grand_total-$inv->paid)+$skrill_fee).'&return_url='.site_url('sales/view/'.$inv->id).'&cancel_url='.site_url('sales/view/'.$inv->id).'&detail1_description='.$inv->reference_no.'&detail1_text=Payment for the sale invoice '.$inv->reference_no . ': '.$inv->grand_total.'(+ fee: '.$skrill_fee.') = '.$this->sma->formatMoney($inv->grand_total+$skrill_fee).'&currency='.$this->default_currency->code.'&status_url='.site_url('payments/skrillipn').'"><img src="'.base_url('assets/images/btn-skrill.png').'" alt="Pay by Skrill"></a>';
        } 

        $btn_code .= '<div class="clearfix"></div>
    </div>';
    $message = $message.$btn_code;

            //$name = lang("sale") . "_" . str_replace('/', '_', $inv->reference_no) . ".pdf";
            //$file_content = $this->pdf($id, NULL, 'S');
            //$attachment = array('file' => $file_content, 'name' => $name, 'mime' => 'application/pdf');
            $attachment = $this->pdf($id, NULL, 'S'); //delete_files($attachment);
        } elseif($this->input->post('send_email')) {
            $this->data['error'] = (validation_errors() ? validation_errors() : $this->session->flashdata('error'));
            $this->session->set_flashdata('error', $this->data['error']);
            redirect($_SERVER["HTTP_REFERER"]);
        }

        if($this->form_validation->run() == true && $this->sma->send_email($to, $subject, $message, NULL, NULL, $attachment, $cc, $bcc)) {
            delete_files($attachment);
            $this->session->set_flashdata('message', lang("email_sent"));
            redirect("sales");
        } else {

            if(file_exists('./themes/'.$this->theme.'/views/email_templates/sale.html')) {
                $sale_temp = read_file('themes/'.$this->theme.'/views/email_templates/sale.html');
            } else {
                $sale_temp = read_file('./themes/default/views/email_templates/sale.html');
            }

            $this->data['subject'] = array('name' => 'subject',
                'id' => 'subject',
                'type' => 'text',
                'value' => $this->form_validation->set_value('subject', 'Invoice (' . $inv->reference_no . ') from ' . $this->Settings->site_name),
                );
            $this->data['note'] = array('name' => 'note',
                'id' => 'note',
                'type' => 'text',
                'value' => $this->form_validation->set_value('note', $sale_temp),
                );
            $this->data['customer'] = $this->site->getCompanyByID($inv->customer_id);

            $this->data['id'] = $id;
            $this->data['modal_js'] = $this->site->modal_js();
            $this->load->view($this->theme . 'sales/email', $this->data);
        }
    }

    /* -------------------------------------------------------------------------------------------------------------------------------- */


    function add($quote_id = NULL) {
        $this->sma->checkPermissions();
        
        $this->form_validation->set_message('is_natural_no_zero', lang("no_zero_required"));
        $this->form_validation->set_rules('reference_no', lang("reference_no"), 'required');
        $this->form_validation->set_rules('customer', lang("customer"), 'required');
        $this->form_validation->set_rules('biller', lang("biller"), 'required');
        $this->form_validation->set_rules('sale_status', lang("sale_status"), 'required');
        $this->form_validation->set_rules('payment_status', lang("payment_status"), 'required');
        //$this->form_validation->set_rules('note', lang("note"), 'xss_clean');

        if($this->form_validation->run() == true) {
            $quantity = "quantity";
            $product = "product";
            $unit_cost = "unit_cost";
            $tax_rate = "tax_rate";
            $reference = $this->input->post('reference_no');
            if($this->Owner || $this->Admin) {
                $date = $this->sma->fld(trim($this->input->post('date')));
            } else {
                $date = date('Y-m-d H:i:s');
            }
            $warehouse_id = $this->input->post('warehouse');
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
            $i = isset($_POST['product_code']) ? sizeof($_POST['product_code']) : 0;
            for ($r = 0; $r < $i; $r++) {
                $item_id = $_POST['product_id'][$r];
                $item_type = $_POST['product_type'][$r];
                $item_code = $_POST['product_code'][$r];
                $item_name = $_POST['product_name'][$r];
                $item_option = isset($_POST['product_option'][$r]) ? $_POST['product_option'][$r] : NULL;
                //$option_details = $this->sales_model->getProductOptionByID($item_option);
                $item_net_price = $this->sma->formatDecimal($_POST['net_price'][$r]);
                $item_quantity = $_POST['quantity'][$r];
                $item_serial = isset($_POST['serial'][$r]) ? $_POST['serial'][$r] : '';
                $item_tax_rate = isset($_POST['product_tax'][$r]) ? $_POST['product_tax'][$r] : NULL;
                $item_discount = isset($_POST['product_discount'][$r]) ? $_POST['product_discount'][$r] : NULL;

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
                        'item_tax' => $pr_item_tax,
                        'tax_rate_id' => $pr_tax,
                        'tax' => $tax,
                        'discount' => $item_discount,
                        'item_discount' => $pr_item_discount,
                        'subtotal' => $this->sma->formatDecimal($subtotal),
                        'serial_no' => $item_serial
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
                'reference_no' => $reference,
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
                'paid' => 0,
                'created_by' => $this->session->userdata('user_id'),
                'route_id' => $this->input->post('route_id'),
                'outlet_id' => $this->input->post('outlet_id'),
                'type' => $this->input->post('type'),
                'receipt_no' => $this->input->post('receipt_no'),
                'sold_by' => $this->session->userdata('username')
                );

if($payment_status == 'partial' || $payment_status == 'paid') {
    if($this->input->post('paid_by') == 'gift_card') {
        $gc = $this->site->getGiftCardByNO($this->input->post('gift_card_no'));
        $amount_paying = $grand_total >= $gc->balance ? $gc->balance : $grand_total;
        $gc_balance = $gc->balance - $amount_paying;
        $payment = array(
            'date' => $date,
            'reference_no' => $this->input->post('payment_reference_no'),
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
            'reference_no' => $this->input->post('payment_reference_no'),
            'amount' => $this->sma->formatDecimal($this->input->post('amount-paid')),
            'paid_by' => $this->input->post('paid_by'),
            'cheque_no' => $this->input->post('cheque_no'),
            'cc_no' => $this->input->post('pcc_no'),
            'cc_holder' => $this->input->post('pcc_holder'),
            'cc_month' => $this->input->post('pcc_month'),
            'cc_year' => $this->input->post('pcc_year'),
            'cc_type' => $this->input->post('pcc_type'),
            'created_by' => $this->session->userdata('user_id'),
            'note' => $this->input->post('payment_note'),
            'type' => 'received'
            );
    }
} else {
    $payment = array();
}

            //$this->sma->print_arrays($data, $products, $payment);exit;
}


if($this->form_validation->run() == true && $this->sales_model->addSale($data, $products, $payment)) {
    $this->session->set_userdata('remove_slls', 1);
    if($quote_id) { $this->db->update('quotes', array('status' => 'completed'), array('id' => $quote_id)); }
    $this->session->set_flashdata('message', lang("sale_added"));
    redirect("sales");
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
            //$row->type = $item->product_type;
            $row->qty = $item->quantity;
            $row->discount = $item->discount ? $item->discount : '0';
            $row->price = $item->net_unit_price;
            $row->tax_rate = $item->tax_rate_id;
            $row->serial = '';
            $row->tax_method = 1;
            //$row->option = $item->option_id;
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
    }

    $this->data['error'] = (validation_errors() ? validation_errors() : $this->session->flashdata('error'));
    $this->data['quote_id'] = $quote_id;
    $this->data['billers'] = $this->site->getAllCompanies('biller');
    $this->data['warehouses'] = $this->site->getAllWarehouses();
    $this->data['tax_rates'] = $this->site->getAllTaxRates();
    $this->data['outlets'] = $this->custom_model->getOutletsDrop();
    $this->data['routes'] = $this->custom_model->getRoutesDrop();
            //$this->data['currencies'] = $this->sales_model->getAllCurrencies();
    $this->data['slnumber'] = $this->site->getReference('so');
    $this->data['payment_ref'] = $this->site->getReference('pay');
    $bc = array(array('link' => base_url(), 'page' => lang('home')), array('link' => site_url('sales'), 'page' => lang('sales')), array('link' => '#', 'page' => lang('add_sale')));
    $meta = array('page_title' => lang('add_sale'), 'bc' => $bc);
    //print_r($this->data);exit;
    $this->page_construct('sales/add', $meta, $this->data);
}
}

/* -------------------------------------------------------------------------------------------------------------------------------- */

function edit($id = NULL) {
    $this->sma->checkPermissions();

    if($this->input->get('id')) {
        $id = $this->input->get('id');
    }

    $this->form_validation->set_message('is_natural_no_zero', lang("no_zero_required"));
    $this->form_validation->set_rules('reference_no', lang("reference_no"), 'required');
    $this->form_validation->set_rules('customer', lang("customer"), 'required');
    $this->form_validation->set_rules('biller', lang("biller"), 'required');
    $this->form_validation->set_rules('sale_status', lang("sale_status"), 'required');
    $this->form_validation->set_rules('payment_status', lang("payment_status"), 'required');
        //$this->form_validation->set_rules('note', lang("note"), 'xss_clean');

    if($this->form_validation->run() == true) {
        $quantity = "quantity";
        $product = "product";
        $unit_cost = "unit_cost";
        $tax_rate = "tax_rate";
        $reference = $this->input->post('reference_no');
        if($this->Owner || $this->Admin) {
            $date = $this->sma->fld(trim($this->input->post('date')));
        } else {
            $date = date('Y-m-d H:i:s');
        }
        $warehouse_id = $this->input->post('warehouse');
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

        $total = 0;
        $product_tax = 0;
        $order_tax = 0;
        $product_discount = 0;
        $order_discount = 0;
        $percentage = '%';
        $i = isset($_POST['product_code']) ? sizeof($_POST['product_code']) : 0;
        for ($r = 0; $r < $i; $r++) {
            $item_id = $_POST['product_id'][$r];
            $item_type = $_POST['product_type'][$r];
            $item_code = $_POST['product_code'][$r];
            $item_name = $_POST['product_name'][$r];
            $item_option = isset($_POST['product_option'][$r]) ? $_POST['product_option'][$r] : NULL;
            $item_net_price = $this->sma->formatDecimal($_POST['net_price'][$r]);
            $item_quantity = $_POST['quantity'][$r];
            $item_serial = isset($_POST['serial'][$r]) ? $_POST['serial'][$r] : '';
            $item_tax_rate = isset($_POST['product_tax'][$r]) ? $_POST['product_tax'][$r] : NULL;
            $item_discount = isset($_POST['product_discount'][$r]) ? $_POST['product_discount'][$r] : NULL;

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
                    'sale_id' => $id,
                    'product_id' => $item_id,
                    'product_code' => $item_code,
                    'product_name' => $item_name,
                    'product_type' => $item_type,
                    'option_id' => $item_option,
                    'net_unit_price' => $this->sma->formatDecimal($item_net_price),
                    'unit_price' => $this->sma->formatDecimal($item_net_price + $item_tax),
                    'quantity' => $item_quantity,
                    'warehouse_id' => $warehouse_id,
                    'item_tax' => $pr_item_tax,
                    'tax_rate_id' => $pr_tax,
                    'tax' => $tax,
                    'discount' => $item_discount,
                    'item_discount' => $pr_item_discount,
                    'subtotal' => $subtotal,
                    'serial_no' => $item_serial
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
            'reference_no' => $reference,
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
            'updated_by' => $this->session->userdata('user_id'),
            'updated_at' => date('Y-m-d H:i:s')
            );

            //$this->sma->print_arrays($data, $products);
}

if($this->form_validation->run() == true && $this->sales_model->updateSale($id, $data, $products)) {
    $this->session->set_userdata('remove_slls', 1);
    $this->session->set_flashdata('message', lang("sale_updated"));
    redirect("sales");
} else {

    $this->data['error'] = (validation_errors() ? validation_errors() : $this->session->flashdata('error'));

    $this->data['inv'] = $this->sales_model->getInvoiceByID($id);
    if($this->data['inv']->date <= date('Y-m-d', strtotime('-3 months'))) {
        $this->session->set_flashdata('error', lang("sale_x_edited_older_than_3_months"));
        redirect($_SERVER["HTTP_REFERER"]);
    }
    $inv_items = $this->sales_model->getAllInvoiceItems($id);
    $c = rand(100000, 9999999);
    foreach ($inv_items as $item) {
        $row = $this->site->getProductByID($item->product_id);
        if(!$row) { json_decode('{}'); $row->quantity = 0; } else { unset($row->cost, $row->supplier1price, $row->supplier2price, $row->supplier3price, $row->supplier4price, $row->supplier5price); }
        $row->id = $item->product_id;
        $row->code = $item->product_code;
        $row->name = $item->product_name;
        $row->type = $item->product_type;
        $row->qty = $item->quantity;
        $row->quantity += $item->quantity;
        $row->discount = $item->discount ? $item->discount : '0';
        $row->price = $item->net_unit_price;
        $row->tax_rate = $item->tax_rate_id;
        $row->serial = $item->serial_no;
        $row->tax_method = 1;
        $row->option = $item->option_id;
        $options = $this->sales_model->getProductOptions($row->id, $item->warehouse_id);
        if($options) {
            foreach ($options as $option) {
                $option->quantity += $item->quantity;
            }
        }
        if(isset($row->details)) {unset($row->details); }
        if(isset($row->product_details)) {unset($row->product_details); }
        $ri = $this->Settings->item_addition ? $row->id : $c;
        if($row->tax_rate) {
            $tax_rate = $this->site->getTaxRateByID($row->tax_rate);
            $pr[$ri] = array('id' => $c, 'item_id' => $row->id, 'label' => $row->name . " (" . $row->code . ")", 'row' => $row, 'tax_rate' => $tax_rate, 'options' => $options);
        } else {
            $pr[$ri] = array('id' => $c, 'item_id' => $row->id, 'label' => $row->name . " (" . $row->code . ")", 'row' => $row, 'tax_rate' => false, 'options' => $options);
        }
        $c++;
    }

    $this->data['inv_items'] = json_encode($pr);
    $this->data['id'] = $id;
    //$this->data['currencies'] = $this->site->getAllCurrencies();
    $this->data['billers'] = ($this->Owner || $this->Admin) ? $this->site->getAllCompanies('biller') : NULL;
    $this->data['tax_rates'] = $this->site->getAllTaxRates();
    $this->data['warehouses'] = $this->site->getAllWarehouses();

    $bc = array(array('link' => base_url(), 'page' => lang('home')), array('link' => site_url('sales'), 'page' => lang('sales')), array('link' => '#', 'page' => lang('edit_sale')));
    $meta = array('page_title' => lang('edit_sale'), 'bc' => $bc);
    $this->page_construct('sales/edit', $meta, $this->data);
}
}

/* ------------------------------- */

function return_sale($id = NULL) {
    $this->sma->checkPermissions();

    if($this->input->get('id')) {
        $id = $this->input->get('id');
    }

    $this->form_validation->set_rules('reference_no', lang("reference_no"), 'required');
    $this->form_validation->set_rules('paid_by', lang("paying_by"), 'required');
    //$this->form_validation->set_rules('note', lang("note"), 'required');

    if($this->form_validation->run() == true) {
        $sale = $this->sales_model->getInvoiceByID($id);
        $quantity = "quantity";
        $product = "product";
        $unit_cost = "unit_cost";
        $tax_rate = "tax_rate";
        $reference = $this->input->post('reference_no');
        if($this->Owner || $this->Admin) {
            $date = $this->sma->fld(trim($this->input->post('date')));
        } else {
            $date = date('Y-m-d H:i:s');
        }

        $return_surcharge = $this->input->post('return_surcharge') ? $this->input->post('return_surcharge') : 0;
        $note = $this->sma->clear_tags($this->input->post('note'));

        $total = 0;
        $product_tax = 0;
        $order_tax = 0;
        $product_discount = 0;
        $order_discount = 0;
        $percentage = '%';
        $i = isset($_POST['product_code']) ? sizeof($_POST['product_code']) : 0;
        for ($r = 0; $r < $i; $r++) {
            $item_id = $_POST['product_id'][$r];
            $sale_item_id = $_POST['sale_item_id'][$r];
            $item_option = $_POST['option_id'][$r];
            $item_type = $_POST['product_type'][$r];
            $item_code = $_POST['product_code'][$r];
            $item_name = $_POST['product_name'][$r];
            $item_net_price = $this->sma->formatDecimal($_POST['net_price'][$r]);
            $item_quantity = $_POST['quantity'][$r];
            $item_serial = isset($_POST['serial'][$r]) ? $_POST['serial'][$r] : '';
            $item_tax_rate = $_POST['product_tax'][$r];
            $item_discount = $_POST['product_discount'][$r];

            if(isset($item_code) && isset($item_net_price) && isset($item_quantity)) {
                //$product_details = $this->sales_model->getProductByCode($item_code);
                if(isset($item_discount)) {
                    $discount = $item_discount;
                    $dpos = strpos($discount, $percentage);
                    if($dpos !== false) {
                        $pds = explode("%", $discount);
                        $pr_discount = $this->sma->formatDecimal((($item_net_price * $item_quantity) * (Float) ($pds[0])) / 100);
                    } else {
                        $pr_discount = $this->sma->formatDecimal($discount);
                    }
                    $product_discount += $pr_discount;
                } else {
                    $pr_discount = 0;
                }

                if(isset($item_tax_rate) && $item_tax_rate != 0) {
                    $pr_tax = $item_tax_rate;
                    $tax_details = $this->site->getTaxRateByID($pr_tax);

                    if($tax_details->type == 1 && $tax_details->rate != 0) {
                        $item_tax = $this->sma->formatDecimal((($item_quantity * ($item_net_price-$pr_discount)) * $tax_details->rate) / 100);
                        $product_tax += $item_tax;
                    } else {
                        $item_tax = $this->sma->formatDecimal($tax_details->rate);
                        $product_tax += $item_tax;
                    }

                    if($tax_details->type == 1)
                        $tax = $tax_details->rate . "%";
                    else
                        $tax = $tax_details->rate;
                } else {
                    $pr_tax = 0;
                    $item_tax = 0;
                    $tax = "";
                }

                $subtotal = $this->sma->formatDecimal(($this->sma->formatDecimal($item_net_price * $item_quantity) + $item_tax) - $pr_discount);
                $products[] = array(
                    'sale_id' => $id,
                    'sale_item_id' => $sale_item_id,
                    'product_id' => $item_id,
                    'option_id' => $item_option,
                    'product_code' => $item_code,
                    'product_name' => $item_name,
                    'product_type' => $item_type,
                    'net_unit_price' => $item_net_price,
                    'quantity' => $item_quantity,
                    'warehouse_id' => $sale->warehouse_id,
                    'item_tax' => $item_tax,
                    'tax_rate_id' => $pr_tax,
                    'tax' => $tax,
                    'discount' => $item_discount,
                    'item_discount' => $pr_discount,
                    'subtotal' => $subtotal,
                    'serial_no' => $item_serial
                    );

                $total += $item_net_price * $item_quantity;
            }
        }
        if(empty($products)) {
            $this->form_validation->set_rules('product', lang("order_items"), 'required');
        } else {
            krsort($products);
        }

        if($this->input->post('discount')) {
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
        $total_discount = $order_discount + $product_discount;

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
        $grand_total = $this->sma->formatDecimal($this->sma->formatDecimal($total) + $total_tax - $this->sma->formatDecimal($return_surcharge) - $total_discount);
        $data = array('date' => $date,
            'sale_id' => $id,
            'reference_no' => $reference,
            'customer_id' => $sale->customer_id,
            'customer' => $sale->customer,
            'biller_id' => $sale->biller_id,
            'biller' => $sale->biller,
            'warehouse_id' => $sale->warehouse_id,
            'note' => $note,
            'total' => $this->sma->formatDecimal($total),
            'product_discount' => $this->sma->formatDecimal($product_discount),
            'order_discount_id' => $order_discount_id,
            'order_discount' => $order_discount,
            'total_discount' => $total_discount,
            'product_tax' => $this->sma->formatDecimal($product_tax),
            'order_tax_id' => $order_tax_id,
            'order_tax' => $order_tax,
            'total_tax' => $total_tax,
            'surcharge' => $this->sma->formatDecimal($return_surcharge),
            'grand_total' => $grand_total,
            'created_by' => $this->session->userdata('user_id'),
            );
        if($this->input->post('amount-paid') && $this->input->post('amount-paid') != 0) {
            $payment = array(
                'date' => $date,
                'reference_no' => $this->input->post('payment_reference_no'),
                'amount' => $this->sma->formatDecimal($this->input->post('amount-paid')),
                'paid_by' => $this->input->post('paid_by'),
                'cheque_no' => $this->input->post('cheque_no'),
                'cc_no' => $this->input->post('pcc_no'),
                'cc_holder' => $this->input->post('pcc_holder'),
                'cc_month' => $this->input->post('pcc_month'),
                'cc_year' => $this->input->post('pcc_year'),
                'cc_type' => $this->input->post('pcc_type'),
                'created_by' => $this->session->userdata('user_id'),
                'type' => 'returned'
                );
        } else {
            $payment = array();
        }

            //$this->sma->print_arrays($data, $products);
    }


    if($this->form_validation->run() == true && $this->sales_model->returnSale($data, $products, $payment)) {
        $this->session->set_flashdata('message', lang("return_sale_added"));
        redirect("sales/return_sales");
    } else {

        $this->data['error'] = (validation_errors() ? validation_errors() : $this->session->flashdata('error'));

        $this->data['inv'] = $this->sales_model->getInvoiceByID($id);
        if($this->data['inv']->sale_status != 'completed'){
            $this->session->set_flashdata('error', lang("sale_status_x_competed"));
            redirect($_SERVER["HTTP_REFERER"]);
        }
        $inv_items = $this->sales_model->getAllInvoiceItems($id);
        $c = rand(100000, 9999999);
        foreach ($inv_items as $item) {
            //$row = $this->sales_model->getWHProduct($item->product_id);
            $row = json_decode('{}');
            $row->id = $item->product_id;
            $row->sale_item_id = $item->id;
            $row->code = $item->product_code;
            $row->name = $item->product_name;
            $row->qty = $item->quantity;
            $row->quantity = $item->quantity;
            $row->discount = $item->discount ? $item->discount : '0';
            $row->price = $item->net_unit_price;
            $row->serial = '';
            $row->option = $item->option_id;
            $row->tax_rate = $item->tax_rate_id;
            $row->tax_method = 1;
            $row->type = $item->product_type;
            $option = $item->option_id ? $this->sales_model->getProductOptionByID($item->option_id) : false;
            $ri = $this->Settings->item_addition ? $row->id : $c;
            if($row->tax_rate) {
                $tax_rate = $this->site->getTaxRateByID($row->tax_rate);
                $pr[$ri] = array('id' => $c, 'item_id' => $row->id, 'label' => $row->name . " (" . $row->code . ")", 'row' => $row, 'tax_rate' => $tax_rate, 'option' => $option);
            } else {
                $pr[$ri] = array('id' => $c, 'item_id' => $row->id, 'label' => $row->name . " (" . $row->code . ")", 'row' => $row, 'tax_rate' => false, 'option' => $option);
            }
            $c++;
        }
        $this->data['inv_items'] = json_encode($pr);
        $this->data['id'] = $id;
        $this->data['payment_ref'] = $this->site->getReference('pay');
        $this->data['reference'] = $this->site->getReference('re');
        $this->data['tax_rates'] = $this->site->getAllTaxRates();
        $bc = array(array('link' => base_url(), 'page' => lang('home')), array('link' => site_url('sales'), 'page' => lang('sales')), array('link' => '#', 'page' => lang('return_sale')));
        $meta = array('page_title' => lang('return_sale'), 'bc' => $bc);
        $this->page_construct('sales/return_sale', $meta, $this->data);
    }
}


/* ------------------------------- */

function delete($id = NULL) {
    $this->sma->checkPermissions(NULL, TRUE);

    if($this->input->get('id')) {
        $id = $this->input->get('id');
    }

    if($this->sales_model->deleteSale($id)) {
        echo lang("sale_deleted");
    }
}

function delete_return($id = NULL) {
    $this->sma->checkPermissions(NULL, TRUE);

    if($this->input->get('id')) {
        $id = $this->input->get('id');
    }

    if($this->sales_model->deleteReturn($id)) {
        echo lang("return_sale_deleted");
    }
}

function sale_actions() {
    // if(!$this->Owner || !$this->Admin) {
    //     $this->session->set_flashdata('warning', lang('access_denied'));
    //     redirect($_SERVER["HTTP_REFERER"]);
    // }

    $this->form_validation->set_rules('form_action', lang("form_action"), 'required');

    if($this->form_validation->run() == true) {

        if(!empty($_POST['val'])) {
            if($this->input->post('form_action') == 'delete') {
                foreach ($_POST['val'] as $id) {
                    $this->sales_model->deleteSale($id);
                }
                $this->session->set_flashdata('message', lang("sales_deleted"));
                redirect($_SERVER["HTTP_REFERER"]);
            }

            if($this->input->post('form_action') == 'export_backoffice') {
                foreach ($_POST['val'] as $id) {
                   // echo $id;
                    $this->sales_model->pushSale($id);
                }
                $this->session->set_flashdata('message', lang("sales_pushed"));
                //redirect($_SERVER["HTTP_REFERER"]);
                $_POST['form_action'] = 'backend';

                $this->check = true;

            }

            if($this->input->post('form_action') == 'backend'){
                $this->load->library('excel');
                $this->excel->setActiveSheetIndex(0);
                $this->excel->getActiveSheet()->setTitle(lang('sales'));
                $this->excel->getActiveSheet()->SetCellValue('A1','Distributor ID');
                $this->excel->getActiveSheet()->SetCellValue('B1','Distributor Name');
                $this->excel->getActiveSheet()->SetCellValue('C1','Transaction Reference');
                $this->excel->getActiveSheet()->SetCellValue('D1','Transaction Date');
                $this->excel->getActiveSheet()->SetCellValue('E1','Customer Code');
                $this->excel->getActiveSheet()->SetCellValue('F1','Customer Reference');
                $this->excel->getActiveSheet()->SetCellValue('G1','Branch Code');
                $this->excel->getActiveSheet()->SetCellValue('H1','Sales Type');
                $this->excel->getActiveSheet()->SetCellValue('I1','Product Category');
                $this->excel->getActiveSheet()->SetCellValue('J1','Product Code');
                $this->excel->getActiveSheet()->SetCellValue('K1','Product Name');
                $this->excel->getActiveSheet()->SetCellValue('L1','Location ID');
                $this->excel->getActiveSheet()->SetCellValue('M1','Quantity');
                $this->excel->getActiveSheet()->SetCellValue('N1','Selling Price');
                $this->excel->getActiveSheet()->SetCellValue('O1','Amount');
                $this->excel->getActiveSheet()->SetCellValue('P1','VAT');
                $this->excel->getActiveSheet()->SetCellValue('Q1','Sales Person ID');
                $this->excel->getActiveSheet()->SetCellValue('R1','Sales Person Name');
                $this->excel->getActiveSheet()->SetCellValue('S1','Outlet');
                $this->excel->getActiveSheet()->SetCellValue('T1','Store Type');
                $this->excel->getActiveSheet()->SetCellValue('U1','Route');
                $this->excel->getActiveSheet()->SetCellValue('V1','Month');
                $this->excel->getActiveSheet()->SetCellValue('W1','Year');

                $row = 2;
                foreach ($_POST['val'] as $id){
                    $sales = $this->sales_model->getBackOffice($id);

                    foreach ($sales->result() as $sale){
                        $temp = date_create($sale->date);
                        $date =  date_format($temp,"Y-m-d");
                        $year =     date_format($temp,"Y");
                        $month = date_format($temp,"m");
                        if ($sale->tax == '16.0000%'){$temp2 = 'Y';}else{$temp2 = 'N';}

                        $this->excel->getActiveSheet()->SetCellValue('A' . $row, $sale->biller_code);
                        $this->excel->getActiveSheet()->SetCellValue('B' . $row, $sale->biller);
                        $this->excel->getActiveSheet()->SetCellValue('C' . $row, $sale->reference_no);
                        $this->excel->getActiveSheet()->SetCellValue('D' . $row, $date);
                        $this->excel->getActiveSheet()->SetCellValue('E' . $row, $sale->cf2);
                        $this->excel->getActiveSheet()->SetCellValue('F' . $row, $sale->name);
                        $this->excel->getActiveSheet()->SetCellValue('G' . $row, $sale->cf3);
                        $this->excel->getActiveSheet()->SetCellValue('H' . $row, $sale->cf1);
                        $this->excel->getActiveSheet()->SetCellValue('I' . $row, $sale->category);
                        $this->excel->getActiveSheet()->SetCellValue('J' . $row, $sale->product_code);
                        $this->excel->getActiveSheet()->SetCellValue('K' . $row, $sale->product_name);
                        $this->excel->getActiveSheet()->SetCellValue('L' . $row, $sale->company);
                        $this->excel->getActiveSheet()->SetCellValue('M' . $row, $sale->quantity);
                        $this->excel->getActiveSheet()->SetCellValue('N' . $row, $sale->unit_price);
                        $this->excel->getActiveSheet()->SetCellValue('O' . $row, $sale->subtotal);
                        $this->excel->getActiveSheet()->SetCellValue('P' . $row, $temp2);
                        $this->excel->getActiveSheet()->SetCellValue('Q' . $row, $sale->code);
                        $this->excel->getActiveSheet()->SetCellValue('R' . $row, $sale->sp_name);
                        $this->excel->getActiveSheet()->SetCellValue('S' . $row, $sale->outlet_id);
                        $this->excel->getActiveSheet()->SetCellValue('T' . $row, $sale->type);
                        $this->excel->getActiveSheet()->SetCellValue('U' . $row, $sale->route_id);
                        $this->excel->getActiveSheet()->SetCellValue('V' . $row, $month);
                        $this->excel->getActiveSheet()->SetCellValue('W' . $row, $year);
                        $row++;
                    }//RESULT LOOP
                }//VAL LOOP
                $this->excel->getActiveSheet()->getColumnDimension('A')->setWidth(20);
                $this->excel->getActiveSheet()->getColumnDimension('B')->setWidth(20);
                $this->excel->getActiveSheet()->getColumnDimension('C')->setWidth(20);
                $this->excel->getActiveSheet()->getColumnDimension('D')->setWidth(20);
                $this->excel->getActiveSheet()->getColumnDimension('E')->setWidth(20);
                $this->excel->getActiveSheet()->getColumnDimension('F')->setWidth(20);
                $this->excel->getActiveSheet()->getColumnDimension('G')->setWidth(20);
                $this->excel->getActiveSheet()->getColumnDimension('H')->setWidth(20);
                $this->excel->getActiveSheet()->getColumnDimension('I')->setWidth(20);
                $this->excel->getActiveSheet()->getColumnDimension('J')->setWidth(20);
                $this->excel->getActiveSheet()->getColumnDimension('K')->setWidth(20);
                $this->excel->getActiveSheet()->getColumnDimension('L')->setWidth(20);
                //$this->excel->getActiveSheet()->getColumnDimension('M')->setWidth(20);
                $this->excel->getActiveSheet()->getColumnDimension('N')->setWidth(20);
                $this->excel->getActiveSheet()->getColumnDimension('O')->setWidth(20);
                //$this->excel->getActiveSheet()->getColumnDimension('P')->setWidth(20);
                $this->excel->getActiveSheet()->getColumnDimension('Q')->setWidth(20);
                $this->excel->getActiveSheet()->getColumnDimension('R')->setWidth(20);
                $this->excel->getActiveSheet()->getColumnDimension('S')->setWidth(20);
                $this->excel->getActiveSheet()->getColumnDimension('T')->setWidth(20);
                $this->excel->getActiveSheet()->getColumnDimension('U')->setWidth(20);
                

                $this->excel->getDefaultStyle()->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
                $filename = 'sales_' . date('Y_m_d_H_i_s');

                if($this->input->post('form_action') == 'backend') {
                    header('Content-Type: application/vnd.ms-excel');
                    header('Content-Disposition: attachment;filename="' . $filename . '.xls"');
                    header('Cache-Control: max-age=0');

                    $objWriter = PHPExcel_IOFactory::createWriter($this->excel, 'Excel5');
                    return $objWriter->save('php://output');
                }
                redirect('sales');
            }//BACKEND EXPORT

            if($this->input->post('form_action') == 'export_excel' || $this->input->post('form_action') == 'export_pdf') {

                $this->load->library('excel');
                $this->excel->setActiveSheetIndex(0);
                $this->excel->getActiveSheet()->setTitle(lang('sales'));
                $this->excel->getActiveSheet()->SetCellValue('A1', lang('date'));
                $this->excel->getActiveSheet()->SetCellValue('B1', lang('reference_no'));
                $this->excel->getActiveSheet()->SetCellValue('C1', lang('biller'));
                $this->excel->getActiveSheet()->SetCellValue('D1', lang('customer'));
                $this->excel->getActiveSheet()->SetCellValue('E1', lang('full_name'));
                $this->excel->getActiveSheet()->SetCellValue('F1', lang('grand_total'));
                $this->excel->getActiveSheet()->SetCellValue('G1', lang('paid'));
                $this->excel->getActiveSheet()->SetCellValue('H1', lang('payment_status'));

                $row = 2;
                foreach ($_POST['val'] as $id) {
                    $sale = $this->sales_model->getInvoiceByID($id);
                    $this->excel->getActiveSheet()->SetCellValue('A' . $row, $this->sma->hrld($sale->date));
                    $this->excel->getActiveSheet()->SetCellValue('B' . $row, $sale->reference_no);
                    $this->excel->getActiveSheet()->SetCellValue('C' . $row, $sale->biller);
                    $this->excel->getActiveSheet()->SetCellValue('D' . $row, $sale->customer);
                    $this->excel->getActiveSheet()->SetCellValue('E' . $row, $sale->sold_by );
                    $this->excel->getActiveSheet()->SetCellValue('F' . $row, $sale->grand_total);
                    $this->excel->getActiveSheet()->SetCellValue('G' . $row, $sale->paid);
                    $this->excel->getActiveSheet()->SetCellValue('H' . $row, $sale->payment_status);
                    $row++;
                }

                $this->excel->getActiveSheet()->getColumnDimension('A')->setWidth(20);
                $this->excel->getActiveSheet()->getColumnDimension('B')->setWidth(20);
                $this->excel->getDefaultStyle()->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
                $filename = 'sales_' . date('Y_m_d_H_i_s');
                if($this->input->post('form_action') == 'export_pdf') {
                    $styleArray = array('borders' => array('allborders' => array('style' => PHPExcel_Style_Border::BORDER_THIN)));
                    $this->excel->getDefaultStyle()->applyFromArray($styleArray);
                    $this->excel->getActiveSheet()->getPageSetup()->setOrientation(PHPExcel_Worksheet_PageSetup::ORIENTATION_LANDSCAPE);
                    require_once (APPPATH . "third_party".DIRECTORY_SEPARATOR."MPDF".DIRECTORY_SEPARATOR."mpdf.php");
                    $rendererName = PHPExcel_Settings::PDF_RENDERER_MPDF;
                    $rendererLibrary = 'MPDF';
                    $rendererLibraryPath = APPPATH . 'third_party'. DIRECTORY_SEPARATOR . $rendererLibrary;
                    if(!PHPExcel_Settings::setPdfRenderer( $rendererName, $rendererLibraryPath )) {
                        die('Please set the $rendererName: ' . $rendererName . ' and $rendererLibraryPath: ' . $rendererLibraryPath . ' values' .
                            PHP_EOL . ' as appropriate for your directory structure');
                    }

                    header('Content-Type: application/pdf');
                    header('Content-Disposition: attachment;filename="' . $filename . '.pdf"');
                    header('Cache-Control: max-age=0');

                    $objWriter = PHPExcel_IOFactory::createWriter($this->excel, 'PDF');
                    return $objWriter->save('php://output');
                }
                if($this->input->post('form_action') == 'export_excel') {
                    header('Content-Type: application/vnd.ms-excel');
                    header('Content-Disposition: attachment;filename="' . $filename . '.xls"');
                    header('Cache-Control: max-age=0');

                    $objWriter = PHPExcel_IOFactory::createWriter($this->excel, 'Excel5');
                    return $objWriter->save('php://output');
                }

                    redirect($_SERVER["HTTP_REFERER"]);
            }//excel & pdf
        }//not empty
         else {
            $this->session->set_flashdata('error', lang("no_sale_selected"));
            redirect($_SERVER["HTTP_REFERER"]);
        }
    } //form validation
     else {
        $this->session->set_flashdata('error', validation_errors());
        redirect($_SERVER["HTTP_REFERER"]);
    }
}

/* ------------------------------- */

function deliveries() {
    $this->sma->checkPermissions();

    $data['error'] = (validation_errors() ? validation_errors() : $this->session->flashdata('error'));
    $bc = array(array('link' => base_url(), 'page' => lang('home')), array('link' => site_url('sales'), 'page' => lang('sales')), array('link' => '#', 'page' => lang('deliveries')));
    $meta = array('page_title' => lang('deliveries'), 'bc' => $bc);
    $this->page_construct('sales/deliveries', $meta, $this->data);

}

function getDeliveries() {
    $this->sma->checkPermissions('deliveries');

    $detail_link = anchor('sales/view_delivery/$1', '<i class="fa fa-file-text-o"></i> ' . lang('delivery_details'), 'data-toggle="modal" data-target="#myModal"');
    $email_link = anchor('sales/email_delivery/$1', '<i class="fa fa-envelope"></i> ' . lang('email_delivery'), 'data-toggle="modal" data-target="#myModal"');
    $edit_link = anchor('sales/edit_delivery/$1', '<i class="fa fa-edit"></i> ' . lang('edit_delivery'), 'data-toggle="modal" data-target="#myModal"');
    $pdf_link = anchor('sales/pdf_delivery/$1', '<i class="fa fa-file-pdf-o"></i> ' . lang('download_pdf'));
    $delete_link = "<a href='#' class='po' title='<b>" . lang("delete_delivery") . "</b>' data-content=\"<p>"
    . lang('r_u_sure') . "</p><a class='btn btn-danger po-delete' href='" . site_url('sales/delete_delivery/$1') . "'>"
    . lang('i_m_sure') . "</a> <button class='btn po-close'>" . lang('no') . "</button>\"  rel='popover'><i class=\"fa fa-trash-o\"></i> "
    . lang('delete_delivery') . "</a>";
    $action = '<div class="text-center"><div class="btn-group text-left">'
    . '<button type="button" class="btn btn-default btn-xs btn-primary dropdown-toggle" data-toggle="dropdown">'
    . lang('actions') . ' <span class="caret"></span></button>
    <ul class="dropdown-menu pull-right" role="menu">
        <li>' . $detail_link . '</li>
        <li>' . $edit_link . '</li>
        <li>' . $pdf_link . '</li>
        <li>' . $delete_link . '</li>
    </ul>
</div></div>';

$this->load->library('datatables');
        //GROUP_CONCAT(CONCAT('Name: ', sale_items.product_name, ' Qty: ', sale_items.quantity ) SEPARATOR '<br>')
$this->datatables
->select("deliveries.id as id, date, do_reference_no, sale_reference_no, customer, address")
->from('deliveries')
->join('sale_items', 'sale_items.sale_id=deliveries.sale_id', 'left')
->group_by('deliveries.id');
$this->datatables->add_column("Actions", $action, "id");

echo $this->datatables->generate();
}

function pdf_delivery($id = NULL, $view = NULL, $save_bufffer = NULL) {
    $this->sma->checkPermissions();

    if($this->input->get('id')) {
        $id = $this->input->get('id');
    }
    $this->data['error'] = (validation_errors() ? validation_errors() : $this->session->flashdata('error'));
    $deli = $this->sales_model->getDeliveryByID($id);

    $this->data['delivery'] = $deli;
    $sale = $this->sales_model->getInvoiceByID($deli->sale_id);
    $this->data['biller'] = $this->site->getCompanyByID($sale->biller_id);
    $this->data['rows'] = $this->sales_model->getAllInvoiceItemsWithDetails($deli->sale_id);
    $this->data['user'] = $this->site->getUser($deli->created_by);


    $name = lang("delivery") . "_" . str_replace('/', '_', $deli->do_reference_no) . ".pdf";
    $html = $this->load->view($this->theme . 'sales/pdf_delivery', $this->data, TRUE);
    if($view) {
        $this->load->view($this->theme . 'sales/pdf_delivery', $this->data);
    } elseif($save_bufffer) {
        return $this->sma->generate_pdf($html, $name, $save_bufffer);
    } else {
        $this->sma->generate_pdf($html, $name);
    }
}

function view_delivery($id = NULL) {
    $this->sma->checkPermissions('deliveries');

    if($this->input->get('id')) {
        $id = $this->input->get('id');
    }

    $this->data['error'] = (validation_errors() ? validation_errors() : $this->session->flashdata('error'));
    $deli = $this->sales_model->getDeliveryByID($id);

    $this->data['delivery'] = $deli;
    $sale = $this->sales_model->getInvoiceByID($deli->sale_id);
    $this->data['biller'] = $this->site->getCompanyByID($sale->biller_id);
    $this->data['rows'] = $this->sales_model->getAllInvoiceItemsWithDetails($deli->sale_id);
    $this->data['user'] = $this->site->getUser($deli->created_by);
    $this->data['page_title'] = lang("delivery_order");

    $this->load->view($this->theme . 'sales/view_delivery', $this->data);
}

function add_delivery($id = NULL) {
    $this->sma->checkPermissions();

    if($this->input->get('id')) {
        $id = $this->input->get('id');
    }

    $this->form_validation->set_rules('do_reference_no', lang("do_reference_no"), 'required');
    $this->form_validation->set_rules('sale_reference_no', lang("sale_reference_no"), 'required');
    $this->form_validation->set_rules('customer', lang("customer"), 'required');
    $this->form_validation->set_rules('address', lang("address"), 'required');
        //$this->form_validation->set_rules('note', lang("note"), 'xss_clean');

    if($this->form_validation->run() == true) {
        if($this->Owner || $this->Admin) {
            $date = $this->sma->fld(trim($this->input->post('date')));
        } else {
            $date = date('Y-m-d H:i:s');
        }
        $dlDetails = array(
            'date' => $date,
            'sale_id' => $this->input->post('sale_id'),
            'do_reference_no' => $this->input->post('do_reference_no'),
            'sale_reference_no' => $this->input->post('sale_reference_no'),
            'customer' => $this->input->post('customer'),
            'address' => $this->input->post('address'),
            'note' => $this->sma->clear_tags($this->input->post('note')),
            'created_by' => $this->session->userdata('user_id')
            );
    } elseif($this->input->post('add_delivery')) {
        $this->session->set_flashdata('error', validation_errors());
        redirect($_SERVER["HTTP_REFERER"]);
    }


    if($this->form_validation->run() == true && $this->sales_model->addDelivery($dlDetails)) {
        $this->session->set_flashdata('message', lang("delivery_added"));
        redirect("sales/deliveries");
    } else {

        $this->data['error'] = (validation_errors() ? validation_errors() : $this->session->flashdata('error'));

        $sale = $this->sales_model->getInvoiceByID($id);
        $this->data['customer'] = $this->site->getCompanyByID($sale->customer_id);
        $this->data['inv'] = $sale;
        $this->data['do_reference_no'] = $this->site->getReference('do');
        $this->data['modal_js'] = $this->site->modal_js();

        $this->load->view($this->theme . 'sales/add_delivery', $this->data);
    }
}

function edit_delivery($id = NULL) {
    $this->sma->checkPermissions();

    if($this->input->get('id')) {
        $id = $this->input->get('id');
    }

    $this->form_validation->set_rules('do_reference_no', lang("do_reference_no"), 'required');
    $this->form_validation->set_rules('sale_reference_no', lang("sale_reference_no"), 'required');
    $this->form_validation->set_rules('customer', lang("customer"), 'required');
    $this->form_validation->set_rules('address', lang("address"), 'required');
        //$this->form_validation->set_rules('note', lang("note"), 'xss_clean');

    if($this->form_validation->run() == true) {

        $dlDetails = array(
            'sale_id' => $this->input->post('sale_id'),
            'do_reference_no' => $this->input->post('do_reference_no'),
            'sale_reference_no' => $this->input->post('sale_reference_no'),
            'customer' => $this->input->post('customer'),
            'address' => $this->input->post('address'),
            'note' => $this->sma->clear_tags($this->input->post('note')),
            'created_by' => $this->session->userdata('user_id')
            );

        if($this->Owner || $this->Admin) {
            $date = $this->sma->fld(trim($this->input->post('date')));
            $dlDetails['date'] = $date;
        }
    } elseif($this->input->post('edit_delivery')) {
        $this->session->set_flashdata('error', validation_errors());
        redirect($_SERVER["HTTP_REFERER"]);
    }


    if($this->form_validation->run() == true && $this->sales_model->updateDelivery($id, $dlDetails)) {
        $this->session->set_flashdata('message', lang("delivery_updated"));
        redirect("sales/deliveries");
    } else {

        $this->data['error'] = (validation_errors() ? validation_errors() : $this->session->flashdata('error'));


        $this->data['delivery'] = $this->sales_model->getDeliveryByID($id);
        $this->data['modal_js'] = $this->site->modal_js();

        $this->load->view($this->theme . 'sales/edit_delivery', $this->data);
    }
}

function delete_delivery($id = NULL) {
    $this->sma->checkPermissions();

    if($this->input->get('id')) {
        $id = $this->input->get('id');
    }

    if($this->sales_model->deleteDelivery($id)) {
        //echo lang("delivery_deleted");
        $this->session->set_flashdata('message', lang("delivery_deleted"));
        redirect($_SERVER["HTTP_REFERER"]);
    }
}
function delivery_actions() {
    if(!$this->Owner) {
        $this->session->set_flashdata('warning', lang('access_denied'));
        redirect($_SERVER["HTTP_REFERER"]);
    }

    $this->form_validation->set_rules('form_action', lang("form_action"), 'required');

    if($this->form_validation->run() == true) {

        if(!empty($_POST['val'])) {
            if($this->input->post('form_action') == 'delete') {
                foreach ($_POST['val'] as $id) {
                    $this->sales_model->deleteDelivery($id);
                }
                $this->session->set_flashdata('message', lang("deliveries_deleted"));
                redirect($_SERVER["HTTP_REFERER"]);
            }

            if($this->input->post('form_action') == 'export_excel' || $this->input->post('form_action') == 'export_pdf') {

                $this->load->library('excel');
                $this->excel->setActiveSheetIndex(0);
                $this->excel->getActiveSheet()->setTitle(lang('deliveries'));
                $this->excel->getActiveSheet()->SetCellValue('A1', lang('date'));
                $this->excel->getActiveSheet()->SetCellValue('B1', lang('do_reference_no'));
                $this->excel->getActiveSheet()->SetCellValue('C1', lang('sale_reference_no'));
                $this->excel->getActiveSheet()->SetCellValue('D1', lang('customer'));
                $this->excel->getActiveSheet()->SetCellValue('E1', lang('address'));

                $row = 2;
                foreach ($_POST['val'] as $id) {
                    $delivery = $this->sales_model->getDeliveryByID($id);
                    $this->excel->getActiveSheet()->SetCellValue('A' . $row, $this->sma->hrld($delivery->date));
                    $this->excel->getActiveSheet()->SetCellValue('B' . $row, $delivery->do_reference_no);
                    $this->excel->getActiveSheet()->SetCellValue('C' . $row, $delivery->sale_reference_no);
                    $this->excel->getActiveSheet()->SetCellValue('D' . $row, $delivery->customer);
                    $this->excel->getActiveSheet()->SetCellValue('E' . $row, $delivery->address);
                    $row++;
                }

                $this->excel->getActiveSheet()->getColumnDimension('A')->setWidth(20);
                $this->excel->getActiveSheet()->getColumnDimension('B')->setWidth(20);

                $filename = 'deliveries_' . date('Y_m_d_H_i_s');
                if($this->input->post('form_action') == 'export_pdf') {
                    $styleArray = array('borders' => array('allborders' => array('style' => PHPExcel_Style_Border::BORDER_THIN)));
                    $this->excel->getDefaultStyle()->applyFromArray($styleArray);
                    $this->excel->getActiveSheet()->getPageSetup()->setOrientation(PHPExcel_Worksheet_PageSetup::ORIENTATION_LANDSCAPE);
                    require_once (APPPATH . "third_party".DIRECTORY_SEPARATOR."MPDF".DIRECTORY_SEPARATOR."mpdf.php");
                    $rendererName = PHPExcel_Settings::PDF_RENDERER_MPDF;
                    $rendererLibrary = 'MPDF';
                    $rendererLibraryPath = APPPATH . 'third_party'. DIRECTORY_SEPARATOR . $rendererLibrary;
                    if(!PHPExcel_Settings::setPdfRenderer( $rendererName, $rendererLibraryPath )) {
                        die('Please set the $rendererName: ' . $rendererName . ' and $rendererLibraryPath: ' . $rendererLibraryPath . ' values' .
                            PHP_EOL . ' as appropriate for your directory structure');
                    }

                    header('Content-Type: application/pdf');
                    header('Content-Disposition: attachment;filename="' . $filename . '.pdf"');
                    header('Cache-Control: max-age=0');

                    $objWriter = PHPExcel_IOFactory::createWriter($this->excel, 'PDF');
                    return $objWriter->save('php://output');
                }
                if($this->input->post('form_action') == 'export_excel') {
                    header('Content-Type: application/vnd.ms-excel');
                    header('Content-Disposition: attachment;filename="' . $filename . '.xls"');
                    header('Cache-Control: max-age=0');

                    $objWriter = PHPExcel_IOFactory::createWriter($this->excel, 'Excel5');
                    return $objWriter->save('php://output');
                }

                redirect($_SERVER["HTTP_REFERER"]);
            }
        } else {
            $this->session->set_flashdata('error', lang("no_delivery_selected"));
            redirect($_SERVER["HTTP_REFERER"]);
        }
    } else {
        $this->session->set_flashdata('error', validation_errors());
        redirect($_SERVER["HTTP_REFERER"]);
    }
}

/* -------------------------------------------------------------------------------- */

function payments($id = NULL) {
    $this->sma->checkPermissions(false, true);
    $this->data['payments'] = $this->sales_model->getInvoicePayments($id);
    $this->load->view($this->theme.'sales/payments', $this->data);
}

function payment_note($id = NULL) {
    $payment = $this->sales_model->getPaymentByID($id);
    $inv = $this->sales_model->getInvoiceByID($payment->sale_id);
    $this->data['biller'] = $this->site->getCompanyByID($inv->biller_id);
    $this->data['customer'] = $this->site->getCompanyByID($inv->customer_id);
    $this->data['inv'] = $inv;
    $this->data['payment'] = $payment;
    $this->data['page_title'] = $this->lang->line("payment_note");
    
    $this->load->view($this->theme.'sales/payment_note', $this->data);
}

function add_payment($id = NULL) {
    $this->sma->checkPermissions('payments', true);
    $this->load->helper('security');
    if($this->input->get('id')) {
        $id = $this->input->get('id');
    }

    $this->form_validation->set_rules('reference_no', lang("reference_no"), 'required');
    $this->form_validation->set_rules('amount-paid', lang("amount"), 'required');
    $this->form_validation->set_rules('paid_by', lang("paid_by"), 'required');
        //$this->form_validation->set_rules('note', lang("note"), 'xss_clean');
    $this->form_validation->set_rules('userfile', lang("attachment"), 'xss_clean');
    if($this->form_validation->run() == true) {
        if($this->Owner || $this->Admin) {
            $date = $this->sma->fld(trim($this->input->post('date')));
        } else {
            $date = date('Y-m-d H:i:s');
        }
        $payment = array(
            'date' => $date,
            'sale_id' => $this->input->post('sale_id'),
            'reference_no' => $this->input->post('reference_no'),
            'amount' => $this->input->post('amount-paid'),
            'paid_by' => $this->input->post('paid_by'),
            'cheque_no' => $this->input->post('cheque_no'),
            'cc_no' => $this->input->post('paid_by') == 'gift_card' ? $this->input->post('gift_card_no') : $this->input->post('pcc_no'),
            'cc_holder' => $this->input->post('pcc_holder'),
            'cc_month' => $this->input->post('pcc_month'),
            'cc_year' => $this->input->post('pcc_year'),
            'cc_type' => $this->input->post('pcc_type'),
            'note' => $this->input->post('note'),
            'created_by' => $this->session->userdata('user_id'),
            'type' => 'received'
            );

        if($_FILES['userfile']['size'] > 0) {
            $this->load->library('upload');
            $config['upload_path'] = $this->upload_path;
            $config['allowed_types'] = $this->digital_file_types;
            $config['max_size'] = $this->allowed_file_size;
            $config['overwrite'] = FALSE;
            $config['encrypt_name'] = TRUE;
            $this->upload->initialize($config);
            if(!$this->upload->do_upload()) {
                $error = $this->upload->display_errors();
                $this->session->set_flashdata('error', $error);
                redirect($_SERVER["HTTP_REFERER"]);
            }
            $photo = $this->upload->file_name;
            $payment['attachment'] = $photo;
        } 

            //$this->sma->print_arrays($payment);

    } elseif($this->input->post('add_payment')) {
        $this->session->set_flashdata('error', validation_errors());
        redirect($_SERVER["HTTP_REFERER"]);
    }


    if($this->form_validation->run() == true && $this->sales_model->addPayment($payment)) {
        $this->session->set_flashdata('message', lang("payment_added"));
        redirect($_SERVER["HTTP_REFERER"]);
    } else {

        $this->data['error'] = (validation_errors() ? validation_errors() : $this->session->flashdata('error'));

        $sale = $this->sales_model->getInvoiceByID($id);
        $this->data['inv'] = $sale;
        $this->data['payment_ref'] = $this->site->getReference('pay');
        $this->data['modal_js'] = $this->site->modal_js();

        $this->load->view($this->theme . 'sales/add_payment', $this->data);
    }
}

function edit_payment($id = NULL) {
    $this->sma->checkPermissions('edit', true);
    $this->load->helper('security');
    if($this->input->get('id')) {
        $id = $this->input->get('id');
    }

    $this->form_validation->set_rules('reference_no', lang("reference_no"), 'required');
    $this->form_validation->set_rules('amount-paid', lang("amount"), 'required');
    $this->form_validation->set_rules('paid_by', lang("paid_by"), 'required');
        //$this->form_validation->set_rules('note', lang("note"), 'xss_clean');
    $this->form_validation->set_rules('userfile', lang("attachment"), 'xss_clean');
    if($this->form_validation->run() == true) {
        if($this->Owner || $this->Admin) {
            $date = $this->sma->fld(trim($this->input->post('date')));
        } else {
            $date = date('Y-m-d H:i:s');
        }
        $payment = array(
            'date' => $date,
            'sale_id' => $this->input->post('sale_id'),
            'reference_no' => $this->input->post('reference_no'),
            'amount' => $this->input->post('amount-paid'),
            'paid_by' => $this->input->post('paid_by'),
            'cheque_no' => $this->input->post('cheque_no'),
            'cc_no' => $this->input->post('pcc_no'),
            'cc_holder' => $this->input->post('pcc_holder'),
            'cc_month' => $this->input->post('pcc_month'),
            'cc_year' => $this->input->post('pcc_year'),
            'cc_type' => $this->input->post('pcc_type'),
            'note' => $this->input->post('note'),
            'created_by' => $this->session->userdata('user_id')
            );

        if($_FILES['userfile']['size'] > 0) {
            $this->load->library('upload');
            $config['upload_path'] = $this->upload_path;
            $config['allowed_types'] = $this->digital_file_types;
            $config['max_size'] = $this->allowed_file_size;
            $config['overwrite'] = FALSE;
            $config['encrypt_name'] = TRUE;
            $this->upload->initialize($config);
            if(!$this->upload->do_upload()) {
                $error = $this->upload->display_errors();
                $this->session->set_flashdata('error', $error);
                redirect($_SERVER["HTTP_REFERER"]);
            }
            $photo = $this->upload->file_name;
            $payment['attachment'] = $photo;
        } 

            //$this->sma->print_arrays($payment);

    } elseif($this->input->post('edit_payment')) {
        $this->session->set_flashdata('error', validation_errors());
        redirect($_SERVER["HTTP_REFERER"]);
    }


    if($this->form_validation->run() == true && $this->sales_model->updatePayment($id, $payment)) {
        $this->session->set_flashdata('message', lang("payment_updated"));
        redirect("sales");
    } else {

        $this->data['error'] = (validation_errors() ? validation_errors() : $this->session->flashdata('error'));

        $this->data['payment'] = $this->sales_model->getPaymentByID($id);
        $this->data['modal_js'] = $this->site->modal_js();

        $this->load->view($this->theme . 'sales/edit_payment', $this->data);
    }
}

function delete_payment($id = NULL) {
    $this->sma->checkPermissions('delete');

    if($this->input->get('id')) {
        $id = $this->input->get('id');
    }

    if($this->sales_model->deletePayment($id)) {
        //echo lang("payment_deleted");
        $this->session->set_flashdata('message', lang("payment_deleted"));
        redirect($_SERVER["HTTP_REFERER"]);
    }
}

/* --------------------------------------------------------------------------------------------- */

function suggestions() {
    $term = $this->input->get('term', TRUE);
    $warehouse_id = $this->input->get('warehouse_id', TRUE);
    $customer_id = $this->input->get('customer_id', TRUE);

    // if(strlen($term) < 1 || !$term) {
    //     die("<script type='text/javascript'>setTimeout(function(){ window.top.location.href = '" . site_url('welcome') . "'; }, 0);</script>");
    // }

    $spos = strpos($term, ' ');
    if($spos !== false) {
        $st = explode(" ", $term);
        $sr = trim($st[0]);
        $option = trim($st[1]);
    } else {
        $sr = $term;
        $option = '';
    }
    $customer = $this->site->getCompanyByID($customer_id);
    $customer_group = $this->site->getCustomerGroupByID($customer->customer_group_id);
    $rows = $this->sales_model->getProductNames($sr, $warehouse_id);
    if($rows) {
        foreach ($rows as $row) {
            $row->qty = 1;
            $row->discount = '0';
            $row->serial = '';
            $options = $this->sales_model->getProductOptions($row->id, $warehouse_id);
            if($options) {
                $opt = current($options);
                if(!$option) { $option = $opt->id; }
            } else {
                $opt = json_decode('{}');
                $opt->price = 0;
            }
            $row->option = $option;
            if($opt->price != 0) {
                $row->price = $opt->price + (($opt->price*$customer_group->percent)/100);
            } else {
                $row->price = $row->price + (($row->price*$customer_group->percent)/100);
            }
            $combo_items = FALSE;
            if($row->tax_rate) {
                $tax_rate = $this->site->getTaxRateByID($row->tax_rate);
                if($row->type == 'combo') {
                    $combo_items = $this->sales_model->getProductComboItems($row->id, $warehouse_id);
                } 
                $pr[] = array('id' => str_replace(".", "", microtime(true)), 'item_id' => $row->id, 'label' => $row->name . " (" . $row->code . ")", 'row' => $row, 'combo_items' => $combo_items, 'tax_rate' => $tax_rate, 'options' => $options);
            } else {
                $pr[] = array('id' => str_replace(".", "", microtime(true)), 'item_id' => $row->id, 'label' => $row->name . " (" . $row->code . ")", 'row' => $row, 'combo_items' => $combo_items, 'tax_rate' => false, 'options' => $options);
            }
        }
        echo json_encode($pr);
    } else {
        echo json_encode(array(array('id' => 0, 'label' => lang('no_match_found'), 'value' => $term)));
    }
}

/* ------------------------------------ Gift Cards ---------------------------------- */

function gift_cards() {
    $this->sma->checkPermissions();

    $this->data['error'] = validation_errors() ? validation_errors() : $this->session->flashdata('error');

    $bc = array(array('link' => base_url(), 'page' => lang('home')), array('link' => site_url('sales'), 'page' => lang('sales')), array('link' => '#', 'page' => lang('gift_cards')));
    $meta = array('page_title' => lang('gift_cards'), 'bc' => $bc);
    $this->page_construct('sales/gift_cards', $meta, $this->data);
}

function getGiftCards() {

    $this->load->library('datatables');
    $this->datatables
    ->select($this->db->dbprefix('gift_cards').".id as id, card_no, value, balance, CONCAT(".$this->db->dbprefix('users').".first_name, ' ', ".$this->db->dbprefix('users').".last_name) as created_by, customer, expiry", FALSE)
    ->join('users', 'users.id=gift_cards.created_by', 'left')
    ->from("gift_cards")
    ->add_column("Actions", "<center><a href='" . site_url('sales/edit_gift_card/$1') . "' class='tip' title='" . lang("edit_gift_card") . "' data-toggle='modal' data-target='#myModal'><i class=\"fa fa-edit\"></i></a> <a href='#' class='tip po' title='<b>" . lang("delete_gift_card") . "</b>' data-content=\"<p>" . lang('r_u_sure') . "</p><a class='btn btn-danger po-delete' href='" . site_url('sales/delete_gift_card/$1') . "'>" . lang('i_m_sure') . "</a> <button class='btn po-close'>" . lang('no') . "</button>\"  rel='popover'><i class=\"fa fa-trash-o\"></i></a></center>", "id");
        //->unset_column('id');

    echo $this->datatables->generate();
}

function validate_gift_card($no) {
        //$this->sma->checkPermissions();
    if($gc = $this->site->getGiftCardByNO($no)) {
        if($gc->expiry) {
            if($gc->expiry >= date('Y-m-d')) {
                echo json_encode($gc);
            } else {
                echo json_encode(false);
            }
        } else {
            echo json_encode($gc);
        }
    } else {
        echo json_encode(false);
    }
}

function add_gift_card() {
    $this->sma->checkPermissions(false, true);

    $this->form_validation->set_rules('card_no', lang("card_no"), 'trim|is_unique[gift_cards.card_no]|required');
    $this->form_validation->set_rules('value', lang("value"), 'required');

    if($this->form_validation->run() == true) {
        $customer_details = $this->input->post('customer') ? $this->site->getCompanyByID($this->input->post('customer')) : NULL;
        $customer = $customer_details ? $customer_details->company : NULL;
        $data = array('card_no' => $this->input->post('card_no'),
            'value' => $this->input->post('value'),
            'customer_id' => $this->input->post('customer') ? $this->input->post('customer') : NULL,
            'customer' => $customer,
            'balance' => $this->input->post('value'),
            'expiry' => $this->input->post('expiry') ? $this->sma->fsd($this->input->post('expiry')) : NULL,
            'created_by' => $this->session->userdata('user_id')
            );
        $sa_data = array();
        $ca_data = array();
        if($this->input->post('staff_points')) {
            $sa_points = $this->input->post('sa_points');
            $user = $this->site->getUser($this->input->post('user'));
            if($user->award_points < $sa_points) {
                $this->session->set_flashdata('error', lang("award_points_wrong"));
                redirect("sales/gift_cards");
            }
            $sa_data = array('user' => $user->id, 'points' => ($user->award_points-$sa_points));
        } elseif($customer_details && $this->input->post('use_points')) {
            $ca_points = $this->input->post('ca_points');
            if($customer_details->award_points < $ca_points) {
                $this->session->set_flashdata('error', lang("award_points_wrong"));
                redirect("sales/gift_cards");
            }
            $ca_data = array('customer' => $customer->id, 'points' => ($customer_details->award_points-$ca_points));
        }
    } elseif($this->input->post('add_gift_card')) {
        $this->session->set_flashdata('error', validation_errors());
        redirect("sales/gift_cards");
    }

        if($this->form_validation->run() == true && $this->sales_model->addGiftCard($data, $ca_data, $sa_data)) {
            $this->session->set_flashdata('message', lang("gift_card_added"));
            redirect("sales/gift_cards");
        } else {
            $this->data['error'] = (validation_errors() ? validation_errors() : $this->session->flashdata('error'));
            $this->data['modal_js'] = $this->site->modal_js();
            $this->data['users'] = $this->sales_model->getStaff();
            $this->data['page_title'] = lang("new_gift_card");
            $this->load->view($this->theme . 'sales/add_gift_card', $this->data);
        }
    }

    function edit_gift_card($id = NULL) {
        $this->sma->checkPermissions(false, true);

        $this->form_validation->set_rules('card_no', lang("card_no"), 'trim|required');
        $gc_details = $this->site->getGiftCardByID($id);
        if($this->input->post('card_no') != $gc_details->card_no) {
            $this->form_validation->set_rules('card_no', lang("card_no"), 'is_unique[gift_cards.card_no]');
        }
        $this->form_validation->set_rules('value', lang("value"), 'required');
        //$this->form_validation->set_rules('customer', lang("customer"), 'xss_clean');

        if($this->form_validation->run() == true) {
            $gift_card = $this->site->getGiftCardByID($id);
            $customer_details = $this->input->post('customer') ? $this->site->getCompanyByID($this->input->post('customer')) : NULL;
            $customer = $customer_details ? $customer_details->company : NULL;
            $data = array('card_no' => $this->input->post('card_no'),
                'value' => $this->input->post('value'),
                'customer_id' => $this->input->post('customer') ? $this->input->post('customer') : NULL,
                'customer' => $customer,
                'balance' => ($this->input->post('value') - $gift_card->value) + $gift_card->balance,
                'expiry' => $this->input->post('expiry') ? $this->sma->fsd($this->input->post('expiry')) : NULL,
                );
        } elseif($this->input->post('edit_gift_card')) {
            $this->session->set_flashdata('error', validation_errors());
            redirect("sales/gift_cards");
        }

        if($this->form_validation->run() == true && $this->sales_model->updateGiftCard($id, $data)) {
            $this->session->set_flashdata('message', lang("gift_card_updated"));
            redirect("sales/gift_cards");
        } else {
            $this->data['error'] = (validation_errors() ? validation_errors() : $this->session->flashdata('error'));
            $this->data['gift_card'] = $this->site->getGiftCardByID($id);
            $this->data['id'] = $id;
            $this->data['modal_js'] = $this->site->modal_js();
            $this->load->view($this->theme . 'sales/edit_gift_card', $this->data);
        }
    }

    function sell_gift_card() {
        $this->sma->checkPermissions('gift_cards', true);
        $error = NULL;
        $gcData = $this->input->get('gcdata');
        if(empty($gcData[0])) { $error =  lang("value")." ".lang("is_required"); }
        if(empty($gcData[1])) { $error =  lang("card_no")." ".lang("is_required"); }


        $customer_details = (!empty($gcData[2])) ? $this->site->getCompanyByID($gcData[2]) : NULL;
        $customer = $customer_details ? $customer_details->company : NULL;
        $data = array('card_no' => $gcData[0],
            'value' => $gcData[1],
            'customer_id' => (!empty($gcData[2])) ? $gcData[2] : NULL,
            'customer' => $customer,
            'balance' => $gcData[1],
            'expiry' => (!empty($gcData[3])) ? $this->sma->fsd($gcData[3]) : NULL,
            'created_by' => $this->session->userdata('username')
            );
        
        if(!$error) {
            if( $this->sales_model->addGiftCard($data) ) {  
                echo json_encode(array('result' => 'success', 'message' => lang("gift_card_added")));
            }
        } else {
            echo json_encode(array('result' => 'failed', 'message' => $error));
        }

    }

    function delete_gift_card($id = NULL) {
        $this->sma->checkPermissions();

        if($this->sales_model->deleteGiftCard($id)) {
            echo lang("gift_card_deleted");
        }
    }

    function gift_card_actions() {
        if(!$this->Owner) {
            $this->session->set_flashdata('warning', lang('access_denied'));
            redirect($_SERVER["HTTP_REFERER"]);
        }
        
        $this->form_validation->set_rules('form_action', lang("form_action"), 'required');

        if($this->form_validation->run() == true) {

            if(!empty($_POST['val'])) {
                if($this->input->post('form_action') == 'delete') {
                    foreach ($_POST['val'] as $id) {
                        $this->sales_model->deleteGiftCard($id);
                    }
                    $this->session->set_flashdata('message', lang("gift_cards_deleted"));
                    redirect($_SERVER["HTTP_REFERER"]);
                }

                if($this->input->post('form_action') == 'export_excel' || $this->input->post('form_action') == 'export_pdf') {

                    $this->load->library('excel');
                    $this->excel->setActiveSheetIndex(0);
                    $this->excel->getActiveSheet()->setTitle(lang('gift_cards'));
                    $this->excel->getActiveSheet()->SetCellValue('A1', lang('card_no'));
                    $this->excel->getActiveSheet()->SetCellValue('B1', lang('value'));
                    $this->excel->getActiveSheet()->SetCellValue('C1', lang('customer'));

                    $row = 2;
                    foreach ($_POST['val'] as $id) {
                        $sc = $this->site->getGiftCardByID($id);
                        $this->excel->getActiveSheet()->SetCellValue('A' . $row, $sc->card_no);
                        $this->excel->getActiveSheet()->SetCellValue('B' . $row, $sc->value);
                        $this->excel->getActiveSheet()->SetCellValue('C' . $row, $sc->customer);
                        $row++;
                    }

                    $this->excel->getActiveSheet()->getColumnDimension('B')->setWidth(20);
                    $this->excel->getDefaultStyle()->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
                    $filename = 'gift_cards_' . date('Y_m_d_H_i_s');
                    if($this->input->post('form_action') == 'export_pdf') {
                        $styleArray = array('borders' => array('allborders' => array('style' => PHPExcel_Style_Border::BORDER_THIN)));
                        $this->excel->getDefaultStyle()->applyFromArray($styleArray);
                        $this->excel->getActiveSheet()->getPageSetup()->setOrientation(PHPExcel_Worksheet_PageSetup::ORIENTATION_LANDSCAPE);
                        require_once (APPPATH . "third_party".DIRECTORY_SEPARATOR."MPDF".DIRECTORY_SEPARATOR."mpdf.php");
                        $rendererName = PHPExcel_Settings::PDF_RENDERER_MPDF;
                        $rendererLibrary = 'MPDF';
                        $rendererLibraryPath = APPPATH . 'third_party'. DIRECTORY_SEPARATOR . $rendererLibrary;
                        if(!PHPExcel_Settings::setPdfRenderer( $rendererName, $rendererLibraryPath )) {
                            die('Please set the $rendererName: ' . $rendererName . ' and $rendererLibraryPath: ' . $rendererLibraryPath . ' values' .
                                PHP_EOL . ' as appropriate for your directory structure');
                        }

                        header('Content-Type: application/pdf');
                        header('Content-Disposition: attachment;filename="' . $filename . '.pdf"');
                        header('Cache-Control: max-age=0');

                        $objWriter = PHPExcel_IOFactory::createWriter($this->excel, 'PDF');
                        return $objWriter->save('php://output');
                    }
                    if($this->input->post('form_action') == 'export_excel') {
                        header('Content-Type: application/vnd.ms-excel');
                        header('Content-Disposition: attachment;filename="' . $filename . '.xls"');
                        header('Cache-Control: max-age=0');

                        $objWriter = PHPExcel_IOFactory::createWriter($this->excel, 'Excel5');
                        return $objWriter->save('php://output');
                    }

                    redirect($_SERVER["HTTP_REFERER"]);
                }
            } else {
                $this->session->set_flashdata('error', lang("no_gift_card_selected"));
                redirect($_SERVER["HTTP_REFERER"]);
            }
        } else {
            $this->session->set_flashdata('error', validation_errors());
            redirect($_SERVER["HTTP_REFERER"]);
        }
    }

    function get_award_points($id = NULL) {
        $this->sma->checkPermissions('index');
        
        $row = $this->site->getUser($id);
        echo json_encode(array('sa_points' => $row->award_points));
    }

    function test (){
if(!$this->Owner && !$warehouse_id) {
            $user = $this->site->getUser();
            $warehouse_id = $user->warehouse_id;
        }
        $this->load->library('datatables');
        if($warehouse_id) {
        $this->datatables->select('sma_sales.id,sma_sales.date,sma_sales.reference_no,sma_sales.biller,sma_users.first_name,sma_users.last_name,sma_sales.route_id,sma_sales.outlet_id,sma_sales.type,sma_sales.receipt_no,
            sma_categories.name as category,sma_products.product_details,sma_sale_items.quantity,sma_sale_items.subtotal as val')
        ->from('sma_sales')
        ->join('sma_sale_items','sma_sales.id = sma_sale_items.sale_id','inner')
        ->join('sma_products','sma_sale_items.product_id = sma_products.id','inner')
        ->join('sma_categories','sma_products.category_id = sma_categories.id')
        ->join('sma_users','sma_sales.created_by = sma_users.id','inner')->unset_column('id')->unset_column('biller_id')
        ->where('sma_sales.warehouse_id', $warehouse_id);
    } else {
         $this->datatables->select('sma_sales.id,sma_sales.date,sma_sales.reference_no,sma_sales.biller,sma_users.first_name,sma_users.last_name,sma_sales.route_id,sma_sales.outlet_id,sma_sales.type,sma_sales.receipt_no,
            sma_categories.name as category,sma_products.product_details,sma_sale_items.quantity,sma_sale_items.subtotal as val')
        ->from('sma_sales')
        ->join('sma_sale_items','sma_sales.id = sma_sale_items.sale_id','inner')
        ->join('sma_products','sma_sale_items.product_id = sma_products.id','inner')
        ->join('sma_categories','sma_products.category_id = sma_categories.id')
        ->join('sma_users','sma_sales.created_by = sma_users.id','inner')->unset_column('id')->unset_column('biller_id');
    }
    $this->datatables->where('pos !=', 1);
    if(!$this->Customer && !$this->Supplier && !$this->Owner && !$this->Admin) {
        $this->datatables->where('sma_sales.created_by', $this->session->userdata('user_id'));
    } elseif($this->Customer) {
        $this->datatables->where('sma_sales.customer_id', $this->session->userdata('user_id'));
    }

    //$query = $this->db->get()->result();
echo $this->datatables->generate();
    //print_r($query);
    }

    function test2 (){
if(!$this->Owner && !$warehouse_id) {
            $user = $this->site->getUser();
            $warehouse_id = $user->warehouse_id;
        }
        $this->load->library('datatables');
        if($warehouse_id) {
        $this->datatables->query(' select a.id, a.date, a.reference_no,a.biller,c.first_name,c.last_name,b.sale_id,a.route_id,a.outlet_id,a.type,a.receipt_no,e.name as category,d.product_details,b.quantity,b.subtotal as val
       from sma_sales a
       left join sma_sale_items b on b.sale_id = a.id
       left join sma_users c on c.id = a.created_by
       left join sma_products d on d.id = b.product_id
       left join sma_categories e on e.id = d.category_id')
        ->where('a.warehouse_id', $warehouse_id);
    } else {
        $this->datatables->query(' select a.id, a.date, a.reference_no,a.biller,c.first_name,c.last_name,b.sale_id,a.route_id,a.outlet_id,a.type,a.receipt_no,e.name as category,d.product_details,b.quantity,b.subtotal as val
       from sma_sales a
       left join sma_sale_items b on b.sale_id = a.id
       left join sma_users c on c.id = a.created_by
       left join sma_products d on d.id = b.product_id
       left join sma_categories e on e.id = d.category_id');
    }
    $this->datatables->where('pos !=', 1);
    if(!$this->Customer && !$this->Supplier && !$this->Owner && !$this->Admin) {
        $this->datatables->where('a.created_by', $this->session->userdata('user_id'))->unset_column('id')->unset_column('biller_id');
    } elseif($this->Customer) {
        $this->datatables->where('a.customer_id', $this->session->userdata('user_id'))->unset_column('id')->unset_column('biller_id');
    }

    //$query = $this->db->get()->result();
 echo $this->datatables->generate();
    //print_r($query);
    }

    function test3 (){
        $this->load->library('datatables');

        $this->datatables->select('sma_sales.id,sma_sales.date,sma_sales.reference_no,sma_sales.biller,sma_users.first_name,sma_users.last_name,sma_sales.route_id,sma_sales.outlet_id,sma_sales.type,sma_sales.receipt_no,
            sma_categories.name as category,sma_products.product_details,sma_sale_items.quantity,sma_sale_items.subtotal as val')
        ->from('sma_sales')
        ->join('sma_sale_items','sma_sales.id = sma_sale_items.sale_id','inner')
        ->join('sma_products','sma_sale_items.product_id = sma_products.id','inner')
        ->join('sma_categories','sma_products.category_id = sma_categories.id')
        ->join('sma_users','sma_sales.created_by = sma_users.id','inner')->unset_column('id')->unset_column('biller_id');
        echo $this->datatables->generate();
    }

        function test4 (){
            $this->load->model('sales_model');
        $data = $this->sales_model->getR(3);

        foreach ($data as $sales) {
            echo $sales->id."<br>";
            echo $sales->date."<br>";
            echo $sales->last_name."<br>";
        }

        echo "dsf<br>";

        print_r($data);

         }


}
