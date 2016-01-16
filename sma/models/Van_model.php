<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Van_model extends CI_Model {

    public function __construct() {
        parent::__construct();
    }

    public function getAllVans(){
    	 $q = $this->db->get('vans');
        if($q->num_rows() > 0) {
            foreach (($q->result()) as $row) {
                $data[] = $row;
            }
            return $data;
        }
        return FALSE;
    }

    public function getVanStock(){

    }

      public function getVanByID($id) {
        $q = $this->db->get_where('vans', array('id' => $id), 1);
        if($q->num_rows() > 0) {
            return $q->row();
        }
        return FALSE;
    }

          public function getVanByCode($id) {
        $q = $this->db->get_where('vans', array('van_code' => $id));
        if($q->num_rows() > 0) {
            return $q->row();
        }
        return FALSE;
    }
       public function updateVan($id, $data = array()) {
        $this->db->where('id', $id);
        if($this->db->update('vans', $data)) {
            return true;
        }
        return false;
    }

        public function addVan($data) {
        if($this->db->insert('vans', $data)) {
            $id = $this->db->insert_id();
            $user = array(
                'van_id' => $id);
            $this->db->where('id',$data['user_id'])
            ->update('users',$user);
            return true;
        }
        return false;
    }

    public function getVanByID2($id){
          $this->db->select('vans.id,van_code,van_name,concat(first_name," ",last_name) as full_name',false)
               ->from('vans')
               ->join('users','users.id=vans.user_id');
               if(null != $this->session->userdata('warehouse_id')){
                $this->db->where('users.warehouse_id',$this->session->userdata('warehouse_id'));
               }
              $this->db->where('vans.id',$id);

              $q = $this->db->get();
               //print_r($q);exit;
        if($q->num_rows() > 0){
            return $q->row();
        }
        return false;

    }

        public function addTransfer($data = array(), $items = array()) {
        $status = $data['status'];
        if($this->db->insert('van_transfers', $data)) {
            $transfer_id = $this->db->insert_id();
            if($this->site->getReference('tov') == $data['transfer_no']) {
                $this->site->updateReference('tov');
            }
            foreach ($items as $item) {
                $item['transfer_id'] = $transfer_id;
                if($status == 'completed') {
                    $item['date'] = date('Y-m-d');
                    // $item['warehouse_id'] = $data['to_van_id'];
                    // $item['status'] = 'received';
                    $this->db->insert('van_transfer_items', $item);
                } else {
                    $this->db->insert('van_transfer_items', $item);
                }

                if($status == 'sent') {
                    $this->updateWarehouseQuantity($item['product_id'], $data['from_warehouse_id'], $item['quantity'], '-');
                    if(isset($item['option_id']) && !empty($item['option_id'])) {
                        $this->updateProductOptionQuantity($item['option_id'], $data['from_warehouse_id'], $item['quantity'], $item['product_id'], '-');
                        //$this->site->syncVariantQty($item['option_id']);
                    }
                } elseif($status == 'completed') {
                    $this->updateWarehouseQuantity($item['product_id'], $data['from_warehouse_id'], $item['quantity'], '-');
                    $this->updateWarehouseQuantity2($item['product_id'], $data['to_van_id'], $item['quantity'], '+');
                    if(isset($item['option_id']) && !empty($item['option_id'])) {
                        $this->updateProductOptionQuantity($item['option_id'], $data['from_warehouse_id'], $item['quantity'], $item['product_id'], '-');
                        $this->updateProductOptionQuantity2($item['option_id'], $data['to_van_id'], $item['quantity'], $item['product_id'], '+');
                        //$this->site->syncVariantQty($item['option_id']);
                    }
                }

            }

            return true;
        }
        return false;
    }

        public function updateWarehouseQuantity($product_id, $warehouse_id, $quantity, $scope) {
        if($scope == '-') { 

            if($this->getProductQuantity($product_id, $warehouse_id)) {
                $warehouse_quantity = $this->getProductQuantity($product_id, $warehouse_id);
                $warehouse_quantity = $warehouse_quantity['quantity'];
                $new_quantity = $warehouse_quantity - $quantity;
                if($this->updateQuantity($product_id, $warehouse_id, $new_quantity)) {
                    return TRUE;
                }
            } else {
                if($this->insertQuantity($product_id, $warehouse_id, -$quantity)) {
                    return TRUE;
                }
            }
            
        } elseif($scope == '+') {

            if($this->getProductQuantity($product_id, $warehouse_id)) {
                $warehouse_quantity = $this->getProductQuantity($product_id, $warehouse_id);
                $warehouse_quantity = $warehouse_quantity['quantity'];
                $new_quantity = $warehouse_quantity + $quantity;
                if($this->updateQuantity($product_id, $warehouse_id, $new_quantity)) {
                    return TRUE;
                }
            } else {
                if($this->insertQuantity($product_id, $warehouse_id, $quantity)) {
                    return TRUE;
                }
            }
            
        }

        return FALSE;
    }

            public function updateWarehouseQuantity2($product_id, $warehouse_id, $quantity, $scope) {
        if($scope == '-') { 

            if($this->getProductQuantity2($product_id, $warehouse_id)) {
                $warehouse_quantity = $this->getProductQuantity2($product_id, $warehouse_id);
                $warehouse_quantity = $warehouse_quantity['quantity'];
                $new_quantity = $warehouse_quantity - $quantity;
                if($this->updateQuantity2($product_id, $warehouse_id, $new_quantity)) {
                    return TRUE;
                }
            } else {
                if($this->insertQuantity2($product_id, $warehouse_id, -$quantity)) {
                    return TRUE;
                }
            }
            
        } elseif($scope == '+') {

            if($this->getProductQuantity2($product_id, $warehouse_id)) {
                $warehouse_quantity = $this->getProductQuantity2($product_id, $warehouse_id);
                $warehouse_quantity = $warehouse_quantity['quantity'];
                $new_quantity = $warehouse_quantity + $quantity;
                if($this->updateQuantity2($product_id, $warehouse_id, $new_quantity)) {
                    return TRUE;
                }
            } else {
                if($this->insertQuantity2($product_id, $warehouse_id, $quantity)) {
                    return TRUE;
                }
            }
            
        }

        return FALSE;
    }

    public function getProductQuantity($product_id, $warehouse = DEFAULT_WAREHOUSE) {
        $q = $this->db->get_where('warehouses_products', array('product_id' => $product_id, 'warehouse_id' => $warehouse), 1);
        if($q->num_rows() > 0) {
            return $q->row_array(); //$q->row();
        }
        return FALSE;
    }

    public function getProductQuantity2($product_id, $warehouse = DEFAULT_WAREHOUSE) {
        $q = $this->db->get_where('van_products', array('product_id' => $product_id, 'van_id' => $warehouse), 1);
        if($q->num_rows() > 0) {
            return $q->row_array(); //$q->row();
        }
        return FALSE;
    }

    public function insertQuantity($product_id, $warehouse_id, $quantity) {
        if($this->db->insert('warehouses_products', array('product_id' => $product_id, 'warehouse_id' => $warehouse_id, 'quantity' => $quantity))) {
            $this->site->syncProductQty($product_id);
            return true;
        } 
        return false;
    }

    public function updateQuantity($product_id, $warehouse_id, $quantity) {
        if($this->db->update('warehouses_products', array('quantity' => $quantity), array('product_id' => $product_id, 'warehouse_id' => $warehouse_id))) {
            $this->site->syncProductQty($product_id);
            return true;
        }
        return false;
    }
    public function insertQuantity2($product_id, $warehouse_id, $quantity) {
        if($this->db->insert('van_products', array('product_id' => $product_id, 'van_id' => $warehouse_id, 'quantity' => $quantity))) {
            $this->site->syncProductQty2($product_id);
            return true;
        } 
        return false;
    }

    public function updateQuantity2($product_id, $warehouse_id, $quantity) {
        if($this->db->update('van_products', array('quantity' => $quantity), array('product_id' => $product_id, 'van_id' => $warehouse_id))) {
            $this->site->syncProductQty2($product_id);
            return true;
        }
        return false;
    }

    public function getProductNames($term, $warehouse_id, $limit = 5) {
        $this->db->select('products.id, code, name, warehouses_products.quantity, cost, tax_rate, type, tax_method')
        ->join('warehouses_products', 'warehouses_products.product_id=products.id', 'left')
        ->group_by('products.id');
        if($this->Settings->overselling) {
            $this->db->where("type = 'standard' AND (name LIKE '%" . $term . "%' OR code LIKE '%" . $term . "%' OR  concat(name, ' (', code, ')') LIKE '%" . $term . "%')");
        } else {
            $this->db->where("type = 'standard' AND warehouses_products.warehouse_id = '" . $warehouse_id . "' AND warehouses_products.quantity > 0 AND "
            . "(name LIKE '%" . $term . "%' OR code LIKE '%" . $term . "%' OR  concat(name, ' (', code, ')') LIKE '%" . $term . "%')");
        }
        $this->db->limit($limit);
        $q = $this->db->get('products');
        if($q->num_rows() > 0) {
            foreach (($q->result()) as $row) {
                $data[] = $row;
            }
            return $data;
        }
    }

    public function getProductOptions($product_id, $warehouse_id, $zero_check = TRUE) {
        $this->db->select('product_variants.id as id, product_variants.name as name, product_variants.cost as cost, product_variants.quantity as total_quantity, warehouses_products_variants.quantity as quantity')
        ->join('warehouses_products_variants', 'warehouses_products_variants.option_id=product_variants.id', 'left')
        ->where('product_variants.product_id', $product_id)
        ->where('warehouses_products_variants.warehouse_id', $warehouse_id)
        ->group_by('product_variants.id');
        if($zero_check) { $this->db->where('warehouses_products_variants.quantity >', 0); }
        $q = $this->db->get('product_variants');
        if($q->num_rows() > 0) {
            foreach (($q->result()) as $row) {
                $data[] = $row;
            }
            return $data;
        }
        return FALSE;
    }

        public function getProductByCode($code) {

        $q = $this->db->get_where('products', array('code' => $code), 1);
        if($q->num_rows() > 0) {
            return $q->row();
        }

        return FALSE;
    }

}