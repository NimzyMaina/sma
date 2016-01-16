<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Van extends MY_Controller {

    //public $data =array();

    function __construct() {
        parent::__construct();

        if(!$this->loggedIn) {
            $this->session->set_userdata('requested_page', $this->uri->uri_string());
            redirect('login');
        }
        if($this->Customer || $this->Supplier) {
            $this->session->set_flashdata('warning', lang('access_denied'));
            redirect($_SERVER["HTTP_REFERER"]);
        }
        $this->load->model('van_model');
        $this->load->model('products_model');
        $this->load->model('transfers_model');
        $this->load->model('site');
        $this->load->model('auth_model');
        $this->load->library('datatables');
        $this->load->library('form_validation');
        $this->popup_attributes = array('width' => '900','height' => '600','window_name' => 'sma_popup', 'menubar' => 'yes', 'scrollbars' => 'yes','status' => 'no', 'resizable' => 'yes','screenx' => '0','screeny' => '0');
    }

    function index(){

        $this->data['error'] = validation_errors() ? validation_errors() : $this->session->flashdata('error');

        $bc = array(array('link' => base_url(), 'page' => lang('home')), array('link' => site_url('van'), 'page' => lang('van')), array('link' => '#', 'page' => lang('list_vans')));
        $meta = array('page_title' => lang('van'), 'bc' => $bc);
        $this->page_construct('vans/van', $meta, $this->data);
    }

    function getVans(){
        $stock_link = anchor('van/stock/$1', '<i class="fa fa-file-text-o"></i> ' . lang('view_stock'));

        $edit_link = anchor('van/edit_van/$1', '<i class="fa fa-edit"></i> ' . lang('edit_van'), 'data-toggle="modal" data-target="#myModal"');

        $action = '<div class="text-center"><div class="btn-group text-left">'
        . '<button type="button" class="btn btn-default btn-xs btn-primary dropdown-toggle" data-toggle="dropdown">'
        . lang('actions') . ' <span class="caret"></span></button>
        <ul class="dropdown-menu pull-right" role="menu">
            <li>' . $stock_link . '</li>
            <li>' . $edit_link . '</li>';

           $action.= '</ul>
    </div></div>';
               $this->load->library('datatables');
              $q = $this->datatables->select('vans.id,van_code,van_name,concat(first_name," ",last_name) as full_name',false)
               ->from('vans')
               ->join('users','users.id=vans.user_id')
                ->add_column("Actions", $action, "vans.id");
               if(null != $this->session->userdata('warehouse_id')){
                $this->datatables->where('users.warehouse_id',$this->session->userdata('warehouse_id'));
               }
               //print_r($q);
               echo $this->datatables->generate();
    }

    function edit_van($id = NULL){
        $this->load->helper('security');
        $this->form_validation->set_rules('van_code', lang("code"), 'trim|required');
        $wh_details = $this->van_model->getVanByID($id);
        if($this->input->post('van_code') != $wh_details->van_code) {
            $this->form_validation->set_rules('van_code', lang("code"), 'is_unique[vans.van_code]');
        }

        if($this->form_validation->run() == true) {
            $data = array('van_code' => $this->input->post('code'),
                'van_name' => $this->input->post('name'),
                'user_id' => $this->input->post('user'),
            );
        }
        if($this->form_validation->run() == true && $this->van_model->updateVan($id, $data)) { //check to see if we are updateing the customer
            $van = array('van_id' => $id);
            $user = $data['user_id'];
            $this->db->where('id',$user)
            ->update('users',$van);
            $this->session->set_flashdata('message', lang("van_updated"));
            redirect("van");
        } else {
            $this->data['error'] = (validation_errors() ? validation_errors() : $this->session->flashdata('error'));

            $this->data['warehouse'] = $this->van_model->getVanByID($id);
            $this->data['agents'] = $this->auth_model->get_agents();
            $this->data['id'] = $id;
            $this->data['modal_js'] = $this->site->modal_js();
            $this->load->view($this->theme . 'vans/edit_van', $this->data);
        }
    }

    function add_van(){
                $this->load->helper('security');
        $this->form_validation->set_rules('code', lang("code"), 'trim|is_unique[vans.van_code]|required');
        $this->form_validation->set_rules('name', lang("name"), 'required');
        $this->form_validation->set_rules('user', lang("full_name"), 'required|is_unique[vans.user_id]');

        if($this->form_validation->run() == true) {
            $id = $this->session->userdata('warehouse_id');
            $data = array('van_code' => $this->input->post('code'),
                'van_name' => $this->input->post('name'),
                'user_id' => $this->input->post('user'),
                'warehouse' => $id
            );
        } 
        if($this->form_validation->run() == true && $this->van_model->addVan($data)) {
            $this->session->set_flashdata('message', lang("van_added"));
            redirect("van");
        } else {
            $this->data['error'] = (validation_errors() ? validation_errors() : $this->session->flashdata('error'));

            $this->data['modal_js'] = $this->site->modal_js();
            $this->data['agents'] = $this->auth_model->get_agents();
            $this->load->view($this->theme . 'vans/add_van', $this->data);
        }
    }

        function van_actions() {

        $this->form_validation->set_rules('form_action', lang("form_action"), 'required');

        if($this->form_validation->run() == true) {

            if(!empty($_POST['val'])) {
                if($this->input->post('form_action') == 'delete') {
                    foreach ($_POST['val'] as $id) {
                        $this->settings_model->deleteWarehouse($id);
                    }
                    $this->session->set_flashdata('message', lang("warehouses_deleted"));
                    redirect($_SERVER["HTTP_REFERER"]);
                }

                if($this->input->post('form_action') == 'export_excel' || $this->input->post('form_action') == 'export_pdf') {

                    $this->load->library('excel');
                    $this->excel->setActiveSheetIndex(0);
                    $this->excel->getActiveSheet()->setTitle(lang('warehouses'));
                    $this->excel->getActiveSheet()->SetCellValue('A1', lang('code'));
                    $this->excel->getActiveSheet()->SetCellValue('B1', lang('name'));
                    $this->excel->getActiveSheet()->SetCellValue('C1', lang('full_name'));

                    $row = 2;
                    foreach ($_POST['val'] as $id) {
                        $wh = $this->van_model->getVanByID2($id);
                        $this->excel->getActiveSheet()->SetCellValue('A' . $row, $wh->van_code);
                        $this->excel->getActiveSheet()->SetCellValue('B' . $row, $wh->van_name);
                        $this->excel->getActiveSheet()->SetCellValue('C' . $row, $wh->full_name);
                        $row++;
                    }
                    $this->excel->getActiveSheet()->getColumnDimension('A')->setWidth(20);
                    $this->excel->getActiveSheet()->getColumnDimension('B')->setWidth(20);
                    $this->excel->getActiveSheet()->getColumnDimension('C')->setWidth(20);
                    $this->excel->getDefaultStyle()->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
                    $filename = 'vans_' . date('Y_m_d_H_i_s');
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
                $this->session->set_flashdata('error', lang("no_van_selected"));
                redirect($_SERVER["HTTP_REFERER"]);
            }
        } else {
            $this->session->set_flashdata('error', validation_errors());
            redirect($_SERVER["HTTP_REFERER"]);
        }
    }

    function transfers(){
         $this->sma->checkPermissions();
        
        $this->data['error'] = (validation_errors()) ? validation_errors() : $this->session->flashdata('error');

        $bc = array(array('link' => base_url(), 'page' => lang('home')), array('link' => '#', 'page' => lang('van_transfers')));
        $meta = array('page_title' => lang('van_transfers'), 'bc' => $bc);
        $this->page_construct('vans/transfers', $meta, $this->data);
    }

    function getTransfers(){
                $this->sma->checkPermissions('index');
        
        $detail_link = anchor('transfers/view/$1', '<i class="fa fa-file-text-o"></i> ' . lang('transfer_details'), 'data-toggle="modal" data-target="#myModal"');
        $email_link = anchor('transfers/email/$1', '<i class="fa fa-envelope"></i> ' . lang('email_transfer'), 'data-toggle="modal" data-target="#myModal"');
        $edit_link = anchor('transfers/edit/$1', '<i class="fa fa-edit"></i> ' . lang('edit_transfer'));
        $pdf_link = anchor('transfers/pdf/$1', '<i class="fa fa-file-pdf-o"></i> ' . lang('download_pdf'));
        $delete_link = "<a href='#' class='tip po' title='<b>" . lang("delete_transfer") . "</b>' data-content=\"<p>"
        . lang('r_u_sure') . "</p><a class='btn btn-danger po-delete1' id='a__$1' href='" . site_url('transfers/delete/$1') . "'>"
        . lang('i_m_sure') . "</a> <button class='btn po-close'>" . lang('no') . "</button>\"  rel='popover'><i class=\"fa fa-trash-o\"></i> "
        . lang('delete_transfer') . "</a>";
        $action = '<div class="text-center"><div class="btn-group text-left">'
        . '<button type="button" class="btn btn-default btn-xs btn-primary dropdown-toggle" data-toggle="dropdown">'
        . lang('actions') . ' <span class="caret"></span></button>
        <ul class="dropdown-menu pull-right" role="menu">
            <li>' . $detail_link . '</li>
            <li>' . $edit_link . '</li>
            <li>' . $pdf_link . '</li>
            <li>' . $email_link . '</li>
            <li>' . $delete_link . '</li>
        </ul>
    </div></div>';

    $this->load->library('datatables');

    $this->datatables
    ->select("id, date, transfer_no, from_warehouse_name as fname, from_warehouse_code as fcode, to_van_name as tname,to_van_code as tcode, total, total_tax, grand_total, status")
    ->from('van_transfers')
    ->edit_column("fname", "$1 ($2)", "fname, fcode")
    ->edit_column("tname", "$1 ($2)", "tname, tcode");

    $this->datatables->add_column("Actions", $action, "id")
    ->unset_column('fcode')
    ->unset_column('tcode');
    echo $this->datatables->generate();
    }

    function add_transfer(){
    $this->sma->checkPermissions();

    $this->form_validation->set_message('is_natural_no_zero', lang("no_zero_required"));
    $this->form_validation->set_rules('reference_no', lang("reference_no"), 'required');
    $this->form_validation->set_rules('to_warehouse', lang("van") . ' (' . lang("to") . ')', 'required|is_natural_no_zero');
    $this->form_validation->set_rules('from_warehouse', lang("warehouse") . ' (' . lang("from") . ')', 'required|is_natural_no_zero');
        //$this->form_validation->set_rules('note', lang("note"), 'xss_clean');

    if($this->form_validation->run()) {

        $transfer_no = $this->input->post('reference_no');
        if($this->Owner || $this->Admin) {
            $date = $this->sma->fld(trim($this->input->post('date')));
        } else {
            $date = date('Y-m-d H:i:s');
        }
        $to_warehouse = $this->input->post('to_warehouse');
        $from_warehouse = $this->input->post('from_warehouse');
        $note = $this->sma->clear_tags($this->input->post('note'));
        $shipping = $this->input->post('shipping');
        $status = $this->input->post('status');
        $from_warehouse_details = $this->site->getWarehouseByID($from_warehouse);
        $from_warehouse_code = $from_warehouse_details->code;
        $from_warehouse_name = $from_warehouse_details->name;
        $to_warehouse_details = $this->van_model->getVanByID($to_warehouse);
        $to_warehouse_code = $to_warehouse_details->van_code;
        $to_warehouse_name = $to_warehouse_details->van_name;

        $total = 0;
        $product_tax = 0;
        print_r($_POST['product_code']);
        $i = isset($_POST['product_code']) ? sizeof($_POST['product_code']) : 0;
        for ($r = 0; $r < $i; $r++) {
            $item_code = $_POST['product_code'][$r];
            $item_net_cost = $_POST['net_cost'][$r];
            $item_quantity = $_POST['quantity'][$r];
            $item_tax_rate = isset($_POST['product_tax'][$r]) ? $_POST['product_tax'][$r] : NULL;
            $item_expiry = isset($_POST['expiry'][$r]) ? $this->sma->fsd($_POST['expiry'][$r]) : NULL;
            $item_option = isset($_POST['product_option'][$r]) ? $_POST['product_option'][$r] : NULL;

            if(isset($item_code) && isset($item_net_cost) && isset($item_quantity)) {
                $product_details = $this->van_model->getProductByCode($item_code);
                print_r($product_details);
                //$this->sma->print_arrays($product_details);exit;
                if(!$this->Settings->overselling) { 
                    $warehouse_quantity = $this->transfers_model->getWarehouseProduct($from_warehouse_details->id, $product_details->id, $item_option);
                    if($warehouse_quantity->quantity < $item_quantity) {
                        $this->session->set_flashdata('error', lang("no_match_found")." (".lang('product_name')." <strong>".$product_details->name."</strong> ".lang('product_code')." <strong>".$product_details->code."</strong>)" );
                        redirect("van/add_transfer");
                    }
                }
                if(isset($item_tax_rate) && $item_tax_rate != 0) {
                    $pr_tax = $item_tax_rate;
                    $tax_details = $this->site->getTaxRateByID($pr_tax);

                    if($tax_details->type == 1 && $tax_details->rate != 0) {
                        $item_tax = ((($item_quantity * $item_net_cost) * $tax_details->rate) / 100);
                        $product_tax += $item_tax;
                    } else {
                        $item_tax = $tax_details->rate;
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

                $subtotal = (($item_net_cost * $item_quantity) + $item_tax);

                $products[] = array(
                    'product_id' => $product_details->id,
                    'product_code' => $item_code,
                    'product_name' => $product_details->name,
                    'option_id' => $item_option,
                    'net_unit_cost' => $item_net_cost,
                    'quantity' => $item_quantity,
                    'quantity_balance' => $item_quantity,
                    'item_tax' => $item_tax,
                    'tax_rate_id' => $pr_tax,
                    'tax' => $tax,
                    'expiry' => $item_expiry,
                    'subtotal' => $subtotal,
                    );

                $total += $item_net_cost * $item_quantity;
            }
        }
        if(empty($products)) {
            $this->form_validation->set_rules('product', lang("order_item"), 'required');
        } else {
            krsort($products);
        }
        $grand_total = $total + $shipping + $product_tax;
        $data = array('transfer_no' => $transfer_no,
            'date' => $date,
            'from_warehouse_id' => $from_warehouse,
            'from_warehouse_code' => $from_warehouse_code,
            'from_warehouse_name' => $from_warehouse_name,
            'to_van_id' => $to_warehouse,
            'to_van_code' => $to_warehouse_code,
            'to_van_name' => $to_warehouse_name,
            'note' => $note,
            'total_tax' => $product_tax,
            'total' => $total,
            'grand_total' => $grand_total,
            'created_by' => $this->session->userdata('username'),
            'status' => $status,
            'shipping' => $shipping
            );

        //$this->sma->print_arrays($data, $products);
    }

    if($this->form_validation->run() == true && $this->van_model->addTransfer($data, $products)) {
        $this->session->set_userdata('remove_tols', 1);
        $this->session->set_flashdata('message', lang("transfer_added"));
        redirect("van/transfers");
    } else {


        $this->data['error'] = (validation_errors() ? validation_errors() : $this->session->flashdata('error'));

        $this->data['name'] = array('name' => 'name',
            'id' => 'name',
            'type' => 'text',
            'value' => $this->form_validation->set_value('name'),
            );
        $this->data['quantity'] = array('name' => 'quantity',
            'id' => 'quantity',
            'type' => 'text',
            'value' => $this->form_validation->set_value('quantity'),
            );

        $this->data['warehouses'] = $this->site->getAllWarehouses();
        $this->data['vans'] = $this->van_model->getAllVans();
        $this->data['tax_rates'] = $this->site->getAllTaxRates();
        $this->data['rnumber'] = $this->site->getReference('tov');

        $bc = array(array('link' => base_url(), 'page' => lang('home')), array('link' => site_url('van_transfers'), 'page' => lang('van_transfers')), array('link' => '#', 'page' => lang('add_transfer')));
        $meta = array('page_title' => lang('transfer_quantity'), 'bc' => $bc);
        $this->page_construct('vans/add', $meta, $this->data);
    }
}

        function suggestions() {
        $this->sma->checkPermissions('index', TRUE);
        $term = $this->input->get('term', TRUE);
        $warehouse_id = $this->input->get('warehouse_id', TRUE);

        if(strlen($term) < 1 || !$term) {
            die("<script type='text/javascript'>setTimeout(function(){ window.top.location.href = '" . site_url('welcome') . "'; }, 0);</script>");
        }

        $spos = strpos($term, ' ');
        if($spos !== false) {
            $st = explode(" ", $term);
            $sr = trim($st[0]);
            $option = trim($st[1]);
        } else {
            $sr = $term;
            $option = '';
        }

        $rows = $this->van_model->getProductNames($sr, $warehouse_id);
        if($rows) {
            foreach ($rows as $row) {
                $row->qty = 1;
                $row->discount = '0';
                $row->serial = '';
                $row->expiry = '';
                $options = $this->van_model->getProductOptions($row->id, $warehouse_id);
                if($options) {
                    $opt = current($options);
                    if(!$option) { $option = $opt->id; }
                } else {
                    $opt = json_decode('{}');
                    $opt->cost = 0;
                }
                $row->option = $option;
                if($opt->cost != 0) {
                    $row->cost = $opt->cost;
                } 

                $combo_items = FALSE;
                if($row->tax_rate) {
                    $tax_rate = $this->site->getTaxRateByID($row->tax_rate);
                    $pr[] = array('id' => str_replace(".", "", microtime(true)), 'item_id' => $row->id, 'label' => $row->name . " (" . $row->code . ")", 'row' => $row, 'tax_rate' => $tax_rate, 'options' => $options);
                } else {
                    $pr[] = array('id' => str_replace(".", "", microtime(true)), 'item_id' => $row->id, 'label' => $row->name . " (" . $row->code . ")", 'row' => $row, 'tax_rate' => false, 'options' => $options);
                }
            }
            echo json_encode($pr);
        } else {
            echo json_encode(array(array('id' => 0, 'label' => lang('no_match_found'), 'value' => $term)));
        }
    }

    function stock(){
        if(null == $this->uri->segment(3)){
            $this->session->set_flashdata('error','Van_ID Not Selected');
            redirect('van');
        }else{
            $warehouse_id = $this->uri->segment(3);
        }

        $this->data['warehouses'] = NULL;
            $this->data['warehouse_id'] = $warehouse_id;
            $this->data['warehouse'] = $warehouse_id ? $this->van_model->getVanByID($warehouse_id) : NULL;
            //print_r( $this->data['warehouse']);exit;


        $this->data['error'] = (validation_errors()) ? validation_errors() : $this->session->flashdata('error');

        $bc = array(array('link' => base_url(), 'page' => lang('home')), array('link' => '#', 'page' => lang('van_transfers')));
        $meta = array('page_title' => lang('van_transfers'), 'bc' => $bc);
        $this->page_construct('vans/products', $meta, $this->data);

    }

    function getStock(){

        if(null == $this->uri->segment(3)){
            $this->session->set_flashdata('error','Van_ID Not Selected');
            redirect('van');
        }else{
            $warehouse_id = $this->uri->segment(3);
        }

        $detail_link = anchor('products/view/$1', '<i class="fa fa-file-text-o"></i> ' . lang('product_details'));
        //'data-toggle="modal" data-target="#myModal"'
        $delete_link = "<a href='#' class='tip po' title='<b>" . $this->lang->line("delete_product") . "</b>' data-content=\"<p>"
        . lang('r_u_sure') . "</p><a class='btn btn-danger po-delete1' id='a__$1' href='" . site_url('products/delete/$1') . "'>"
        . lang('i_m_sure') . "</a> <button class='btn po-close'>" . lang('no') . "</button>\"  rel='popover'><i class=\"fa fa-trash-o\"></i> "
        . lang('delete_product') . "</a>";
        $single_barcode = anchor_popup('products/single_barcode/$1/'.($warehouse_id ? $warehouse_id : ''), '<i class="fa fa-print"></i> ' . lang('print_barcode'), $this->popup_attributes);
        $single_label = anchor_popup('products/single_label/$1/'.($warehouse_id ? $warehouse_id : ''), '<i class="fa fa-print"></i> ' . lang('print_label'), $this->popup_attributes);
        $action = '<div class="text-center"><div class="btn-group text-left">'
        . '<button type="button" class="btn btn-default btn-xs btn-primary dropdown-toggle" data-toggle="dropdown">'
        . lang('actions') . ' <span class="caret"></span></button>
        <ul class="dropdown-menu pull-right" role="menu">
            <li>' . $detail_link . '</li>
            <li><a href="' . site_url('products/add/$1') . '"><i class="fa fa-plus-square"></i> ' . lang('duplicate_product') . '</a></li>';
            if($this->session->userdata('group_id') == 1){
            $action .= '<li><a href="' . site_url('products/edit/$1') . '"><i class="fa fa-edit"></i> ' . lang('edit_product') . '</a></li>';
            }
            if($warehouse_id) {
                $action .= '<li><a href="' . site_url('products/set_rack/$1/'.$warehouse_id) . '" data-toggle="modal" data-target="#myModal"><i class="fa fa-bars"></i> '
                . lang('set_rack') . '</a></li>';
            }
            $action .= '<li><a href="' . site_url() . 'assets/uploads/$2" data-type="image" data-toggle="lightbox"><i class="fa fa-file-photo-o"></i> '
            . lang('view_image') . '</a></li>
            <li>' . $single_barcode . '</li>
            <li>' . $single_label . '</li>
            <li><a href="' . site_url('products/add_damage/$1/'.($warehouse_id ? $warehouse_id : '')).'" data-toggle="modal" data-target="#myModal"><i class="fa fa-filter"></i> '
                . lang('add_damage_qty') . '</a></li>    
                <li class="divider"></li>
                <li>' . $delete_link . '</li>
            </ul>
        </div></div>';
        $this->load->library('datatables');
        if($warehouse_id) {
            $this->datatables
            ->select($this->db->dbprefix('van_products').".product_id as productid, ".$this->db->dbprefix('products').".image as image, ".$this->db->dbprefix('products').".code as code, ".$this->db->dbprefix('products').".name as name, ".$this->db->dbprefix('categories').".name as cname, cost as cost, price as price, ".$this->db->dbprefix('van_products').".quantity as quantity, unit, ".$this->db->dbprefix('van_products').".rack as rack, alert_quantity", FALSE)
            ->from('van_products')
            ->join('products', 'products.id=van_products.product_id', 'left')
            ->join('categories', 'products.category_id=categories.id', 'left')
            ->where('van_products.van_id', $warehouse_id)
            ->where('van_products.quantity !=', 0)
            ->group_by("van_products.product_id");
            // ->select($this->db->dbprefix('products').".id as productid, ".$this->db->dbprefix('products').".image as image, ".$this->db->dbprefix('products').".code as code, ".$this->db->dbprefix('products').".name as name, ".$this->db->dbprefix('categories').".name as cname, cost as cost, price as price, COALESCE(quantity, 0) as quantity, unit, NULL as rack, alert_quantity", FALSE)
            // ->from('products')
            // ->join('categories', 'products.category_id=categories.id', 'left')
            // ->group_by("products.id");
        } else {
            $this->datatables
            ->select($this->db->dbprefix('products').".id as productid, ".$this->db->dbprefix('products').".image as image, ".$this->db->dbprefix('products').".code as code, ".$this->db->dbprefix('products').".name as name, ".$this->db->dbprefix('categories').".name as cname, cost as cost, price as price, COALESCE(quantity, 0) as quantity, unit, NULL as rack, alert_quantity", FALSE)
            ->from('products')
            ->join('categories', 'products.category_id=categories.id', 'left')
            ->group_by("products.id");
        }
        if(!$this->Owner && !$this->Admin) {
            if(!$this->session->userdata('show_cost')){
                $this->datatables->unset_column("cost");
            }
            if(!$this->session->userdata('show_price')){
                $this->datatables->unset_column("price");
            }
        }
        $this->datatables->add_column("Actions", $action, "productid, image, code, name");
        echo $this->datatables->generate();
    }

    function product_actions() {


    $this->form_validation->set_rules('form_action', lang("form_action"), 'required');

    if($this->form_validation->run() == true) {

        if(!empty($_POST['val'])) {
            if($this->input->post('form_action') == 'delete') {
                foreach ($_POST['val'] as $id) {
                    $this->products_model->deleteProduct($id);
                }
                $this->session->set_flashdata('message', $this->lang->line("products_deleted"));
                redirect($_SERVER["HTTP_REFERER"]);
            }

            if($this->input->post('form_action') == 'labels') {
                $currencies = $this->site->getAllcurrencies();
                $r = 1;
                $html = "";
                $html .= '<table class="table table-bordered table-condensed bartable"><tbody><tr>';
                foreach ($_POST['val'] as $id) {
                    $pr = $this->products_model->getProductByID($id);

                    $html .= '<td class="text-center"><h4>' . $this->Settings->site_name . '</h4>' . $pr->name . '<br>' . $this->product_barcode($pr->code, $pr->barcode_symbology, 30);
                    $html .= '<table class="table table-bordered">';
                    foreach ($currencies as $currency) {
                        $html .= '<tr><td class="text-left">' . $currency->code . '</td><td class="text-right">' . $this->sma->formatMoney($pr->price * $currency->rate) . '</td></tr>';
                    }
                    $html .= '</table>';
                    $html .= '</td>';

                    if($r%4==0){
                        $html .= '</tr><tr>';
                    }
                    $r++;
                }
                if($r < 4) {
                    for($i=$r;$i<=4;$i++) {
                        $html .= '<td></td>';
                    }
                }
                $html .= '</tr></tbody></table>';

                $this->data['r'] = $r;
                $this->data['html'] = $html;

                $this->data['page_title'] = lang("print_labels");
                $this->data['categories'] = $this->site->getAllCategories();
                    //$this->load->view($this->theme . 'products/print_labels', $this->data);
                $bc = array(array('link' => base_url(), 'page' => lang('home')), array('link' => site_url('products'), 'page' => lang('products')), array('link' => '#', 'page' => lang('print_labels')));
                $meta = array('page_title' => lang('print_labels'), 'bc' => $bc);
                $this->page_construct('products/print_labels', $meta, $this->data);
            }
            if($this->input->post('form_action') == 'barcodes') {
                $currencies = $this->site->getAllcurrencies();
                $r = 1;

                $html = "";
                $html .= '<table class="table table-bordered sheettable"><tbody><tr>';
                foreach ($_POST['val'] as $id) {
                    $pr = $this->site->getProductByID($id);
                    if($r != 1) {
                        $rw = (bool) ($r & 1);
                        $html .= $rw ? '</tr><tr>' : '';
                    }
                    $html .= '<td colspan="2" class="text-center"><h3>' . $this->Settings->site_name . '</h3>' . $pr->name . '<br>' . $this->product_barcode($pr->code, $pr->barcode_symbology, 60);
                    $html .= '<table class="table table-bordered">';
                    foreach ($currencies as $currency) {
                        $html .= '<tr><td class="text-left">' . $currency->code . '</td><td class="text-right">' . $this->sma->formatMoney($pr->price * $currency->rate) . '</td></tr>';
                    }
                    $html .= '</table>';
                    $html .= '</td>';
                    $r++;
                }
                if(!(bool) ($r & 1)) {
                    $html .= '<td></td>';
                }
                $html .= '</tr></tbody></table>';

                $this->data['r'] = $r;
                $this->data['html'] = $html;

                $this->data['categories'] = $this->site->getAllCategories();
                $bc = array(array('link' => base_url(), 'page' => lang('home')), array('link' => site_url('products'), 'page' => lang('products')), array('link' => '#', 'page' => lang('print_barcodes')));
                $meta = array('page_title' => lang('print_barcodes'), 'bc' => $bc);
                $this->page_construct('products/print_barcodes', $meta, $this->data);
                    //$this->load->view($this->theme . 'products/print_barcodes', $this->data);
            }
            if($this->input->post('form_action') == 'export_excel' || $this->input->post('form_action') == 'export_pdf') {

                $this->load->library('excel');
                $this->excel->setActiveSheetIndex(0);
                $this->excel->getActiveSheet()->setTitle('Products');
                $this->excel->getActiveSheet()->SetCellValue('A1', lang('product_code'));
                $this->excel->getActiveSheet()->SetCellValue('B1', lang('product_name'));
                $this->excel->getActiveSheet()->SetCellValue('C1', lang('category_code'));
                $this->excel->getActiveSheet()->SetCellValue('D1', lang('unit'));
                $this->excel->getActiveSheet()->SetCellValue('E1', lang('cost'));
                $this->excel->getActiveSheet()->SetCellValue('F1', lang('price'));
                $this->excel->getActiveSheet()->SetCellValue('G1', lang('quantity'));
                $this->excel->getActiveSheet()->SetCellValue('H1', lang('tax_rate'));
                $this->excel->getActiveSheet()->SetCellValue('I1', lang('tax_method'));
                $this->excel->getActiveSheet()->SetCellValue('J1', lang('subcategory_code'));
                // $this->excel->getActiveSheet()->SetCellValue('K1', lang('product_variants'));
                // $this->excel->getActiveSheet()->SetCellValue('L1', lang('pcf1'));
                // $this->excel->getActiveSheet()->SetCellValue('M1', lang('pcf2'));
                // $this->excel->getActiveSheet()->SetCellValue('N1', lang('pcf3'));
                // $this->excel->getActiveSheet()->SetCellValue('O1', lang('pcf4'));
                // $this->excel->getActiveSheet()->SetCellValue('P1', lang('pcf5'));
                // $this->excel->getActiveSheet()->SetCellValue('Q1', lang('pcf6'));

                $row = 2;
                foreach ($_POST['val'] as $id) {
                    $product = $this->products_model->getProductDetail($id);
                    $qty = $this->van_model->getProductQuantity2($id,$_POST['van_id']);
                    // echo $_POST['van_id'];
                    // echo $qty['quantity'];exit;
                    //$variants = $this->products_model->getProductOptions2($id);
                    $product_variants = '';
                    // foreach ($variants as $variant) {
                    //     $product_variants .= trim($variant->name).'|';
                    // }
                    $this->excel->getActiveSheet()->SetCellValue('A' . $row, $product->code);
                    $this->excel->getActiveSheet()->SetCellValue('B' . $row, $product->name);
                    $this->excel->getActiveSheet()->SetCellValue('C' . $row, $product->category_code);
                    $this->excel->getActiveSheet()->SetCellValue('D' . $row, $product->unit);
                    $this->excel->getActiveSheet()->SetCellValue('E' . $row, $product->cost);
                    $this->excel->getActiveSheet()->SetCellValue('F' . $row, $product->price);
                    $this->excel->getActiveSheet()->SetCellValue('G' . $row, $qty['quantity']);
                    $this->excel->getActiveSheet()->SetCellValue('H' . $row, $product->tax_rate_code);
                    $this->excel->getActiveSheet()->SetCellValue('I' . $row, $product->tax_method ? lang('exclusive') : lang('inclusive'));
                    $this->excel->getActiveSheet()->SetCellValue('J' . $row, $product->subcategory_code);
                    // $this->excel->getActiveSheet()->SetCellValue('K' . $row, $product_variants);
                    // $this->excel->getActiveSheet()->SetCellValue('L' . $row, $product->cf1);
                    // $this->excel->getActiveSheet()->SetCellValue('M' . $row, $product->cf2);
                    // $this->excel->getActiveSheet()->SetCellValue('N' . $row, $product->cf3);
                    // $this->excel->getActiveSheet()->SetCellValue('O' . $row, $product->cf4);
                    // $this->excel->getActiveSheet()->SetCellValue('P' . $row, $product->cf5);
                    // $this->excel->getActiveSheet()->SetCellValue('Q' . $row, $product->cf6);
                    $row++;
                }

                $this->excel->getActiveSheet()->getColumnDimension('A')->setWidth(20);
                $this->excel->getActiveSheet()->getColumnDimension('B')->setWidth(30);
                $this->excel->getActiveSheet()->getColumnDimension('C')->setWidth(15);
                $this->excel->getDefaultStyle()->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
                $filename = 'van_products_' . date('Y_m_d_H_i_s');
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
            $this->session->set_flashdata('error', $this->lang->line("no_product_selected"));
            redirect($_SERVER["HTTP_REFERER"]);
        }
    } else {
        $this->session->set_flashdata('error', validation_errors());
        redirect($_SERVER["HTTP_REFERER"]);
    }
}


}