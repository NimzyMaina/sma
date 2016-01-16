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
            foreach (($q->result()) as $row) {
                $data[] = $row;
            }
            return $data;
        }
        return FALSE;
    }
    function getRoutesDrop()     
    { 
        $this->db->select('id');
        $this->db->select('route_name');
        $this->db->from('routes');
        //$this->db->where('warehouse_id',$this->session->userdata('warehouse_id'))
        ;
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

        function getRoutesDrop2()     
    { 
        $this->db->select('id');
        $this->db->select('route_name');
        $this->db->from('routes');
        //$this->db->where('warehouse_id',$this->session->userdata('warehouse_id'))
        ;
        $query = $this->db->get();
        $result = $query->result();

        $designation_id = array('-SELECT-');
        $designation_name = array('-SELECT-');

        for ($i = 0; $i < count($result); $i++)
        {
            array_push($designation_id, $result[$i]->id);
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
    if($this->db->insert("outlets", array( 'outlet_name' => $name, 'warehouse_id' => $warehouse_id,
        'route_id' => $route_id))) {
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

      public function getConversionByID($id) {
        $q = $this->db->get_where("conversions", array('id' => $id), 1);
        if($q->num_rows() > 0) {
            return $q->row();
        }
        return FALSE;
    }

     public function getOutlets() {
        $q = $this->db->get("outlets");
        if($q->num_rows() > 0) {
            foreach (($q->result()) as $row) {
                $data[] = $row;
            }
            return $data;
        }
        return FALSE;
    }

    public function updateOutlet($id, $data = array()) {
        $routeData = array( 'outlet_name' => $data['name'],
            'route_id' => $data['route_id']);
        $this->db->where('id', $id);
        if($this->db->update("outlets", $routeData)) {
            return true;
        }
        return false;
    }

        public function updateConversion($id, $data = array()) {
        //$routeData = array( 'outlet_name' => $data['name'] );
        $this->db->where('id', $id);
        if($this->db->update("conversions", $data)) {
            return true;
        }
        return false;
    }

    public function check2($outlet_name) {
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

    public function deleteConversion($id) {
        if($this->db->delete("conversions", array('id' => $id))) {
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

        function getTypesDrop()     
    { 
        $this->db->select('id');
        $this->db->select('name');
        $this->db->from('types');
        $query = $this->db->get();
        $result = $query->result();

        $designation_id = array('-SELECT-');
        $designation_name = array('-SELECT-');

        for ($i = 0; $i < count($result); $i++)
        {
            array_push($designation_id, $result[$i]->name);
            array_push($designation_name, $result[$i]->name);
        }
        return $designation_result = array_combine($designation_id, $designation_name);
    }

    function addConversion($data = array()){
        $result = $this->db->insert('conversions',$data);

        if($result){
            return true;
        }
        return false;
    }

       function addProductConversions($datas = array()){

        if(!empty($datas)){
            foreach($datas as $data){
        $result = $this->db->insert('product_conversions',$data);
       
            }
             return true;
                }
                return false;
    }

         public function getConversions() {
        $q = $this->db->get("conversions");
        if($q->num_rows() > 0) {
            foreach (($q->result()) as $row) {
                $data[] = $row;
            }
            return $data;
        }
        return FALSE;
    }

             public function getProductConversions() {
        $q = $this->db->get("product_conversions");
        if($q->num_rows() > 0) {
            foreach (($q->result()) as $row) {
                $data[] = $row;
            }
            return $data;
        }
        return FALSE;
    }

    public function getMonthlyTarget($id){
         $month = date('m');
         $year = date('Y');

             $query = "select sum(target) as target
                from sma_category_targets
                where u_id = $id
                and DATE_FORMAT( date, '%Y' ) = $year
                and DATE_FORMAT( date, '%c' ) = $month
                group by DATE_FORMAT( date, '%c' )
                ORDER BY date DESC
                limit 1;";

         $q = $this->db->query($query,false);

         if($q->num_rows() > 0){
            $tag = $this->db->query($query,false)->row();
         return $tag->target;
         }

         return 0;

    }

     public function getMonthlySales($id,$cat){
         $month = date('m');
         $year = date('Y');

             // $query = "select sum(paid) as sales
             //    from sma_sales
             //    j
             //    where created_by = $id
             //    and DATE_FORMAT( date, '%Y' ) = $year
             //    and DATE_FORMAT( date, '%c' ) = $month
             //    group by DATE_FORMAT( date, '%c' )
             //    ORDER BY date DESC
             //    limit 1;";

             $query = "SELECT sum(i.subtotal) as total
from sma_sale_items i
join sma_sales s on s.id = i.sale_id
join sma_products p on p.id = i.product_id
where p.category_id = $cat
and DATE_FORMAT( s.date, '%Y' ) = $year
and DATE_FORMAT( s.date, '%c' ) = $month
and s.created_by = $id";

         $q = $this->db->query($query,false);

         if($q->num_rows() > 0){
            $tag = $this->db->query($query,false)->row();
         return $tag->total;
         }

         return 0;

    }


        function getUserTargets($id){
               $month = date('m');
         $year = date('Y');

          $query = "select target,DATE_FORMAT( date, '%c' ) as month,category_id ,name
                from sma_category_targets t
                join sma_categories c on c.id = t.category_id
                where u_id = $id
                and DATE_FORMAT( date, '%Y' ) = $year
                and DATE_FORMAT( date, '%c' ) = $month";

        $q = $this->db->query($query,false);
//print_r($q);exit;
        if($q->num_rows() > 0) {
            return $q->result();
        }
        return FALSE;
    }

    function getTypes(){
        return $this->db->get('types')->result();
    }

        public function addType($name) {
    if($this->db->insert("types", array( 'name' => $name))) {
        return true;
    }
    return false;
    }

           public function getTypeByID($id) {
        $q = $this->db->get_where("types", array('id' => $id), 1);
        if($q->num_rows() > 0) {
            return $q->row();
        }
        return FALSE;
    }

        public function updateType($id, $data = array()) {
        $routeData = array( 'name' => $data['name'] );
        $this->db->where('id', $id);
        if($this->db->update("types", $routeData)) {
            return true;
        }
        return false;
    }


    public function deleteType($id) {
        if($this->db->delete("types", array('id' => $id))) {
            return true;
        }
        return FALSE;
    }



}