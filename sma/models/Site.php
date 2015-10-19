<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Site extends CI_Model {

    public function __construct() {
        parent::__construct();
    }

    public function get_total_results() {
        $this->db->where('quantity < alert_quantity', NULL, FALSE)->where('track_quantity', 1)->from('products');
        return $this->db->count_all_results();
    }

    public function get_setting() {
        $q = $this->db->get('settings');
        if($q->num_rows() > 0) {
            return $q->row();
        }
        return FALSE;
    }

    public function getDateFormat($id) {
        $q = $this->db->get_where('date_format', array('id' => $id), 1);
        if($q->num_rows() > 0) {
            return $q->row();
        }
        return FALSE;
    }

    public function getAllCompanies($group_name) {
        $q = $this->db->get_where('companies', array('group_name' => $group_name));
        if($q->num_rows() > 0) {
            foreach (($q->result()) as $row) {
                $data[] = $row;
            }
            return $data;
        }
        return FALSE;
    }

    public function getCompanyByID($id) {
        $q = $this->db->get_where('companies', array('id' => $id), 1);
        if($q->num_rows() > 0) {
            return $q->row();
        }
        return FALSE;
    }

    public function getCustomerGroupByID($id) {
        $q = $this->db->get_where('customer_groups', array('id' => $id), 1);
        if($q->num_rows() > 0) {
            return $q->row();
        }
        return FALSE;
    }
    
    public function getUser($id = NULL) {
        if(!$id) { $id = $this->session->userdata('user_id'); }
        $q = $this->db->get_where('users', array('id' => $id), 1);
        if($q->num_rows() > 0) {
            return $q->row();
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

    public function getAllCurrencies() {
        $q = $this->db->get('currencies');
        if($q->num_rows() > 0) {
            foreach (($q->result()) as $row) {
                $data[] = $row;
            }
            return $data;
        }
        return FALSE;
    }

    public function getCurrencyByCode($code) {
        $q = $this->db->get_where('currencies', array('code' => $code), 1);
        if($q->num_rows() > 0) {
            return $q->row();
        }
        return FALSE;
    }

    public function getAllTaxRates() {
        $q = $this->db->get('tax_rates');
        if($q->num_rows() > 0) {
            foreach (($q->result()) as $row) {
                $data[] = $row;
            }
            return $data;
        }
        return FALSE;
    }

    public function getTaxRateByID($id) {
        $q = $this->db->get_where('tax_rates', array('id' => $id), 1);
        if($q->num_rows() > 0) {
            return $q->row();
        }
        return FALSE;
    }

    public function getAllWarehouses() {
        $q = $this->db->get('warehouses');
        if($q->num_rows() > 0) {
            foreach (($q->result()) as $row) {
                $data[] = $row;
            }
            return $data;
        }
        return FALSE;
    }

    public function getWarehouseByID($id) {
        $q = $this->db->get_where('warehouses', array('id' => $id), 1);
        if($q->num_rows() > 0) {
            return $q->row();
        }
        return FALSE;
    }
    
    public function getAllCategories() {
        $this->db->order_by('name');
        $q = $this->db->get('categories');
        if($q->num_rows() > 0) {
            foreach (($q->result()) as $row) {
                $data[] = $row;
            }
            return $data;
        }
        return FALSE;
    }

    public function getCategoryByID($id) {
        $q = $this->db->get_where('categories', array('id' => $id), 1);
        if($q->num_rows() > 0) {
            return $q->row();
        }
        return FALSE;
    }
    
    public function getGiftCardByID($id) {
        $q = $this->db->get_where('gift_cards', array('id' => $id), 1);
        if($q->num_rows() > 0) {
            return $q->row();
        }
        return FALSE;
    }
    
    public function getGiftCardByNO($no) {
        $q = $this->db->get_where('gift_cards', array('card_no' => $no), 1);
        if($q->num_rows() > 0) {
            return $q->row();
        }
        return FALSE;
    }

    public function updateInvoiceStatus() {
        $date = date('Y-m-d');
        $q = $this->db->get_where('invoices', array('status' => 'unpaid'));
        if($q->num_rows() > 0) {
            foreach (($q->result()) as $row) {
                if($row->due_date < $date) {
                    $this->db->update('invoices', array('status' => 'due'), array('id' => $row->id));
                }
            }
            $this->db->update('settings', array('update' => $date), array('setting_id' => '1'));
            return true;
        }
    }

    public function modal_js() {
        return '<script type="text/javascript">' . read_file($this->data['assets'] . 'js/modal.js') . '</script>';
    }
    
    public function getReference($field) {
        $q = $this->db->get_where('order_ref', array('ref_id' => '1'), 1);
        if($q->num_rows() > 0) {
            $ref = $q->row();
            switch ($field) {
                case 'so':
                    $prefix = $this->Settings->sales_prefix;
                    break;
                case 'qu':
                    $prefix = $this->Settings->quote_prefix;
                    break;
                case 'po':
                    $prefix = $this->Settings->purchase_prefix;
                    break;
                case 'to':
                    $prefix = $this->Settings->transfer_prefix;
                    break;
                case 'do':
                    $prefix = $this->Settings->delivery_prefix;
                    break;
                case 'pay':
                    $prefix = $this->Settings->payment_prefix;
                    break;
                case 'pos':
                    $prefix = isset($this->Settings->sales_prefix) ? $this->Settings->sales_prefix.'/POS' : 'POS';
                    break;
                case 're':
                    $prefix = $this->Settings->return_prefix;
                    break;
                default:
                    $prefix = '';
            }

            $ref_no = (! empty($prefix)) ? $prefix.'/' : '';

            if($this->Settings->reference_format == 1) {
                $ref_no .= date('Y')."/".sprintf("%04s", $ref->{$field});
            } elseif($this->Settings->reference_format == 2) {
                $ref_no .= date('Y')."/".date('m')."/".sprintf("%04s", $ref->{$field});
            } elseif($this->Settings->reference_format == 3) {
                $ref_no .= sprintf("%04s", $ref->{$field});
            } else {
                $ref_no .= $this->getRandomReference();
            }
            
            return $ref_no;
        }
        return FALSE;
    }

    public function getRandomReference($len = 12) {
        $result = '';
        for($i = 0; $i < $len; $i++) {
            $result .= mt_rand(0, 9);
        }

        if($this->getSaleByReference($result)) {
            $this->getRandomReference();
        }

        return $result;
    }

    public function getSaleByReference($ref) {
        $this->db->like('reference_no', $ref, 'before');
        $q = $this->db->get('sales', 1);
        if($q->num_rows() > 0) {
            return $q->row();
        }
        return FALSE;
    }
    
    public function updateReference($field) {
        $q = $this->db->get_where('order_ref', array('ref_id' => '1'), 1);
        if($q->num_rows() > 0) {
            $ref = $q->row();
            $this->db->update('order_ref', array($field => $ref->{$field}+1), array('ref_id' => '1'));
            return TRUE;
        }
        return FALSE;
    }
    
    public function checkPermissions() {
        $q = $this->db->get_where('permissions', array('group_id' => $this->session->userdata('group_id')), 1);
        if($q->num_rows() > 0) {
            return $q->result_array();
        }
        return FALSE;
    }
    public function getNotifications() 
    {
        $date = date('Y-m-d H:i:s', time());
        $this->db->where("from_date <=", $date);
        $this->db->where("till_date >=", $date);
        if(!$this->Owner) {
            if($this->Supplier) { $this->db->where('scope', 4); }
            elseif($this->Customer) { $this->db->where('scope', 1)->or_where('scope', 3); }
            elseif(!$this->Customer && !$this->Supplier) { $this->db->where('scope', 2)->or_where('scope', 3); }
        }
        $q = $this->db->get("notifications");
            if($q->num_rows() > 0) {
                    foreach (($q->result()) as $row) {
                            $data[] = $row;
                    }
                    return $data;
            }
    }
    
    public function getUpcomingEvents() {
        $dt = date('Y-m-d');
        $this->db->where('date >=', $dt)->order_by('date')->limit(5);
        if($this->Settings->restrict_calendar) {
            $q = $this->db->get_where('calendar', array('user_id' => $this->session->userdata('iser_id')));
        } else {
            $q = $this->db->get('calendar');
        }
        if($q->num_rows() > 0) {
            foreach (($q->result()) as $row) {
                $data[] = $row;
            }
            return $data;
        }
        return FALSE;
    }

    public function getUserGroup($user_id = false) {
        if(!$user_id) { $user_id = $this->session->userdata('user_id'); }
        $group_id = $this->getUserGroupID($user_id); 
        $q = $this->db->get_where('groups', array('id' => $group_id), 1);
        if($q->num_rows() > 0) {
            return $q->row();
        }
        return FALSE;
    }

    public function getUserGroupID($user_id = false) {
        $user = $this->getUser($user_id);
        return $user->group_id;
    }

    public function getWarehouseProductsVariants($option_id) {
        $q = $this->db->get_where('warehouses_products_variants', array('option_id' => $option_id));
        if($q->num_rows() > 0) {
            foreach (($q->result()) as $row) {
                $data[] = $row;
            }
            return $data;
        }
        return FALSE;
    }

    public function syncVariantQty($option_id) {
        $wh_pr_vars = $this->getWarehouseProductsVariants($option_id);
        $qty = 0;
        foreach ($wh_pr_vars as $row) {
            $qty += $row->quantity;
        }
        if($this->db->update('product_variants', array('quantity' => $qty), array('id' => $option_id))) {
            return TRUE;
        }
        return FALSE;
    }

    public function getWarehouseProducts($product_id) {
        $q = $this->db->get_where('warehouses_products', array('product_id' => $product_id));
        if($q->num_rows() > 0) {
            foreach (($q->result()) as $row) {
                $data[] = $row;
            }
            return $data;
        }
        return FALSE;
    }

    public function syncProductQty($product_id) {
        $wh_prs = $this->getWarehouseProducts($product_id);
        $qty = 0;
        foreach ($wh_prs as $row) {
            $qty += $row->quantity;
        }
        if($this->db->update('products', array('quantity' => $qty), array('id' => $product_id))) {
            return TRUE;
        }
        return FALSE;
    }

    public function getSaleByID($id) {
        $q = $this->db->get_where('sales', array('id' => $id), 1);
        if($q->num_rows() > 0) {
            return $q->row();
        }
        return FALSE;
    }

    public function getSalePayments($sale_id) {
        $q = $this->db->get_where('payments', array('sale_id' => $sale_id));
        if($q->num_rows() > 0) {
            foreach (($q->result()) as $row) {
                $data[] = $row;
            }
            return $data;
        }
        return FALSE;
    }

    public function syncSalePayments($id) {
        $sale = $this->getSaleByID($id);
        $payments = $this->getSalePayments($id);
        $paid = 0;
        foreach ($payments as $payment) {
            if($payment->type == 'returned') {
                $paid -= $payment->amount;
            } else {
                $paid += $payment->amount;
            }
        }
        
        $payment_status = $paid <= 0 ? 'pending' : $sale->payment_status;
        if($paid <= 0 && $sale->due_date <= date('Y-m-d')) {
            $payment_status = 'due';
        } elseif($this->sma->formatDecimal($sale->grand_total) > $this->sma->formatDecimal($paid) && $paid > 0) {
            $payment_status = 'partial';
        } elseif($this->sma->formatDecimal($sale->grand_total) <= $this->sma->formatDecimal($paid))  {
            $payment_status = 'paid';
        }

        if($this->db->update('sales', array('paid' => $paid, 'payment_status' => $payment_status), array('id' => $id))) {
            return true;
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

    public function getPurchasePayments($purchase_id) {
        $q = $this->db->get_where('payments', array('purchase_id' => $purchase_id));
        if($q->num_rows() > 0) {
            foreach (($q->result()) as $row) {
                $data[] = $row;
            }
            return $data;
        }
        return FALSE;
    }

    public function syncPurchasePayments($id) {
        $purchase = $this->getPurchaseByID($id);
        $payments = $this->getPurchasePayments($id);
        $paid = 0;
        foreach ($payments as $payment) {
            $paid += $payment->amount;
        }

        $payment_status = $paid <= 0 ? 'pending' : $purchase->payment_status;
        if($this->sma->formatDecimal($purchase->grand_total) > $this->sma->formatDecimal($paid) && $paid > 0) {
            $payment_status = 'partial';
        } elseif($this->sma->formatDecimal($purchase->grand_total) <= $this->sma->formatDecimal($paid))  {
            $payment_status = 'paid';
        }

        if($this->db->update('purchases', array('paid' => $paid, 'payment_status' => $payment_status), array('id' => $id))) {
            return true;
        }

        return FALSE;
    }

}

/* End of file site.php */ 
/* Location: ./application/models/site.php */
