<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Custom_model extends CI_Model {

    public function __construct() {
        parent::__construct();
    }

    public function addRoute($name,$warehouse_id) {
    if($this->db->insert("routes", array( 'route_name' => $name, 'warehouse_id' => $warehouse_id))) {
        return true;
    }
    return false;
    }

           public function getRouteByID($id) {
        $q = $this->db->get_where("routes", array('id' => $id), 1);
        if($q->num_rows() > 0) {
            return $q->row();
        }
        return FALSE;
    }

     public function getRoutes() {
        $q = $this->db->get("routes");
         $this->db->where('warehouse_id',$this->session->userdata('warehouse_id'));
        if($q->num_rows() > 0) {
            return $q->row();
        }
        return FALSE;
    }
    function getRoutesDrop()     
    { 
        $this->db->select('id');
        $this->db->select('route_name');
        $this->db->from('routes');
        $this->db->where('warehouse_id',$this->session->userdata('warehouse_id'));
        $query = $this->db->get();
        $result = $query->result();

        $designation_id = array('-SELECT-');
        $designation_name = array('-SELECT-');

        for ($i = 0; $i < count($result); $i++)
        {
            array_push($designation_id, $result[$i]->route_name);
            array_push($designation_name, $result[$i]->route_name);
        }
        return $designation_result = array_combine($designation_id, $designation_name);
    }

    public function updateRoute($id, $data = array()) {
        $routeData = array( 'route_name' => $data['name'] );
        $this->db->where('id', $id);
        if($this->db->update("routes", $routeData)) {
            return true;
        }
        return false;
    }

    public function check($route_name) {
        $q = $this->db->get_where("sales", array('route_id' => $route_name));
        if($q->num_rows() > 0) {
            foreach (($q->result()) as $row) {
                $data[] = $row;
            }
            return $data;
        }
        return FALSE;
    }

    public function deleteRoute($id) {
        if($this->db->delete("routes", array('id' => $id))) {
            return true;
        }
        return FALSE;
    }




     public function addOutlet($name,$warehouse_id) {
    if($this->db->insert("outlets", array( 'outlet_name' => $name, 'warehouse_id' => $warehouse_id))) {
        return true;
    }
    return false;
    }

           public function getOutletByID($id) {
        $q = $this->db->get_where("outlets", array('id' => $id), 1);
        if($q->num_rows() > 0) {
            return $q->row();
        }
        return FALSE;
    }

     public function getOutlets() {
        $q = $this->db->get("outlets");
        if($q->num_rows() > 0) {
            return $q->row();
        }
        return FALSE;
    }

    public function updateOutlet($id, $data = array()) {
        $routeData = array( 'outlet_name' => $data['name'] );
        $this->db->where('id', $id);
        if($this->db->update("outlets", $routeData)) {
            return true;
        }
        return false;
    }

    public function check2($route_name) {
        $q = $this->db->get_where("sales", array('outlet_id' => $outlet_name));
        if($q->num_rows() > 0) {
            foreach (($q->result()) as $row) {
                $data[] = $row;
            }
            return $data;
        }
        return FALSE;
    }

    public function deleteOutlet($id) {
        if($this->db->delete("outlets", array('id' => $id))) {
            return true;
        }
        return FALSE;
    }

    function getOutletsDrop()     
    { 
        $this->db->select('id');
        $this->db->select('outlet_name');
        $this->db->from('outlets');
        $query = $this->db->get();
        $result = $query->result();

        $designation_id = array('-SELECT-');
        $designation_name = array('-SELECT-');

        for ($i = 0; $i < count($result); $i++)
        {
            array_push($designation_id, $result[$i]->outlet_name);
            array_push($designation_name, $result[$i]->outlet_name);
        }
        return $designation_result = array_combine($designation_id, $designation_name);
    }
}