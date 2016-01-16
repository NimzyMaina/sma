<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Products_model extends CI_Model {

    public function __construct() {
        parent::__construct();
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
    
    public function getProductOptions($pid) {
        $q = $this->db->get_where('product_variants', array('product_id' => $pid));
        if($q->num_rows() > 0) {
            foreach (($q->result()) as $row) {
                $data[] = $row;
            }
            return $data;
        }
        return FALSE;
    }

        public function getProductOptions2($pid) {
        $q = $this->db->get_where('van_product_variants', array('product_id' => $pid));
        if($q->num_rows() > 0) {
            foreach (($q->result()) as $row) {
                $data[] = $row;
            }
            return $data;
        }
        return FALSE;
    }
    
    public function getProductOptionsWithWH($pid) {
        $this->db->select($this->db->dbprefix('product_variants').'.*, '.$this->db->dbprefix('warehouses').'.name as wh_name, '.$this->db->dbprefix('warehouses').'.id as warehouse_id, '.$this->db->dbprefix('warehouses_products_variants').'.quantity as wh_qty')
        ->join('warehouses_products_variants', 'warehouses_products_variants.option_id=product_variants.id', 'left')
        ->join('warehouses', 'warehouses.id=warehouses_products_variants.warehouse_id', 'left')
        ->group_by(array(''.$this->db->dbprefix('product_variants').'.id', ''.$this->db->dbprefix('warehouses_products_variants').'.warehouse_id'))
        ->order_by('product_variants.id');
        $q = $this->db->get_where('product_variants', array('product_variants.product_id' => $pid, 'warehouses_products_variants.quantity !=' => NULL));
        if($q->num_rows() > 0) {
            foreach (($q->result()) as $row) {
                $data[] = $row;
            }

            return $data;
        }
    }
    
    public function getProductComboItems($pid) {
        $this->db->select($this->db->dbprefix('products').'.id as id, '.$this->db->dbprefix('products').'.code as code, '.$this->db->dbprefix('combo_items').'.quantity as qty, '.$this->db->dbprefix('products').'.name as name')->join('products', 'products.code=combo_items.item_code', 'left')->group_by('combo_items.id');
        $q = $this->db->get_where('combo_items', array('product_id' => $pid));
        if($q->num_rows() > 0) {
            foreach (($q->result()) as $row) {
                $data[] = $row;
            }

            return $data;
        }
        return FALSE;
    }

    public function getProductByID($id) {
        $q = $this->db->get_where('products', array('id' => $id), 1);
        if($q->num_rows() > 0) {
            return $q->row();
        }
        return FALSE;
    }

    public function getProductDetails($id) {
        $this->db->select($this->db->dbprefix('products').'.code, '.$this->db->dbprefix('products').'.name, '.$this->db->dbprefix('categories').'.code as category_code, cost, price, quantity, alert_quantity')
        ->join('categories', 'categories.id=products.category_id', 'left');
        $q = $this->db->get_where('products', array('products.id' => $id), 1);
        if($q->num_rows() > 0) {
            return $q->row();
        }
        return FALSE;
    }

    public function getProductDetail($id) {
        $this->db->select($this->db->dbprefix('products').'.*, '.$this->db->dbprefix('tax_rates').'.code as tax_rate_code, '.$this->db->dbprefix('categories').'.code as category_code, '.$this->db->dbprefix('subcategories').'.code as subcategory_code')
        ->join('tax_rates', 'tax_rates.id=products.tax_rate', 'left')
        ->join('categories', 'categories.id=products.category_id', 'left')
        ->join('subcategories', 'subcategories.id=products.subcategory_id', 'left');
        $q = $this->db->get_where('products', array('products.id' => $id), 1);
        if($q->num_rows() > 0) {
            return $q->row();
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
    
    public function getAllWarehousesWithPQ($product_id) {
        $this->db->select(''.$this->db->dbprefix('warehouses').'.*, '.$this->db->dbprefix('warehouses_products').'.quantity, '.$this->db->dbprefix('warehouses_products').'.rack')
        ->join('warehouses_products', 'warehouses_products.warehouse_id=warehouses.id', 'left')
        ->where('warehouses_products.product_id', $product_id)
        ->group_by('warehouses.id');
        $q = $this->db->get('warehouses');
        if($q->num_rows() > 0) {
            foreach (($q->result()) as $row) {
                $data[] = $row;
            }
            return $data;
        }
        return FALSE;
    }
    
    public function getProductPhotos($id) {
        $q = $this->db->get_where("product_photos", array('product_id' => $id));
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

    public function addProduct($data, $items, $warehouse_qty, $product_attributes, $photos) {

        if($this->db->insert('products', $data)) {
            $product_id = $this->db->insert_id();
            
            if($items) {
                foreach($items as $item) {
                    $item['product_id'] = $product_id;
                    $this->db->insert('combo_items', $item);
                }
            }
            
            if($data['type'] == 'combo' || $data['type'] == 'service') {
                $warehouses = $this->site->getAllWarehouses();
                foreach($warehouses as $warehouse) {
                    $this->db->insert('warehouses_products', array('product_id' => $product_id, 'warehouse_id' => $warehouse->id, 'quantity' => 0));
                }
            } 

            $tax_rate = $this->site->getTaxRateByID($data['tax_rate']);

            if($warehouse_qty && !empty($warehouse_qty)) {
                foreach($warehouse_qty as $wh_qty) {
                    if($wh_qty['quantity'] != 0) {
                        $this->addQuantity($product_id, $wh_qty['warehouse_id'], $wh_qty['quantity']);
                    }
                }
                if(!$product_attributes) {
                    $tax_rate_id = $tax_rate ? $tax_rate->id : NULL;
                    $tax = $tax_rate ? (($tax_rate->type == 1) ? $tax_rate->rate . "%" : $tax_rate->rate) : NULL;
                        //$item_net_cost = $data['tax_method'] == 1 ? $data['cost'] : $data['cost']-();
                    if($tax_rate) {
                        if($tax_rate->type == 1 && $tax_rate->rate != 0) {
                            if($data['tax_method'] == '0') {
                                $pr_tax_val = ($data['cost'] * $tax_rate->rate) / (100 + $tax_rate->rate);
                                $net_item_cost = $data['cost'] - $pr_tax_val;
                                $item_tax = $pr_tax_val * $data['quantity'];
                            } else {
                                $net_item_cost = $data['cost'];
                                $item_tax = ((($data['quantity'] * $net_item_cost) * $tax_rate->rate) / 100);
                            }
                        } else {
                            $net_item_cost = $data['cost'];
                            $item_tax = $tax_rate->rate;
                        }
                    } else {
                        $net_item_cost = $data['cost'];
                        $item_tax = 0;
                    }

                    $subtotal = (($net_item_cost * $data['quantity']) + $item_tax);

                    foreach($warehouse_qty as $wh_qty) {
                        $item = array(
                            'product_id' => $product_id,
                            'product_code' => $data['code'],
                            'product_name' => $data['name'],
                            'net_unit_cost' => $net_item_cost,
                            'quantity' => $wh_qty['quantity'],
                            'quantity_balance' => $wh_qty['quantity'],
                            'item_tax' => $item_tax,
                            'tax_rate_id' => $tax_rate_id,
                            'tax' => $tax,
                            'subtotal' => $subtotal,
                            'warehouse_id' => $wh_qty['warehouse_id'],
                            'date' => date('Y-m-d'),
                            'status' => 'received',
                            );
                        $this->db->insert('purchase_items', $item);
                    }
                }
            }

            if($product_attributes) {
                foreach($product_attributes as $pr_attr) {
                    $pr_attr_details = $this->getPrductVariantByPIDandName($product_id, $pr_attr['name']);

                    $pr_attr['product_id'] = $product_id;
                    $variant_warehouse_id = $pr_attr['warehouse_id'];
                    unset($pr_attr['warehouse_id']);
                    if($pr_attr_details) {
                        $option_id = $pr_attr_details->id;
                    } else {
                        $this->db->insert('product_variants', $pr_attr);
                        $option_id = $this->db->insert_id();
                    }
                    if($pr_attr['quantity'] != 0) {
                        $this->db->insert('warehouses_products_variants', array('option_id' => $option_id, 'product_id' => $product_id, 'warehouse_id' => $variant_warehouse_id, 'quantity' => $pr_attr['quantity']));

                        $tax_rate_id = $tax_rate ? $tax_rate->id : NULL;
                        $tax = $tax_rate ? (($tax_rate->type == 1) ? $tax_rate->rate . "%" : $tax_rate->rate) : NULL;
                        //$item_net_cost = $data['tax_method'] == 1 ? $data['cost'] : $data['cost']-();
                        if($tax_rate) {
                            if($tax_rate->type == 1 && $tax_rate->rate != 0) {
                                if($data['tax_method'] == '0') {
                                    $pr_tax_val = ($data['cost'] * $tax_rate->rate) / (100 + $tax_rate->rate);
                                    $net_item_cost = $data['cost'] - $pr_tax_val;
                                    $item_tax = $pr_tax_val * $pr_attr['quantity'];
                                } else {
                                    $net_item_cost = $data['cost'];
                                    $item_tax = ((($pr_attr['quantity'] * $net_item_cost) * $tax_rate->rate) / 100);
                                }
                            } else {
                                $net_item_cost = $data['cost'];
                                $item_tax = $tax_rate->rate;
                            }
                        } else {
                            $net_item_cost = $data['cost'];
                            $item_tax = 0;
                        }

                        $subtotal = (($net_item_cost * $pr_attr['quantity']) + $item_tax);
                        $item = array(
                            'product_id' => $product_id,
                            'product_code' => $data['code'],
                            'product_name' => $data['name'],
                            'net_unit_cost' => $net_item_cost,
                            'quantity' => $pr_attr['quantity'],
                            'option_id' => $option_id,
                            'quantity_balance' => $pr_attr['quantity'],
                            'item_tax' => $item_tax,
                            'tax_rate_id' => $tax_rate_id,
                            'tax' => $tax,
                            'subtotal' => $subtotal,
                            'warehouse_id' => $variant_warehouse_id,
                            'date' => date('Y-m-d'),
                            'status' => 'received',
                            );
                        $this->db->insert('purchase_items', $item);

                    }
                }
                $this->site->syncVariantQty($option_id);
            }

            if($photos) {
                foreach($photos as $photo) {
                    $this->db->insert('product_photos', array('product_id' =>$product_id, 'photo' => $photo));
                }
            }

            return true;
        } 

        return false;

    }

    public function getPrductVariantByPIDandName($product_id, $name) {
        $q = $this->db->get_where('product_variants', array('product_id' => $product_id, 'name' => $name), 1);
        if($q->num_rows() > 0) {
            return $q->row();
        }

        return FALSE;
    }

    public function addAjaxProduct($data) {

        if($this->db->insert('products', $data)) {
            $product_id = $this->db->insert_id();
            return $this->getProductByID($product_id);
        } 

        return false;

    }

    public function add_products($products = array()) {
        if(!empty($products)) {
            foreach($products as $product) {
                $variants = explode('|', $product['variants']);
                unset($product['variants']);
                if($this->db->insert('products', $product)) {
                    $product_id = $this->db->insert_id();
                    foreach ($variants as $variant) {
                        if($variant && trim($variant) != '') {
                            $vat = array('product_id' => $product_id, 'name' => trim($variant));
                            $this->db->insert('product_variants', $vat);
                        }
                    }
                } 
            }
            return true;
        }
        return false;
    }

    public function getProductNames($term, $limit = 5) {
        $this->db->select(''.$this->db->dbprefix('products').'.id, code, '.$this->db->dbprefix('products').'.name as name, '.$this->db->dbprefix('product_variants').'.name as vname')
        ->where("type != 'combo' AND "
            . "(".$this->db->dbprefix('products').".name LIKE '%" . $term . "%' OR code LIKE '%" . $term . "%' OR  
                concat(".$this->db->dbprefix('products').".name, ' (', code, ')') LIKE '%" . $term . "%')");
        $this->db->join('product_variants', 'product_variants.product_id=products.id', 'left')
        ->where(''.$this->db->dbprefix('product_variants').'.name', NULL)
        ->group_by('products.id')->limit($limit);
        $q = $this->db->get('products');
        if($q->num_rows() > 0) {
            foreach (($q->result()) as $row) {
                $data[] = $row;
            }
            return $data;
        }
    }

    public function updateProduct($id, $data, $items, $warehouse_qty, $product_attributes, $photos) {

        if($this->db->update('products', $data, array('id' => $id))) {

            if($items) {
                $this->db->delete('combo_items', array('product_id' => $id));
                foreach($items as $item) {
                    $item['product_id'] = $id;
                    $this->db->insert('combo_items', $item);
                }
            }

            $tax_rate = $this->site->getTaxRateByID($data['tax_rate']);

            if($warehouse_qty && !empty($warehouse_qty)) {
                foreach($warehouse_qty as $wh_qty) {
                    $this->addQuantity($id, $wh_qty['warehouse_id'], $wh_qty['quantity'], $wh_qty['rack']);
                }
                if(!$product_attributes) {
                    $tax_rate_id = $tax_rate ? $tax_rate->id : NULL;
                    $tax = $tax_rate ? (($tax_rate->type == 1) ? $tax_rate->rate . "%" : $tax_rate->rate) : NULL;
                        //$item_net_cost = $data['tax_method'] == 1 ? $data['cost'] : $data['cost']-();
                    if($tax_rate) {
                        if($tax_rate->type == 1 && $tax_rate->rate != 0) {
                            if($data['tax_method'] == '0') {
                                $pr_tax_val = ($data['cost'] * $tax_rate->rate) / (100 + $tax_rate->rate);
                                $net_item_cost = $data['cost'] - $pr_tax_val;
                                $item_tax = $pr_tax_val * $data['quantity'];
                            } else {
                                $net_item_cost = $data['cost'];
                                $item_tax = ((($data['quantity'] * $net_item_cost) * $tax_rate->rate) / 100);
                            }
                        } else {
                            $net_item_cost = $data['cost'];
                            $item_tax = $tax_rate->rate;
                        }
                    } else {
                        $net_item_cost = $data['cost'];
                        $item_tax = 0;
                    }
                    $subtotal = (($net_item_cost * $data['quantity']) + $item_tax);
                    foreach($warehouse_qty as $wh_qty) {

                        if($purchase_item = $this->getPurchasedItemDetails($id, $wh_qty['warehouse_id'])) {
                            $quantity_balance = $wh_qty['quantity']-$purchase_item->quantity+$purchase_item->quantity_balance;
                            $item = array(
                                'net_unit_cost' => $net_item_cost,
                                'quantity' => $wh_qty['quantity'],
                                'quantity_balance' => $quantity_balance,
                                'item_tax' => $item_tax,
                                'tax_rate_id' => $tax_rate_id,
                                'tax' => $tax,
                                'subtotal' => $subtotal,
                                'warehouse_id' => $wh_qty['warehouse_id'],
                                'date' => date('Y-m-d'),
                                );
                            $this->db->update('purchase_items', $item, array('id' => $purchase_item->id));

                        } else {

                            $item = array(
                                'product_id' => $id,
                                'product_code' => $data['code'],
                                'product_name' => $data['name'],
                                'net_unit_cost' => $net_item_cost,
                                'quantity' => $wh_qty['quantity'],
                                'quantity_balance' => $wh_qty['quantity'],
                                'item_tax' => $item_tax,
                                'tax_rate_id' => $tax_rate_id,
                                'tax' => $tax,
                                'subtotal' => $subtotal,
                                'warehouse_id' => $wh_qty['warehouse_id'],
                                'date' => date('Y-m-d'),
                                'status' => 'received',
                                );
                            $this->db->insert('purchase_items', $item);

                        }
                    }
                } else {
                    //delete the purchase item where option id is null
                }
            }

            if($product_attributes) {
                foreach($product_attributes as $pr_attr) {
                    $pr_attr_details = $this->getPrductVariantByPIDandName($id, $pr_attr['name']);
                    $pr_attr['product_id'] = $id;
                    $variant_warehouse_id = $pr_attr['warehouse_id'];
                    unset($pr_attr['warehouse_id']);
                    if($pr_attr_details) {
                        $option_id = $pr_attr_details->id;
                    } else {
                        $this->db->insert('product_variants', $pr_attr);
                        $option_id = $this->db->insert_id();
                    }
                    $this->updateProductOptionQuantity($option_id, $variant_warehouse_id, $pr_attr['quantity'], $id);

                    //if($pr_attr['quantity'] != 0) {
                        $tax_rate_id = $tax_rate ? $tax_rate->id : NULL;
                        $tax = $tax_rate ? (($tax_rate->type == 1) ? $tax_rate->rate . "%" : $tax_rate->rate) : NULL;
                        //$item_net_cost = $data['tax_method'] == 1 ? $data['cost'] : $data['cost']-();
                        if($tax_rate) {
                            if($tax_rate->type == 1 && $tax_rate->rate != 0) {
                                if($data['tax_method'] == '0') {
                                    $pr_tax_val = ($data['cost'] * $tax_rate->rate) / (100 + $tax_rate->rate);
                                    $net_item_cost = $data['cost'] - $pr_tax_val;
                                    $item_tax = $pr_tax_val * $pr_attr['quantity'];
                                } else {
                                    $net_item_cost = $data['cost'];
                                    $item_tax = ((($pr_attr['quantity'] * $net_item_cost) * $tax_rate->rate) / 100);
                                }
                            } else {
                                $net_item_cost = $data['cost'];
                                $item_tax = $tax_rate->rate;
                            }
                        } else {
                            $net_item_cost = $data['cost'];
                            $item_tax = 0;
                        }

                        $subtotal = (($net_item_cost * $pr_attr['quantity']) + $item_tax);

                        if($purchase_item = $this->getPurchasedItemDetailsWithOption($id, $variant_warehouse_id, $option_id)) {
                            $quantity_balance = $pr_attr['quantity']-$purchase_item->quantity+$purchase_item->quantity_balance;
                            $item = array(
                                'net_unit_cost' => $net_item_cost,
                                'quantity' => $pr_attr['quantity'],
                                'option_id' => $option_id,
                                'quantity_balance' => $quantity_balance,
                                'item_tax' => $item_tax,
                                'tax_rate_id' => $tax_rate_id,
                                'tax' => $tax,
                                'subtotal' => $subtotal,
                                'date' => date('Y-m-d'),
                                );
                            $this->db->update('purchase_items', $item, array('id' => $purchase_item->id));

                        } else {

                            $item = array(
                                'product_id' => $id,
                                'product_code' => $data['code'],
                                'product_name' => $data['name'],
                                'net_unit_cost' => $net_item_cost,
                                'quantity' => $pr_attr['quantity'],
                                'option_id' => $option_id,
                                'quantity_balance' => $pr_attr['quantity'],
                                'item_tax' => $item_tax,
                                'tax_rate_id' => $tax_rate_id,
                                'tax' => $tax,
                                'subtotal' => $subtotal,
                                'warehouse_id' => $variant_warehouse_id,
                                'date' => date('Y-m-d'),
                                'status' => 'received',
                                );
                            $this->db->insert('purchase_items', $item);

                        }
                    //}
                }
                $this->site->syncVariantQty($option_id);
            }

            if($photos) {
                foreach($photos as $photo) {
                    $this->db->insert('product_photos', array('product_id' =>$id, 'photo' => $photo));
                }
            }

            return true;
        } else {
            return false;
        }
    }

    public function updateProductOptionQuantity($option_id, $warehouse_id, $quantity, $product_id) {
        if($option = $this->getProductWarehouseOptionQty($option_id, $warehouse_id)) {
            if($this->db->update('warehouses_products_variants', array('quantity' => $quantity), array('option_id' => $option_id, 'warehouse_id' => $warehouse_id))) {
                $this->site->syncVariantQty($option_id);
                return TRUE;
            }
        } else {
            if($this->db->insert('warehouses_products_variants', array('option_id' => $option_id, 'product_id' => $product_id, 'warehouse_id' => $warehouse_id, 'quantity' => $quantity))) {
                $this->site->syncVariantQty($option_id);
                return TRUE;
            }
        }
        return FALSE;
    }

    public function getPurchasedItemDetails($product_id, $warehouse_id) {
        $q = $this->db->get_where('purchase_items', array('product_id' => $product_id, 'purchase_id' => NULL, 'option_id' => NULL, 'transfer_id' => NULL, 'warehouse_id' => $warehouse_id), 1);
        if($q->num_rows() > 0) {
            return $q->row();
        }

        return FALSE;
    }

    public function getPurchasedItemDetailsWithOption($product_id, $warehouse_id, $option_id) {
        $q = $this->db->get_where('purchase_items', array('product_id' => $product_id, 'purchase_id' => NULL, 'transfer_id' => NULL, 'warehouse_id' => $warehouse_id, 'option_id' => $option_id), 1);
        if($q->num_rows() > 0) {
            return $q->row();
        }

        return FALSE;
    }

    public function updatePrice($data = array()) {

        if($this->db->update_batch('products', $data, 'code')) {
            return true;
        } else {
            return false;
        }
    }

    public function deleteProduct($id) {
        if($this->db->delete('products', array('id' => $id)) && $this->db->delete('warehouses_products', array('product_id' => $id)) && $this->db->delete('warehouses_products_variants', array('product_id' => $id))) {
            return true;
        }
        return FALSE;
    }


    public function totalCategoryProducts($category_id) {
        $q = $this->db->get_where('products', array('category_id' => $category_id));

        return $q->num_rows();
    }

    public function getSubcategoryByID($id) {
        $q = $this->db->get_where('subcategories', array('id' => $id), 1);
        if($q->num_rows() > 0) {
            return $q->row();
        }

        return FALSE;
    }

    public function getCategoryByCode($code) {
        $q = $this->db->get_where('categories', array('code' => $code), 1);
        if($q->num_rows() > 0) {
            return $q->row();
        }

        return FALSE;
    }

    public function getSubcategoryByCode($code) {

        $q = $this->db->get_where('subcategories', array('code' => $code), 1);
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

    public function getSubCategories() {
        $this->db->select('id as id, name as text');
        $q = $this->db->get("subcategories");
        if($q->num_rows() > 0) {
            foreach (($q->result()) as $row) {
                $data[] = $row;
            }

            return $data;
        }

        return FALSE;
    }

    public function getSubCategoriesForCategoryID($category_id) {
        $this->db->select('id as id, name as text');
        $q = $this->db->get_where("subcategories", array('category_id' => $category_id));
        if($q->num_rows() > 0) {
            foreach (($q->result()) as $row) {
                $data[] = $row;
            }

            return $data;
        }

        return FALSE;
    }

    public function getSubCategoriesByCategoryID($category_id) {
        $q = $this->db->get_where("subcategories", array('category_id' => $category_id));
        if($q->num_rows() > 0) {
            foreach (($q->result()) as $row) {
                $data[] = $row;
            }

            return $data;
        }

        return FALSE;
    }

    public function getDamagePdByID($id) {

        $q = $this->db->get_where('damage_products', array('id' => $id), 1);
        if($q->num_rows() > 0) {
            return $q->row();
        }

        return FALSE;
    }

    public function addDamage($product_id, $date, $quantity, $warehouse, $note, $option = NULL) {

        $prd = $this->getProductByID($product_id);
        $nQTY = $prd->quantity - $quantity;

        $data = array(
            'date' => $date,
            'product_id' => $product_id,
            'option_id' => $option,
            'quantity' => $quantity,
            'warehouse_id' => $warehouse,
            'note' => $note,
            'user' => $this->session->userdata('username')
            );

        if($this->db->insert('damage_products', $data)) {
            if($wh_qty_details = $this->getProductQuantity($product_id, $warehouse)) {
                $balance_qty = $wh_qty_details['quantity'] - $quantity;
                $this->updateQuantity($product_id, $warehouse, $balance_qty);
            } else {
                $balance_qty = 0 - $quantity;
                $this->insertQuantity($product_id, $warehouse, $balance_qty);
            }
            if($option) {
                if($op_wh_qty = $this->getProductWarehouseOptionQty($option, $warehouse)) {
                    $op_balance_qty = $op_wh_qty->quantity - $quantity;
                    $this->db->update('warehouses_products_variants', array('quantity' => $op_balance_qty), array('option_id' => $option, 'warehouse_id' => $warehouse));
                    $this->syncVariantQty($option);
                } else {
                    $op_balance_qty = 0 - $quantity;
                    $wh_pr_op = array('option_id' => $option, 'product_id' => $product_id, 'warehouse_id' => $warehouse, 'quantity' => $op_balance_qty);
                    $this->db->insert('warehouses_products_variants', $wh_pr_op);
                    $this->syncVariantQty($option);
                }
            }
            return true;
        } else {
            return false;
        }
    }

    public function updateDamage($id, $product_id, $date, $quantity, $warehouse, $note, $option) {

        $wh_qty_details = $this->getProductQuantity($product_id, $warehouse);
        $dp_details = $this->getDamagePdByID($id);
        $old_quantity = $wh_qty_details['quantity'] + $dp_details->quantity;
        $balance_qty = $old_quantity - $quantity;
        $prd = $this->getProductByID($product_id);
        $nQTY = ($prd->quantity + $dp_details->quantity) - $quantity;
        if($option) {
            if($op_wh_qty = $this->getProductWarehouseOptionQty($option, $warehouse)) {
                $old_op_quantity = $op_wh_qty->quantity + $dp_details->quantity;
                $op_balance_qty = $old_op_quantity - $quantity;
                $this->db->update('warehouses_products_variants', array('quantity' => $op_balance_qty), array('option_id' => $option, 'warehouse_id' => $warehouse));
                $this->syncVariantQty($option);
            } else {
                $op_balance_qty = 0 - $quantity;
                $wh_pr_op = array('option_id' => $option, 'product_id' => $product_id, 'warehouse_id' => $warehouse, 'quantity' => $op_balance_qty);
                $this->db->insert('warehouses_products_variants', $wh_pr_op);
                $this->syncVariantQty($option);
            }
        }
        $data = array(
            'product_id' => $product_id,
            'quantity' => $quantity,
            'warehouse_id' => $warehouse,
            'note' => $note,
            'user' => USER_NAME
            );
        if($date) { $data['date'] = $date; }
        if($this->db->update('damage_products', $data, array('id' => $id)) && $this->updateQuantity($product_id, $warehouse, $balance_qty)) {
            return true;
        } else {
            return false;
        }
    }

    public function getProductQuantity($product_id, $warehouse) {
        $q = $this->db->get_where('warehouses_products', array('product_id' => $product_id, 'warehouse_id' => $warehouse), 1);
        if($q->num_rows() > 0) {
            return $q->row_array(); //$q->row();
        }
        return FALSE;
    }

    public function addQuantity($product_id, $warehouse_id, $quantity, $rack = NULL) {

        if($this->getProductQuantity($product_id, $warehouse_id)) {
            if($this->updateQuantity($product_id, $warehouse_id, $quantity, $rack)) {
                return TRUE;
            }
        } else {
            if($this->insertQuantity($product_id, $warehouse_id, $quantity, $rack)) {
                return TRUE;
            }
        }

        return FALSE;
    }

    public function insertQuantity($product_id, $warehouse_id, $quantity, $rack = NULL) {
        if($this->db->insert('warehouses_products', array( 'product_id' => $product_id, 'warehouse_id' => $warehouse_id, 'quantity' => $quantity, 'rack' => $rack ))) {
            $this->site->syncProductQty($product_id);
            return true;
        } 
        return false;
    }

    public function updateQuantity($product_id, $warehouse_id, $quantity, $rack = NULL) {     
        $data = $rack ? array( 'quantity' => $quantity, 'rack' => $rack ) : $data = array( 'quantity' => $quantity );
        if($this->db->update('warehouses_products', $data, array('product_id' => $product_id, 'warehouse_id' => $warehouse_id))) {
            $this->site->syncProductQty($product_id);
            return true;
        } 
        return false;
    }

    public function deleteDamage($id) {
        $dp_details = $this->getDamagePdByID($id);
        $wh_qty_details = $this->getProductQuantity($dp_details->product_id, $dp_details->warehouse_id);
        $old_quantity = $wh_qty_details['quantity'] + $dp_details->quantity;
        if($this->updateQuantity($dp_details->product_id, $dp_details->warehouse_id, $old_quantity) && $this->db->delete('damage_products', array('id' => $id))) {
            if($dp_details->option_id) {
                if($op_wh_qty = $this->getProductWarehouseOptionQty($dp_details->option_id, $dp_details->warehouse_id)) {
                    $old_op_quantity = $op_wh_qty->quantity + $dp_details->quantity;
                    $this->db->update('warehouses_products_variants', array('quantity' => $old_op_quantity), array('option_id' => $dp_details->option_id, 'warehouse_id' => $dp_details->warehouse_id));
                    $this->syncVariantQty($dp_details->option_id);
                } 
            }
            return true;
        }

        return false;
    }

    public function products_count($category_id, $subcategory_id = NULL) {
        if($category_id) {
            $this->db->where('category_id', $category_id);
        }
        if($subcategory_id) {
            $this->db->where('subcategory_id', $subcategory_id);
        }
        $this->db->from('products');
        return $this->db->count_all_results();
    }

    public function fetch_products($category_id, $limit, $start, $subcategory_id = NULL) {

        $this->db->limit($limit, $start);
        if($category_id) {
            $this->db->where('category_id', $category_id);
        }
        if($subcategory_id) {
            $this->db->where('subcategory_id', $subcategory_id);
        }
        $this->db->order_by("id", "asc");
        $query = $this->db->get("products");

        if($query->num_rows() > 0) {
            foreach ($query->result() as $row) {
                $data[] = $row;
            }
            return $data;
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

    public function syncVariantQty($option_id) {
        $wh_pr_vars = $this->getProductWarehouseOptions($option_id);
        $qty = 0;
        foreach ($wh_pr_vars as $row) {
            $qty += $row->quantity;
        }
        if($this->db->update('product_variants', array('quantity' => $qty), array('id' => $option_id))) {
            return TRUE;
        }
        return FALSE;
    }

    public function getProductWarehouseOptions($option_id) {
        $q = $this->db->get_where('warehouses_products_variants', array('option_id' => $option_id));
        if($q->num_rows() > 0) {
            foreach (($q->result()) as $row) {
                $data[] = $row;
            }
            return $data;
        }
        return FALSE;
    }

    public function setRack($data) {
        if($this->db->update('warehouses_products', array('rack' => $data['rack']), array('product_id' => $data['product_id'], 'warehouse_id' => $data['warehouse_id']))) {
            return TRUE;
        }
        return FALSE;
    }

    public function getSoldQty($id) {
        $this->db->select("date_format(".$this->db->dbprefix('sales').".date, '%Y-%M') month, SUM( ".$this->db->dbprefix('sale_items').".quantity ) as sold, SUM( ".$this->db->dbprefix('sale_items').".subtotal ) as amount")
        ->from('sales')
        ->join('sale_items', 'sales.id=sale_items.sale_id', 'left')
        ->group_by("date_format(".$this->db->dbprefix('sales').".date, '%Y-%m')")
        ->where($this->db->dbprefix('sale_items').'.product_id', $id)
        //->where('DATE(NOW()) - INTERVAL 1 MONTH')
        ->where('DATE_ADD(curdate(), INTERVAL 1 MONTH)')
        ->order_by("date_format(".$this->db->dbprefix('sales').".date, '%Y-%m') desc")->limit(3);
        $q = $this->db->get();
        if($q->num_rows() > 0) {
            foreach (($q->result()) as $row) {
                $data[] = $row;
            }
            return $data;
        }
        return FALSE;
    }

    public function getPurchasedQty($id) {
        $this->db->select("date_format(".$this->db->dbprefix('purchases').".date, '%Y-%M') month, SUM( ".$this->db->dbprefix('purchase_items').".quantity ) as purchased, SUM( ".$this->db->dbprefix('purchase_items').".subtotal ) as amount")
        ->from('purchases')
        ->join('purchase_items', 'purchases.id=purchase_items.purchase_id', 'left')
        ->group_by("date_format(".$this->db->dbprefix('purchases').".date, '%Y-%m')")
        ->where($this->db->dbprefix('purchase_items').'.product_id', $id)
        //->where('DATE(NOW()) - INTERVAL 1 MONTH')
        ->where('DATE_ADD(curdate(), INTERVAL 1 MONTH)')
        ->order_by("date_format(".$this->db->dbprefix('purchases').".date, '%Y-%m') desc")->limit(3);
        $q = $this->db->get();
        if($q->num_rows() > 0) {
            foreach (($q->result()) as $row) {
                $data[] = $row;
            }
            return $data;
        }
        return FALSE;
    }

    public function getAllVariants() {
        $q = $this->db->get('variants');
        if($q->num_rows() > 0) {
            foreach (($q->result()) as $row) {
                $data[] = $row;
            }
            return $data;
        }
        return FALSE;
    }

    public function getAllProductsByWarehouse() {
        $q = $this->db->get('products')
                ->where('');
        if($q->num_rows() > 0) {
            foreach (($q->result()) as $row) {
                $data[] = $row;
            }

            return $data;
        }
    }

    function test ($warehouse_id = NULL){

        // if(!$this->Owner && !$warehouse_id) {
        //     $user = $this->site->getUser();
        //     $warehouse_id = $user->warehouse_id;
        // }

        // if(!$warehouse_id) {
        //     $this->db
        //     ->select($this->db->dbprefix('warehouses_products').".product_id as productid, ".$this->db->dbprefix('products').".image as image,tax_rate,category_id,subcategory_id,type, ".$this->db->dbprefix('products').".code as code, ".$this->db->dbprefix('products').".name as name, ".$this->db->dbprefix('categories').".name as cname, cost as cost, price as price, ".$this->db->dbprefix('warehouses_products').".quantity as quantity, unit, ".$this->db->dbprefix('warehouses_products').".rack as rack, alert_quantity", FALSE)
        //     ->from('warehouses_products')
        //     ->join('products', 'products.id=warehouses_products.product_id', 'left')
        //     ->join('categories', 'products.category_id=categories.id', 'left')
        //     //->where('warehouses_products.warehouse_id', $warehouse_id)
        //     ->where('warehouses_products.quantity !=', 0)
        //     ->group_by("warehouses_products.product_id");
        // } else {
            $this->db
            ->select($this->db->dbprefix('products').".id as productid, ".$this->db->dbprefix('products').".image as image,tax_rate,category_id,subcategory_id,type, ".$this->db->dbprefix('products').".code as code, ".$this->db->dbprefix('products').".name as name, ".$this->db->dbprefix('categories').".name as cname, cost as cost, price as price, COALESCE(quantity, 0) as quantity, unit, NULL as rack, alert_quantity", FALSE)
            ->from('products')
            ->join('categories', 'products.category_id=categories.id', 'left')
            ->group_by("products.id");
        //}

        $q = $this->db->get();
        if($q->num_rows() > 0) {
            foreach (($q->result()) as $row) {
                $data[] = $row;
            }

            return $data;
        }
    }
}
