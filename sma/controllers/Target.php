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
        ->where('company_id', NULL);
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
    $this->data['targets'] = $this->getUserTargets2();

         $data = array();
         $content = array();
         $i = 0;

         $targets = $this->getUserTargets2();

        // $array = json_decode(json_encode($targets), true);
         //print_r($targets);
        foreach ($targets as  $target ) {
            $name = $target->name;
            if(!in_array( $name,$data)){
                $data[$i] = $name;
                 $i++;
            }
             
        }
        $i = 0;
        //print_r($data);exit;
        // foreach ($data as $dat){
        //     $content[0][$i] = $dat[$i];
        //     $i++;
        // }

        for ($j = 0; $j < count($data); $j++){
            $content[$j][0] = $data[$j];
        }

        for ($z = 0; $z < count($data); $z++){
            foreach($targets as $tag){
                if($tag->name == $content[$z][0]){
                    array_push($content[$z],$tag->target.$tag->date);
                }
            }
        }
        $this->data['content'] = $content;
        // echo $str = $this->db->last_query();
        //  echo json_encode($content);exit;



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

    function getUserTargets2(){
                $id = 0;
        if(null !== $this->uri->segment(3)){
            $id = $this->uri->segment(3);
        }
        else{
            $this->session->set_flashdata('error',"No User ID Selected!");
            redirect($_SERVER['HTTP_REFERER']);
        }

                $this->db
        ->select("concat(first_name,' ',last_name) as full_name,name, date,target ,DATE_FORMAT( date, '%c' ) as month",false)
        ->from("category_targets")
        ->join('users', 'users.id = category_targets.u_id')
        ->join('categories', 'categories.id = category_targets.category_id')
        ->where('users.id',$id)
        ->where("DATE_FORMAT( date, '%Y' ) >=",date('Y'))
        ->where("date >=",date("Y-m-d h:i:s",mktime(0, 0, 0, date('m'), 1, date('Y'))))
        ;
        
        if($this->Admin) { $this->db->where('warehouse_id', $this->session->userdata('warehouse_id')); }

        $q = $this->db->get();
        if($q->num_rows() > 0) {

            return $q->result();
        }
        return FALSE;
    }

}
