<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Custom extends MY_Controller {

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
        //$this->lang->load('customers', $this->Settings->language);
        $this->load->library('form_validation'); 
        $this->load->model('custom_model');
        $this->load->model('products_model');
        $this->lang->load('products', $this->Settings->language);
        $this->digital_upload_path = 'files/';
        $this->upload_path = 'assets/uploads/';
        $this->thumbs_path = 'assets/uploads/thumbs/';
        $this->image_types = 'gif|jpg|jpeg|png|tif';
        $this->digital_file_types = 'zip|psd|ai|rar|pdf|doc|docx|xls|xlsx|ppt|pptx|gif|jpg|jpeg|png|tif';
        $this->allowed_file_size = '1024';
        $this->popup_attributes = array('width' => '900','height' => '600','window_name' => 'sma_popup', 'menubar' => 'yes', 'scrollbars' => 'yes','status' => 'no', 'resizable' => 'yes','screenx' => '0','screeny' => '0');
    }

    function routes (){
       
        
        $this->data['error'] = (validation_errors()) ? validation_errors() : $this->session->flashdata('error');

        $bc = array(array('link' => base_url(), 'page' => lang('home')), array('link' => site_url('custom_fields'), 'page' => lang('custom_fields')), array('link' => '#','page' => lang('route_id')));
        $meta = array('page_title' => lang('route_id'), 'bc' => $bc);
        $this->page_construct('custom/routes', $meta, $this->data);
    }

    function getRoutes (){
         $this->load->library('datatables');
        $this->datatables
                ->select("id, route_name")
                ->from("routes")
                // ->join('warehouses','warehouses.id = routes.warehouse_id')
                
                // ->unset_column('warehouses.id')
                ->add_column("Actions", "<center><a href='" . site_url('custom/edit_route/$1') . "' data-toggle='modal' data-target='#myModal' class='tip' title='" . lang("edit_route") . "'><i class=\"fa fa-edit\"></i></a> <a href='#' class='tip po' title='<b>" . lang("delete_route") . "</b>' data-content=\"<p>" . lang('r_u_sure') . "</p><a class='btn btn-danger po-delete' href='" . site_url('custom/delete_route/$1') . "'>" . lang('i_m_sure') . "</a> <button class='btn po-close'>" . lang('no') . "</button>\"  rel='popover'><i class=\"fa fa-trash-o\"></i></a></center>", "id");

                if(NULL != $this->session->userdata('warehouse_id')){
                    $this->db->where('warehouse_id',$this->session->userdata('warehouse_id'));
                }

        echo $this->datatables->generate();
    }

    function add_route() {

        $this->load->helper('security');
        $this->form_validation->set_rules('name', lang("name"), 'required|min_length[3]');
        //$this->form_validation->set_rules('warehouse_id', lang("warehouse"), 'required');

        if($this->form_validation->run() == true && $this->custom_model->addRoute($this->input->post('name'), $this->session->userdata('warehouse_id'))) {
            $this->session->set_flashdata('message', lang("route_added"));
            redirect("custom/routes");
        } else {
            $this->data['error'] = validation_errors() ? validation_errors() : $this->session->flashdata('error');

            $this->data['name'] = array('name' => 'name',
                'id' => 'name',
                'type' => 'text',
                'class' => 'form-control',
                'required' => 'required',
                'value' => $this->form_validation->set_value('name'),
            );
           
            $this->data['modal_js'] = $this->site->modal_js();
            $this->load->view($this->theme . 'custom/add_route', $this->data);
        }
    }

    function edit_route($id = NULL) {
        $this->load->helper('security');
        $this->form_validation->set_rules('name', lang("route_id"), 'required');

          $data = array('name' => $this->input->post('name'));
       

        if($this->form_validation->run() == true && $this->custom_model->updateRoute($id, $data)) {
            $this->session->set_flashdata('message', lang("route_updated"));
            redirect("custom/routes");
        } else {
            $this->data['error'] = validation_errors() ? validation_errors() : $this->session->flashdata('error');
            $category = $this->custom_model->getRouteByID($id);
            //echo $category->name;exit;
            $this->data['name'] = array('name' => 'name',
                'id' => 'name',
                'type' => 'text',
                'class' => 'form-control',
                'required' => 'required',
                'value' => $this->form_validation->set_value('name', $category->route_name),
            );
            $this->data['modal_js'] = $this->site->modal_js();
            $this->data['id'] = $id;
            $this->load->view($this->theme . 'custom/edit_route', $this->data);
        }
    }

        function delete_route($id = NULL) {

        if($this->custom_model->check($id)) {
            $this->session->set_flashdata('error', lang("route_error"));
            redirect("custom/routes", 'refresh');
        }

        if($this->custom_model->deleteRoute($id)) {
            echo lang("route_deleted");
        }
    }

    function route_actions() {

        $this->form_validation->set_rules('form_action', lang("form_action"), 'required');

        if($this->form_validation->run() == true) {

            if(!empty($_POST['val'])) {
                if($this->input->post('form_action') == 'delete') {
                    foreach ($_POST['val'] as $id) {
                        $this->custom_model->deleteRoute($id);
                    }
                    $this->session->set_flashdata('message', lang("routes_deleted"));
                    redirect($_SERVER["HTTP_REFERER"]);
                }

                if($this->input->post('form_action') == 'export_excel' || $this->input->post('form_action') == 'export_pdf') {

                    $this->load->library('excel');
                    $this->excel->setActiveSheetIndex(0);
                    $this->excel->getActiveSheet()->setTitle(lang('Route ID'));
                    $this->excel->getActiveSheet()->SetCellValue('A1', lang('id'));
                    $this->excel->getActiveSheet()->SetCellValue('B1', lang('route_name'));

                    $row = 2;
                    foreach ($_POST['val'] as $id) {
                        $sc = $this->custom_model->getRouteByID($id);
                        $this->excel->getActiveSheet()->SetCellValue('A' . $row, $sc->id);
                        $this->excel->getActiveSheet()->SetCellValue('B' . $row, $sc->route_name);
                        $row++;
                    }

                    $this->excel->getActiveSheet()->getColumnDimension('B')->setWidth(20);
                    $this->excel->getDefaultStyle()->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
                    $filename = 'routes_' . date('Y_m_d_H_i_s');
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
                $this->session->set_flashdata('error', lang("no_tax_rate_selected"));
                redirect($_SERVER["HTTP_REFERER"]);
            }
        } else {
            $this->session->set_flashdata('error', validation_errors());
            redirect($_SERVER["HTTP_REFERER"]);
        }
    }



function outlets (){
        $this->data['error'] = (validation_errors()) ? validation_errors() : $this->session->flashdata('error');

        $bc = array(array('link' => base_url(), 'page' => lang('home')), array('link' => site_url('custom_fields'), 'page' => lang('custom_fields')), array('link' => '#','page' => lang('outlet_id')));
        $meta = array('page_title' => lang('outlet_id'), 'bc' => $bc);
        $this->page_construct('custom/outlets', $meta, $this->data);
    }

    function getOutlets (){
         $this->load->library('datatables');
        $this->datatables
                ->select("sma_outlets.id, outlet_name",false)
                ->from("sma_outlets")
                //->join('sma_routes','sma_routes.id=sma_outlets.route_id')
                // ->join('warehouses','warehouses.id = routes.warehouse_id')
                 ->unset_column('warehouse_id')
                ->add_column("Actions", "<center><a href='" . site_url('custom/edit_outlet/$1') . "' data-toggle='modal' data-target='#myModal' class='tip' title='" . lang("edit_outlet") . "'><i class=\"fa fa-edit\"></i></a> <a href='#' class='tip po' title='<b>" . lang("delete_outlet") . "</b>' data-content=\"<p>" . lang('r_u_sure') . "</p><a class='btn btn-danger po-delete' href='" . site_url('custom/delete_outlet/$1') . "'>" . lang('i_m_sure') . "</a> <button class='btn po-close'>" . lang('no') . "</button>\"  rel='popover'><i class=\"fa fa-trash-o\"></i></a></center>", "sma_outlets.id");

                if(null != $this->session->userdata('warehouse_id')){
                   // $this->db->where('warehouse_id',$this->session->userdata('warehouse_id'));
                }

        echo $this->datatables->generate();
    }

    function add_outlet() {

        $this->load->helper('security');
        $this->form_validation->set_rules('name', lang("name"), 'required|min_length[3]');
        //$this->form_validation->set_rules('warehouse_id', lang("warehouse"), 'required');

        if($this->form_validation->run() == true && $this->custom_model->addOutlet($this->input->post('name'), $this->session->userdata('warehouse_id'),$this->input->post('route_id'))) {
            $this->session->set_flashdata('message', lang("outlet_added"));
            redirect("custom/outlets");
        } else {
            $this->data['error'] = validation_errors() ? validation_errors() : $this->session->flashdata('error');

            $this->data['name'] = array('name' => 'name',
                'id' => 'name',
                'type' => 'text',
                'class' => 'form-control',
                'required' => 'required',
                'value' => $this->form_validation->set_value('name'),
            );
           
            $this->data['modal_js'] = $this->site->modal_js();
            $this->data['routes'] = $this->custom_model->getRoutesDrop2();
            $this->load->view($this->theme . 'custom/add_outlet', $this->data);
        }
    }

    function edit_outlet($id = NULL) {
        $this->load->helper('security');
        $this->form_validation->set_rules('name', lang("outlet_id"), 'required');

          $data = array('name' => $this->input->post('name'),
            'route_id' => $this->input->post('route_id'));
       

        if($this->form_validation->run() == true && $this->custom_model->updateOutlet($id, $data)) {
            $this->session->set_flashdata('message', lang("outlet_updated"));
            redirect("custom/outlets");
        } else {
            $this->data['error'] = validation_errors() ? validation_errors() : $this->session->flashdata('error');
            $category = $this->custom_model->getOutletByID($id);
            //echo $category->name;exit;
            $this->data['name'] = array('name' => 'name',
                'id' => 'name',
                'type' => 'text',
                'class' => 'form-control',
                'required' => 'required',
                'value' => $this->form_validation->set_value('name', $category->outlet_name),
            );
            $this->data['route_id'] = $category->route_id;
            $this->data['modal_js'] = $this->site->modal_js();
            $this->data['routes'] = $this->custom_model->getRoutesDrop2();
            //print_r($this->data['routes']);exit;
            $this->data['id'] = $id;
            $this->load->view($this->theme . 'custom/edit_outlet', $this->data);
        }
    }

        function delete_outlet($id = NULL) {

        if($this->custom_model->check2($id)) {
            $this->session->set_flashdata('error', lang("outlet_error"));
            redirect("custom/outlets", 'refresh');
        }

        if($this->custom_model->deleteRoute($id)) {
            echo lang("outlet_deleted");
        }
    }

    function outlet_actions() {

        $this->form_validation->set_rules('form_action', lang("form_action"), 'required');

        if($this->form_validation->run() == true) {

            if(!empty($_POST['val'])) {
                if($this->input->post('form_action') == 'delete') {
                    foreach ($_POST['val'] as $id) {
                        $this->custom_model->deleteOutlet($id);
                    }
                    $this->session->set_flashdata('message', lang("outlets_deleted"));
                    redirect($_SERVER["HTTP_REFERER"]);
                }

                if($this->input->post('form_action') == 'export_excel' || $this->input->post('form_action') == 'export_pdf') {

                    $this->load->library('excel');
                    $this->excel->setActiveSheetIndex(0);
                    $this->excel->getActiveSheet()->setTitle(lang('Outlet ID'));
                    $this->excel->getActiveSheet()->SetCellValue('A1', lang('id'));
                    $this->excel->getActiveSheet()->SetCellValue('B1', lang('outlet_name'));

                    $row = 2;
                    foreach ($_POST['val'] as $id) {
                        $sc = $this->custom_model->getRouteByID($id);
                        $this->excel->getActiveSheet()->SetCellValue('A' . $row, $sc->id);
                        $this->excel->getActiveSheet()->SetCellValue('B' . $row, $sc->route_name);
                        $row++;
                    }

                    $this->excel->getActiveSheet()->getColumnDimension('B')->setWidth(20);
                    $this->excel->getDefaultStyle()->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
                    $filename = 'routes_' . date('Y_m_d_H_i_s');
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
                $this->session->set_flashdata('error', 'NO Outlet Selected!!');
                redirect($_SERVER["HTTP_REFERER"]);
            }
        } else {
            $this->session->set_flashdata('error', validation_errors());
            redirect($_SERVER["HTTP_REFERER"]);
        }
    }

    function conversions(){
        $this->data['error'] = (validation_errors()) ? validation_errors() : $this->session->flashdata('error');

        $bc = array(array('link' => base_url(), 'page' => lang('home')), array('link' => site_url('custom_fields'), 'page' => lang('custom_fields')), array('link' => '#','page' => lang('conversions')));
        $meta = array('page_title' => lang('conversions'), 'bc' => $bc);
        $this->page_construct('custom/conversions', $meta, $this->data);
    }

    function getConversions(){
        $this->load->library('datatables');
        $this->datatables->select('id,from,to,factor')
        ->from('conversions')
         ->add_column("Actions", "<center><a href='" . site_url('custom/edit_conversion/$1') . "' data-toggle='modal' data-target='#myModal' class='tip' title='" . lang("edit_conversion") . "'><i class=\"fa fa-edit\"></i></a> <a href='#' class='tip po' title='<b>" . lang("delete_route") . "</b>' data-content=\"<p>" . lang('r_u_sure') . "</p><a class='btn btn-danger po-delete' href='" . site_url('custom/delete_conversion/$1') . "'>" . lang('i_m_sure') . "</a> <button class='btn po-close'>" . lang('no') . "</button>\"  rel='popover'><i class=\"fa fa-trash-o\"></i></a></center>", "id");
        echo $this->datatables->generate();
    }

    function add_conversion(){
               // $this->load->helper('security');
        $this->form_validation->set_rules('from', lang("from"), 'required');
        $this->form_validation->set_rules('to', lang("to"), 'required');
        $this->form_validation->set_rules('factor', lang("factor"), 'required');
        //$this->form_validation->set_rules('warehouse_id', lang("warehouse"), 'required');

        if($this->form_validation->run() == true){
            $data = array(
                'from' => $this->input->post('from'),
                'to' => $this->input->post('to'),
                'factor' => $this->input->post('factor'));
            //print_r($data);exit;
        }

        if($this->form_validation->run() == true && $this->custom_model->addConversion($data)) {
            $this->session->set_flashdata('message', lang("conversion_added"));
            redirect("custom/conversions");
        } else {
            $this->data['error'] = validation_errors() ? validation_errors() : $this->session->flashdata('error');

            $this->data['from'] = array('name' => 'from',
                'id' => 'from',
                'type' => 'text',
                'class' => 'form-control',
                'required' => 'required',
                'value' => $this->form_validation->set_value('from'),
            );

            $this->data['to'] = array('name' => 'to',
                'id' => 'to',
                'type' => 'text',
                'class' => 'form-control',
                'required' => 'required',
                'value' => $this->form_validation->set_value('to'),
            );

            $this->data['factor'] = array('name' => 'factor',
                'id' => 'factor',
                'type' => 'text',
                'class' => 'form-control',
                'required' => 'required',
                'value' => $this->form_validation->set_value('factor'),
            );
           
            $this->data['modal_js'] = $this->site->modal_js();
            $this->load->view($this->theme . 'custom/add_conversion', $this->data);

        }
    }

    function edit_conversion($id = NULL) {
        $this->load->helper('security');
        $this->form_validation->set_rules('from', lang("from"), 'required');

          if($this->form_validation->run() == true){
          $data = array('from' => $this->input->post('from'),
            'to' => $this->input->post('to'),
            'factor' => $this->input->post('factor'));
          }
       

        if($this->form_validation->run() == true && $this->custom_model->updateConversion($id, $data)) {
            $this->session->set_flashdata('message', lang("conversion_updated"));
            redirect("custom/conversions");
        } else {
            $this->data['error'] = validation_errors() ? validation_errors() : $this->session->flashdata('error');
            $conversion = $this->custom_model->getConversionByID($id);
            //echo $category->name;exit;
            $this->data['from'] = array('name' => 'from',
                'id' => 'from',
                'type' => 'text',
                'class' => 'form-control',
                'required' => 'required',
                'value' => $this->form_validation->set_value('from', $conversion->from),
            );

            $this->data['to'] = array('name' => 'to',
                'id' => 'to',
                'type' => 'text',
                'class' => 'form-control',
                'required' => 'required',
                'value' => $this->form_validation->set_value('to',$conversion->to),
            );

            $this->data['factor'] = array('name' => 'factor',
                'id' => 'factor',
                'type' => 'text',
                'class' => 'form-control',
                'required' => 'required',
                'value' => $this->form_validation->set_value('factor',$conversion->factor),
            );
            $this->data['modal_js'] = $this->site->modal_js();
            $this->data['id'] = $id;
            $this->load->view($this->theme . 'custom/edit_conversion', $this->data);
        }
    }

        function delete_conversion($id = NULL) {

        if($this->custom_model->deleteConversion($id)) {
            echo lang("conversion_deleted");
        }
    }

    function product_conversions (){

    }

    function import_csv() {
    $this->sma->checkPermissions('csv');
    $this->load->helper('security');
    $this->form_validation->set_rules('userfile', lang("upload_file"), 'xss_clean');

    if($this->form_validation->run() == true) {

        if(DEMO) {
            $this->session->set_flashdata('message', lang("disabled_in_demo"));
            redirect('welcome');
        }

        if(isset($_FILES["userfile"])) {

            $this->load->library('upload');

            $config['upload_path'] = $this->digital_upload_path;
            $config['allowed_types'] = 'csv';
            $config['max_size'] = $this->allowed_file_size;
            $config['overwrite'] = TRUE;

            $this->upload->initialize($config);

            if(!$this->upload->do_upload()) {

                $error = $this->upload->display_errors();
                $this->session->set_flashdata('error', $error);
                redirect("custom/import_csv");
            }

            $csv = $this->upload->file_name;

            $arrResult = array();
            $handle = fopen($this->digital_upload_path. $csv, "r");
            if($handle) {
                while (($row = fgetcsv($handle, 1000, ",")) !== FALSE) {
                    $arrResult[] = $row;
                }
                fclose($handle);
            }
            $titles = array_shift($arrResult);

            $keys = array('product_code', 'conversion_id');

            $final = array();

            foreach ($arrResult as $key => $value) {
                $final[] = array_combine($keys, $value);
            }
            $rw = 2;
            foreach ($final as $csv_pr) {
                if(!$this->products_model->getProductByCode(trim($csv_pr['product_code']))) {
                    $this->session->set_flashdata('error', lang("check_product_code") . " (" . $csv_pr['code'] . "). " . lang("code_x_exist") . " " . lang("line_no") . " " . $rw);
                    redirect("custom/import_csv");
                }
                $rw++;
            }
        }

        //print_r($final);exit;

    }

    if($this->form_validation->run() == true && isset($_POST['import'])) {
        //print_r($final);exit;
        $result = $this->custom_model->addProductConversions($final);
        if($result){
        $this->session->set_flashdata('message', lang("conversion_added"));
        redirect('custom/import_csv');
    }else{
        $this->session->set_flashdata('error', "Conversion Not Added");
        redirect('custom/import_csv');
    }
    } else {

        $this->data['error'] = (validation_errors() ? validation_errors() : $this->session->flashdata('error'));

        $this->data['userfile'] = array('name' => 'userfile',
                                        'id' => 'userfile',
                                        'type' => 'text',
                                        'value' => $this->form_validation->set_value('userfile')
                                        );

        $bc = array(array('link' => base_url(), 'page' => lang('home')), array('link' => site_url('custom'), 'page' => lang('custom')), array('link' => '#', 'page' => lang('import_csv')));
        $meta = array('page_title' => lang('update_price_csv'), 'bc' => $bc);
        $this->page_construct('custom/import', $meta, $this->data);

    }
}

function types (){
        // $id = $this->session->userdata('user_id');
        // $this->data['userdata'] = $this->User_model->getUserData($id);

        
        $this->data['error'] = (validation_errors()) ? validation_errors() : $this->session->flashdata('error');

        $bc = array(array('link' => base_url(), 'page' => lang('home')), array('link' => site_url('custom_fields'), 'page' => lang('custom_fields')), array('link' => '#','page' => lang('type')));
        $meta = array('page_title' => lang('route_id'), 'bc' => $bc);
        $this->page_construct('custom/types', $meta, $this->data);
    }

    function getTypes (){
         $this->load->library('datatables');
        $this->datatables
                ->select("id, name")
                ->from("types")
                // ->join('warehouses','warehouses.id = routes.warehouse_id')
                // ->unset_column('warehouses.id')
                ->add_column("Actions", "<center><a href='" . site_url('custom/edit_type/$1') . "' data-toggle='modal' data-target='#myModal' class='tip' title='" . lang("edit_type") . "'><i class=\"fa fa-edit\"></i></a> <a href='#' class='tip po' title='<b>" . lang("delete_type") . "</b>' data-content=\"<p>" . lang('r_u_sure') . "</p><a class='btn btn-danger po-delete' href='" . site_url('custom/delete_type/$1') . "'>" . lang('i_m_sure') . "</a> <button class='btn po-close'>" . lang('no') . "</button>\"  rel='popover'><i class=\"fa fa-trash-o\"></i></a></center>", "id");


        echo $this->datatables->generate();
    }

    function add_type() {

        $this->load->helper('security');
        $this->form_validation->set_rules('name', lang("type"), 'required|min_length[3]');
        //$this->form_validation->set_rules('warehouse_id', lang("warehouse"), 'required');

        if($this->form_validation->run() == true && $this->custom_model->addType($this->input->post('name'))) {
            $this->session->set_flashdata('message', lang("type_added"));
            redirect("custom/types");
        } else {
            $this->data['error'] = validation_errors() ? validation_errors() : $this->session->flashdata('error');

            $this->data['name'] = array('name' => 'name',
                'id' => 'name',
                'type' => 'text',
                'class' => 'form-control',
                'required' => 'required',
                'value' => $this->form_validation->set_value('name'),
            );
           
            $this->data['modal_js'] = $this->site->modal_js();
            $this->load->view($this->theme . 'custom/add_type', $this->data);
        }
    }

    function edit_type($id = NULL) {
        $this->load->helper('security');
        $this->form_validation->set_rules('name', lang("type"), 'required');

          $data = array('name' => $this->input->post('name'));
       

        if($this->form_validation->run() == true && $this->custom_model->updateType($id, $data)) {
            $this->session->set_flashdata('message', lang("type_updated"));
            redirect("custom/types");
        } else {
            $this->data['error'] = validation_errors() ? validation_errors() : $this->session->flashdata('error');
            $category = $this->custom_model->getTypeByID($id);
            //echo $category->name;exit;
            $this->data['name'] = array('name' => 'name',
                'id' => 'name',
                'type' => 'text',
                'class' => 'form-control',
                'required' => 'required',
                'value' => $this->form_validation->set_value('name', $category->name),
            );
            $this->data['modal_js'] = $this->site->modal_js();
            $this->data['id'] = $id;
            $this->load->view($this->theme . 'custom/edit_type', $this->data);
        }
    }

        function delete_type($id = NULL) {

        if($this->custom_model->check4($id)) {
            $this->session->set_flashdata('error', lang("route_error"));
            redirect("custom/types", 'refresh');
        }

        if($this->custom_model->deleteType($id)) {
            echo lang("type_deleted");
        }
    }

    function type_actions() {

        $this->form_validation->set_rules('form_action', lang("form_action"), 'required');

        if($this->form_validation->run() == true) {

            if(!empty($_POST['val'])) {
                if($this->input->post('form_action') == 'delete') {
                    foreach ($_POST['val'] as $id) {
                        $this->custom_model->deleteType($id);
                    }
                    $this->session->set_flashdata('message', lang("types_deleted"));
                    redirect($_SERVER["HTTP_REFERER"]);
                }

                if($this->input->post('form_action') == 'export_excel' || $this->input->post('form_action') == 'export_pdf') {

                    $this->load->library('excel');
                    $this->excel->setActiveSheetIndex(0);
                    $this->excel->getActiveSheet()->setTitle(lang('Route ID'));
                    $this->excel->getActiveSheet()->SetCellValue('A1', lang('id'));
                    $this->excel->getActiveSheet()->SetCellValue('B1', lang('type'));

                    $row = 2;
                    foreach ($_POST['val'] as $id) {
                        $sc = $this->custom_model->getTypeByID($id);
                        $this->excel->getActiveSheet()->SetCellValue('A' . $row, $sc->id);
                        $this->excel->getActiveSheet()->SetCellValue('B' . $row, $sc->name);
                        $row++;
                    }

                    $this->excel->getActiveSheet()->getColumnDimension('B')->setWidth(20);
                    $this->excel->getDefaultStyle()->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
                    $filename = 'types_' . date('Y_m_d_H_i_s');
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
                $this->session->set_flashdata('error', lang("no_tax_rate_selected"));
                redirect($_SERVER["HTTP_REFERER"]);
            }
        } else {
            $this->session->set_flashdata('error', validation_errors());
            redirect($_SERVER["HTTP_REFERER"]);
        }
    }


}