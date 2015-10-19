<?php defined('BASEPATH') OR exit('No direct script access allowed');


class Purchases_model extends CI_Model {

    public function __construct() {
        parent::__construct();
    }

    public function getProductNames($term, $limit = 5) {
        $term = $this->db->escape_str($term);
        $this->db->where("type = 'standard' AND (name LIKE '%".$term."%' OR code LIKE '%".$term."%' OR  concat(name, ' (', code, ')') LIKE '%".$term."%')");
        $this->db->limit($limit);
        $q = $this->db->get('products');
        if($q->num_rows() > 0) {
            foreach (($q->result()) as $row) {
                $data[] = $row;
            }
            return $data;
        }
    }

    public function getAllProducts() {
        $q = $this->db->get('products');
        if($q->num_rows() > 0) {
            foreach (($q->result()) as $row) {
                $data[] = $row;
            }
            return $data;
        }
    }

    public function getProductByID($id) {
        $q = $this->db->get_where('products', array('id' => $id), 1);
        if($q->num_rows() > 0) {
            return $q->row();
        }
        return FALSE;
    }

    public function getProductsByCode($code) {
        $this->db->select('*')->from('products')->like('code', $code, 'both');
        $q = $this->db->get();
        if($q->num_rows() > 0) {
            foreach (($q->result()) as $row) {
                $data[] = $row;
            }
            return $data;
        }
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

    public function updateProductQuantity($product_id, $quantity, $warehouse_id, $product_cost) {
        if($this->addQuantity($product_id, $warehouse_id, $quantity)) {
            $this->site->syncProductQty($product_id);
            return true;
        }
        return false;
    }

    public function calculateAndUpdateQuantity($item_id, $product_id, $quantity, $warehouse_id, $product_cost) {
        if($this->updatePrice($product_id, $product_cost) && $this->calculateAndAddQuantity($item_id, $product_id, $warehouse_id, $quantity)) {
            return true;
        }
        return false;
    }

    public function calculateAndAddQuantity($item_id, $product_id, $warehouse_id, $quantity) {

        if($this->getProductQuantity($product_id, $warehouse_id)) {
            $quantity_details = $this->getProductQuantity($product_id, $warehouse_id);
            $product_quantity = $quantity_details['quantity'];
            $item_details = $this->getItemByID($item_id);
            $item_quantity = $item_details->quantity;
            $after_quantity = $product_quantity - $item_quantity;
            $new_quantity = $after_quantity + $quantity;
            if($this->updateQuantity($product_id, $warehouse_id, $new_quantity)) {
                return TRUE;
            }
        } else {

            if($this->insertQuantity($product_id, $warehouse_id, $quantity)) {
                return TRUE;
            }
        }
        return FALSE;
    }

    public function addQuantity($product_id, $warehouse_id, $quantity) {

        if($this->getProductQuantity($product_id, $warehouse_id)) {
            $warehouse_quantity = $this->getProductQuantity($product_id, $warehouse_id);
            $old_quantity = $warehouse_quantity['quantity'];
            $new_quantity = $old_quantity + $quantity;

            if($this->updateQuantity($product_id, $warehouse_id, $new_quantity)) {
                return TRUE;
            }
        } else {

            if($this->insertQuantity($product_id, $warehouse_id, $quantity)) {
                return TRUE;
            }
        }
        return FALSE;
    }

    public function insertQuantity($product_id, $warehouse_id, $quantity) {
        $productData = array(
            'product_id' => $product_id,
            'warehouse_id' => $warehouse_id,
            'quantity' => $quantity
            );
        if($this->db->insert('warehouses_products', $productData)) {
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

    public function getProductQuantity($product_id, $warehouse) {
        $q = $this->db->get_where('warehouses_products', array('product_id' => $product_id, 'warehouse_id' => $warehouse), 1);

        if($q->num_rows() > 0) {
            return $q->row_array(); //$q->row();
        }

        return FALSE;
    }

    public function updatePrice($id, $unit_cost) {

        if($this->db->update('products', array('cost' => $unit_cost), array('id' => $id))) {
            return true;
        }

        return false;
    }

    public function getAllPurchases() {
        $q = $this->db->get('purchases');
        if($q->num_rows() > 0) {
            foreach (($q->result()) as $row) {
                $data[] = $row;
            }

            return $data;
        }
    }

    public function getAllPurchaseItems($purchase_id) {
        $this->db->select('purchase_items.*, products.details as details, product_variants.name as variant')
        ->join('products', 'products.id=purchase_items.product_id', 'left')
        ->join('product_variants', 'product_variants.id=purchase_items.option_id', 'left')
        ->group_by('purchase_items.id')
        ->order_by('id', 'asc');
        $q = $this->db->get_where('purchase_items', array('purchase_id' => $purchase_id));
        if($q->num_rows() > 0) {
            foreach (($q->result()) as $row) {
                $data[] = $row;
            }

            return $data;
        }
        return FALSE;
    }

    public function getItemByID($id) {
        $q = $this->db->get_where('purchase_items', array('id' => $id), 1);
        if($q->num_rows() > 0) {
            return $q->row();
        }
        return FALSE;
    }
    
    public function getTaxRateByName($name) {
        $q = $this->db->get_where('tax_rates', array('name' => $name), 1);
        if($q->num_rows() > 0) {
            return $q->row();
        }
        return FALSE;
    }

    public function getPurchaseByID($id) {
        $q = $this->db->get_where('purchases', array('id' => $id), 1);
        if($q->num_rows() > 0) {
            return $q->row();
        }
        return FALSE;
    }

    public function getProductOptionByID($id) {
        $q = $this->db->get_where('product_variants', array('id' => $id), 1);
        if($q->num_rows() > 0) {
            return $q->row();
        }
        return FALSE;
    }

    public function getProductWarehouseOptionQty($option_id, $warehouse_id) {
        $q = $this->db->get_where('warehouses_products_variants', array('option_id' => $option_id, 'warehouse_id' => $warehouse_id), 1);
        if($q->num_rows() > 0) {
            return $q->row();
        }
        return FALSE;
    }

    public function addProductOptionQuantity($option_id, $warehouse_id, $quantity, $product_id) {
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
        return FALSE;
    }

    public function resetProductOptionQuantity($option_id, $warehouse_id, $quantity, $product_id) {
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
        return FALSE;
    }

    public function getOverSoldCosting($product_id) {
        $q = $this->db->get_where('costing', array('overselling' => 1));
        if($q->num_rows() > 0) {
            foreach (($q->result()) as $row) {
                $data[] = $row;
            }
            return $data;
        }
        return FALSE;
    }

    public function addPurchase($data, $items) {

        if($this->db->insert('purchases', $data)) {
            $purchase_id = $this->db->insert_id();
            if($this->site->getReference('po') == $data['reference_no']) {
                $this->site->updateReference('po');
            }
            foreach ($items as $item) {
                $item['purchase_id'] = $purchase_id;
                $this->db->insert('purchase_items', $item);
                $purchase_item_id = $this->db->insert_id();
                if($data['status'] == 'received') {
                    $this->updateProductQuantity($item['product_id'], $item['quantity'], $item['warehouse_id'], $item['net_unit_cost']);
                    if(isset($item['option_id']) && !empty($item['option_id'])) {
                        $this->addProductOptionQuantity($item['option_id'], $item['warehouse_id'], $item['quantity'], $item['product_id']);
                        $this->site->syncVariantQty($item['option_id']);
                    }
                    /*if($this->Settings->overselling) {
                        if($costings = $this->getOverSoldCosting($item['product_id'])) {
                            $qty = $item['quantity'];
                            $purcahse_unit_cost = $item['net_unit_cost'] + ($item['item_tax']/$item['quantity']);
                            foreach ($costings as $costing) {
                                if($costing->quantity <= $item['quantity']) {
                                    $qty -= $costing->quantity;
                                    $this->db->update('costing', array('purchase_item_id' => $purchase_item_id, 'purchase_net_cost' => $item['net_unit_cost'], 'purcahse_unit_cost' => $purcahse_unit_cost), array('id' => $costing->id));
                                    $this->db->update('purchase_items', array('quantity_balance' => $qty), array('id' => $purchase_item_id));
                                } else {
                                    $qty = $costing->quantity - $qty;
                                    $this->db->update('costing', array('purchase_item_id' => $purchase_item_id, 'purchase_net_cost' => $item['net_unit_cost'], 'purcahse_unit_cost' => $purcahse_unit_cost, 'quantity' => $item['quantity']), array('id' => $costing->id));
                                    $cost = array('date' => date('Y-m-d'), 'product_id' => $costing->product_id, 'sale_item_id' => $costing->sale_item_id, 'purchase_item_id' => NULL, 'quantity' => $qty, 'purchase_net_unit_cost' => NULL, 'purchase_unit_cost' => NULL, 'sale_net_unit_price' => $costing->sale_net_unit_price, 'sale_unit_price' => $$costing->sale_unit_price, 'quantity_balance' => NULL, 'overselling' => $costing->overselling, 'inventory' => $costing->inventory);
                                    $this->db->insert('costing', $cost);
                                    $this->db->update('purchase_items', array('quantity_balance' => 0), array('id' => $purchase_item_id));
                                }
                            }
                        }
                    }*/
                }
            }
            return true;
        }
        return false;
    }

    public function resetPurchaseActions($id) {
        $purchase = $this->getPurchaseByID($id);
        $items = $this->getAllPurchaseItems($id);
        if($purchase->status == 'received') {
            foreach ($items as $item) {
                $item_details = $this->getItemByID($item->id);
                $pr_qty_details = $this->getProductQuantity($item->product_id, $purchase->warehouse_id);
                $qty = $pr_qty_details['quantity'] - $item_details->quantity;
                $this->updateQuantity($item->product_id, $purchase->warehouse_id, $qty);
                if($item->option_id) {
                    $this->resetProductOptionQuantity($item->option_id, $item->warehouse_id, $item->quantity, $item->product_id);
                    $this->site->syncVariantQty($item->option_id);
                }
            }
        }
    }

    public function updatePurchase($id, $data, $items = array()) {
        $this->resetPurchaseActions($id);

        if($this->db->update('purchases', $data, array('id' => $id)) && $this->db->delete('purchase_items', array('purchase_id' => $id))) {
            $purchase_id = $this->db->insert_id();
            foreach ($items as $item) {
                $item['purchase_id'] = $id;
                $this->db->insert('purchase_items', $item);
                $purchase_item_id = $this->db->insert_id();
                if($data['status'] == 'received') {
                    $this->updateProductQuantity($item['product_id'], $item['quantity'], $item['warehouse_id'], $item['net_unit_cost']);
                    if(isset($item['option_id']) && !empty($item['option_id'])) {
                        $this->addProductOptionQuantity($item['option_id'], $item['warehouse_id'], $item['quantity'], $item['product_id']);
                        $this->site->syncVariantQty($item['option_id']);
                    }
                }
            }
            $this->site->syncPurchasePayments($id);
            return true;
        }

        return false;
    }

    public function deletePurchase($id) {
        $this->resetPurchaseActions($id);

        if($this->db->delete('purchase_items', array('purchase_id' => $id)) && $this->db->delete('purchases', array('id' => $id))) {
            $this->db->delete('payments', array('purchase_id' => $id));
            return true;
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

    public function getPurchasePayments($purchase_id) {
        $this->db->order_by('id', 'asc');
        $q = $this->db->get_where('payments', array('purchase_id' => $purchase_id));
        if($q->num_rows() > 0) {
            foreach (($q->result()) as $row) {
                $data[] = $row;
            }
            return $data;
        }
    }
    
    public function getPaymentByID($id) {
        $q = $this->db->get_where('payments', array('id' => $id), 1);
        if($q->num_rows() > 0) {
            return $q->row();
        }

        return FALSE;
    }
    
    public function getPaymentsForPurchase($purchase_id) {
        $this->db->select('payments.date, payments.paid_by, payments.amount, payments.reference_no, users.first_name, users.last_name, type')
        ->join('users', 'users.id=payments.created_by', 'left');
        $q = $this->db->get_where('payments', array('purchase_id' => $purchase_id));
        if($q->num_rows() > 0) {
            foreach (($q->result()) as $row) {
                $data[] = $row;
            }
            return $data;
        }
        return FALSE;
    }
    
    public function addPayment($data = array()) {
        if($this->db->insert('payments', $data)) {
            if($this->site->getReference('pay')  == $data['reference_no']) {
                $this->site->updateReference('pay');
            }
            $this->site->syncPurchasePayments($data['purchase_id']);
            return true;
        }
        return false;
    }
    
    public function updatePayment($id, $data = array()) {
        if($this->db->update('payments', $data, array('id' => $id))) {
            $this->site->syncPurchasePayments($data['purchase_id']);
            return true;
        }
        return false;
    }
    
    public function deletePayment($id) {
        $opay = $this->getPaymentByID($id);
        if($this->db->delete('payments', array('id' => $id))) {
            $this->site->syncPurchasePayments($opay->purchase_id);
            return true;
        }
        return FALSE;
    }

    public function getProductOptions($product_id) {
        $q = $this->db->get_where('product_variants', array('product_id' => $product_id));
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

    public function getExpenseByID($id) {
        $q = $this->db->get_where('expenses', array('id' => $id), 1);
        if($q->num_rows() > 0) {
            return $q->row();
        }
        return FALSE;
    }
    
    public function addExpense($data = array()) {
        if($this->db->insert('expenses', $data)) {
            return true;
        }
        return false;
    }
    
    public function updateExpense($id, $data = array()) {
        if($this->db->update('expenses', $data, array('id' => $id))) {
            return true;
        }
        return false;
    }
    
    public function deleteExpense($id) {
        if($this->db->delete('expenses', array('id' => $id))) {
            return true;
        }
        return FALSE;
    }

    public function getQuoteByID($id) {
        $q = $this->db->get_where('quotes', array('id' => $id), 1);
        if($q->num_rows() > 0) {
            return $q->row();
        }
        return FALSE;
    }

    public function getAllQuoteItems($quote_id) {
        $q = $this->db->get_where('quote_items', array('quote_id' => $quote_id));
        if($q->num_rows() > 0) {
            foreach (($q->result()) as $row) {
                $data[] = $row;
            }
            return $data;
        }
        return FALSE;
    }

}
