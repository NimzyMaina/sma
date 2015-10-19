<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Sales_model extends CI_Model {

    public function __construct() {
        parent::__construct();
    }
    
    public function getProductNames($term, $warehouse_id, $limit = 5) {
        $this->db->select('products.id, code, name, type, warehouses_products.quantity, price, tax_rate, tax_method')
        ->join('warehouses_products', 'warehouses_products.product_id=products.id', 'left')
        ->group_by('products.id');
        if($this->Settings->overselling) {
            $this->db->where("(name LIKE '%" . $term . "%' OR code LIKE '%" . $term . "%' OR  concat(name, ' (', code, ')') LIKE '%" . $term . "%')");
        } else {
            $this->db->where("(products.track_quantity = 0 OR warehouses_products.quantity > 0) AND warehouses_products.warehouse_id = '" . $warehouse_id . "' AND "
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

    public function getProductByCode($code) {
        $q = $this->db->get_where('products', array('code' => $code), 1);
        if($q->num_rows() > 0) {
            return $q->row();
        }
        return FALSE;
    }

    public function syncQuantity($product_id, $warehouse_id, $quantity) {
        if($warehouse_quantity = $this->getProductQuantity($product_id, $warehouse_id)) {
            $new_quantity = $warehouse_quantity['quantity'] - $quantity;
            if($this->updateQuantity($product_id, $warehouse_id, $new_quantity)) {
                $this->site->syncProductQty($product_id);
                return TRUE;
            }
        } else {
            if($this->insertQuantity($product_id, $warehouse_id, -$quantity)) {
                $this->site->syncProductQty($product_id);
                return TRUE;
            }
        }
        return FALSE;
    }

    public function insertQuantity($product_id, $warehouse_id, $quantity) {
        if($this->db->insert('warehouses_products', array( 'product_id' => $product_id, 'warehouse_id' => $warehouse_id, 'quantity' => $quantity ))) {
            return true;
        } 
        return false;
    }

    public function updateQuantity($product_id, $warehouse_id, $quantity) {		
        if($this->db->update('warehouses_products', array( 'quantity' => $quantity ), array('product_id' => $product_id, 'warehouse_id' => $warehouse_id))) {
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
    
    public function getProductOptions($product_id, $warehouse_id) {
        $this->db->select('product_variants.id as id, product_variants.name as name, product_variants.price as price, product_variants.quantity as total_quantity, warehouses_products_variants.quantity as quantity')
        ->join('warehouses_products_variants', 'warehouses_products_variants.option_id=product_variants.id', 'left')
        //->join('warehouses', 'warehouses.id=product_variants.warehouse_id', 'left')
        ->where('product_variants.product_id', $product_id)
        ->where('warehouses_products_variants.warehouse_id', $warehouse_id)
        ->where('warehouses_products_variants.quantity >', 0)
        ->group_by('product_variants.id');
        $q = $this->db->get('product_variants');
        if($q->num_rows() > 0) {
            foreach (($q->result()) as $row) {
                $data[] = $row;
            }
            return $data;
        }
        return FALSE;
    }

    public function getProductVariants($product_id) {
        $q = $this->db->get_where('product_variants', array('product_id' => $product_id));
        if($q->num_rows() > 0) {
            foreach (($q->result()) as $row) {
                $data[] = $row;
            }
            return $data;
        }
        return FALSE;
    }

    public function getItemByID($id) {

        $q = $this->db->get_where('sale_items', array('id' => $id), 1);
        if($q->num_rows() > 0) {
            return $q->row();
        }

        return FALSE;
    }

    public function getAllInvoiceItems($sale_id) {
        $this->db->select('sale_items.*, tax_rates.code as tax_code, tax_rates.name as tax_name, tax_rates.rate as tax_rate, products.details as details, product_variants.name as variant')
        ->join('products', 'products.id=sale_items.product_id', 'left')
        ->join('product_variants', 'product_variants.id=sale_items.option_id', 'left')
        ->join('tax_rates', 'tax_rates.id=sale_items.tax_rate_id', 'left')
        ->group_by('sale_items.id')
        ->order_by('id', 'asc');
        $q = $this->db->get_where('sale_items', array('sale_id' => $sale_id));
        if($q->num_rows() > 0) {
            foreach (($q->result()) as $row) {
                $data[] = $row;
            }
            return $data;
        }
        return FALSE;
    }

    public function getAllReturnItems($return_id) {
        $this->db->select('return_items.*, products.details as details, product_variants.name as variant')
        ->join('products', 'products.id=return_items.product_id', 'left')
        ->join('product_variants', 'product_variants.id=return_items.option_id', 'left')
        ->group_by('return_items.id')
        ->order_by('id', 'asc');
        $q = $this->db->get_where('return_items', array('return_id' => $return_id));
        if($q->num_rows() > 0) {
            foreach (($q->result()) as $row) {
                $data[] = $row;
            }
            return $data;
        }
    }

    public function getAllInvoiceItemsWithDetails($sale_id) {
        $this->db->select('sale_items.id, sale_items.product_name, sale_items.product_code, sale_items.quantity, sale_items.serial_no, sale_items.tax, sale_items.net_unit_price, sale_items.item_tax, sale_items.item_discount, sale_items.subtotal, products.details');
        $this->db->join('products', 'products.id=sale_items.product_id', 'left');
        $this->db->order_by('id', 'asc');
        $q = $this->db->get_where('sale_items', array('sale_id' => $sale_id));
        if($q->num_rows() > 0) {
            foreach (($q->result()) as $row) {
                $data[] = $row;
            }
            return $data;
        }
    }

    public function getInvoiceByID($id) {
        $q = $this->db->get_where('sales', array('id' => $id), 1);
        if($q->num_rows() > 0) {
            return $q->row();
        }
        return FALSE;
    }

    public function getBackOffice($id){
        $sql = "select c2.cf1 as biller_code, s.biller, c.cf1, c.cf2, c.name, s.date, ca.name as category, si.product_code,  si.product_name, si.quantity, c.cf3, si.tax, u.company, u.code, concat(u.first_name,' ',u.last_name) as sp_name, s.reference_no, s.outlet_id, s.type, s.route_id, si.unit_price , si.subtotal

from sma_sale_items si
join sma_sales s on s.id = si.sale_id
join sma_companies c on c.id = s.customer_id
join sma_users u on u.id = s.created_by
join sma_products p on p.id = si.product_id
join sma_categories ca on ca.id = p.category_id
join sma_companies c2 on s.biller_id = c2.id

where si.sale_id = $id";
        $q = $this->db->query($sql);
        if($q->num_rows() > 0){
            return $q;
        }
        else{
            return false;
        }
    }

    public function getReturnByID($id) {
        $q = $this->db->get_where('return_sales', array('id' => $id), 1);
        if($q->num_rows() > 0) {
            return $q->row();
        }
        return FALSE;
    }

    public function getReturnBySID($sale_id) {
        $q = $this->db->get_where('return_sales', array('sale_id' => $sale_id), 1);
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
    
    public function getPurchasedItems($product_id, $warehouse_id, $option_id = NULL) {
        $orderby = ($this->Settings->accounting_method == 1) ? 'asc' : 'desc';
        $this->db->select('id, quantity, quantity_balance, net_unit_cost, item_tax');
        //$this->db->select('id, quantity, quantity_balance, net_unit_cost, item_tax, avg(net_unit_cost) as avg_net_unit_cost, avg(item_tax) as avg_item_tax');
        //$this->db->where('product_id', $product_id)->where('warehouse_id', $warehouse_id)->where('quantity_balance >', 0);
        $this->db->where('product_id', $product_id)->where('warehouse_id', $warehouse_id)->where('quantity_balance !=', 0);
        if($option_id) { $this->db->where('option_id', $option_id); }
        $this->db->group_by('id');
        $this->db->order_by('date', $orderby);
        $this->db->order_by('purchase_id', $orderby);
        $q = $this->db->get('purchase_items');
        if($q->num_rows() > 0) {
            foreach (($q->result()) as $row) {
                $data[] = $row;
            }
            return $data;
        }
        return FALSE;
    }
    
    public function updateOptionQuantity($option_id, $quantity) {
        if($option = $this->getProductOptionByID($option_id)) {
            $nq = $option->quantity - $quantity;
            if($this->db->update('product_variants', array('quantity' => $nq), array('id' => $option_id))) {
                return TRUE;
            }
        }
        return FALSE;
    }

    public function addOptionQuantity($option_id, $quantity) {
        if($option = $this->getProductOptionByID($option_id)) {
            $nq = $option->quantity + $quantity;
            if($this->db->update('product_variants', array('quantity' => $nq), array('id' => $option_id))) {
                return TRUE;
            }
        }
        return FALSE;
    }

    public function calculateAVCost($product_id, $warehouse_id, $net_unit_price, $unit_price, $quantity, $product_name, $option_id) {
        $pis = $this->getPurchasedItems($product_id, $warehouse_id, $option_id);
        $cost_row = array();
        $balance_qty = $quantity;
        $total_net_unit_cost = 0; $total_item_tax = 0;
        foreach($pis as $pi) { 
            $total_net_unit_cost += $pi->net_unit_cost;
            $total_item_tax += $pi->item_tax;
        }
        $as = sizeof($pis);
        $avg_net_unit_cost = $total_net_unit_cost / $as;
        $avg_item_tax = $total_item_tax / $as;
        foreach($pis as $pi) {
            if(!empty($pi) && $pi->quantity > 0 && $balance_qty <= $quantity && $quantity > 0) {
                $purchase_unit_cost = ($avg_net_unit_cost + ($avg_item_tax/$pi->quantity));
                if($pi->quantity_balance >= $quantity && $quantity > 0) {
                    $balance_qty = $pi->quantity_balance - $quantity;
                    $cost_row = array('date' => date('Y-m-d'), 'product_id' => $product_id, 'sale_item_id' => 'sale_items.id', 'purchase_item_id' => $pi->id, 'quantity' => $quantity, 'purchase_net_unit_cost' => $avg_net_unit_cost, 'purchase_unit_cost' => $purchase_unit_cost, 'sale_net_unit_price' => $net_unit_price, 'sale_unit_price' => $unit_price, 'quantity_balance' => $balance_qty, 'inventory' => 1);
                    $quantity = 0;
                } elseif($quantity > 0) {
                    $quantity = $quantity - $pi->quantity_balance;
                    $balance_qty = $quantity;
                    $cost_row = array('date' => date('Y-m-d'), 'product_id' => $product_id, 'sale_item_id' => 'sale_items.id', 'purchase_item_id' => $pi->id, 'quantity' => $pi->quantity_balance, 'purchase_net_unit_cost' => $avg_net_unit_cost, 'purchase_unit_cost' => $purchase_unit_cost, 'sale_net_unit_price' => $net_unit_price, 'sale_unit_price' => $unit_price, 'quantity_balance' => 0, 'inventory' => 1);
                }
            }
            if(empty($cost_row)) { break; }
            $cost[] = $cost_row;
            if($quantity == 0) { break; }
        }
        if($quantity > 0 && !$this->Settings->overselling) { 
            $this->session->set_flashdata('error', sprintf(lang("quantity_out_of_stock_for_%s"), ($pi->product_name ? $pi->product_name : $product_name)));
            redirect($_SERVER["HTTP_REFERER"]);
        } elseif($quantity > 0) {
            $cost[] = array('date' => date('Y-m-d'), 'product_id' => $product_id, 'sale_item_id' => 'sale_items.id', 'purchase_item_id' => NULL, 'quantity' => $quantity, 'purchase_net_unit_cost' => NULL, 'purchase_unit_cost' => NULL, 'sale_net_unit_price' => $net_unit_price, 'sale_unit_price' => $unit_price, 'quantity_balance' => NULL, 'overselling' => 1, 'inventory' => 1);
        }
        return $cost;
    }
    
    public function calculateCost($product_id, $warehouse_id, $net_unit_price, $unit_price, $quantity, $product_name, $option_id) {
        $pis = $this->getPurchasedItems($product_id, $warehouse_id, $option_id);
        $balance_qty = $quantity;
        foreach($pis as $pi) {
            if(!empty($pi) && $balance_qty <= $quantity && $quantity > 0) {
                $purchase_unit_cost = ($pi->net_unit_cost + ($pi->item_tax/$pi->quantity));
                if($pi->quantity_balance >= $quantity && $quantity > 0) {
                    $balance_qty = $pi->quantity_balance - $quantity;
                    $cost_row = array('date' => date('Y-m-d'), 'product_id' => $product_id, 'sale_item_id' => 'sale_items.id', 'purchase_item_id' => $pi->id, 'quantity' => $quantity, 'purchase_net_unit_cost' => $pi->net_unit_cost, 'purchase_unit_cost' => $purchase_unit_cost, 'sale_net_unit_price' => $net_unit_price, 'sale_unit_price' => $unit_price, 'quantity_balance' => $balance_qty, 'inventory' => 1);
                    $quantity = 0;
                } elseif($quantity > 0) {
                    $quantity = $quantity - $pi->quantity_balance;
                    $balance_qty = $quantity;
                    $cost_row = array('date' => date('Y-m-d'), 'product_id' => $product_id, 'sale_item_id' => 'sale_items.id', 'purchase_item_id' => $pi->id, 'quantity' => $pi->quantity_balance, 'purchase_net_unit_cost' => $pi->net_unit_cost, 'purchase_unit_cost' => $purchase_unit_cost, 'sale_net_unit_price' => $net_unit_price, 'sale_unit_price' => $unit_price, 'quantity_balance' => 0, 'inventory' => 1);
                }
            }
            $cost[] = $cost_row;
            if($quantity == 0) { break; }
        }
        if($quantity > 0) { 
            $this->session->set_flashdata('error', sprintf(lang("quantity_out_of_stock_for_%s"), ($pi->product_name ? $pi->product_name : $product_name)));
            redirect($_SERVER["HTTP_REFERER"]);
        }
        return $cost;
    }

    public function getProductWarehouseOptionQty($option_id, $warehouse_id) {
        $q = $this->db->get_where('warehouses_products_variants', array('option_id' => $option_id, 'warehouse_id' => $warehouse_id), 1);
        if($q->num_rows() > 0) {
            return $q->row();
        }
        return FALSE;
    }

    public function updateProductOptionQuantity($option_id, $warehouse_id, $quantity, $product_id) {
        if($option = $this->getProductWarehouseOptionQty($option_id, $warehouse_id)) {
            $nq = $option->quantity - $quantity;
            if($this->db->update('warehouses_products_variants', array('quantity' => $nq), array('option_id' => $option_id, 'warehouse_id' => $warehouse_id))) {
                $this->site->syncVariantQty($option_id);
                return TRUE;
            }
        } else {
            $nq = 0 - $quantity;
            if($this->db->insert('warehouses_products_variants', array('option_id' => $option_id, 'product_id' => $product_id, 'warehouse_id' => $warehouse_id, 'quantity' => $nq))) {
                $this->site->syncVariantQty($option_id);
                return TRUE;
            }
        }
        return FALSE;
    }

    public function addSale($data = array(), $items = array(), $payment = array()) {

        if($data['sale_status'] == 'completed') {
            if($this->Settings->accounting_method != 2 && !$this->Settings->overselling) {
                foreach ($items as $item) {
                    if($this->site->getProductByID($item['product_id'])) {
                        if($item['product_type'] == 'standard') {
                            $cost[] = $this->calculateCost($item['product_id'], $item['warehouse_id'], $item['net_unit_price'], $item['unit_price'], $item['quantity'], $item['product_name'], $item['option_id']);
                        } elseif($item['product_type'] == 'combo') {
                            $combo_items = $this->getProductComboItems($item['product_id'], $item['warehouse_id']);
                            foreach ($combo_items as $combo_item) {
                                $cost[] = $this->calculateCost($combo_item->id, $item['warehouse_id'], ($combo_item->qty*$item['quantity']), $item['product_name'], $item['option_id']);
                            }
                        } else {
                            $cost[] = array(array('date' => date('Y-m-d'), 'product_id' => $item['product_id'], 'sale_item_id' => 'sale_items.id', 'purchase_item_id' => NULL, 'quantity' => $item['quantity'], 'purchase_net_unit_cost' => 0, 'purchase_unit_cost' => 0, 'sale_net_unit_price' => $item['net_unit_price'], 'sale_unit_price' => $item['unit_price'], 'quantity_balance' => NULL, 'inventory' => NULL));
                        }
                    } elseif($item['product_type'] == 'manual') {
                        $cost[] = array(array('date' => date('Y-m-d'), 'product_id' => $item['product_id'], 'sale_item_id' => 'sale_items.id', 'purchase_item_id' => NULL, 'quantity' => $item['quantity'], 'purchase_net_unit_cost' => 0, 'purchase_unit_cost' => 0, 'sale_net_unit_price' => $item['net_unit_price'], 'sale_unit_price' => $item['unit_price'], 'quantity_balance' => NULL, 'inventory' => NULL));
                    }
                }
            } else {
                foreach ($items as $item) {
                    if($this->site->getProductByID($item['product_id'])) {
                        if($item['product_type'] == 'standard') {
                            $cost[] = $this->calculateAVCost($item['product_id'], $item['warehouse_id'], $item['net_unit_price'], $item['unit_price'], $item['quantity'], $item['product_name'], $item['option_id']);
                        } elseif($item['product_type'] == 'combo') {
                            $combo_items = $this->getProductComboItems($item['product_id'], $item['warehouse_id']);
                            foreach ($combo_items as $combo_item) {
                                $cost[] = $this->calculateAVCost($combo_item->id, $item['warehouse_id'], ($combo_item->qty*$item['quantity']), $item['unit_price'], $item['quantity'], $item['product_name'], $item['option_id']);
                            }
                        } else {
                            $cost[] = array(array('date' => date('Y-m-d'), 'product_id' => $item['product_id'], 'sale_item_id' => 'sale_items.id', 'purchase_item_id' => NULL, 'quantity' => $item['quantity'], 'purchase_net_unit_cost' => 0, 'purchase_unit_cost' => 0, 'sale_net_unit_price' => $item['net_unit_price'], 'sale_unit_price' => $item['unit_price'], 'quantity_balance' => NULL, 'inventory' => NULL));
                        }
                    } elseif($item['product_type'] == 'manual') {
                        $cost[] = array(array('date' => date('Y-m-d'), 'product_id' => $item['product_id'], 'sale_item_id' => 'sale_items.id', 'purchase_item_id' => NULL, 'quantity' => $item['quantity'], 'purchase_net_unit_cost' => 0, 'purchase_unit_cost' => 0, 'sale_net_unit_price' => $item['net_unit_price'], 'sale_unit_price' => $item['unit_price'], 'quantity_balance' => NULL, 'inventory' => NULL));
                    }
                }
            }
        }

        //$this->sma->print_arrays($cost);exit;

        if($this->db->insert('sales', $data)) {
            $sale_id = $this->db->insert_id();
            if($this->site->getReference('so') == $data['reference_no']) {
                $this->site->updateReference('so');
            }
            foreach ($items as $item) {
                $item['sale_id'] = $sale_id;
                $this->db->insert('sale_items', $item);
                $sale_item_id = $this->db->insert_id();
                if($data['sale_status'] == 'completed' && $this->site->getProductByID($item['product_id'])) {
                    if($item['product_type'] == 'standard') {
                        $this->syncQuantity($item['product_id'], $item['warehouse_id'], $item['quantity']);
                        if(isset($item['option_id']) && !empty($item['option_id'])) {
                            $this->updateProductOptionQuantity($item['option_id'], $item['warehouse_id'], $item['quantity'], $item['product_id']);
                        }
                    } elseif($item['product_type'] == 'combo') {
                        $combo_items = $this->getProductComboItems($item['product_id'], $item['warehouse_id']);
                        foreach($combo_items as $combo_item) {
                            $qty = $combo_item->qty*$item['quantity'];
                            $this->syncQuantity($combo_item->id, $item['warehouse_id'], $qty);
                            /*if($variants = $this->getProductOptions($combo_item->id, $item['warehouse_id'])) {
                                $vqty = $qty;
                                foreach ($variants as $variant) {
                                    if($variant->quantity >= $vqty) {
                                        $this->updateProductOptionQuantity($variant->id, $item['warehouse_id'], $vqty, $combo_item->id);
                                        $vqty = 0;
                                    } else {
                                        $vqty -= $variant->quantity;
                                        $this->updateProductOptionQuantity($variant->id, $item['warehouse_id'], $variant->quantity, $combo_item->id);
                                    }
                                    if($vqty == 0) { break; }
                                }
                            }*/
                        }
                    }

                    foreach ($cost as $item_costs) {
                        foreach ($item_costs as $item_cost) {
                            if($item_cost['product_id'] == $item['product_id']) {
                                $item_cost['sale_item_id'] = $sale_item_id;
                                $item_cost['sale_id'] = $sale_id; 
                                $costing[] = $item_cost;
                            }
                        }
                    }
                } 

            }

            if($data['payment_status'] == 'partial' || $data['payment_status'] == 'paid' && !empty($payment)) {
                $payment['sale_id'] = $sale_id;
                if($payment['paid_by'] == 'gift_card') {
                    $this->db->update('gift_cards', array('balance' => $payment['gc_balance']), array('card_no' => $payment['cc_no']));
                    unset($payment['gc_balance']);
                    $this->db->insert('payments', $payment);
                } else {
                    $this->db->insert('payments', $payment);
                }
                if($this->site->getReference('pay')  == $payment['reference_no']) {
                    $this->site->updateReference('pay');
                }
                $this->site->syncSalePayments($sale_id);

            }

            if($data['sale_status'] == 'completed') {
                $this->addCostingLines($costing);
            }
            $this->sma->update_award_points($data['grand_total'], $data['customer_id'], $data['created_by']);
            return true;

        }

        return false;
    }

    public function addCostingLines($costing = array()) {
        if(!empty($costing)) {
            foreach ($costing as $item_cost) {
                $this->db->insert('costing', $item_cost);
                if($item_cost['inventory']) {
                    $this->db->update('purchase_items', array('quantity_balance' => $item_cost['quantity_balance']), array('id' => $item_cost['purchase_item_id']));
                }
            }
            return TRUE;
        }
        return FALSE;
    }

    public function resetSaleActions($id) {
        $sale = $this->getInvoiceByID($id);
        $items = $this->getAllInvoiceItems($id);
        foreach ($items as $item) {
            if($sale->sale_status == 'completed' && $this->site->getProductByID($item->product_id)) {
                $pr_qty_details = $this->getProductQuantity($item->product_id, $item->warehouse_id);
                $qty = $pr_qty_details['quantity'] + $item->quantity;
                $this->updateQuantity($item->product_id, $item->warehouse_id, $qty);
                if($item->product_type == 'standard' && $item->option_id) {
                    $option = $this->getProductWarehouseOptionQty($item->option_id, $item->warehouse_id);
                    $var_quantity = $option->quantity + $item->quantity;
                    $this->db->update('warehouses_products_variants', array('quantity' => $var_quantity), array('option_id' => $item->option_id, 'warehouse_id' => $item->warehouse_id));
                    $this->site->syncVariantQty($item->option_id);
                } /*elseif($item->product_type == 'combo') {
                    $combo_items = $this->getProductComboItems($item->product_id, $item->warehouse_id);
                    foreach($combo_items as $combo_item) {
                        if($options = $this->getProductOptions($combo_item->id, $item->warehouse_id)) {
                            foreach ($options as $option) {
                                $vqty = $item->quantity + $option->quantity;
                                $this->updateProductOptionQuantity($variant->id, $item->warehouse_id, $vqty, $combo_item->id);
                                break; 
                            }
                        } elseif($variants = $this->getProductVariants($combo_item->id)) {
                            foreach ($variants as $variant) {
                                $this->updateProductOptionQuantity($variant->id, $item->warehouse_id, $item->quantity, $combo_item->id);
                                break; 
                            }
                        }
                    }
                }*/
            } 
            if($sale->sale_status == 'completed') {
                $costings = $this->getCostingLines($item->id, $item->product_id);
                if(!empty($costings)) {
                    $quantity = $item->quantity;
                    foreach ($costings as $cost) {
                        if($cost->quantity > $quantity) {
                            $qty = $cost->quantity - $quantity;
                            $bln = $cost->quantity_balance && $cost->quantity_balance >= $quantity ? $cost->quantity_balance - $quantity : 0; 
                            $this->db->update('costing', array('quantity' => $qty, 'quantity_balance' => $bln), array('id' => $cost->id));
                            $quantity = 0;
                        } elseif($cost->quantity < $quantity) {
                            $qty = $quantity - $cost->quantity;
                            $this->db->delete('costing', array('id' => $cost->id));
                            $quantity = $qty;
                        } 
                        if($quantity == 0) { break; }
                    }
                    $this->updatePurchaseItem($cost->purchase_item_id, $item->quantity);
                }
            }
        }
        $this->sma->update_award_points($sale->grand_total, $sale->customer_id, $sale->created_by, TRUE);
    }

    public function updateSale($id, $data, $items = array()) {
        $this->resetSaleActions($id);

        if($data['sale_status'] == 'completed') {
            if($this->Settings->accounting_method != 2 && !$this->Settings->overselling) {
                foreach ($items as $item) {
                    if($this->site->getProductByID($item['product_id'])) {
                        if($item['product_type'] == 'standard') {
                            $cost[] = $this->calculateCost($item['product_id'], $item['warehouse_id'], $item['net_unit_price'], $item['unit_price'], $item['quantity'], $item['product_name'], $item['option_id']);
                        } elseif($item['product_type'] == 'combo') {
                            $combo_items = $this->getProductComboItems($item['product_id'], $item['warehouse_id']);
                            foreach ($combo_items as $combo_item) {
                                $cost[] = $this->calculateCost($combo_item->id, $item['warehouse_id'], ($combo_item->qty*$item['quantity']), $item['product_name'], $item['option_id']);
                            }
                        } else {
                            $cost[] = array(array('date' => date('Y-m-d'), 'product_id' => $item['product_id'], 'sale_item_id' => 'sale_items.id', 'purchase_item_id' => NULL, 'quantity' => $item['quantity'], 'purchase_net_unit_cost' => 0, 'purchase_unit_cost' => 0, 'sale_net_unit_price' => $item['net_unit_price'], 'sale_unit_price' => $item['unit_price'], 'quantity_balance' => NULL, 'inventory' => NULL));
                        }
                    } elseif($item['product_type'] == 'manual') {
                        $cost[] = array(array('date' => date('Y-m-d'), 'product_id' => $item['product_id'], 'sale_item_id' => 'sale_items.id', 'purchase_item_id' => NULL, 'quantity' => $item['quantity'], 'purchase_net_unit_cost' => 0, 'purchase_unit_cost' => 0, 'sale_net_unit_price' => $item['net_unit_price'], 'sale_unit_price' => $item['unit_price'], 'quantity_balance' => NULL, 'inventory' => NULL));
                    }
                }
            } else {
                foreach ($items as $item) {
                    if($this->site->getProductByID($item['product_id'])) {
                        if($item['product_type'] == 'standard') {
                            $cost[] = $this->calculateAVCost($item['product_id'], $item['warehouse_id'], $item['net_unit_price'], $item['unit_price'], $item['quantity'], $item['product_name'], $item['option_id']);
                        } elseif($item['product_type'] == 'combo') {
                            $combo_items = $this->getProductComboItems($item['product_id'], $item['warehouse_id']);
                            foreach ($combo_items as $combo_item) {
                                $cost[] = $this->calculateAVCost($combo_item->id, $item['warehouse_id'], ($combo_item->qty*$item['quantity']), $item['product_name'], $item['option_id']);
                            }
                        } else {
                            $cost[] = array(array('date' => date('Y-m-d'), 'product_id' => $item['product_id'], 'sale_item_id' => 'sale_items.id', 'purchase_item_id' => NULL, 'quantity' => $item['quantity'], 'purchase_net_unit_cost' => 0, 'purchase_unit_cost' => 0, 'sale_net_unit_price' => $item['net_unit_price'], 'sale_unit_price' => $item['unit_price'], 'quantity_balance' => NULL, 'inventory' => NULL));
                        }
                    } elseif($item['product_type'] == 'manual') {
                        $cost[] = array(array('date' => date('Y-m-d'), 'product_id' => $item['product_id'], 'sale_item_id' => 'sale_items.id', 'purchase_item_id' => NULL, 'quantity' => $item['quantity'], 'purchase_net_unit_cost' => 0, 'purchase_unit_cost' => 0, 'sale_net_unit_price' => $item['net_unit_price'], 'sale_unit_price' => $item['unit_price'], 'quantity_balance' => NULL, 'inventory' => NULL));
                    }
                }
            }
        }

        if($this->db->update('sales', $data, array('id' => $id)) && $this->db->delete('sale_items', array('sale_id' => $id))) {

            foreach ($items as $item) {
                $item['sale_id'] = $id;
                $this->db->insert('sale_items', $item);
                $sale_item_id = $this->db->insert_id();
                if($data['sale_status'] == 'completed' && $this->site->getProductByID($item['product_id'])) {
                    if($item['product_type'] == 'standard') {
                        $this->syncQuantity($item['product_id'], $item['warehouse_id'], $item['quantity']);
                        if(isset($item['option_id']) && !empty($item['option_id'])) {
                            $this->updateProductOptionQuantity($item['option_id'], $item['warehouse_id'], $item['quantity'], $item['product_id']);
                        }
                    } elseif($item['product_type'] == 'combo') {
                        $combo_items = $this->getProductComboItems($item['product_id'], $item['warehouse_id']);
                        foreach($combo_items as $combo_item) {
                            $qty = $combo_items->qty*$item['quantity'];
                            $this->syncQuantity($combo_items->id, $item['warehouse_id'], $qty);
                        }
                    }

                    foreach ($cost as $item_costs) {
                        foreach ($item_costs as $item_cost) {
                            if($item_cost['product_id'] == $item['product_id']) {
                                $item_cost['sale_item_id'] = $sale_item_id;
                                $item_cost['sale_id'] = $id;
                                $costing[] = $item_cost;
                            }
                        }
                    }
                } 
            }
            if($data['sale_status'] == 'completed') {
                $this->addCostingLines($costing);
            }
            $this->sma->update_award_points($data['grand_total'], $data['customer_id'], $data['created_by']);
            return true;

        }

        return false;
    }

    public function deleteSale($id) {
        $this->resetSaleActions($id);
        if($this->db->delete('sale_items', array('sale_id' => $id)) && $this->db->delete('sales', array('id' => $id))) {
            $this->db->delete('payments', array('sale_id' => $id));
            if($return = $this->getReturnBySID($id)) {
                $this->deleteReturn($return->id);
            }
            return true;
        }
        return FALSE;
    }

    public function pushSale($id){
        $result = $this->db->where('id',$id)->update('sales',array('push' => 1));
        if($result){
            return true;
        }
        else{
            return false;
        }
    }

    public function deleteReturn($id) {

        if($this->db->delete('return_items', array('return_id' => $id)) && $this->db->delete('return_sales', array('id' => $id))) {
            return true;
        }
        return FALSE;
    }
    

    public function updatePurchaseItem($id, $qty){
        $pi = $this->getPurchaseItemById($id);
        $bln = $pi->quantity_balance + $qty;
        $this->db->update('purchase_items', array('quantity_balance' => $bln), array('id' => $id));
    }

    public function returnSale($data = array(), $items = array(), $payment = array()) {

        foreach ($items as $item) {
            if($costings = $this->getCostingLines($item['sale_item_id'], $item['product_id'])) {
                $quantity = $item['quantity'];
                foreach ($costings as $cost) {
                    if($cost->quantity >= $quantity) {
                        $qty = $cost->quantity - $quantity;
                        $bln = $cost->quantity_balance && $cost->quantity_balance >= $quantity ? $cost->quantity_balance - $quantity : 0; 
                        $this->db->update('costing', array('quantity' => $qty, 'quantity_balance' => $bln), array('id' => $cost->id));
                        $quantity = 0;
                    } elseif($cost->quantity < $quantity) {
                        $qty = $quantity - $cost->quantity;
                        $this->db->delete('costing', array('id' => $cost->id));
                        $quantity = $qty;
                    } 
                }
                $this->updatePurchaseItem($cost->purchase_item_id, $item['quantity']);
            }

        }
        //$this->sma->print_arrays($items);

        if($this->db->insert('return_sales', $data)) {
            $return_id = $this->db->insert_id();
            if($this->site->getReference('re')  == $data['reference_no']) {
                $this->site->updateReference('re');
            }
            foreach ($items as $item) {
                $item['return_id'] = $return_id;
                $this->db->insert('return_items', $item);

                if($this->site->getProductByID($item['product_id'])) {
                    if($item['product_type'] == 'standard') {
                        $pr_qty_details = $this->getProductQuantity($item['product_id'], $item['warehouse_id']);
                        $qty = $pr_qty_details['quantity'] + $item['quantity'];
                        $this->updateQuantity($item['product_id'], $item['warehouse_id'], $qty);

                        if(isset($item['option_id']) && !empty($item['option_id']) && $item['option_id'] != 0) {
                            //$this->addOptionQuantity($item['option_id'], $item['quantity']);
                            $option = $this->getProductWarehouseOptionQty($item['option_id'], $item['warehouse_id']);
                            $var_quantity = $option->quantity + $item['quantity'];
                            $this->db->update('warehouses_products_variants', array('quantity' => $var_quantity), array('option_id' => $item['option_id'], 'warehouse_id' => $item['warehouse_id']));
                            $this->site->syncVariantQty($item['option_id']);
                        }
                    } elseif($item['product_type'] == 'combo') {
                        $combo_items = $this->getProductComboItems($item['product_id'], $item['warehouse_id']);
                        foreach($combo_items as $combo_item) {
                            $qty = $combo_items->qty*$item['quantity'];
                            $pr_qty_details = $this->getProductQuantity($item['product_id'], $item['warehouse_id']);
                            $wqty = $pr_qty_details['quantity'] + $qty;
                            $this->updateQuantity($item['product_id'], $item['warehouse_id'], $wqty);
                            /*if($options = $this->getProductOptions($combo_item->id, $item['warehouse_id'])) {
                                foreach ($options as $option) {
                                    $vqty = $qty + $option->quantity;
                                    $this->updateProductOptionQuantity($variant->id, $item['warehouse_id'], $vqty, $combo_item->id);
                                    break; 
                                }
                            } elseif($variants = $this->getProductVariants($combo_item->id)) {
                                foreach ($variants as $variant) {
                                    $this->updateProductOptionQuantity($variant->id, $item['warehouse_id'], $qty, $combo_item->id);
                                    break; 
                                }
                            }*/
                        }
                    }
                } 
                if($sale_item = $this->getSaleItemByID($item['sale_item_id'])) {
                    if($sale_item->quantity == $item['quantity']) {
                        $this->db->delete('sale_items', array('id' => $item['sale_item_id']));
                    } else {
                        $nqty = $sale_item->quantity - $item['quantity'];
                        $tax = $sale_item->unit_price - $sale_item->net_unit_price;
                        $discount = $sale_item->total_discount / $sale_item->quantity;
                        $item_tax = $tax * $nqty;
                        $item_discount = $discount * $nqty;
                        $subtotal = $sale_item->unit_price * $nqty;
                        $this->db->update('sale_items', array('quantity' => $nqty, 'item_tax' => $item_tax, 'item_discount' => $item_discount, 'subtotal' => $subtotal), array('id' => $item['sale_item_id']));
                    }

                }
            }
            $this->calculateSaleTotals($data['sale_id'], $return_id, $data['surcharge']);
            if(!empty($payment)) {
                $payment['sale_id'] = $data['sale_id'];
                $payment['return_id'] = $return_id;
                $this->db->insert('payments', $payment);
                if($this->site->getReference('pay')  == $data['reference_no']) {
                    $this->site->updateReference('pay');
                }
                $this->site->syncSalePayments($data['sale_id']);
            }
            
            return true;
        }
        return false;
    }

    public function getCostingLines($sale_item_id, $product_id) {
        $this->db->order_by('id', 'asc');
        $q = $this->db->get_where('costing', array('sale_item_id' => $sale_item_id, 'product_id' => $product_id));
        if($q->num_rows() > 0) {
            foreach (($q->result()) as $row) {
                $data[] = $row;
            }
            return $data;
        }
        return FALSE;
    }

    public function getSaleItemByID($id) {
        $q = $this->db->get_where('sale_items', array('id' => $id), 1);
        if($q->num_rows() > 0) {
            return $q->row();
        }
        return FALSE;
    }

    public function calculateSaleTotals($id, $return_id, $surcharge) {
        $sale = $this->getInvoiceByID($id);
        $items = $this->getAllInvoiceItems($id);
        if(!empty($items)) {
            $this->sma->update_award_points($sale->grand_total, $sale->customer_id, $sale->created_by, TRUE);
            $total = 0;
            $product_tax = 0;
            $order_tax = 0;
            $product_discount = 0;
            $order_discount = 0;
            $total_items = 0;
            foreach ($items as $item) {
                $total_items += $item->quantity;
                $product_tax += $item->item_tax;
                $product_discount += $item->item_discount;
                $total += $item->net_unit_price * $item->quantity;
            }
            if($sale->order_discount_id) {
                $order_discount_id = $sale->order_discount_id;
                $opos = strpos($order_discount_id, $percentage);
                if($opos !== false) {
                    $ods = explode("%", $order_discount_id);
                    $order_discount = (($total + $product_tax) * (Float) ($ods[0])) / 100;
                } else {
                    $order_discount = $order_discount_id;
                }
            } 
            if($sale->order_tax_id) {
                $order_tax_id = $sale->order_tax_id;
                if($order_tax_details = $this->site->getTaxRateByID($order_tax_id)) {
                    if($order_tax_details->type == 2) {
                        $order_tax = $order_tax_details->rate;
                    }
                    if($order_tax_details->type == 1) {
                        $order_tax = (($total + $product_tax - $order_discount) * $order_tax_details->rate) / 100;
                    }
                }
            }
            $total_discount = $order_discount + $product_discount;
            $total_tax = $product_tax + $order_tax;
            $grand_total = $total + $total_tax + $sale->shipping - $total_discount + $surcharge;
            $data = array(
                'total' => $total,
                'product_discount' => $product_discount,
                'order_discount' => $order_discount,
                'total_discount' => $total_discount,
                'product_tax' => $product_tax,
                'order_tax' => $order_tax,
                'total_tax' => $total_tax,
                'grand_total' => $grand_total,
                'total_items' => $total_items,
                'return_id' => $return_id,
                'surcharge' => $surcharge
                );

            if($this->db->update('sales', $data, array('id' => $id))) {
                $this->sma->update_award_points($data['grand_total'], $sale->customer_id, $sale->created_by);
                return true;
            }
        } else {
            $this->db->delete('sales', array('id' => $id));
            //$this->db->delete('payments', array('sale_id' => $id, 'return_id !=' => $return_id));
        }
        return FALSE;
    }

    public function getPurchaseItemByID($id) {
        $q = $this->db->get_where('purchase_items', array('id' => $id), 1);
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

    public function addDelivery($data = array()) {
        if($this->db->insert('deliveries', $data)) {
            if($this->site->getReference('do')  == $data['do_reference_no']) {
                $this->site->updateReference('do');
            }
            return true;
        }
        return false;
    }

    public function updateDelivery($id, $data = array()) {
        if($this->db->update('deliveries', $data, array('id' => $id))) {
            return true;
        }
        return false;
    }

    public function getDeliveryByID($id) {
        $q = $this->db->get_where('deliveries', array('id' => $id), 1);
        if($q->num_rows() > 0) {
            return $q->row();
        }

        return FALSE;
    }

    public function deleteDelivery($id) {
        if($this->db->delete('deliveries', array('id' => $id))) {
            return true;
        }
        return FALSE;
    }

    public function getInvoicePayments($sale_id) {
        $this->db->order_by('id', 'asc');
        $q = $this->db->get_where('payments', array('sale_id' => $sale_id));
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

    public function getPaymentsForSale($sale_id) {
        $this->db->select('payments.date, payments.paid_by, payments.amount, payments.cc_no, payments.cheque_no, payments.reference_no, users.first_name, users.last_name, type')
        ->join('users', 'users.id=payments.created_by', 'left');
        $q = $this->db->get_where('payments', array('sale_id' => $sale_id));
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
            $this->site->syncSalePayments($data['sale_id']);
            if($data['paid_by'] == 'gift_card') {
                $gc = $this->site->getGiftCardByNO($data['cc_no']);
                $this->db->update('gift_cards', array('balance' => ($gc->balance - $data['amount'])), array('card_no' => $data['cc_no']));
            }
            return true;
        }
        return false;
    }

    public function updatePayment($id, $data = array()) {
        if($this->db->update('payments', $data, array('id' => $id))) {
            $this->site->syncSalePayments($data['sale_id']);
            return true;
        }
        return false;
    }

    public function deletePayment($id) {
        $opay = $this->getPaymentByID($id);
        if($this->db->delete('payments', array('id' => $id))) {
            $this->site->syncSalePayments($opay->sale_id);
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

    /* ----------------- Gift Cards --------------------- */

    public function addGiftCard($data = array(), $ca_data = array(), $sa_data = array()) {
        if($this->db->insert('gift_cards', $data)) {
            if(!empty($ca_data)) {
                $this->db->update('companies', array('award_points' => $ca_data['points']), array('id' => $ca_data['customer']));
            } elseif(!empty($sa_data)) {
                $this->db->update('users', array('award_points' => $sa_data['points']), array('id' => $sa_data['user']));
            }
            return true;
        }
        return false;
    }

    public function updateGiftCard($id, $data = array()) {
        $this->db->where('id', $id);
        if($this->db->update('gift_cards', $data)) {
            return true;
        }
        return false;
    }

    public function deleteGiftCard($id) {
        if($this->db->delete('gift_cards', array('id' => $id))) {
            return true;
        }
        return FALSE;
    }

    public function getPaypalSettings() {
        $q = $this->db->get_where('paypal', array('id' => 1));
        if($q->num_rows() > 0) {
            return $q->row();
        }
        return FALSE;
    }

    public function getSkrillSettings() {
        $q = $this->db->get_where('skrill', array('id' => 1));
        if($q->num_rows() > 0) {
            return $q->row();
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

    public function getStaff() {
        if(!$this->Owner) { $this->db->where('group_id !=', 1); }
        $this->db->where('group_id !=', 3)->where('group_id !=', 4);
        $q = $this->db->get('users');
        if($q->num_rows() > 0) {
            foreach (($q->result()) as $row) {
                $data[] = $row;
            }
            return $data;
        }
        return FALSE;
    }

   public function getTest(){
  $this->db->select("*");
  $this->db->from('sma_sales');
  $query = $this->db->get();
  return $query->result();
}

public function getR ($id){
    $this->db->select('sma_sales.id,sma_sales.date,sma_sales.reference_no,sma_sales.biller,sma_users.first_name,sma_users.last_name,sma_sales.route_id,sma_sales.outlet_id,sma_sales.type,sma_sales.receipt_no,
            sma_categories.name as category,sma_products.product_details,sma_sale_items.quantity,sma_sale_items.subtotal as val')
        ->from('sma_sales')
        ->join('sma_sale_items','sma_sales.id = sma_sale_items.sale_id','inner')
        ->join('sma_products','sma_sale_items.product_id = sma_products.id','inner')
        ->join('sma_categories','sma_products.category_id = sma_categories.id')
        ->join('sma_users','sma_sales.created_by = sma_users.id','inner')
        ->where('sma_sales.id',$id);
        $query = $this->db->get();
  return $query->result();
}

public function getQ (){
    if(!$this->Owner && !$warehouse_id) {
            $user = $this->site->getUser();
            $warehouse_id = $user->warehouse_id;
        }
    $this->db->select('sma_sales.id,sma_sales.date,sma_sales.reference_no,sma_sales.biller,sma_users.first_name,sma_users.last_name,sma_sales.route_id,sma_sales.outlet_id,sma_sales.type,sma_sales.receipt_no,
            sma_categories.name as category,sma_products.product_details,sma_sale_items.quantity,sma_sale_items.subtotal as val')
        ->from('sma_sales')
        ->join('sma_sale_items','sma_sales.id = sma_sale_items.sale_id','inner')
        ->join('sma_products','sma_sale_items.product_id = sma_products.id','inner')
        ->join('sma_categories','sma_products.category_id = sma_categories.id')
        ->join('sma_users','sma_sales.created_by = sma_users.id','inner');
        $query = $this->db->get();
  return $query->result();
}

 public function getInvoiceByIDs($id) {
        $q = $this->db->get_where('sales', array('id' => $id), 1);
        if($q->num_rows() > 0) {
            return $q->row();
        }
        return FALSE;
    }

    public function testsales( $warehouse_id = NULL){

        $this->sma->checkPermissions('index');

        if($warehouse_id) {
        $this->db->select('sma_sales.id,sma_sales.date,sma_sales.reference_no,sma_sales.biller,sma_sales.customer,sma_sales.sale_status, sma_sales.grand_total, sma_sales.paid, (sma_sales.grand_total-sma_sales.paid) as balance, sma_sales.payment_status,sma_users.first_name,sma_users.last_name,sma_sales.route_id,sma_sales.outlet_id,sma_sales.type,sma_sales.receipt_no,
            sma_categories.name as category,sma_products.product_details,sma_sale_items.quantity,sma_sale_items.subtotal as val')
        ->from('sma_sales')
        ->join('sma_sale_items','sma_sales.id = sma_sale_items.sale_id','inner')
        ->join('sma_products','sma_sale_items.product_id = sma_products.id','inner')
        ->join('sma_categories','sma_products.category_id = sma_categories.id')
        ->join('sma_users','sma_sales.created_by = sma_users.id','inner')
        ->where('sma_sales.warehouse_id', $warehouse_id);
    } else {
         $this->db->select('sma_sales.id,sma_sales.date,sma_sales.reference_no,sma_sales.biller,sma_sales.customer,sma_sales.sale_status, sma_sales.grand_total, sma_sales.paid, (sma_sales.grand_total-sma_sales.paid) as balance, sma_sales.payment_status,sma_users.first_name,sma_users.last_name,sma_sales.route_id,sma_sales.outlet_id,sma_sales.type,sma_sales.receipt_no,
            sma_categories.name as category,sma_products.product_details,sma_sale_items.quantity,sma_sale_items.subtotal as val')
        ->from('sma_sales')
        ->join('sma_sale_items','sma_sales.id = sma_sale_items.sale_id','inner')
        ->join('sma_products','sma_sale_items.product_id = sma_products.id','inner')
        ->join('sma_categories','sma_products.category_id = sma_categories.id')
        ->join('sma_users','sma_sales.created_by = sma_users.id','inner');
    }
    $this->db->where('pos !=', 1);
    if($this->session->userdata('group_id') == 5) {
        $this->db->where('sma_sales.created_by', $this->session->userdata('user_id'));
    } elseif($this->session->userdata('group_id') == 3) {
        $this->db->where('sma_sales.customer_id', $this->session->userdata('user_id'));
    }

    $q = $this->db->get();
        if($q->num_rows() > 0) {
            foreach (($q->result()) as $row) {
                $data[] = $row;
            }

            return $data;
        }

    }

}
