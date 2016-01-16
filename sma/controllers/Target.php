<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Target extends MY_Controller {

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
        $this->load->model('auth_model');
        $this->load->model('target_model');
    }

    function index(){
        $this->data['error'] = (validation_errors()) ? validation_errors() : $this->session->flashdata('error');    
        $bc = array(array('link' => base_url(), 'page' => lang('home')), array('link' => site_url('target'), 'page' => lang('target_management')), array('link' => '#', 'page' => lang('list_targets')));
        $meta = array('page_title' => lang('target_management'), 'bc' => $bc);
        $this->page_construct('target/users', $meta, $this->data);
    }

    function getUsers() {
        $this->load->library('datatables');
        $this->datatables
        ->select("users.id as id, first_name, last_name, email,groups.name")
        ->from("users")
        ->join('groups', 'users.group_id=groups.id', 'left')
        ->group_by('users.id')
        ->where('company_id', NULL)
        ->where('group_id',5);
        if(!$this->Owner) { $this->datatables->where('group_id !=', 1); }
        if($this->Admin) { $this->datatables->where('warehouse_id', $this->session->userdata('warehouse_id')); }
        $this->datatables
        ->edit_column('active', '$1__$2', 'active, id')
        ->add_column("Actions", "<div class='text-center'><a class=\"tip\" title='" . lang("view_report") . "' href='" . site_url('target/staff_target/$1') . "'><span class='label label-primary'>" . lang("view_report") . "</span></a></div>", "id")
        ->unset_column('id');
        echo $this->datatables->generate();
    }

    public function add(){

    $this->data['title'] = "Target Management";

    $this->form_validation->set_rules('user' ,'Full Name', 'callback_combo_check');
    $this->form_validation->set_rules('category[]' ,'Category Name', 'callback_combo_check');
    $this->form_validation->set_rules('target[]' ,'Category Target', 'required');
    $this->form_validation->set_rules('date' ,'Target Date', 'required');
    
    if($this->form_validation->run() == true && $this->target_model->setTarget()) {

        // echo ($_POST['target'][0]);
        //     echo ($_POST['category'][0]);
        
    // }
    // if($this->form_validation->run() == true && $this->ion_auth->register($username, $password, $email, $additional_data, $active, $notify)) {
    //     $id = $this->db->insert_id();
    //     $data['target'] = $this->input->post('target');
    //     $data['t_date'] = date("Y-m-d h:i:s");
    //     $data['u_id'] = $id;

    //     $this->ion_auth->setTarget($data);
        $this->session->set_flashdata('message',lang('target_set'));
        redirect("target/add");
    } else {


        $this->data['agents'] = $this->auth_model->get_agents();
        $this->data['categories'] = $this->auth_model->get_categories();

        $bc = array(array('link' => site_url('home'), 'page' => lang('home')), array('link' => site_url('target'), 'page' => lang('target_management')), array('link' => '#', 'page' => lang('set_target')));
        $meta = array('page_title' => lang('set_target'), 'bc' => $bc);

        $this->session->set_flashdata('error', validation_errors());
        $this->page_construct('target/add', $meta, $this->data);
    }

}

function getcat(){
    $categories = $this->auth_model->get_categories();
    echo '<div class="form-group multi-field" id="it">'; 
    echo lang('category','category');
   echo '<div class="controls">';
    echo form_dropdown('category[]',$categories,set_value('category[]'),'class="form-control" required="required" ');
    echo ' </div>

    <div class="form-groujp">
                                            '.lang('target', 'target').'
                                            <div class="controls">
                                                <input type="number" id="target[]" name="target[]" class="form-control" required="required" pattern=".{4,20}"/>
                                            </div>
                                        </div>
    <button type="button" class="btn btn-sm btn-danger remove" id="remove">Remove</button>
    </div> ';
    ?>
    <script type="text/javascript">
            $(".remove").click(function(e){
                    $(".remove").parent().remove();
            });
</script>
<?php 

}

function combo_check($str)
{
    if ($str == '-SELECT-')
    {
        $this->form_validation->set_message('combo_check', 'Valid %s is required');
        return FALSE;
    }
    else
    {
        return TRUE;
    }
}

    function in_array_r($needle, $haystack, $strict = false) {
    foreach ($haystack as $item) {
        if (($strict ? $item === $needle : $item == $needle) || (is_array($item) && in_array_r($needle, $item, $strict))) {
            return true;
        }
    }

    return false;
}

function staff_target(){
    $this->data['error'] = (validation_errors()) ? validation_errors() : $this->session->flashdata('error'); 
   

$contents = $this->get_stuff();

$this->data['contents'] = $contents;

        $bc = array(array('link' => base_url(), 'page' => lang('home')), array('link' => site_url('target'), 'page' => lang('target_management')), array('link' => '#', 'page' => lang('list_targets')));
        $meta = array('page_title' => lang('staff_target'), 'bc' => $bc);
        $this->page_construct('target/targets', $meta, $this->data);
}

    function getUserTargets() {
        $id = 0;
        if(null !== $this->uri->segment(3)){
            $id = $this->uri->segment(3);
        }
        else{
            $this->session->set_flashdata('error',"No User ID Selected!");
            redirect($_SERVER['HTTP_REFERER']);
        }
        $this->load->library('datatables');
        $this->datatables
        ->select("concat(first_name,' ',last_name) as full_name,name, target ,DATE_FORMAT( date, '%c' ) as month",false)
        ->from("category_targets")
        ->join('users', 'users.id = category_targets.u_id')
        ->join('categories', 'categories.id = category_targets.category_id')
        ->where('users.id',$id);
        
        if($this->Admin) { $this->datatables->where('warehouse_id', $this->session->userdata('warehouse_id')); }
        $this->datatables->unset_column('id');
        echo $this->datatables->generate();
    }

    function getMonths($no = 0){
    $month = date('m');
    $year = date('Y');

    $x = $month + $no;
    if($x > 12){
        $x = $x - 12;
        $year++;
    }
    $data = mktime(0, 0, 0, $x, 1, $year);
    return $data;
}

    function get_stuff (){
        $data = array();
         $contents = array();
         $i = 0;

         $targets = $this->target_model->getUserTargets();

          $this->data['targets'] =  $targets;

         if(!$targets){
           $this->session->set_flashdata('error',"This User Has No Set Targets");  
           redirect('target');
         }

        foreach ($targets as  $target ) {
            $name = $target->name;
            if(!in_array( $name,$data)){
                $data[$i] = $name;
                 $i++;
            }
             
        }


        for ($j = 0; $j < count($data); $j++){
            $contents[$j][0] = $data[$j];
        }

        

        
foreach ($targets as $target){
    for ($i = 0; $i < count($contents); $i++){
        for ($j = 0; $j <12; $j++){
            if($contents[$i][0] == $target->name){
                if($target->month == date('n',$this->getMonths($j)) && date('Y',$this->getMonths($j)) == date('Y',strtotime($target->date))){
                    $contents[$i][$j+1] = $target->target;
                } else{
                    if(!isset($contents[$i][$j+1])){
                        $contents[$i][$j+1] = 0;
                    }
                }
            }
        }
    }
}

$totals = array();
$totals[0] = 'Total';
for($i = 1; $i <= 12; $i++){
    $sum = 0;
    for ($j = 0; $j < count($contents); $j++){
        $sum = $sum + $contents[$j][$i];
    }
    
    array_push($totals,$sum);
}
array_push($contents,$totals);
 return $contents;

    }

    //export

    function export ($id = NULL,$pdf = NULL, $xls = NULL){
        $data = $this->get_stuff();
//print_r($data);exit;

        if(isset($_GET['s'])){
            $data = $this->get_otherstuff();
        }

        if($pdf || $xls) {
            //echo "good";exit;

            if(!empty($data)) {

                $this->load->library('excel');
                $this->excel->setActiveSheetIndex(0);
                $this->excel->getActiveSheet()->setTitle(lang('sales_report'));
                $this->excel->getActiveSheet()->SetCellValue('A1', lang('full_name'));
                $this->excel->getActiveSheet()->SetCellValue('B1', lang('category'));
                $this->excel->getActiveSheet()->SetCellValue('C1', date('M-Y',$this->getMonths()));
                $this->excel->getActiveSheet()->SetCellValue('D1', date('M-Y',$this->getMonths(1)));
                $this->excel->getActiveSheet()->SetCellValue('E1', date('M-Y',$this->getMonths(2)));
                $this->excel->getActiveSheet()->SetCellValue('F1', date('M-Y',$this->getMonths(3)));
                $this->excel->getActiveSheet()->SetCellValue('G1', date('M-Y',$this->getMonths(4)));
                $this->excel->getActiveSheet()->SetCellValue('H1', date('M-Y',$this->getMonths(5)));
                $this->excel->getActiveSheet()->SetCellValue('I1', date('M-Y',$this->getMonths(6)));
                $this->excel->getActiveSheet()->SetCellValue('J1', date('M-Y',$this->getMonths(7)));
                $this->excel->getActiveSheet()->SetCellValue('K1', date('M-Y',$this->getMonths(8)));
                $this->excel->getActiveSheet()->SetCellValue('L1', date('M-Y',$this->getMonths(9)));
                $this->excel->getActiveSheet()->SetCellValue('M1', date('M-Y',$this->getMonths(10)));
                $this->excel->getActiveSheet()->SetCellValue('N1', date('M-Y',$this->getMonths(11)));





                $row = 2;
                $total = 0; $paid = 0; $balance = 0;
                $target = $this->data['targets'];

                for($i = 0; $i < count($data)-1; $i++){
                    $this->excel->getActiveSheet()->SetCellValue('A' . $row, $target[0]->full_name);
                    $this->excel->getActiveSheet()->SetCellValue('B' . $row, $data[$i][0]);
                    $this->excel->getActiveSheet()->SetCellValue('C' . $row, ($data[$i][1]));
                    $this->excel->getActiveSheet()->SetCellValue('D' . $row, number_format($data[$i][2],2));
                    $this->excel->getActiveSheet()->SetCellValue('E' . $row, number_format($data[$i][3],2));
                    $this->excel->getActiveSheet()->SetCellValue('F' . $row, number_format($data[$i][4],2));
                    $this->excel->getActiveSheet()->SetCellValue('G' . $row, number_format($data[$i][5],2));
                    $this->excel->getActiveSheet()->SetCellValue('H' . $row, number_format($data[$i][6],2));
                    $this->excel->getActiveSheet()->SetCellValue('I' . $row, number_format($data[$i][7],2));
                    $this->excel->getActiveSheet()->SetCellValue('J' . $row, number_format($data[$i][8],2));
                    $this->excel->getActiveSheet()->SetCellValue('K' . $row, number_format($data[$i][9],2));
                    $this->excel->getActiveSheet()->SetCellValue('L' . $row, number_format($data[$i][10],2));
                    $this->excel->getActiveSheet()->SetCellValue('M' . $row, number_format($data[$i][11],2));
                    $this->excel->getActiveSheet()->SetCellValue('N' . $row, number_format($data[$i][12],2));
                    $row++;
                    $j = $i;
                }
                $j = count($data)-1;

                $this->excel->getActiveSheet()->getStyle("C".$row.":N".$row)->getBorders()
                ->getTop()->setBorderStyle(PHPExcel_Style_Border::BORDER_MEDIUM);
                    $this->excel->getActiveSheet()->SetCellValue('A' . $row, $target[0]->full_name.' TOTAL');
                    $this->excel->getActiveSheet()->SetCellValue('C' . $row, $data[$j][1]);
                    $this->excel->getActiveSheet()->SetCellValue('D' . $row, number_format($data[$j][2],2));
                    $this->excel->getActiveSheet()->SetCellValue('E' . $row, number_format($data[$j][3],2));
                    $this->excel->getActiveSheet()->SetCellValue('F' . $row, number_format($data[$j][4],2));
                    $this->excel->getActiveSheet()->SetCellValue('G' . $row, number_format($data[$j][5],2));
                    $this->excel->getActiveSheet()->SetCellValue('H' . $row, number_format($data[$j][6],2));
                    $this->excel->getActiveSheet()->SetCellValue('I' . $row, number_format($data[$j][7],2));
                    $this->excel->getActiveSheet()->SetCellValue('J' . $row, number_format($data[$j][8],2));
                    $this->excel->getActiveSheet()->SetCellValue('K' . $row, number_format($data[$j][9],2));
                    $this->excel->getActiveSheet()->SetCellValue('L' . $row, number_format($data[$j][10],2));
                    $this->excel->getActiveSheet()->SetCellValue('M' . $row, number_format($data[$j][11],2));
                    $this->excel->getActiveSheet()->SetCellValue('N' . $row, number_format($data[$j][12],2));

                $this->excel->getActiveSheet()->getColumnDimension('A')->setWidth(20);
                $this->excel->getActiveSheet()->getColumnDimension('B')->setWidth(20);
                $this->excel->getActiveSheet()->getColumnDimension('C')->setWidth(10);
                $this->excel->getActiveSheet()->getColumnDimension('D')->setWidth(10);
                $this->excel->getActiveSheet()->getColumnDimension('E')->setWidth(10);
                $this->excel->getActiveSheet()->getColumnDimension('F')->setWidth(10);
                $this->excel->getActiveSheet()->getColumnDimension('G')->setWidth(10);
                $this->excel->getActiveSheet()->getColumnDimension('H')->setWidth(10);
                $this->excel->getActiveSheet()->getColumnDimension('I')->setWidth(10);
                $this->excel->getActiveSheet()->getColumnDimension('J')->setWidth(10);
                $this->excel->getActiveSheet()->getColumnDimension('K')->setWidth(10);
                $this->excel->getActiveSheet()->getColumnDimension('L')->setWidth(10);
                $this->excel->getActiveSheet()->getColumnDimension('M')->setWidth(10);
                $this->excel->getActiveSheet()->getColumnDimension('N')->setWidth(10);
                $filename = 'targets';
                $this->excel->getDefaultStyle()->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
                if($pdf) {
                    $styleArray = array(
                        'borders' => array(
                            'allborders' => array(
                                'style' => PHPExcel_Style_Border::BORDER_THIN
                                )
                            )
                        );
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
                    $objWriter->save('php://output');
                    exit();
                }
                if($xls) {
                    $this->excel->getActiveSheet()->getStyle('E2:E'.$row)->getAlignment()->setWrapText(true);
                    ob_clean();
                    header('Content-Type: application/vnd.ms-excel');
                    header('Content-Disposition: attachment;filename="' . $filename . '.xls"');
                    header('Cache-Control: max-age=0');
                    ob_clean();
                    $objWriter = PHPExcel_IOFactory::createWriter($this->excel, 'Excel5');
                    $objWriter->save('php://output');
                    exit();
                }

            }
            $this->session->set_flashdata('error', lang('nothing_found'));
            redirect($_SERVER["HTTP_REFERER"]);

        } 

    }

        function distributor(){
        $this->data['error'] = (validation_errors()) ? validation_errors() : $this->session->flashdata('error');    
        $bc = array(array('link' => base_url(), 'page' => lang('home')), array('link' => site_url('target'), 'page' => lang('target_management')), array('link' => '#', 'page' => lang('list_targets')));
        $meta = array('page_title' => lang('target_management'), 'bc' => $bc);
        $this->page_construct('target/distributors', $meta, $this->data);
    }

    function getBillers() {
        $this->sma->checkPermissions('index');

        $this->load->library('datatables');
        $this->datatables
                ->select("id, company, name, phone, email, city")
                ->from("companies")
                ->where('group_name', 'biller')
               ->add_column("Actions", "<div class='text-center'><a class=\"tip\" title='" . lang("view_report") . "' href='" . site_url('target/distributor_target/$1') . "'><span class='label label-primary'>" . lang("view_report") . "</span></a></div>", "id")
                ->unset_column('id');
        echo $this->datatables->generate();
    }

    function distributor_target(){
    $this->data['error'] = (validation_errors()) ? validation_errors() : $this->session->flashdata('error'); 
   

$contents = $this->get_otherstuff();

$this->data['contents'] = $contents;

        $bc = array(array('link' => base_url(), 'page' => lang('home')), array('link' => site_url('target'), 'page' => lang('target_management')), array('link' => '#', 'page' => lang('list_targets')));
        $meta = array('page_title' => lang('staff_target'), 'bc' => $bc);
        $this->page_construct('target/billers', $meta, $this->data);
}


function get_otherstuff (){
        $data = array();
         $contents = array();
         $i = 0;

         $targets = $this->target_model->getBillerTargets();

          $this->data['targets'] =  $targets;

         if(!$targets){
           $this->session->set_flashdata('error',"This Distributor Has No Set Targets");  
           redirect('target/distributor');
         }

        foreach ($targets as  $target ) {
            $name = $target->name;
            if(!in_array( $name,$data)){
                $data[$i] = $name;
                 $i++;
            }
             
        }


        for ($j = 0; $j < count($data); $j++){
            $contents[$j][0] = $data[$j];
        }

        

        
foreach ($targets as $target){
    for ($i = 0; $i < count($contents); $i++){
        for ($j = 0; $j <12; $j++){
            if($contents[$i][0] == $target->name){
                if($target->month == date('n',$this->getMonths($j)) && date('Y',$this->getMonths($j)) == date('Y',strtotime($target->date))){
                    $contents[$i][$j+1] = $target->target;
                } else{
                    if(!isset($contents[$i][$j+1])){
                        $contents[$i][$j+1] = 0;
                    }
                }
            }
        }
    }
}

$totals = array();
$totals[0] = 'Total';
for($i = 1; $i <= 12; $i++){
    $sum = 0;
    for ($j = 0; $j < count($contents); $j++){
        $sum = $sum + $contents[$j][$i];
    }
    
    array_push($totals,$sum);
}
array_push($contents,$totals);
 return $contents;

    }

    

}
