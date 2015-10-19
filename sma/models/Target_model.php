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
				and DATE_FORMAT( date, '%Y' ) = ".date('Y',$date)."
				and DATE_FORMAT( date, '%c' ) = ".date('m',$date)."
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
}