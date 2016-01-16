<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Reports_model extends CI_Model {

    public function __construct() {
        parent::__construct();
    }

    public function getProductNames($term, $limit = 5) {
        $this->db->select('id, code, name')
                ->like('name', $term, 'both')->or_like('code', $term, 'both');
        $this->db->limit($limit);
        $q = $this->db->get('products');
        if($q->num_rows() > 0) {
            foreach (($q->result()) as $row) {
                $data[] = $row;
            }
            return $data;
        }
        return FALSE;
    }

    public function getStaff() {
        if($this->Admin) { $this->db->where('group_id !=', 1); }
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
    
    public function getSalesTotals($customer_id) {

        $this->db->select('SUM(COALESCE(grand_total, 0)) as total_amount, SUM(COALESCE(paid, 0)) as paid', FALSE)
            ->where('customer_id', $customer_id);
        $q = $this->db->get('sales');
        if($q->num_rows() > 0) {
            return $q->row();
        }
        return FALSE;
    }
    
    public function getCustomerSales($customer_id) {
        $this->db->from('sales')->where('customer_id', $customer_id);
        return $this->db->count_all_results();
    }
    
    public function getCustomerQuotes($customer_id) {
        $this->db->from('quotes')->where('customer_id', $customer_id);
        return $this->db->count_all_results();
    }

    public function getStockValue() {
        $q = $this->db->query("SELECT SUM(by_price) as stock_by_price, SUM(by_cost) as stock_by_cost FROM ( Select COALESCE(sum(".$this->db->dbprefix('warehouses_products').".quantity), 0)*price as by_price, COALESCE(sum(".$this->db->dbprefix('warehouses_products').".quantity), 0)*cost as by_cost FROM ".$this->db->dbprefix('products')." JOIN ".$this->db->dbprefix('warehouses_products')." ON ".$this->db->dbprefix('warehouses_products').".product_id=".$this->db->dbprefix('products').".id GROUP BY ".$this->db->dbprefix('products').".id )a");
        if($q->num_rows() > 0) {
            return $q->row();
        }
        return FALSE;
    }

    public function getWarehouseStockValue($id) {
        $q = $this->db->query("SELECT SUM(by_price) as stock_by_price, SUM(by_cost) as stock_by_cost FROM ( Select sum(COALESCE(".$this->db->dbprefix('warehouses_products').".quantity, 0))*price as by_price, sum(COALESCE(".$this->db->dbprefix('warehouses_products').".quantity, 0))*cost as by_cost FROM ".$this->db->dbprefix('products')." JOIN ".$this->db->dbprefix('warehouses_products')." ON ".$this->db->dbprefix('warehouses_products').".product_id=".$this->db->dbprefix('products').".id WHERE ".$this->db->dbprefix('warehouses_products').".warehouse_id = ? GROUP BY ".$this->db->dbprefix('products').".id )a", array($id));
        if($q->num_rows() > 0) {
            return $q->row();
        }
        return FALSE;
    }

    public function getmonthlyPurchases() {
        $myQuery = "SELECT (CASE WHEN date_format( date, '%b' ) Is Null THEN 0 ELSE date_format( date, '%b' ) END) as month, SUM( COALESCE( total, 0 ) ) AS purchases FROM purchases WHERE date >= date_sub( now( ) , INTERVAL 12 MONTH ) GROUP BY date_format( date, '%b' ) ORDER BY date_format( date, '%m' ) ASC";
        $q = $this->db->query($myQuery);
        if($q->num_rows() > 0) {
            foreach (($q->result()) as $row) {
                $data[] = $row;
            }
            return $data;
        }
        return FALSE;
    }

    public function getChartData() {
        $myQuery = "SELECT S.month,
        COALESCE(S.sales, 0) as sales,
        COALESCE( P.purchases, 0 ) as purchases,
        COALESCE(S.tax1, 0) as tax1,
        COALESCE(S.tax2, 0) as tax2,
        COALESCE( P.ptax, 0 ) as ptax
        FROM (  SELECT  date_format(date, '%Y-%m') Month,
                SUM(total) Sales,
                SUM(product_tax) tax1,
                SUM(order_tax) tax2
                FROM ".$this->db->dbprefix('sales')."
                WHERE date >= date_sub( now( ) , INTERVAL 12 MONTH )
                GROUP BY date_format(date, '%Y-%m')) S
            LEFT JOIN ( SELECT  date_format(date, '%Y-%m') Month,
                        SUM(product_tax) ptax,
                        SUM(order_tax) otax,
                        SUM(total) purchases
                        FROM ".$this->db->dbprefix('purchases')."
                        GROUP BY date_format(date, '%Y-%m')) P
            ON S.Month = P.Month
            GROUP BY S.Month
            ORDER BY S.Month";
        $q = $this->db->query($myQuery);
        if($q->num_rows() > 0) {
            foreach (($q->result()) as $row) {
                $data[] = $row;
            }
            return $data;
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

    public function getAllCustomers() {
        $q = $this->db->get('customers');
        if($q->num_rows() > 0) {
            foreach (($q->result()) as $row) {
                $data[] = $row;
            }
            return $data;
        }
        return FALSE;
    }

    public function getAllBillers() {
        $q = $this->db->get('billers');
        if($q->num_rows() > 0) {
            foreach (($q->result()) as $row) {
                $data[] = $row;
            }
            return $data;
        }
        return FALSE;
    }

    public function getAllSuppliers() {
        $q = $this->db->get('suppliers');
        if($q->num_rows() > 0) {
            foreach (($q->result()) as $row) {
                $data[] = $row;
            }
            return $data;
        }
        return FALSE;
    }

    public function getDailySales($year, $month) {
        $myQuery = "SELECT DATE_FORMAT( date,  '%e' ) AS date, SUM( COALESCE( product_tax, 0 ) ) AS tax1, SUM( COALESCE( order_tax, 0 ) ) AS tax2, SUM( COALESCE( grand_total, 0 ) ) AS total, SUM( COALESCE( total_discount, 0 ) ) AS discount
			FROM ".$this->db->dbprefix('sales')."
			WHERE DATE_FORMAT( date,  '%Y-%m' ) =  '{$year}-{$month}'
			GROUP BY DATE_FORMAT( date,  '%e' )";
        $q = $this->db->query($myQuery, false);
        if($q->num_rows() > 0) {
            foreach (($q->result()) as $row) {
                $data[] = $row;
            }
            return $data;
        }
        return FALSE;
    }

    public function getMonthlySales($year) {
        $myQuery = "SELECT DATE_FORMAT( date,  '%c' ) AS date, SUM( COALESCE( product_tax, 0 ) ) AS tax1, SUM( COALESCE( order_tax, 0 ) ) AS tax2, SUM( COALESCE( grand_total, 0 ) ) AS total
			FROM ".$this->db->dbprefix('sales')."
			WHERE DATE_FORMAT( date,  '%Y' ) =  '{$year}' 
			GROUP BY date_format( date, '%c' ) ORDER BY date_format( date, '%c' ) ASC";
        $q = $this->db->query($myQuery, false);
        if($q->num_rows() > 0) {
            foreach (($q->result()) as $row) {
                $data[] = $row;
            }
            return $data;
        }
        return FALSE;
    }

    public function getStaffDailySales($user_id, $year, $month) {
        $myQuery = "SELECT DATE_FORMAT( date,  '%e' ) AS date, SUM( COALESCE( product_tax, 0 ) ) AS tax1, SUM( COALESCE( order_tax, 0 ) ) AS tax2, SUM( COALESCE( grand_total, 0 ) ) AS total, SUM( COALESCE( total_discount, 0 ) ) AS discount
            FROM ".$this->db->dbprefix('sales')."
            WHERE created_by = {$user_id} AND DATE_FORMAT( date,  '%Y-%m' ) =  '{$year}-{$month}'
            GROUP BY DATE_FORMAT( date,  '%e' )";
        $q = $this->db->query($myQuery, false);
        if($q->num_rows() > 0) {
            foreach (($q->result()) as $row) {
                $data[] = $row;
            }
            return $data;
        }
        return FALSE;
    }

    public function getStaffMonthlySales($user_id, $year) {
         $myQuery = "SELECT DATE_FORMAT( date,  '%c' ) AS date, SUM( COALESCE( product_tax, 0 ) ) AS tax1, SUM( COALESCE( order_tax, 0 ) ) AS tax2, SUM( COALESCE( grand_total, 0 ) ) AS total
            FROM ".$this->db->dbprefix('sales')."
            WHERE created_by = {$user_id} AND DATE_FORMAT( date,  '%Y' ) =  '{$year}' 
            GROUP BY date_format( date, '%c' ) ORDER BY date_format( date, '%c' ) ASC";
        $q = $this->db->query($myQuery, false);
        if($q->num_rows() > 0) {
            foreach (($q->result()) as $row) {
                $data[] = $row;
            }
            //print_r($data);exit;
            return $data;
        }
        return FALSE;
    }

    public function getStaffTargetSales($user_id, $year){
         $myQuery = "SELECT DATE_FORMAT( date, '%c' ) AS date, sum(grand_total) as total,target

            from sma_sales s
            join sma_targets t on t.u_id = s.created_by
            where created_by = $user_id
            and DATE_FORMAT( date, '%Y' ) = $year
            and date_format( date, '%c' ) = date_format( t_date, '%c' )
            group by DATE_FORMAT( date, '%c' )
            ORDER BY date_format( date, '%c' ) ASC";

        $q = $this->db->query($myQuery, false);
        if($q->num_rows() > 0) {
            foreach (($q->result()) as $row) {
                $data[] = $row;
            }
            //print_r($data);exit;
            return $data;
        }
        return FALSE;
    }

    public function get2($user_id,$year){
        $sales = array();
        $targets = array();
        $query1 = "Select DATE_FORMAT( date, '%c' ) as month,sum(grand_total)
                    from sma_sales
                    where created_by = $user_id
                    and DATE_FORMAT( date, '%Y' ) = $year
                    group by DATE_FORMAT( date, '%c' )";
        $q = $this->db->query($query1, false);
        if($q->num_rows() > 0) {
            foreach (($q->result()) as $row) {
                $sales[] = $row;
            }
        }

        $query2 = "SELECT DATE_FORMAT( date, '%c' ) as month,sum(target) as sum
                    from sma_category_targets
                    where u_id = $user_id
                    and DATE_FORMAT( date, '%Y' ) = $year
                    group by DATE_FORMAT( date, '%c' )";

        $q = $this->db->query($query2, false);
        if($q->num_rows() > 0) {
            foreach (($q->result()) as $row) {
                $targets[] = $row;
            }
        }

        return $this->getOutput($sales,$targets);
}

public function getTag($category, $user,$date){

        $strs = strtotime($date);

        $month = date("m",$strs);
        $year = date("Y",$strs);


        $query = "SELECT sma_category_targets.target FROM sma_category_targets
        where 
        sma_category_targets.u_id in ($user) AND
        sma_category_targets.category_id = $category AND
        Date_format(sma_category_targets.date ,'%c') = $month AND
        Date_format(sma_category_targets.date ,'%Y') = $year";

        $q = $this->db->query($query,false);
        if($q != NULL) {

            if($q->num_rows() > 0){

                foreach (($q->result()) as $row) {
                    return $row->target;
                }
            }
        }else{
    return 0;}

        //return $target;
}

public function getSal($category, $user,$date){
        $strs = strtotime($date);

        $month = date("m",$strs);
        $year = date("Y",$strs);

    $query = "SELECT DISTINCT(sma_sales.id),SUM(sma_sale_items.net_unit_price * sma_sale_items.quantity) as total FROM sma_sales, sma_sale_items, sma_products
    where 
    sma_sales.id = sma_sale_items.sale_id AND
    sma_sale_items.product_id = sma_products.id AND
    sma_sales.created_by in ($user) AND
    sma_products.category_id = $category AND
    Date_format(sma_sales.date ,'%c') = $month AND
    Date_format(sma_sales.date ,'%Y') = $year";

    $q = $this->db->query($query,false);
    if($q != NULL){
        if($q->num_rows() > 0) {
            foreach (($q->result()) as $row) {
                return $row->total;
            }
        }
    }else{
        return 0;
        }

}


    public function getPurchasesTotals($supplier_id) {
        $this->db->select('SUM(COALESCE(grand_total, 0)) as total_amount, SUM(COALESCE(paid, 0)) as paid', FALSE)
            ->where('supplier_id', $supplier_id);
        $q = $this->db->get('purchases');
        if($q->num_rows() > 0) {
            return $q->row();
        }
        return FALSE;
    }
    
    public function getSupplierPurchases($supplier_id) {
        $this->db->from('purchases')->where('supplier_id', $supplier_id);
        return $this->db->count_all_results();
    }

    public function getStaffPurchases($user_id) {
        $this->db->select('count(id) as total, SUM(COALESCE(grand_total, 0)) as total_amount, SUM(COALESCE(paid, 0)) as paid', FALSE)
            ->where('created_by', $user_id);
        $q = $this->db->get('purchases');
        if($q->num_rows() > 0) {
            return $q->row();
        }
        return FALSE;
    }
    
    public function getStaffSales($user_id) {
        $this->db->select('count(id) as total, SUM(COALESCE(grand_total, 0)) as total_amount, SUM(COALESCE(paid, 0)) as paid', FALSE)
            ->where('created_by', $user_id);
        $q = $this->db->get('sales');
        if($q->num_rows() > 0) {
            return $q->row();
        }
        return FALSE;
    }

    public function getTotalSales($start, $end) {
        $this->db->select('count(id) as total, sum(COALESCE(grand_total, 0)) as total_amount, SUM(COALESCE(paid, 0)) as paid, SUM(COALESCE(total_tax, 0)) as tax', FALSE)
        ->where('sale_status !=', 'pending')
        ->where('date BETWEEN '.$start.' and '.$end);
        $q = $this->db->get('sales');
        if($q->num_rows() > 0) {
            return $q->row();
        }
        return FALSE;
    }

    public function getTotalPurchases($start, $end) {
        $this->db->select('count(id) as total, sum(COALESCE(grand_total, 0)) as total_amount, SUM(COALESCE(paid, 0)) as paid, SUM(COALESCE(total_tax, 0)) as tax', FALSE)
        ->where('status', 'received')
        ->where('date BETWEEN '.$start.' and '.$end);
        $q = $this->db->get('purchases');
        if($q->num_rows() > 0) {
            return $q->row();
        }
        return FALSE;
    }

    public function getTotalExpenses($start, $end) {
        $this->db->select('count(id) as total, sum(COALESCE(amount, 0)) as total_amount', FALSE)
        ->where('date BETWEEN '.$start.' and '.$end);
        $q = $this->db->get('expenses');
        if($q->num_rows() > 0) {
            return $q->row();
        }
        return FALSE;
    }

    public function getTotalPaidAmount($start, $end) {
        $this->db->select('count(id) as total, SUM(COALESCE(amount, 0)) as total_amount', FALSE)
        ->where('type', 'sent')
        ->where('date BETWEEN '.$start.' and '.$end);
        $q = $this->db->get('payments');
        if($q->num_rows() > 0) {
            return $q->row();
        }
        return FALSE;
    }

    public function getTotalReceivedAmount($start, $end) {
        $this->db->select('count(id) as total, SUM(COALESCE(amount, 0)) as total_amount', FALSE)
        ->where('type', 'received')
        ->where('date BETWEEN '.$start.' and '.$end);
        $q = $this->db->get('payments');
        if($q->num_rows() > 0) {
            return $q->row();
        }
        return FALSE;
    }

    public function getTotalReceivedCashAmount($start, $end) {
        $this->db->select('count(id) as total, SUM(COALESCE(amount, 0)) as total_amount', FALSE)
        ->where('type', 'received')->where('paid_by', 'cash')
        ->where('date BETWEEN '.$start.' and '.$end);
        $q = $this->db->get('payments');
        if($q->num_rows() > 0) {
            return $q->row();
        }
        return FALSE;
    }

    public function getTotalReceivedCCAmount($start, $end) {
        $this->db->select('count(id) as total, SUM(COALESCE(amount, 0)) as total_amount', FALSE)
        ->where('type', 'received')->where('paid_by', 'CC')
        ->where('date BETWEEN '.$start.' and '.$end);
        $q = $this->db->get('payments');
        if($q->num_rows() > 0) {
            return $q->row();
        }
        return FALSE;
    }

    public function getTotalReceivedChequeAmount($start, $end) {
        $this->db->select('count(id) as total, SUM(COALESCE(amount, 0)) as total_amount', FALSE)
        ->where('type', 'received')->where('paid_by', 'Cheque')
        ->where('date BETWEEN '.$start.' and '.$end);
        $q = $this->db->get('payments');
        if($q->num_rows() > 0) {
            return $q->row();
        }
        return FALSE;
    }

    public function getTotalReceivedPPPAmount($start, $end) {
        $this->db->select('count(id) as total, SUM(COALESCE(amount, 0)) as total_amount', FALSE)
        ->where('type', 'received')->where('paid_by', 'ppp')
        ->where('date BETWEEN '.$start.' and '.$end);
        $q = $this->db->get('payments');
        if($q->num_rows() > 0) {
            return $q->row();
        }
        return FALSE;
    }

    public function getTotalReceivedStripeAmount($start, $end) {
        $this->db->select('count(id) as total, SUM(COALESCE(amount, 0)) as total_amount', FALSE)
        ->where('type', 'received')->where('paid_by', 'stripe')
        ->where('date BETWEEN '.$start.' and '.$end);
        $q = $this->db->get('payments');
        if($q->num_rows() > 0) {
            return $q->row();
        }
        return FALSE;
    }

    public function getTotalReturnedAmount($start, $end) {
        $this->db->select('count(id) as total, SUM(COALESCE(amount, 0)) as total_amount', FALSE)
        ->where('type', 'returned')
        ->where('date BETWEEN '.$start.' and '.$end);
        $q = $this->db->get('payments');
        if($q->num_rows() > 0) {
            return $q->row();
        }
        return FALSE;
    }

    public function getWarehouseTotals($warehouse_id = NULL) {
        $this->db->select('sum(quantity) as total_quantity, count(id) as total_items', FALSE);
        $this->db->where('quantity !=', 0);
        if($warehouse_id) {
            $this->db->where('warehouse_id', $warehouse_id);
        }
        $q = $this->db->get('warehouses_products');
        if($q->num_rows() > 0) {
            return $q->row();
        }
        return FALSE;
    }

    function getOutput($salesArray, $targetsArray)
{
    $result = [];

    //Adding months from targets
    $i = 0;
    foreach ($targetsArray as $targets) {
        $targetArray = (array) $targets;
        array_push($result, [$targetArray['month'], '0', '0']);
        $i++;
    }

    //Adding months form sales
    foreach ($salesArray as $sales) {

        $saleArray = (array) $sales;

        $add = TRUE;

        foreach ($result as $row) {

            if (in_array($saleArray['month'], $row)) {
                $add = FALSE;
            }
        }

        if ($add == TRUE) {
            array_push($result, [$saleArray['month'], '0', '0']);
        }

        $i++;
    }

    //Adding Sales Amount
    foreach ($salesArray as $sales) {

        $saleArray = (array) $sales;

        $add = TRUE;

        $i = 0;
        foreach ($result as $row) {

            if ($row[0] == $saleArray['month']) {
                $result[$i][1] = $saleArray['sum(grand_total)'];
            }
            $i++;
        }

        $i++;
    }

    //Addding Target Value
    foreach ($targetsArray as $targets) {

        $targetArray = (array) $targets;

        $add = TRUE;

        $i = 0;

        foreach ($result as $row) {

            if ($row[0] == $targetArray['month']) {
                $result[$i][2] = $targetArray['sum'];
            }
            $i++;
        }

        $i++;
    }

    return $result;
}


    function getDistributorTargetTable($distId){

       return  $users = $this->db->select('id')
        ->from('users')
        ->where('biller_id',$distId)
        ->get()->result();

    }

    function getALLDistributorTargetTable(){

        $dist = $this->db->select('id')
        ->from('companies')
        ->where('group_name','biller')
        ->get()->result();

        print_r($dist);

        foreach ($dist as $value) {
            $this->getDistributorTargetTable($value);
        }

    }

}
