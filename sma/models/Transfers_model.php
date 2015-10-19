<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Transfers_model extends CI_Model {

    public function __construct() {
        parent::__construct();
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
    
    public function getWHProduct($id) {
        $this->db->select('products.id, code, name, warehouses_products.quantity, cost, tax_rate')
        ->join('warehouses_products', 'warehouses_products.product_id=products.id', 'left')
        ->group_by('products.id');
        $q = $this->db->get_where('products', array('warehouses_products.product_id' => $id), 1);
        if($q->num_rows() > 0) {
            return $q->row();
        }

        return FALSE;
    }

    public function addTransfer($data = array(), $items = array()) {
        $status = $data['status'];
        if($this->db->insert('transfers', $data)) {
            $transfer_id = $this->db->insert_id();
            if($this->site->getReference('to') == $data['transfer_no']) {
                $this->site->updateReference('to');
            }
            foreach ($items as $item) {
                $item['transfer_id'] = $transfer_id;
                if($status == 'completed') {
                    $item['date'] = date('Y-m-d');
                    $item['warehouse_id'] = $data['to_warehouse_id'];
                    $item['status'] = 'received';
                    $this->db->insert('purchase_items', $item);
                } else {
                    $this->db->insert('transfer_items', $item);
                }

                if($status == 'sent') {
                    $this->updateWarehouseQuantity($item['product_id'], $data['from_warehouse_id'], $item['quantity'], '-');
                    if(isset($item['option_id']) && !empty($item['option_id'])) {
                        $this->updateProductOptionQuantity($item['option_id'], $data['from_warehouse_id'], $item['quantity'], $item['product_id'], '-');
                        $this->site->syncVariantQty($item['option_id']);
                    }
                } elseif($status == 'completed') {
                    $this->updateWarehouseQuantity($item['product_id'], $data['from_warehouse_id'], $item['quantity'], '-');
                    $this->updateWarehouseQuantity($item['product_id'], $data['to_warehouse_id'], $item['quantity'], '+');
                    if(isset($item['option_id']) && !empty($item['option_id'])) {
                        $this->updateProductOptionQuantity($item['option_id'], $data['from_warehouse_id'], $item['quantity'], $item['product_id'], '-');
                        $this->updateProductOptionQuantity($item['option_id'], $data['to_warehouse_id'], $item['quantity'], $item['product_id'], '+');
                        $this->site->syncVariantQty($item['option_id']);
                    }
                }

            }

            return true;
        }
        return false;
    }
    
    public function updateTransfer($id, $data = array(), $items = array()) {
        $ostatus = $this->resetTransferActions($id);
        $status = $data['status'];
        if($this->db->update('transfers', $data, array('id' => $id))) {
            $tbl = $ostatus == 'completed' ? 'purchase_items' : 'transfer_items';
            $this->db->delete($tbl, array('transfer_id' => $id));

            foreach ($items as $item) {
                $item['transfer_id'] = $id;
                if($status == 'completed') {
                    $item['date'] = date('Y-m-d');
                    $item['warehouse_id'] = $data['to_warehouse_id'];
                    $item['status'] = 'received';
                    $this->db->insert('purchase_items', $item);
                } else {
                    $this->db->insert('transfer_items', $item);
                }

                $status = $data['status'];
                if($status == 'sent') {
                    $this->updateWarehouseQuantity($item['product_id'], $data['from_warehouse_id'], $item['quantity'], '-');
                    if(isset($item['option_id']) && !empty($item['option_id'])) {
                        $this->updateProductOptionQuantity($item['option_id'], $data['from_warehouse_id'], $item['quantity'], $item['product_id'], '-');
                        $this->site->syncVariantQty($item['option_id']);
                    }
                } elseif($status == 'completed') {
                    $this->updateWarehouseQuantity($item['product_id'], $data['from_warehouse_id'], $item['quantity'], '-');
                    $this->updateWarehouseQuantity($item['product_id'], $data['to_warehouse_id'], $item['quantity'], '+');
                    if(isset($item['option_id']) && !empty($item['option_id'])) {
                        $this->updateProductOptionQuantity($item['option_id'], $data['from_warehouse_id'], $item['quantity'], $item['product_id'], '-');
                        $this->updateProductOptionQuantity($item['option_id'], $data['to_warehouse_id'], $item['quantity'], $item['product_id'], '+');
                        $this->site->syncVariantQty($item['option_id']);
                    }
                }

            }
            
            return true;
        }

        return false;
    }

    public function getProductWarehouseOptionQty($option_id, $warehouse_id) {
        $q = $this->db->get_where('warehouses_products_variants', array('option_id' => $option_id, 'warehouse_id' => $warehouse_id), 1);
        if($q->num_rows() > 0) {
            return $q->row();
        }
        return FALSE;
    }

    public function updateProductOptionQuantity($option_id, $warehouse_id, $quantity, $product_id, $scope) {
        if($scope == '-') { 
            if($option = $this->getProductWarehouseOptionQty($option_id, $warehouse_id)) {
                $nq = $option->quantity - $quantity;
                if($this->db->update('warehouses_products_variants', array('quantity' => $nq), array('option_id' => $option_id, 'warehouse_id' => $warehouse_id))) {
                    return TRUE;
                }
            } else {
                $nq = 0 - $quantity;
                if($this->db->insert('warehouses_products_variants', array('option_id' => $option_id, 'product_id' => $product_id, 'warehouse_id' => $warehouse_id, 'quantity' => $nq))) {
                    return TRUE;
                }
            }
        } elseif($scope == '+') { 
            if($option = $this->getProductWarehouseOptionQty($option_id, $warehouse_id)) {
                $nq = $option->quantity + $quantity;
                if($this->db->update('warehouses_products_variants', array('quantity' => $nq), array('option_id' => $option_id, 'warehouse_id' => $warehouse_id))) {
                    return TRUE;
                }
            } else {
                if($this->db->insert('warehouses_products_variants', array('option_id' => $option_id, 'product_id' => $product_id, 'warehouse_id' => $warehouse_id, 'quantity' => $quantity))) {
                    return TRUE;
                }
            }
        }
        return FALSE;
    }

    public function getProductByCategoryID($id) {

        $q = $this->db->get_where('products', array('category_id' => $id), 1);
        if($q->num_rows() > 0) {
            return true;
        }

        return FALSE;
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
    
    public function getProductQuantity($product_id, $warehouse = DEFAULT_WAREHOUSE) {
        $q = $this->db->get_where('warehouses_products', array('product_id' => $product_id, 'warehouse_id' => $warehouse), 1);
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

    public function getProductByCode($code) {

        $q = $this->db->get_where('products', array('code' => $code), 1);
        if($q->num_rows() > 0) {
            return $q->row();
        }

        return FALSE;
    }

    public function getProductByName($name) {

        $q = $this->db->get_where('products', array('name' => $name), 1);
        if($q->num_rows() > 0) {
            return $q->row();
        }

        return FALSE;
    }
    
    public function getTransferByID($id) {

        $q = $this->db->get_where('transfers', array('id' => $id), 1);
        if($q->num_rows() > 0) {
            return $q->row();
        }

        return FALSE;
    }

    public function getAllTransferItems($transfer_id, $status) {
        if($status == 'completed') {
            $this->db->select('purchase_items.*, product_variants.name as variant')
            ->from('purchase_items')
            ->join('product_variants', 'product_variants.id=purchase_items.option_id', 'left')
            ->group_by('purchase_items.id')
            ->where('transfer_id', $transfer_id);
        } else {
            $this->db->select('transfer_items.*, product_variants.name as variant')
            ->from('transfer_items')
            ->join('product_variants', 'product_variants.id=transfer_items.option_id', 'left')
            ->group_by('transfer_items.id')
            ->where('transfer_id', $transfer_id);
        }
        $q = $this->db->get();
        if($q->num_rows() > 0) {
            foreach (($q->result()) as $row) {
                $data[] = $row;
            }
            return $data;
        }
    }

    public function getWarehouseProduct($warehouse_id, $product_id, $variant_id) {
        if($variant_id) {
            $data = $this->getProductWarehouseOptionQty($variant_id, $warehouse_id);
            return $data;
        } else {
            $data = $this->getWarehouseProductQuantity($warehouse_id, $product_id);
            return $data;
        }
        return FALSE;
    }
    
    public function getWarehouseProductQuantity($warehouse_id, $product_id) {
        $q = $this->db->get_where('warehouses_products', array('warehouse_id' => $warehouse_id, 'product_id' => $product_id), 1);
        if($q->num_rows() > 0) {
            return $q->row();
        }
        return FALSE;
    }

    public function resetTransferActions($id){
        $otransfer = $this->transfers_model->getTransferByID($id);
        $oitems = $this->transfers_model->getAllTransferItems($id, $otransfer->status);
        $ostatus = $otransfer->status;
        if($ostatus == 'sent') {
            foreach ($oitems as $oitem) {
                $oproduct_id = $oitem->product_id;
                $oproduct_quantity = $oitem->quantity;
                $ofrom_warehouse_id = $otransfer->from_warehouse_id;
                $oto_warehouse_id = $otransfer->to_warehouse_id;
                $this->updateWarehouseQuantity($oproduct_id, $ofrom_warehouse_id, $oproduct_quantity, '+');
                if($oitem->option_id) {
                    $this->updateProductOptionQuantity($oitem->option_id, $otransfer->from_warehouse_id, $oitem->quantity, $oitem->product_id, '+');
                    $this->site->syncVariantQty($oitem->option_id);
                }
            }
        } elseif($ostatus == 'completed') {
            foreach ($oitems as $oitem) {
                $oproduct_id = $oitem->product_id;
                $oproduct_quantity = $oitem->quantity;
                $oto_product_quantity = $oitem->quantity + ($oitem->quantity - $oitem->quantity_balance);
                $ofrom_warehouse_id = $otransfer->from_warehouse_id;
                $oto_warehouse_id = $otransfer->to_warehouse_id;
                $this->updateWarehouseQuantity($oproduct_id, $ofrom_warehouse_id, $oproduct_quantity, '+');
                $this->updateWarehouseQuantity($oproduct_id, $oto_warehouse_id, $oto_product_quantity, '-');
                if($oitem->option_id) {
                    $this->updateProductOptionQuantity($oitem->option_id, $otransfer->from_warehouse_id, $oitem->quantity, $oitem->product_id, '+');
                    $this->updateProductOptionQuantity($oitem->option_id, $otransfer->to_warehouse_id, $oitem->quantity, $oitem->product_id, '-');
                    $this->site->syncVariantQty($oitem->option_id);
                }
            }
        }
        return $ostatus;
    }
    
    public function deleteTransfer($id) {
        $ostatus = $this->resetTransferActions($id);
        $tbl = $ostatus == 'completed' ? 'purchase_items' : 'transfer_items';
        if($this->db->delete('transfers', array('id' => $id)) && $this->db->delete($tbl, array('transfer_id' => $id))) {
            return true;
        }
        return FALSE;
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

    public function getProductComboItems($pid, $warehouse_id) {
        $this->db->select('products.id as id, combo_items.item_code as code, combo_items.quantity as qty, products.name as name, warehouses_products.quantity as quantity')
        ->join('products', 'products.code=combo_items.item_code', 'left')
        ->join('warehouses_products', 'warehouses_products.product_id=products.id', 'left')
        ->where('warehouses_products.warehouse_id', $warehouse_id)
        ->group_by('combo_items.id');
        $q = $this->db->get_where('combo_items', array('combo_items.product_id' => $pid));
        if($q->num_rows() > 0) {
            foreach (($q->result()) as $row) {
                $data[] = $row;
            }

            return $data;
        }
        return FALSE;
    }

    public function getProductVariantByName($name, $product_id) {
        $q = $this->db->get_where('product_variants', array('name' => $name, 'product_id' => $product_id), 1);
        if($q->num_rows() > 0) {
            return $q->row();
        }
        return FALSE;
    }

}
