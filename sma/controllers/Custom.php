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
                ->where('warehouse_id',$this->session->userdata('warehouse_id'))
                // ->unset_column('warehouses.id')
                ->add_column("Actions", "<center><a href='" . site_url('custom/edit_route/$1') . "' data-toggle='modal' data-target='#myModal' class='tip' title='" . lang("edit_route") . "'><i class=\"fa fa-edit\"></i></a> <a href='#' class='tip po' title='<b>" . lang("delete_route") . "</b>' data-content=\"<p>" . lang('r_u_sure') . "</p><a class='btn btn-danger po-delete' href='" . site_url('custom/delete_route/$1') . "'>" . lang('i_m_sure') . "</a> <button class='btn po-close'>" . lang('no') . "</button>\"  rel='popover'><i class=\"fa fa-trash-o\"></i></a></center>", "id");

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
                ->select("id, outlet_name")
                ->from("outlets")
                // ->join('warehouses','warehouses.id = routes.warehouse_id')
                ->where('warehouse_id',$this->session->userdata('warehouse_id'))
                // ->unset_column('warehouses.id')
                ->add_column("Actions", "<center><a href='" . site_url('custom/edit_outlet/$1') . "' data-toggle='modal' data-target='#myModal' class='tip' title='" . lang("edit_outlet") . "'><i class=\"fa fa-edit\"></i></a> <a href='#' class='tip po' title='<b>" . lang("delete_outlet") . "</b>' data-content=\"<p>" . lang('r_u_sure') . "</p><a class='btn btn-danger po-delete' href='" . site_url('custom/delete_outlet/$1') . "'>" . lang('i_m_sure') . "</a> <button class='btn po-close'>" . lang('no') . "</button>\"  rel='popover'><i class=\"fa fa-trash-o\"></i></a></center>", "id");

        echo $this->datatables->generate();
    }

    function add_outlet() {

        $this->load->helper('security');
        $this->form_validation->set_rules('name', lang("name"), 'required|min_length[3]');
        //$this->form_validation->set_rules('warehouse_id', lang("warehouse"), 'required');

        if($this->form_validation->run() == true && $this->custom_model->addOutlet($this->input->post('name'), $this->session->userdata('warehouse_id'))) {
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
            $this->load->view($this->theme . 'custom/add_outlet', $this->data);
        }
    }

    function edit_outlet($id = NULL) {
        $this->load->helper('security');
        $this->form_validation->set_rules('name', lang("outlet_id"), 'required');

          $data = array('name' => $this->input->post('name'));
       

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
            $this->data['modal_js'] = $this->site->modal_js();
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


}