<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Target_model extends CI_Model {

    public function __construct() {
        parent::__construct();
        $this->load->model('auth_model');
    }

    function setTarget(){
    	$res = true;
         $this->input->post('date').'<br>';
    	 $date = strtotime($this->input->post('date'));
		 $date = date("Y-m-d H:i:s",$date);
        //exit;

    	for ($i = 0; $i < count($_POST['category']); $i++){
    		$data = array('u_id' => $this->input->post('user'),
    		'category_id' => $_POST['category'][$i],
    		'target' => $_POST['target'][$i] ,
    		'date' => $date);

//print_r($data);exit;
    		$result = $this->db->insert('category_targets',$data);

    		if(!$result){
    			$res = false;
    		}


    	}

    	$id = $this->input->post('user');
    	//$date = $this->input->post('date');
     $query = "select sum(target) as target
				from sma_category_targets
				where u_id = $id
				and DATE_FORMAT( date, '%Y' ) = ".date('Y',strtotime($date))."
				and DATE_FORMAT( date, '%c' ) = ".date('m',strtotime($date))."
				group by DATE_FORMAT( date, '%c' )
				ORDER BY date DESC
				limit 1;";

    	 $tag = $this->db->query($query,false)->row();
    	 $tag = $tag->target;//exit;

    	        $dat['target'] = $tag;
        $dat['t_date'] = $date;
        $dat['u_id'] = $id;
    	$this->set($dat);
    	return $res;
    }

        public function set($data){
        $result = $this->db->insert('targets',$data);
        if ($result){
            return true;
        }
        else{
            return false;
        }

    }

        function getUserTargets(){
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
        // ob_clean();
        // echo $str = $this->db->last_query();exit();
        if($q->num_rows() > 0) {
            return $q->result();
        }
        return FALSE;
    }

     function getBillerTargets(){
                $id = 0;
        if(null !== $this->uri->segment(3)){
            $id = $this->uri->segment(3);
        }
        else{
            $this->session->set_flashdata('error',"No User ID Selected!");
            redirect($_SERVER['HTTP_REFERER']);
        }
        $id = $this->uri->segment(3);

        $sql = "select  companies.name as full_name, categories.name, date,SUM(target) as target ,DATE_FORMAT( date, '%c' ) as month
from sma_category_targets category_targets
join sma_users users on users.id = category_targets.u_id
join sma_categories  categories on categories.id = category_targets.category_id
join sma_companies companies on companies.id = users.biller_id
where DATE_FORMAT( date, '%Y' ) >= ".date('Y')."
and biller_id = $id
group by categories.name, biller_id, month";

               $q = $this->db->query($sql,false);

        // ob_clean();
        // echo $str = $this->db->last_query();exit();
        if($q->num_rows() > 0) {
            return $q->result();
        }
        return FALSE;
    }
}