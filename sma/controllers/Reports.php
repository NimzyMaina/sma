<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Reports extends MY_Controller {

	function __construct() {
		parent::__construct();

		if(!$this->loggedIn) {
			$this->session->set_userdata('requested_page', $this->uri->uri_string());
			redirect('login');
		}
		
		$this->lang->load('reports', $this->Settings->language);
		$this->load->library('form_validation'); 
		$this->load->model('reports_model');

	}

	function index() {
		$this->sma->checkPermissions();
		$data['error'] = (validation_errors()) ? validation_errors() : $this->session->flashdata('error');
		$this->data['monthly_sales'] = $this->reports_model->getChartData();
		$this->data['stock'] = $this->reports_model->getStockValue();
		$bc = array(array('link' => base_url(), 'page' => lang('home')), array('link' => '#', 'page' => lang('reports')));
		$meta = array('page_title' => lang('reports'), 'bc' => $bc);
		$this->page_construct('reports/index', $meta, $this->data);

	}

	function warehouse_stock($warehouse = NULL) {
		$this->sma->checkPermissions('index', TRUE);
		$data['error'] = (validation_errors()) ? validation_errors() : $this->session->flashdata('error');
		if($this->input->get('warehouse')){ $warehouse = $this->input->get('warehouse'); }
		//if(!$warehouse) { $warehouse = $this->Settings->default_warehouse; }

		$this->data['stock'] = $warehouse ? $this->reports_model->getWarehouseStockValue($warehouse) : $this->reports_model->getStockValue();
		$this->data['warehouses'] = $this->reports_model->getAllWarehouses();
		$this->data['warehouse_id'] = $warehouse;
		$this->data['warehouse'] = $warehouse ? $this->site->getWarehouseByID($warehouse) : NULL;
		$this->data['totals'] = $this->reports_model->getWarehouseTotals($warehouse);
		$bc = array(array('link' => base_url(), 'page' => lang('home')), array('link' => '#', 'page' => lang('reports')));
		$meta = array('page_title' => lang('reports'), 'bc' => $bc);
		$this->page_construct('reports/warehouse_stock', $meta, $this->data);

	}

	function expiry_alerts($warehouse_id = NULL) {
		$this->sma->checkPermissions('expiry_alerts');
		$this->data['error'] = (validation_errors()) ? validation_errors() : $this->session->flashdata('error');
			//if(!$warehouse_id) { $warehouse_id = $this->Settings->default_warehouse; }
		if($this->Owner) {
			$this->data['warehouses'] = $this->site->getAllWarehouses();
			$this->data['warehouse_id'] = $warehouse_id;
			$this->data['warehouse'] = $warehouse_id ? $this->site->getWarehouseByID($warehouse_id) : NULL;
		} else {
			$user = $this->site->getUser();
			$this->data['warehouses'] = NULL;
			$this->data['warehouse_id'] = $user->warehouse_id;
			$this->data['warehouse'] = $user->warehouse_id ? $this->site->getWarehouseByID($user->warehouse_id) : NULL;
		}

		$bc = array(array('link' => base_url(), 'page' => lang('home')), array('link' => site_url('reports'), 'page' => lang('reports')), array('link' => '#', 'page' => lang('product_expiry_alerts')));
		$meta = array('page_title' => lang('product_expiry_alerts'), 'bc' => $bc);
		$this->page_construct('reports/expiry_alerts', $meta, $this->data);
	}

	function getExpiryAlerts($warehouse_id = NULL) {
		$this->sma->checkPermissions('expiry_alerts', TRUE);
		$date = date('Y-m-d', strtotime('+3 months'));

		if(!$this->Owner && !$warehouse_id) {
			$user = $this->site->getUser();
			$warehouse_id = $user->warehouse_id;
		}

		$this->load->library('datatables');
		if($warehouse_id) {
			$this->datatables
			->select("products.image, product_code, product_name, quantity_balance, expiry")
			->from('purchase_items')
			->join('products', 'products.id=purchase_items.product_id', 'left')
			->where('warehouse_id', $warehouse_id)->where('expiry <', $date);
		} else {
			$this->datatables
			->select("products.image, product_code, product_name, quantity_balance, expiry")
			->from('purchase_items')
			->join('products', 'products.id=purchase_items.product_id', 'left')
			->where('expiry <', $date);
		}
		echo $this->datatables->generate();
	}

	function quantity_alerts($warehouse_id = NULL) {
		$this->sma->checkPermissions('quantity_alerts');
		$this->data['error'] = (validation_errors()) ? validation_errors() : $this->session->flashdata('error');
			//if(!$warehouse_id) { $warehouse_id = $this->Settings->default_warehouse; }
		if($this->Owner) {
			$this->data['warehouses'] = $this->site->getAllWarehouses();
			$this->data['warehouse_id'] = $warehouse_id;
			$this->data['warehouse'] = $warehouse_id ? $this->site->getWarehouseByID($warehouse_id) : NULL;
		} else {
			$user = $this->site->getUser();
			$this->data['warehouses'] = NULL;
			$this->data['warehouse_id'] = $user->warehouse_id;
			$this->data['warehouse'] = $user->warehouse_id ? $this->site->getWarehouseByID($user->warehouse_id) : NULL;
		}

		$bc = array(array('link' => base_url(), 'page' => lang('home')), array('link' => site_url('reports'), 'page' => lang('reports')), array('link' => '#', 'page' => lang('product_quantity_alerts')));
		$meta = array('page_title' => lang('product_quantity_alerts'), 'bc' => $bc);
		$this->page_construct('reports/quantity_alerts', $meta, $this->data);
	}

	function getQuantityAlerts($warehouse_id = NULL, $pdf = NULL, $xls = NULL) {
		$this->sma->checkPermissions('quantity_alerts', TRUE);
		if(!$this->Owner && !$warehouse_id) {
			$user = $this->site->getUser();
			$warehouse_id = $user->warehouse_id;
		}

		if($pdf || $xls) {

			if($warehouse_id) {
				$this->db
				->select('products.image as image, products.code, products.name, warehouses_products.quantity, alert_quantity')
				->from('products')->join('warehouses_products', 'warehouses_products.product_id=products.id', 'left')
				->where('alert_quantity > warehouses_products.quantity', NULL)
				->where('warehouse_id', $warehouse_id)
				->where('track_quantity', 1)
				->order_by('products.code desc');
			} else {
				$this->db
				->select('image, code, name, quantity, alert_quantity')
				->from('products')
				->where('alert_quantity > quantity', NULL)
				->where('track_quantity', 1)
				->order_by('code desc');
			}

			$q = $this->db->get();
			if($q->num_rows() > 0) {
				foreach (($q->result()) as $row) {
					$data[] = $row;
				}
			} else {
				$data = NULL;
			}

			if(!empty($data)) {

				$this->load->library('excel');
				$this->excel->setActiveSheetIndex(0);
				$this->excel->getActiveSheet()->setTitle(lang('product_quantity_alerts'));
				$this->excel->getActiveSheet()->SetCellValue('A1', lang('product_code'));
				$this->excel->getActiveSheet()->SetCellValue('B1', lang('product_name'));
				$this->excel->getActiveSheet()->SetCellValue('C1', lang('quantity'));
				$this->excel->getActiveSheet()->SetCellValue('D1', lang('alert_quantity'));

				$row = 2;
				foreach ($data as $data_row) {
					$this->excel->getActiveSheet()->SetCellValue('A' . $row, $data_row->code);
					$this->excel->getActiveSheet()->SetCellValue('B' . $row, $data_row->name);
					$this->excel->getActiveSheet()->SetCellValue('C' . $row, $data_row->quantity);
					$this->excel->getActiveSheet()->SetCellValue('D' . $row, $data_row->alert_quantity);
					$row++;
				}

				$this->excel->getActiveSheet()->getColumnDimension('A')->setWidth(35);
				$this->excel->getActiveSheet()->getColumnDimension('B')->setWidth(35);
				$this->excel->getActiveSheet()->getColumnDimension('C')->setWidth(25);
				$this->excel->getActiveSheet()->getColumnDimension('D')->setWidth(25);

				$filename = 'product_quantity_alerts';
				$this->excel->getDefaultStyle()->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
				if($pdf) {
					$styleArray = array(
						'borders' => array(
							'allborders' => array(
								'style' => PHPExcel_Style_Border::BORDER_THIN
								)
							)
						);
					$this->excel->getDefaultStyle()->applyFromArray($styleArray);
					$this->excel->getActiveSheet()->getPageSetup()->setOrientation(PHPExcel_Worksheet_PageSetup::ORIENTATION_LANDSCAPE);
					require_once (APPPATH . "third_party".DIRECTORY_SEPARATOR."MPDF".DIRECTORY_SEPARATOR."mpdf.php");
					$rendererName = PHPExcel_Settings::PDF_RENDERER_MPDF;
					$rendererLibrary = 'MPDF';
					$rendererLibraryPath = APPPATH . 'third_party'. DIRECTORY_SEPARATOR . $rendererLibrary;
					if(!PHPExcel_Settings::setPdfRenderer( $rendererName, $rendererLibraryPath )) {
						die('Please set the $rendererName: ' . $rendererName . ' and $rendererLibraryPath: ' . $rendererLibraryPath . ' values' .
							PHP_EOL . ' as appropriate for your directory structure');
					}

					header('Content-Type: application/pdf');
					header('Content-Disposition: attachment;filename="' . $filename . '.pdf"');
					header('Cache-Control: max-age=0');

					$objWriter = PHPExcel_IOFactory::createWriter($this->excel, 'PDF');
					$objWriter->save('php://output');
					exit();
				}
				if($xls) {
					ob_clean();
					header('Content-Type: application/vnd.ms-excel');
					header('Content-Disposition: attachment;filename="' . $filename . '.xls"');
					header('Cache-Control: max-age=0');
					ob_clean();
					$objWriter = PHPExcel_IOFactory::createWriter($this->excel, 'Excel5');
					$objWriter->save('php://output');
					exit();
				}

			}
			$this->session->set_flashdata('error', lang('nothing_found'));
			redirect($_SERVER["HTTP_REFERER"]);

		} else {

			$this->load->library('datatables');
			if($warehouse_id) {
				$this->datatables
				->select('products.image as image, products.code, products.name, warehouses_products.quantity, alert_quantity')
				->from('products')->join('warehouses_products', 'warehouses_products.product_id=products.id', 'left')
				->where('alert_quantity > warehouses_products.quantity', NULL)
				->where('warehouse_id', $warehouse_id)
				->where('track_quantity', 1);
			} else {
				$this->datatables
				->select('image, code, name, quantity, alert_quantity')
				->from('products')
				->where('alert_quantity > quantity', NULL)
				->where('track_quantity', 1);
			}

			echo $this->datatables->generate();

		}

	}

	function suggestions() {
		$term = $this->input->get('term', TRUE);
		if(strlen($term) < 1) {
			die();
		}

		$rows = $this->reports_model->getProductNames($term);
		if($rows) {
			foreach ($rows as $row) {
				$pr[] = array('id' => $row->id, 'label' => $row->name . " (" . $row->code . ")");

			}
			echo json_encode($pr);
		} else {
			echo FALSE;
		}
	}

	function products() {
		$this->sma->checkPermissions();
		$this->data['error'] = (validation_errors() ? validation_errors() : $this->session->flashdata('error'));
		$this->data['categories'] = $this->site->getAllCategories();
		if($this->input->post('start_date')){ $dt = "From ".$this->input->post('start_date')." to ".$this->input->post('end_date'); } else { $dt = "Till ".$this->input->post('end_date'); }
		$bc = array(array('link' => base_url(), 'page' => lang('home')), array('link' => site_url('reports'), 'page' => lang('reports')), array('link' => '#', 'page' => lang('products_report')));
		$meta = array('page_title' => lang('products_report'), 'bc' => $bc);
		$this->page_construct('reports/products', $meta, $this->data);
	}
	function getProductsReport($pdf = NULL, $xls = NULL) {
		$this->sma->checkPermissions('products', TRUE);
		if($this->input->get('product')){ $product = $this->input->get('product'); } else { $product = NULL; }
		if($this->input->get('cf1')){ $cf1 = $this->input->get('cf1'); } else { $cf1 = NULL; }
		if($this->input->get('cf2')){ $cf2 = $this->input->get('cf2'); } else { $cf2 = NULL; }
		if($this->input->get('cf3')){ $cf3 = $this->input->get('cf3'); } else { $cf3 = NULL; }
		if($this->input->get('cf4')){ $cf4 = $this->input->get('cf4'); } else { $cf4 = NULL; }
		if($this->input->get('cf5')){ $cf5 = $this->input->get('cf5'); } else { $cf5 = NULL; }
		if($this->input->get('cf6')){ $cf6 = $this->input->get('cf6'); } else { $cf6 = NULL; }
		if($this->input->get('category')){ $category = $this->input->get('category'); } else { $category = NULL; }
		if($this->input->get('start_date')){ $start_date = $this->input->get('start_date'); } else { $start_date = NULL; }
		if($this->input->get('end_date')){ $end_date = $this->input->get('end_date'); } else { $end_date = NULL; }
		if($start_date) {
			$start_date = $this->sma->fld($start_date);
			$end_date = $end_date ? $this->sma->fld($end_date) : date('Y-m-d');

			$pp =    "( SELECT pi.product_id, SUM( pi.quantity ) purchasedQty, SUM( pi.subtotal ) totalPurchase, p.date as pdate from ".$this->db->dbprefix('purchases')." p JOIN ".$this->db->dbprefix('purchase_items')." pi on p.id = pi.purchase_id where p.date >= '{$start_date}' and p.date < '{$end_date}' group by pi.product_id ) PCosts";
			$sp = "( SELECT si.product_id, SUM( si.quantity ) soldQty, SUM( si.subtotal ) totalSale, s.date as sdate from ".$this->db->dbprefix('sales')." s JOIN ".$this->db->dbprefix('sale_items')." si on s.id = si.sale_id where s.date >= '{$start_date}' and s.date < '{$end_date}' group by si.product_id ) PSales";
		} else {
			$pp ="( SELECT pi.product_id, SUM( pi.quantity ) purchasedQty, SUM( pi.subtotal ) totalPurchase from ".$this->db->dbprefix('purchase_items')." pi group by pi.product_id ) PCosts";
			$sp = "( SELECT si.product_id, SUM( si.quantity ) soldQty, SUM( si.subtotal ) totalSale from ".$this->db->dbprefix('sale_items')." si group by si.product_id ) PSales";
		}
		if($pdf || $xls) {

			$this->db
			->select($this->db->dbprefix('products').".code, ".$this->db->dbprefix('products').".name,
				COALESCE( PCosts.purchasedQty, 0 ) as PurchasedQty,
				COALESCE( PSales.soldQty, 0 ) as SoldQty,
				COALESCE( PCosts.totalPurchase, 0 ) as TotalPurchase,
				COALESCE( PSales.totalSale, 0 ) as TotalSales,
				(COALESCE( PSales.totalSale, 0 ) - COALESCE( PCosts.totalPurchase, 0 )) as Profit", FALSE)
			->from('products')
			->join($sp, 'products.id = PSales.product_id', 'left')
			->join($pp, 'products.id = PCosts.product_id', 'left')
			->order_by('products.name');

			if($product) { $this->db->where($this->db->dbprefix('products').".id", $product); }
			if($cf1) { $this->db->where($this->db->dbprefix('products').".cf1", $cf1); }
			if($cf2) { $this->db->where($this->db->dbprefix('products').".cf2", $cf2); }
			if($cf3) { $this->db->where($this->db->dbprefix('products').".cf3", $cf3); }
			if($cf4) { $this->db->where($this->db->dbprefix('products').".cf4", $cf4); }
			if($cf5) { $this->db->where($this->db->dbprefix('products').".cf5", $cf5); }
			if($cf6) { $this->db->where($this->db->dbprefix('products').".cf6", $cf6); }
			if($category) { $this->db->where($this->db->dbprefix('products').".category_id", $category); }

			$q = $this->db->get();
			if($q->num_rows() > 0) {
				foreach (($q->result()) as $row) {
					$data[] = $row;
				}
			} else {
				$data = NULL;
			}

			if(!empty($data)) {

				$this->load->library('excel');
				$this->excel->setActiveSheetIndex(0);
				$this->excel->getActiveSheet()->setTitle(lang('products_report'));
				$this->excel->getActiveSheet()->SetCellValue('A1', lang('product_code'));
				$this->excel->getActiveSheet()->SetCellValue('B1', lang('product_name'));
				$this->excel->getActiveSheet()->SetCellValue('C1', lang('purchased'));
				$this->excel->getActiveSheet()->SetCellValue('D1', lang('sold'));
				$this->excel->getActiveSheet()->SetCellValue('E1', lang('purchased_amount'));
				$this->excel->getActiveSheet()->SetCellValue('F1', lang('sold_amount'));
				$this->excel->getActiveSheet()->SetCellValue('G1', lang('profit_loss'));

				$row = 2;
				foreach ($data as $data_row) {
					$this->excel->getActiveSheet()->SetCellValue('A' . $row, $data_row->code);
					$this->excel->getActiveSheet()->SetCellValue('B' . $row, $data_row->name);
					$this->excel->getActiveSheet()->SetCellValue('C' . $row, $data_row->PurchasedQty);
					$this->excel->getActiveSheet()->SetCellValue('D' . $row, $data_row->SoldQty);
					$this->excel->getActiveSheet()->SetCellValue('E' . $row, $data_row->TotalPurchase);
					$this->excel->getActiveSheet()->SetCellValue('F' . $row, $data_row->TotalSales);
					$this->excel->getActiveSheet()->SetCellValue('G' . $row, $data_row->Profit);
					$row++;
				}

				$this->excel->getActiveSheet()->getColumnDimension('A')->setWidth(35);
				$this->excel->getActiveSheet()->getColumnDimension('B')->setWidth(35);
				$this->excel->getActiveSheet()->getColumnDimension('C')->setWidth(15);
				$this->excel->getActiveSheet()->getColumnDimension('D')->setWidth(15);
				$this->excel->getActiveSheet()->getColumnDimension('E')->setWidth(25);
				$this->excel->getActiveSheet()->getColumnDimension('F')->setWidth(25);
				$this->excel->getActiveSheet()->getColumnDimension('G')->setWidth(25);

				$filename = 'products_report';
				$this->excel->getDefaultStyle()->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
				if($pdf) {
					$styleArray = array(
						'borders' => array(
							'allborders' => array(
								'style' => PHPExcel_Style_Border::BORDER_THIN
								)
							)
						);
					$this->excel->getDefaultStyle()->applyFromArray($styleArray);
					$this->excel->getActiveSheet()->getPageSetup()->setOrientation(PHPExcel_Worksheet_PageSetup::ORIENTATION_LANDSCAPE);
					require_once (APPPATH . "third_party".DIRECTORY_SEPARATOR."MPDF".DIRECTORY_SEPARATOR."mpdf.php");
					$rendererName = PHPExcel_Settings::PDF_RENDERER_MPDF;
					$rendererLibrary = 'MPDF';
					$rendererLibraryPath = APPPATH . 'third_party'. DIRECTORY_SEPARATOR . $rendererLibrary;
					if(!PHPExcel_Settings::setPdfRenderer( $rendererName, $rendererLibraryPath )) {
						die('Please set the $rendererName: ' . $rendererName . ' and $rendererLibraryPath: ' . $rendererLibraryPath . ' values' .
							PHP_EOL . ' as appropriate for your directory structure');
					}

					header('Content-Type: application/pdf');
					header('Content-Disposition: attachment;filename="' . $filename . '.pdf"');
					header('Cache-Control: max-age=0');

					$objWriter = PHPExcel_IOFactory::createWriter($this->excel, 'PDF');
					$objWriter->save('php://output');
					exit();
				}
				if($xls) {
					ob_clean();
					header('Content-Type: application/vnd.ms-excel');
					header('Content-Disposition: attachment;filename="' . $filename . '.xls"');
					header('Cache-Control: max-age=0');
					ob_clean();
					$objWriter = PHPExcel_IOFactory::createWriter($this->excel, 'Excel5');
					$objWriter->save('php://output');
					exit();
				}

			}
			$this->session->set_flashdata('error', lang('nothing_found'));
			redirect($_SERVER["HTTP_REFERER"]);

		} else {

			$this->load->library('datatables');
			$this->datatables
			->select($this->db->dbprefix('products').".code, ".$this->db->dbprefix('products').".name,
				COALESCE( PCosts.purchasedQty, 0 ) as PurchasedQty,
				COALESCE( PSales.soldQty, 0 ) as SoldQty,
				COALESCE( PCosts.totalPurchase, 0 ) as TotalPurchase,
				COALESCE( PSales.totalSale, 0 ) as TotalSales,
				(COALESCE( PSales.totalSale, 0 ) - COALESCE( PCosts.totalPurchase, 0 )) as Profit", FALSE)
			->from('sma_products')
			->join($sp, 'sma_products.id = PSales.product_id', 'left')
			->join($pp, 'sma_products.id = PCosts.product_id', 'left');
			// ->group_by('products.id');

			if($product) { $this->datatables->where($this->db->dbprefix('products').".id", $product); }
			if($cf1) { $this->datatables->where($this->db->dbprefix('products').".cf1", $cf1); }
			if($cf2) { $this->datatables->where($this->db->dbprefix('products').".cf2", $cf2); }
			if($cf3) { $this->datatables->where($this->db->dbprefix('products').".cf3", $cf3); }
			if($cf4) { $this->datatables->where($this->db->dbprefix('products').".cf4", $cf4); }
			if($cf5) { $this->datatables->where($this->db->dbprefix('products').".cf5", $cf5); }
			if($cf6) { $this->datatables->where($this->db->dbprefix('products').".cf6", $cf6); }
			if($category) { $this->datatables->where($this->db->dbprefix('products').".category_id", $category); }

			echo $this->datatables->generate();

		}

	}

	function daily_sales($year = NULL, $month = NULL, $pdf = NULL, $user_id = NULL) {
		$this->sma->checkPermissions('daily_sales');
		if(!$year) { $year = date('Y'); }
		if(!$month) { $month = date('m'); }
		if(!$this->Owner && !$this->Admin) {
			$user_id = $this->session->userdata('user_id');
		}
		$this->data['error'] = (validation_errors()) ? validation_errors() : $this->session->flashdata('error');
		$config = array (
			'show_next_prev'  => TRUE,
			'next_prev_url'   => site_url('reports/daily_sales'),
			'month_type'   => 'long',
			'day_type'     => 'long'
			);

		$config['template'] = '{table_open}<table border="0" cellpadding="0" cellspacing="0" class="table table-bordered dfTable">{/table_open}
		{heading_row_start}<tr>{/heading_row_start}
		{heading_previous_cell}<th><a href="{previous_url}">&lt;&lt;</a></th>{/heading_previous_cell}
		{heading_title_cell}<th colspan="{colspan}" id="month_year">{heading}</th>{/heading_title_cell}
		{heading_next_cell}<th><a href="{next_url}">&gt;&gt;</a></th>{/heading_next_cell}
		{heading_row_end}</tr>{/heading_row_end}
		{week_row_start}<tr>{/week_row_start}
		{week_day_cell}<td class="cl_wday">{week_day}</td>{/week_day_cell}
		{week_row_end}</tr>{/week_row_end}
		{cal_row_start}<tr class="days">{/cal_row_start}
		{cal_cell_start}<td class="day">{/cal_cell_start}
		{cal_cell_content}
		<div class="day_num">{day}</div>
		<div class="content">{content}</div>
		{/cal_cell_content}
		{cal_cell_content_today}
		<div class="day_num highlight">{day}</div>
		<div class="content">{content}</div>
		{/cal_cell_content_today}
		{cal_cell_no_content}<div class="day_num">{day}</div>{/cal_cell_no_content}
		{cal_cell_no_content_today}<div class="day_num highlight">{day}</div>{/cal_cell_no_content_today}
		{cal_cell_blank}&nbsp;{/cal_cell_blank}
		{cal_cell_end}</td>{/cal_cell_end}
		{cal_row_end}</tr>{/cal_row_end}
		{table_close}</table>{/table_close}';

		$this->load->library('calendar', $config);
		$sales = $user_id ? $sales = $this->reports_model->getStaffDailySales($user_id, $year, $month) : $this->reports_model->getDailySales($year, $month);

		if(!empty($sales)) {
			foreach($sales as $sale) {
				$daily_sale[$sale->date] = "<table class='table table-bordered table-hover table-striped table-condensed data' style='margin:0;'><tr><td>".lang("discount")."</td><td>". $this->sma->formatMoney($sale->discount) ."</td></tr><tr><td>".lang("product_tax")."</td><td>". $this->sma->formatMoney($sale->tax1) ."</td></tr><tr><td>".lang("order_tax")."</td><td>". $this->sma->formatMoney($sale->tax2) ."</td></tr><tr><td>".lang("total")."</td><td>". $this->sma->formatMoney($sale->total) ."</td></tr></table>";	
			}
		} else { $daily_sale = array(); }

		$this->data['calender'] = $this->calendar->generate($year, $month, $daily_sale);
		$this->data['year'] = $year;
		$this->data['month'] = $month;
		if($pdf) {
			$html = $this->load->view($this->theme.'reports/daily', $this->data, true);
			$name = lang("daily_sales") . "_" . $year . "_" . $month . ".pdf";
			$html = str_replace('<p class="introtext">'. lang("reports_calendar_text") .'</p>', '', $html);
			$this->sma->generate_pdf($html, $name, null, null, null, null, null, 'L');
		}
		$bc = array(array('link' => base_url(), 'page' => lang('home')), array('link' => site_url('reports'), 'page' => lang('reports')), array('link' => '#', 'page' => lang('daily_sales_report')));
		$meta = array('page_title' => lang('daily_sales_report'), 'bc' => $bc);
		$this->page_construct('reports/daily', $meta, $this->data);

	}


	function monthly_sales($year = NULL, $pdf = NULL, $user_id = NULL) {
		$this->sma->checkPermissions('monthly_sales');
		if(!$year) { $year = date('Y'); }
		if(!$this->Owner && !$this->Admin) {
			$user_id = $this->session->userdata('user_id');
		}
		$this->load->language('calendar');
		$this->data['error'] = (validation_errors()) ? validation_errors() : $this->session->flashdata('error');
		$this->data['year'] = $year;
		$this->data['sales'] = $user_id ? $this->reports_model->getStaffMonthlySales($user_id, $year) : $this->reports_model->getMonthlySales($year);
		if($pdf) {
			$html = $this->load->view($this->theme.'reports/monthly', $this->data, true);
			$name = lang("monthly_sales") . "_" . $year . ".pdf";
			$html = str_replace('<p class="introtext">'. lang("reports_calendar_text") .'</p>', '', $html);
			$this->sma->generate_pdf($html, $name, null, null, null, null, null, 'L');
		}
		$bc = array(array('link' => base_url(), 'page' => lang('home')), array('link' => site_url('reports'), 'page' => lang('reports')), array('link' => '#', 'page' => lang('monthly_sales_report')));
		$meta = array('page_title' => lang('monthly_sales_report'), 'bc' => $bc);
		$this->page_construct('reports/monthly', $meta, $this->data);

	}

	function target_sales($year = NULL, $pdf = NULL, $user_id = NULL) {
		$this->sma->checkPermissions('monthly_sales');
		if(!$year) { $year = date('Y'); }
		if(!$this->Owner && !$this->Admin) {
			$user_id = $this->session->userdata('user_id');
		}
		else{
			if(null !== $this->session->userdata('temp')){
			$user_id = $this->session->userdata('temp');
			$year = $this->uri->segment(3);
		}
		}
		$this->load->language('calendar');
		$this->data['error'] = (validation_errors()) ? validation_errors() : $this->session->flashdata('error');
	    $this->data['year'] = $year;
		$this->data['sales'] = $user_id ? $this->reports_model->getStaffTargetSales($user_id, $year) : $this->reports_model->getMonthlySales($year);
		if($pdf) {
			$html = $this->load->view($this->theme.'reports/monthly2', $this->data, true);
			$name = lang("monthly_target") . "_" . $year . ".pdf";
			$html = str_replace('<p class="introtext">'. lang("reports_calendar_text") .'</p>', '', $html);
			$this->sma->generate_pdf($html, $name, null, null, null, null, null, 'L');
		}
		$bc = array(array('link' => base_url(), 'page' => lang('home')), array('link' => site_url('reports'), 'page' => lang('reports')), array('link' => '#', 'page' => lang('sales_target')));
		$meta = array('page_title' => lang('sales_target'), 'bc' => $bc);
		$this->page_construct('reports/monthly2', $meta, $this->data);

	}

	function sales() {
		$this->sma->checkPermissions('sales');
		$this->data['error'] = (validation_errors()) ? validation_errors() : $this->session->flashdata('error');	   
		$this->data['users'] = $this->reports_model->getStaff();
		$this->data['warehouses'] = $this->site->getAllWarehouses();
		$this->data['billers'] = $this->site->getAllCompanies('biller');
		$bc = array(array('link' => base_url(), 'page' => lang('home')), array('link' => site_url('reports'), 'page' => lang('reports')), array('link' => '#', 'page' => lang('sales_report')));
		$meta = array('page_title' => lang('sales_report'), 'bc' => $bc);
		$this->page_construct('reports/sales', $meta, $this->data);
	}

	function getSalesReport($pdf = NULL, $xls = NULL) {
		$this->sma->checkPermissions('sales', TRUE);
		if($this->input->get('product')){ $product = $this->input->get('product'); } else { $product = NULL; }
		if($this->input->get('user')){ $user = $this->input->get('user'); } else { $user = NULL; }
		if($this->input->get('customer')){ $customer = $this->input->get('customer'); } else { $customer = NULL; }
		if($this->input->get('biller')){ $biller = $this->input->get('biller'); } else { $biller = NULL; }
		if($this->input->get('warehouse')){ $warehouse = $this->input->get('warehouse'); } else { $warehouse = NULL; }
		if($this->input->get('reference_no')){ $reference_no = $this->input->get('reference_no'); } else { $reference_no = NULL; }
		if($this->input->get('start_date')){ $start_date = $this->input->get('start_date'); } else { $start_date = NULL; }
		if($this->input->get('end_date')){ $end_date = $this->input->get('end_date'); } else { $end_date = NULL; }
		if($this->input->get('product_code')){ $product_code = $this->input->get('product_code'); } else { $product_code = NULL; }
		if($start_date) {
			$start_date = $this->sma->fld($start_date);
			$end_date = $this->sma->fld($end_date);
		}
		if(!$this->Owner && !$this->Admin) {
			$user = $this->session->userdata('user_id');
		}

		if($pdf || $xls) {

			// $this->db
			// ->select("date, reference_no, biller, customer, GROUP_CONCAT(CONCAT(".$this->db->dbprefix('sale_items').".product_name, ' (', ".$this->db->dbprefix('sale_items').".quantity, ')') SEPARATOR '\n') as iname, grand_total, paid, payment_status", FALSE)
			// ->from('sales')
			// ->join('sale_items', 'sale_items.sale_id=sales.id', 'left')
			// ->join('warehouses', 'warehouses.id=sales.warehouse_id', 'left')

			// $this->db->select('sma_sales.date,sma_sales.reference_no,sma_sales.biller,concat(sma_users.first_name," ",sma_users.last_name) as full_name,sma_sales.route_id,sma_sales.outlet_id,sma_sales.type,sma_sales.receipt_no,
   //          sma_categories.name as category,sma_products.code,sma_products.name as product_name,sma_sale_items.quantity,sma_sale_items.subtotal as val',false)
   //      ->from('sma_sales')
   //      ->join('sma_sale_items','sma_sales.id = sma_sale_items.sale_id','inner')
   //      ->join('sma_products','sma_sale_items.product_id = sma_products.id','inner')
   //      ->join('sma_categories','sma_products.category_id = sma_categories.id')
   //      ->join('sma_users','sma_sales.created_by = sma_users.id','inner')

			$q = "SELECT c2.cf1 as biller_code, c2.state as region, s.biller, c.cf1, c.cf2, c.name, s.date, ca.name as category, si.product_code,  si.product_name, si.quantity, c.cf3, si.tax, u.company, u.code, concat(u.first_name,' ',u.last_name) as sp_name, s.reference_no, s.outlet_id, s.type, s.route_id, si.net_unit_price,si.unit_price , (si.quantity*si.net_unit_price) as subtotal
from sma_sale_items si
join sma_sales s on s.id = si.sale_id
join sma_companies c on c.id = s.customer_id
join sma_users u on u.id = s.created_by
join sma_products p on p.id = si.product_id
join sma_categories ca on ca.id = p.category_id
join sma_companies c2 on s.biller_id = c2.id WHERE s.id != 0 ";

			// $this->db->select('c2.cf1 as biller_code, s.biller, c.cf1, c.cf2, c.name, s.date, ca.name as category, si.product_code,  si.product_name, si.quantity, c.cf3, si.tax, u.company, u.code, concat(u.first_name," ",u.last_name) as sp_name, s.reference_no, s.outlet_id, s.type, s.route_id, si.net_unit_price si.unit_price, si.subtotal',false)
			// ->from('sale_items si')
			// ->join('sales s' , 's.id=si.sale_id')
			// ->join('companies c', 'c.id=s.customer_id')
			// ->join('users u','u.id = s.created_by')
			// ->join('products p','p.id = si.product_id')
			// ->join('categories ca','ca.id = p.category_id')
			// ->join('companies c2','s.biller_id = c2.id')

			// ->group_by('s.id')
			// ->order_by('s.date desc');

			//if($user) { $this->db->where('s.created_by', $user); }
			if($user) { $q .= " AND s.created_by = $user "; }
			//if($product) { $this->db->like('si.product_id', $product); }
		if($product) {$q .= " AND si.product_id = $product"; }
			//if($biller) { $this->db->where('s.biller_id', $biller); }
			if($biller) { $q .= "AND s.biller_id = $biller "; }
			//if($customer) { $this->db->where('s.customer_id', $customer); }
		if($customer) { $q .= " AND s.customer_id = $customer "; }
			//if($warehouse) { $this->db->where('s.warehouse_id', $warehouse); }
		if($warehouse) { $q .= " AND s.warehouse_id = $warehouse "; }
			//if($reference_no) { $this->db->like('s.reference_no', $reference_no, 'both'); }
		if($reference_no) { $q .= " AND s.reference_no LIKE '%".$reference_no."%' "; }
			//if($start_date) { $this->db->where('s.date BETWEEN "'. $start_date. '" and "'.$end_date.'"'); }
			if($start_date) { $q .= " AND (s.date BETWEEN '".$start_date."' AND '".$end_date."') "; }
			//if($product_code) { $this->db->where('p.code', $product_code); }
			if($product_code) { $q .= " AND p.code = $product_code "; }

			if(null != $this->session->userdata('warehouse_id')) 
				{
					$w = $this->session->userdata('warehouse_id');

				 $q .= " AND s.warehouse_id = $w "; }

			//echo $q;exit;

			$query = $this->db->query($q);


			// echo $q;
			// $query = $this->db->get();
			// print_r($query);exit;
			if($query->num_rows() > 0) {
				foreach (($query->result()) as $row) {
					$data[] = $row;
				}
			} else {
				$data = NULL;
			}

			//print_r($data);exit;

			if(!empty($data)) {

				$this->load->library('excel');
				$this->excel->setActiveSheetIndex(0);
				$this->excel->getActiveSheet()->setTitle(lang('sales_report'));
				$this->excel->getActiveSheet()->SetCellValue('A1', "Biller Code");
				$this->excel->getActiveSheet()->SetCellValue('B1', lang('biller'));
				$this->excel->getActiveSheet()->SetCellValue('C1', lang('state'));
				$this->excel->getActiveSheet()->SetCellValue('D1', lang('reference_no'));
				$this->excel->getActiveSheet()->SetCellValue('E1', lang('date'));
				$this->excel->getActiveSheet()->SetCellValue('F1', "Customer Code");
				$this->excel->getActiveSheet()->SetCellValue('G1', "Customer Reference");
				$this->excel->getActiveSheet()->SetCellValue('H1', "Branch Code");
				$this->excel->getActiveSheet()->SetCellValue('I1', "Sales Type");
				$this->excel->getActiveSheet()->SetCellValue('J1', lang('category'));
                $this->excel->getActiveSheet()->SetCellValue('K1', lang('code'));
                $this->excel->getActiveSheet()->SetCellValue('L1', lang('product_name'));
                $this->excel->getActiveSheet()->SetCellValue('M1', "Location ID");
                $this->excel->getActiveSheet()->SetCellValue('N1', lang('quantity'));
                $this->excel->getActiveSheet()->SetCellValue('O1', lang('unit_price'));
                $this->excel->getActiveSheet()->SetCellValue('P1', lang('selling_price'));
                $this->excel->getActiveSheet()->SetCellValue('Q1', lang('amount'));
                $this->excel->getActiveSheet()->SetCellValue('R1', lang('vat'));
                $this->excel->getActiveSheet()->SetCellValue('S1', "Sales Person ID");
                $this->excel->getActiveSheet()->SetCellValue('T1', "Sales Person Name");
                $this->excel->getActiveSheet()->SetCellValue('U1', lang('outlet_id'));
                $this->excel->getActiveSheet()->SetCellValue('V1', lang('route_id'));
                $this->excel->getActiveSheet()->SetCellValue('W1', lang('type'));
                $this->excel->getActiveSheet()->SetCellValue('X1', lang('month'));
                $this->excel->getActiveSheet()->SetCellValue('Y1', lang('year'));





				$row = 2;
				$total = 0; $qty = 0; $balance = 0;
				foreach ($data as $data_row) {
					 $temp = date_create($data_row->date);
                        $date =  date_format($temp,"Y-m-d");
                        $year =     date_format($temp,"Y");
                        $month = date_format($temp,"m");
                        if ($data_row->tax == '16.0000%'){$temp2 = 'Y';}else{$temp2 = 'N';}

                        $this->excel->getActiveSheet()->SetCellValue('A' . $row, $data_row->biller_code);
                        $this->excel->getActiveSheet()->SetCellValue('B' . $row, $data_row->biller);
                        $this->excel->getActiveSheet()->SetCellValue('C' . $row, $data_row->region);
                        $this->excel->getActiveSheet()->SetCellValue('D' . $row, $data_row->reference_no);
                        $this->excel->getActiveSheet()->SetCellValue('E' . $row, $date);
                        $this->excel->getActiveSheet()->SetCellValue('F' . $row, $data_row->cf2);
                        $this->excel->getActiveSheet()->SetCellValue('G' . $row, $data_row->name);
                        $this->excel->getActiveSheet()->SetCellValue('H' . $row, $data_row->cf3);
                        $this->excel->getActiveSheet()->SetCellValue('I' . $row, $data_row->cf1);
                        $this->excel->getActiveSheet()->SetCellValue('J' . $row, $data_row->category);
                        $this->excel->getActiveSheet()->SetCellValue('K' . $row, $data_row->product_code);
                        $this->excel->getActiveSheet()->SetCellValue('L' . $row, $data_row->product_name);
                        $this->excel->getActiveSheet()->SetCellValue('M' . $row, $data_row->company);
                        $this->excel->getActiveSheet()->SetCellValue('N' . $row, $data_row->quantity);
                        $this->excel->getActiveSheet()->SetCellValue('O' . $row, $data_row->net_unit_price);
                        $this->excel->getActiveSheet()->SetCellValue('P' . $row, $data_row->unit_price);
                        $this->excel->getActiveSheet()->SetCellValue('Q' . $row, $data_row->subtotal);
                        $this->excel->getActiveSheet()->SetCellValue('R' . $row, $temp2);
                        $this->excel->getActiveSheet()->SetCellValue('S' . $row, $data_row->code);
                        $this->excel->getActiveSheet()->SetCellValue('T' . $row, $data_row->sp_name);
                        $this->excel->getActiveSheet()->SetCellValue('U' . $row, $data_row->outlet_id);
                        $this->excel->getActiveSheet()->SetCellValue('V' . $row, $data_row->type);
                        $this->excel->getActiveSheet()->SetCellValue('W' . $row, $data_row->route_id);
                        $this->excel->getActiveSheet()->SetCellValue('X' . $row, $month);
                        $this->excel->getActiveSheet()->SetCellValue('Y' . $row, $year);
					 $total += $data_row->net_unit_price;
					 $qty += $data_row->unit_price;
					 $amount += $data_row->subtotal;
					$row++;
				}
				$this->excel->getActiveSheet()->getStyle("L".$row.":M".$row)->getBorders()
				->getTop()->setBorderStyle(PHPExcel_Style_Border::BORDER_MEDIUM);
				$this->excel->getActiveSheet()->SetCellValue('P' . $row, $qty);
				$this->excel->getActiveSheet()->SetCellValue('O' . $row, $total);
				$this->excel->getActiveSheet()->SetCellValue('Q' . $row, $amount);

				$this->excel->getActiveSheet()->getColumnDimension('A')->setWidth(20);
				$this->excel->getActiveSheet()->getColumnDimension('B')->setWidth(20);
				$this->excel->getActiveSheet()->getColumnDimension('C')->setWidth(20);
				$this->excel->getActiveSheet()->getColumnDimension('D')->setWidth(20);
				$this->excel->getActiveSheet()->getColumnDimension('E')->setWidth(30);
				$this->excel->getActiveSheet()->getColumnDimension('F')->setWidth(15);
				$this->excel->getActiveSheet()->getColumnDimension('G')->setWidth(15);
				$this->excel->getActiveSheet()->getColumnDimension('H')->setWidth(15);
				$this->excel->getActiveSheet()->getColumnDimension('I')->setWidth(20);
				$this->excel->getActiveSheet()->getColumnDimension('J')->setWidth(20);
				$this->excel->getActiveSheet()->getColumnDimension('K')->setWidth(20);
				$this->excel->getActiveSheet()->getColumnDimension('L')->setWidth(20);
				$this->excel->getActiveSheet()->getColumnDimension('M')->setWidth(20);
				$filename = 'sales_report';
				$this->excel->getDefaultStyle()->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
				if($pdf) {
					$styleArray = array(
						'borders' => array(
							'allborders' => array(
								'style' => PHPExcel_Style_Border::BORDER_THIN
								)
							)
						);
					$this->excel->getDefaultStyle()->applyFromArray($styleArray);
					$this->excel->getActiveSheet()->getPageSetup()->setOrientation(PHPExcel_Worksheet_PageSetup::ORIENTATION_LANDSCAPE);
					require_once (APPPATH . "third_party".DIRECTORY_SEPARATOR."MPDF".DIRECTORY_SEPARATOR."mpdf.php");
					$rendererName = PHPExcel_Settings::PDF_RENDERER_MPDF;
					$rendererLibrary = 'MPDF';
					$rendererLibraryPath = APPPATH . 'third_party'. DIRECTORY_SEPARATOR . $rendererLibrary;
					if(!PHPExcel_Settings::setPdfRenderer( $rendererName, $rendererLibraryPath )) {
						die('Please set the $rendererName: ' . $rendererName . ' and $rendererLibraryPath: ' . $rendererLibraryPath . ' values' .
							PHP_EOL . ' as appropriate for your directory structure');
					}

					header('Content-Type: application/pdf');
					header('Content-Disposition: attachment;filename="' . $filename . '.pdf"');
					header('Cache-Control: max-age=0');

					$objWriter = PHPExcel_IOFactory::createWriter($this->excel, 'PDF');
					$objWriter->save('php://output');
					exit();
				}
				if($xls) {
					$this->excel->getActiveSheet()->getStyle('E2:E'.$row)->getAlignment()->setWrapText(true);
					ob_clean();
					header('Content-Type: application/vnd.ms-excel');
					header('Content-Disposition: attachment;filename="' . $filename . '.xls"');
					header('Cache-Control: max-age=0');
					ob_clean();
					$objWriter = PHPExcel_IOFactory::createWriter($this->excel, 'Excel5');
					$objWriter->save('php://output');
					exit();
				}

			}
			$this->session->set_flashdata('error', lang('nothing_found'));
			redirect($_SERVER["HTTP_REFERER"]);

		} else {

			$this->load->library('datatables');
			$this->datatables->select('sma_sales.date,sma_sales.reference_no,sma_sales.biller,concat(sma_users.first_name," " ,sma_users.last_name) as full_name,sma_sales.route_id,sma_sales.outlet_id,sma_sales.type,sma_sales.receipt_no,
            sma_categories.name as category,sma_products.code,sma_products.name as product_name,sma_sale_items.quantity,sma_sale_items.subtotal as val',false)
        ->from('sma_sales')
        ->join('sma_sale_items','sma_sales.id = sma_sale_items.sale_id','inner')
        ->join('sma_products','sma_sale_items.product_id = sma_products.id','inner')
        ->join('sma_categories','sma_products.category_id = sma_categories.id')
        ->join('sma_users','sma_sales.created_by = sma_users.id','inner')->unset_column('biller_id')
			->group_by('sma_sales.id');

			if($user) { $this->datatables->where('sma_sales.created_by', $user); }
			if($product) { $this->datatables->like('sma_sale_items.product_id', $product); }
			if($biller) { $this->datatables->where('sma_sales.biller_id', $biller); }
			if($customer) { $this->datatables->where('sma_sales.customer_id', $customer); }
			if($warehouse) { $this->datatables->where('sma_sales.warehouse_id', $warehouse); }
			if($reference_no) { $this->datatables->like('sma_sales.reference_no', $reference_no, 'both'); }
			if($start_date) { $this->datatables->where('sma_sales.date BETWEEN "'. $start_date. '" and "'.$end_date.'"'); }
			if($product_code) { $this->db->where('sma_products.code', $product_code); }

			echo $this->datatables->generate();
exit;
		}

	}

	function getQuotesReport($pdf = NULL, $xls = NULL) {

		if($this->input->get('product')){ $product = $this->input->get('product'); } else { $product = NULL; }
		if($this->input->get('user')){ $user = $this->input->get('user'); } else { $user = NULL; }
		if($this->input->get('customer')){ $customer = $this->input->get('customer'); } else { $customer = NULL; }
		if($this->input->get('biller')){ $biller = $this->input->get('biller'); } else { $biller = NULL; }
		if($this->input->get('warehouse')){ $warehouse = $this->input->get('warehouse'); } else { $warehouse = NULL; }
		if($this->input->get('reference_no')){ $reference_no = $this->input->get('reference_no'); } else { $reference_no = NULL; }
		if($this->input->get('start_date')){ $start_date = $this->input->get('start_date'); } else { $start_date = NULL; }
		if($this->input->get('end_date')){ $end_date = $this->input->get('end_date'); } else { $end_date = NULL; }
		if($start_date) {
			$start_date = $this->sma->fld($start_date);
			$end_date = $this->sma->fld($end_date);
		}
		if($pdf || $xls) {

			$this->db
			->select("date, reference_no, biller, customer, GROUP_CONCAT(CONCAT(".$this->db->dbprefix('quote_items').".product_name, ' (', ".$this->db->dbprefix('quote_items').".quantity, ')') SEPARATOR '<br>') as iname, grand_total, status", FALSE)
			->from('quotes')
			->join('quote_items', 'quote_items.quote_id=quotes.id', 'left')
			->join('warehouses', 'warehouses.id=quotes.warehouse_id', 'left')
			->group_by('quotes.id');

			if($user) { $this->db->where('quotes.created_by', $user); }
			if($product) { $this->db->like('quote_items.product_id', $product); }
			if($biller) { $this->db->where('quotes.biller_id', $biller); }
			if($customer) { $this->db->where('quotes.customer_id', $customer); }
			if($warehouse) { $this->db->where('quotes.warehouse_id', $warehouse); }
			if($reference_no) { $this->db->like('quotes.reference_no', $reference_no, 'both'); }
			if($start_date) { $this->db->where('quotes.date BETWEEN "'. $start_date. '" and "'.$end_date.'"'); }

			$q = $this->db->get();
			if($q->num_rows() > 0) {
				foreach (($q->result()) as $row) {
					$data[] = $row;
				}
			} else {
				$data = NULL;
			}

			if(!empty($data)) {

				$this->load->library('excel');
				$this->excel->setActiveSheetIndex(0);
				$this->excel->getActiveSheet()->setTitle(lang('quotes_report'));
				$this->excel->getActiveSheet()->SetCellValue('A1', lang('date'));
				$this->excel->getActiveSheet()->SetCellValue('B1', lang('reference_no'));
				$this->excel->getActiveSheet()->SetCellValue('C1', lang('biller'));
				$this->excel->getActiveSheet()->SetCellValue('D1', lang('customer'));
				$this->excel->getActiveSheet()->SetCellValue('E1', lang('product_qty'));
				$this->excel->getActiveSheet()->SetCellValue('F1', lang('grand_total'));
				$this->excel->getActiveSheet()->SetCellValue('G1', lang('status'));

				$row = 2;
				foreach ($data as $data_row) {
					$this->excel->getActiveSheet()->SetCellValue('A' . $row, $this->sma->hrld($data_row->date));
					$this->excel->getActiveSheet()->SetCellValue('B' . $row, $data_row->reference_no);
					$this->excel->getActiveSheet()->SetCellValue('C' . $row, $data_row->biller);
					$this->excel->getActiveSheet()->SetCellValue('D' . $row, $data_row->customer);
					$this->excel->getActiveSheet()->SetCellValue('E' . $row, $data_row->iname);
					$this->excel->getActiveSheet()->SetCellValue('F' . $row, $data_row->grand_total);
					$this->excel->getActiveSheet()->SetCellValue('G' . $row, $data_row->status);
					$row++;
				}

				$this->excel->getActiveSheet()->getColumnDimension('A')->setWidth(20);
				$this->excel->getActiveSheet()->getColumnDimension('B')->setWidth(20);
				$this->excel->getActiveSheet()->getColumnDimension('C')->setWidth(20);
				$this->excel->getActiveSheet()->getColumnDimension('D')->setWidth(20);
				$this->excel->getActiveSheet()->getColumnDimension('E')->setWidth(30);
				$this->excel->getActiveSheet()->getColumnDimension('F')->setWidth(15);
				$this->excel->getActiveSheet()->getColumnDimension('G')->setWidth(15);
				$filename = 'quotes_report';
				$this->excel->getDefaultStyle()->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
				if($pdf) {
					$styleArray = array(
						'borders' => array(
							'allborders' => array(
								'style' => PHPExcel_Style_Border::BORDER_THIN
								)
							)
						);
					$this->excel->getDefaultStyle()->applyFromArray($styleArray);
					$this->excel->getActiveSheet()->getPageSetup()->setOrientation(PHPExcel_Worksheet_PageSetup::ORIENTATION_LANDSCAPE);
					require_once (APPPATH . "third_party".DIRECTORY_SEPARATOR."MPDF".DIRECTORY_SEPARATOR."mpdf.php");
					$rendererName = PHPExcel_Settings::PDF_RENDERER_MPDF;
					$rendererLibrary = 'MPDF';
					$rendererLibraryPath = APPPATH . 'third_party'. DIRECTORY_SEPARATOR . $rendererLibrary;
					if(!PHPExcel_Settings::setPdfRenderer( $rendererName, $rendererLibraryPath )) {
						die('Please set the $rendererName: ' . $rendererName . ' and $rendererLibraryPath: ' . $rendererLibraryPath . ' values' .
							PHP_EOL . ' as appropriate for your directory structure');
					}

					header('Content-Type: application/pdf');
					header('Content-Disposition: attachment;filename="' . $filename . '.pdf"');
					header('Cache-Control: max-age=0');

					$objWriter = PHPExcel_IOFactory::createWriter($this->excel, 'PDF');
					$objWriter->save('php://output');
					exit();
				}
				if($xls) {
					$this->excel->getActiveSheet()->getStyle('E2:E'.$row)->getAlignment()->setWrapText(true);
					ob_clean();
					header('Content-Type: application/vnd.ms-excel');
					header('Content-Disposition: attachment;filename="' . $filename . '.xls"');
					header('Cache-Control: max-age=0');
					ob_clean();
					$objWriter = PHPExcel_IOFactory::createWriter($this->excel, 'Excel5');
					$objWriter->save('php://output');
					exit();
				}

			}
			$this->session->set_flashdata('error', lang('nothing_found'));
			redirect($_SERVER["HTTP_REFERER"]);

		} else {

			$this->load->library('datatables');
			$this->datatables
			->select("date, reference_no, biller, customer, GROUP_CONCAT(CONCAT(".$this->db->dbprefix('quote_items').".product_name, '__', ".$this->db->dbprefix('quote_items').".quantity) SEPARATOR '___') as iname, grand_total, status", FALSE)
			->from('quotes')
			->join('quote_items', 'quote_items.quote_id=quotes.id', 'left')
			->join('warehouses', 'warehouses.id=quotes.warehouse_id', 'left')
			->group_by('quotes.id');

			if($user) { $this->datatables->where('quotes.created_by', $user); }
			if($product) { $this->datatables->like('quote_items.product_id', $product); }
			if($biller) { $this->datatables->where('quotes.biller_id', $biller); }
			if($customer) { $this->datatables->where('quotes.customer_id', $customer); }
			if($warehouse) { $this->datatables->where('quotes.warehouse_id', $warehouse); }
			if($reference_no) { $this->datatables->like('quotes.reference_no', $reference_no, 'both'); }
			if($start_date) { $this->datatables->where('quotes.date BETWEEN "'. $start_date. '" and "'.$end_date.'"'); }

			echo $this->datatables->generate();

		}
		
	}

	function getTransfersReport($pdf = NULL, $xls = NULL) {
		if($this->input->get('product')){ $product = $this->input->get('product'); } else { $product = NULL; }

		if($pdf || $xls) {

			$this->db
			->select($this->db->dbprefix('transfers').".date, transfer_no, (CASE WHEN ".$this->db->dbprefix('transfers').".status = 'completed' THEN  GROUP_CONCAT(CONCAT(".$this->db->dbprefix('purchase_items').".product_name, ' (', ".$this->db->dbprefix('purchase_items').".quantity, ')') SEPARATOR '<br>') ELSE GROUP_CONCAT(CONCAT(".$this->db->dbprefix('transfer_items').".product_name, ' (', ".$this->db->dbprefix('transfer_items').".quantity, ')') SEPARATOR '<br>') END) as iname, from_warehouse_name as fname, from_warehouse_code as fcode, to_warehouse_name as tname,to_warehouse_code as tcode, grand_total, ".$this->db->dbprefix('transfers').".status")
			->from('transfers')
			->join('transfer_items', 'transfer_items.transfer_id=transfers.id', 'left')
			->join('purchase_items', 'purchase_items.transfer_id=transfers.id', 'left')
			->group_by('transfers.id')->order_by('transfers.date desc');
			if($product) { $this->db->where($this->db->dbprefix('purchase_items').".product_id", $product); $this->db->or_where($this->db->dbprefix('transfer_items').".product_id", $product); }

			$q = $this->db->get();
			if($q->num_rows() > 0) {
				foreach (($q->result()) as $row) {
					$data[] = $row;
				}
			} else {
				$data = NULL;
			}

			if(!empty($data)) {

				$this->load->library('excel');
				$this->excel->setActiveSheetIndex(0);
				$this->excel->getActiveSheet()->setTitle(lang('transfers_report'));
				$this->excel->getActiveSheet()->SetCellValue('A1', lang('date'));
				$this->excel->getActiveSheet()->SetCellValue('B1', lang('transfer_no'));
				$this->excel->getActiveSheet()->SetCellValue('C1', lang('product_qty'));
				$this->excel->getActiveSheet()->SetCellValue('D1', lang('warehouse').' ('.lang('from').')');
				$this->excel->getActiveSheet()->SetCellValue('E1', lang('warehouse').' ('.lang('to').')');
				$this->excel->getActiveSheet()->SetCellValue('F1', lang('grand_total'));
				$this->excel->getActiveSheet()->SetCellValue('G1', lang('status'));

				$row = 2;
				foreach ($data as $data_row) {
					$this->excel->getActiveSheet()->SetCellValue('A' . $row, $this->sma->hrld($data_row->date));
					$this->excel->getActiveSheet()->SetCellValue('B' . $row, $data_row->transfer_no);
					$this->excel->getActiveSheet()->SetCellValue('C' . $row, $data_row->iname);
					$this->excel->getActiveSheet()->SetCellValue('D' . $row, $data_row->fname.' ('.$data_row->fcode.')');
					$this->excel->getActiveSheet()->SetCellValue('E' . $row, $data_row->tname.' ('.$data_row->tcode.')');
					$this->excel->getActiveSheet()->SetCellValue('F' . $row, $data_row->grand_total);
					$this->excel->getActiveSheet()->SetCellValue('G' . $row, $data_row->status);
					$row++;
				}

				$this->excel->getActiveSheet()->getColumnDimension('A')->setWidth(20);
				$this->excel->getActiveSheet()->getColumnDimension('B')->setWidth(20);
				$this->excel->getActiveSheet()->getColumnDimension('C')->setWidth(30);
				$this->excel->getActiveSheet()->getColumnDimension('D')->setWidth(20);
				$this->excel->getActiveSheet()->getColumnDimension('E')->setWidth(20);
				$this->excel->getActiveSheet()->getColumnDimension('F')->setWidth(15);
				$this->excel->getActiveSheet()->getColumnDimension('G')->setWidth(15);
				$filename = 'transfers_report';
				$this->excel->getDefaultStyle()->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
				if($pdf) {
					$styleArray = array(
						'borders' => array(
							'allborders' => array(
								'style' => PHPExcel_Style_Border::BORDER_THIN
								)
							)
						);
					$this->excel->getDefaultStyle()->applyFromArray($styleArray);
					$this->excel->getActiveSheet()->getPageSetup()->setOrientation(PHPExcel_Worksheet_PageSetup::ORIENTATION_LANDSCAPE);
					require_once (APPPATH . "third_party".DIRECTORY_SEPARATOR."MPDF".DIRECTORY_SEPARATOR."mpdf.php");
					$rendererName = PHPExcel_Settings::PDF_RENDERER_MPDF;
					$rendererLibrary = 'MPDF';
					$rendererLibraryPath = APPPATH . 'third_party'. DIRECTORY_SEPARATOR . $rendererLibrary;
					if(!PHPExcel_Settings::setPdfRenderer( $rendererName, $rendererLibraryPath )) {
						die('Please set the $rendererName: ' . $rendererName . ' and $rendererLibraryPath: ' . $rendererLibraryPath . ' values' .
							PHP_EOL . ' as appropriate for your directory structure');
					}

					header('Content-Type: application/pdf');
					header('Content-Disposition: attachment;filename="' . $filename . '.pdf"');
					header('Cache-Control: max-age=0');

					$objWriter = PHPExcel_IOFactory::createWriter($this->excel, 'PDF');
					$objWriter->save('php://output');
					exit();
				}
				if($xls) {
					$this->excel->getActiveSheet()->getStyle('C2:C'.$row)->getAlignment()->setWrapText(true);
					ob_clean();
					header('Content-Type: application/vnd.ms-excel');
					header('Content-Disposition: attachment;filename="' . $filename . '.xls"');
					header('Cache-Control: max-age=0');
					ob_clean();
					$objWriter = PHPExcel_IOFactory::createWriter($this->excel, 'Excel5');
					$objWriter->save('php://output');
					exit();
				}

			}
			$this->session->set_flashdata('error', lang('nothing_found'));
			redirect($_SERVER["HTTP_REFERER"]);

		} else {

			$this->load->library('datatables');
			$this->datatables
			->select($this->db->dbprefix('transfers').".date, transfer_no, (CASE WHEN ".$this->db->dbprefix('transfers').".status = 'completed' THEN  GROUP_CONCAT(CONCAT(".$this->db->dbprefix('purchase_items').".product_name, '__', ".$this->db->dbprefix('purchase_items').".quantity) SEPARATOR '___') ELSE GROUP_CONCAT(CONCAT(".$this->db->dbprefix('transfer_items').".product_name, '__', ".$this->db->dbprefix('transfer_items').".quantity) SEPARATOR '___') END) as iname, from_warehouse_name as fname, from_warehouse_code as fcode, to_warehouse_name as tname,to_warehouse_code as tcode, grand_total, ".$this->db->dbprefix('transfers').".status", FALSE)
			->from('transfers')
			->join('transfer_items', 'transfer_items.transfer_id=transfers.id', 'left')
			->join('purchase_items', 'purchase_items.transfer_id=transfers.id', 'left')
			->group_by('transfers.id');
			if($product) { $this->datatables->where($this->db->dbprefix('purchase_items').".product_id", $product); $this->datatables->or_where($this->db->dbprefix('transfer_items').".product_id", $product); }
			$this->datatables->edit_column("fname", "$1 ($2)", "fname, fcode")
			->edit_column("tname", "$1 ($2)", "tname, tcode")
			->unset_column('fcode')
			->unset_column('tcode');
			echo $this->datatables->generate();

		}

	}

	function getReturnsReport($pdf = NULL, $xls = NULL) {
		if($this->input->get('product')){ $product = $this->input->get('product'); } else { $product = NULL; }

		if($pdf || $xls) {

			$this->db
			->select($this->db->dbprefix('return_sales').".date as date, ".$this->db->dbprefix('return_sales').".reference_no as ref, ".$this->db->dbprefix('sales').".reference_no as sal_ref, ".$this->db->dbprefix('return_sales').".biller, ".$this->db->dbprefix('return_sales').".customer, GROUP_CONCAT(CONCAT(".$this->db->dbprefix('return_items').".product_name, ' (', ".$this->db->dbprefix('return_items').".quantity, ')') SEPARATOR '<br>') as iname, ".$this->db->dbprefix('return_sales').".surcharge, ".$this->db->dbprefix('return_sales').".grand_total, ".$this->db->dbprefix('return_sales').".id as id", FALSE)
			->join('sales', 'sales.id=return_sales.sale_id', 'left')
			->from('return_sales')
			->join('return_items', 'return_items.return_id=return_sales.id', 'left')
			->group_by('return_sales.id')->order_by('return_sales.date desc');
			if($product) { $this->db->like($this->db->dbprefix('return_items').".product_id", $product); }	  

			$q = $this->db->get();
			if($q->num_rows() > 0) {
				foreach (($q->result()) as $row) {
					$data[] = $row;
				}
			} else {
				$data = NULL;
			}

			if(!empty($data)) {

				$this->load->library('excel');
				$this->excel->setActiveSheetIndex(0);
				$this->excel->getActiveSheet()->setTitle(lang('sales_return_report'));
				$this->excel->getActiveSheet()->SetCellValue('A1', lang('date'));
				$this->excel->getActiveSheet()->SetCellValue('B1', lang('reference_no'));
				$this->excel->getActiveSheet()->SetCellValue('C1', lang('sale_ref'));
				$this->excel->getActiveSheet()->SetCellValue('D1', lang('biller'));
				$this->excel->getActiveSheet()->SetCellValue('E1', lang('customer'));
				$this->excel->getActiveSheet()->SetCellValue('F1', lang('product_qty'));
				$this->excel->getActiveSheet()->SetCellValue('G1', lang('grand_total'));
				$this->excel->getActiveSheet()->SetCellValue('H1', lang('status'));

				$row = 2;
				foreach ($data as $data_row) {
					$this->excel->getActiveSheet()->SetCellValue('A' . $row, $this->sma->hrld($data_row->date));
					$this->excel->getActiveSheet()->SetCellValue('B' . $row, $data_row->ref);
					$this->excel->getActiveSheet()->SetCellValue('C' . $row, $data_row->sal_ref);
					$this->excel->getActiveSheet()->SetCellValue('D' . $row, $data_row->biller);
					$this->excel->getActiveSheet()->SetCellValue('E' . $row, $data_row->customer);
					$this->excel->getActiveSheet()->SetCellValue('F' . $row, $data_row->iname);
					$this->excel->getActiveSheet()->SetCellValue('G' . $row, $data_row->surcharge);
					$this->excel->getActiveSheet()->SetCellValue('H' . $row, $data_row->grand_total);
					$row++;
				}

				$this->excel->getActiveSheet()->getColumnDimension('A')->setWidth(20);
				$this->excel->getActiveSheet()->getColumnDimension('B')->setWidth(20);
				$this->excel->getActiveSheet()->getColumnDimension('C')->setWidth(20);
				$this->excel->getActiveSheet()->getColumnDimension('D')->setWidth(20);
				$this->excel->getActiveSheet()->getColumnDimension('E')->setWidth(20);
				$this->excel->getActiveSheet()->getColumnDimension('F')->setWidth(30);
				$this->excel->getActiveSheet()->getColumnDimension('G')->setWidth(15);
				$this->excel->getActiveSheet()->getColumnDimension('H')->setWidth(15);
				$filename = 'sales_return_report';
				$this->excel->getDefaultStyle()->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
				if($pdf) {
					$styleArray = array(
						'borders' => array(
							'allborders' => array(
								'style' => PHPExcel_Style_Border::BORDER_THIN
								)
							)
						);
					$this->excel->getDefaultStyle()->applyFromArray($styleArray);
					$this->excel->getActiveSheet()->getPageSetup()->setOrientation(PHPExcel_Worksheet_PageSetup::ORIENTATION_LANDSCAPE);
					require_once (APPPATH . "third_party".DIRECTORY_SEPARATOR."MPDF".DIRECTORY_SEPARATOR."mpdf.php");
					$rendererName = PHPExcel_Settings::PDF_RENDERER_MPDF;
					$rendererLibrary = 'MPDF';
					$rendererLibraryPath = APPPATH . 'third_party'. DIRECTORY_SEPARATOR . $rendererLibrary;
					if(!PHPExcel_Settings::setPdfRenderer( $rendererName, $rendererLibraryPath )) {
						die('Please set the $rendererName: ' . $rendererName . ' and $rendererLibraryPath: ' . $rendererLibraryPath . ' values' .
							PHP_EOL . ' as appropriate for your directory structure');
					}

					header('Content-Type: application/pdf');
					header('Content-Disposition: attachment;filename="' . $filename . '.pdf"');
					header('Cache-Control: max-age=0');

					$objWriter = PHPExcel_IOFactory::createWriter($this->excel, 'PDF');
					$objWriter->save('php://output');
					exit();
				}
				if($xls) {
					$this->excel->getActiveSheet()->getStyle('F2:F'.$row)->getAlignment()->setWrapText(true);
					ob_clean();
					header('Content-Type: application/vnd.ms-excel');
					header('Content-Disposition: attachment;filename="' . $filename . '.xls"');
					header('Cache-Control: max-age=0');
					ob_clean();
					$objWriter = PHPExcel_IOFactory::createWriter($this->excel, 'Excel5');
					$objWriter->save('php://output');
					exit();
				}

			}
			$this->session->set_flashdata('error', lang('nothing_found'));
			redirect($_SERVER["HTTP_REFERER"]);

		} else {

			$this->load->library('datatables');
			$this->datatables
			->select($this->db->dbprefix('return_sales').".date as date, ".$this->db->dbprefix('return_sales').".reference_no as ref, ".$this->db->dbprefix('sales').".reference_no as sal_ref, ".$this->db->dbprefix('return_sales').".biller, ".$this->db->dbprefix('return_sales').".customer, GROUP_CONCAT(CONCAT(".$this->db->dbprefix('return_items').".product_name, '__', ".$this->db->dbprefix('return_items').".quantity) SEPARATOR '___') as iname, ".$this->db->dbprefix('return_sales').".surcharge, ".$this->db->dbprefix('return_sales').".grand_total, ".$this->db->dbprefix('return_sales').".id as id", FALSE)
			->join('sales', 'sales.id=return_sales.sale_id', 'left')
			->from('return_sales')
			->join('return_items', 'return_items.return_id=return_sales.id', 'left')
			->group_by('return_sales.id');
			//->where('return_sales.warehouse_id', $warehouse_id);	
			if($product) { $this->datatables->like($this->db->dbprefix('return_items').".product_id", $product); }	   

			echo $this->datatables->generate();

		}
		
	}

	function purchases() {
		$this->sma->checkPermissions('purchases');
		$this->data['error'] = (validation_errors()) ? validation_errors() : $this->session->flashdata('error');	   
		$this->data['users'] = $this->reports_model->getStaff();
		$this->data['warehouses'] = $this->site->getAllWarehouses();

		$bc = array(array('link' => base_url(), 'page' => lang('home')), array('link' => site_url('reports'), 'page' => lang('reports')), array('link' => '#', 'page' => lang('purchases_report')));
		$meta = array('page_title' => lang('purchases_report'), 'bc' => $bc);
		$this->page_construct('reports/purchases', $meta, $this->data);
	}

	function getPurchasesReport($pdf = NULL, $xls = NULL) {
		$this->sma->checkPermissions('purchases', TRUE);
		if($this->input->get('product')){ $product = $this->input->get('product'); } else { $product = NULL; }
		if($this->input->get('user')){ $user = $this->input->get('user'); } else { $user = NULL; }
		if($this->input->get('supplier')){ $supplier = $this->input->get('supplier'); } else { $supplier = NULL; }
		if($this->input->get('warehouse')){ $warehouse = $this->input->get('warehouse'); } else { $warehouse = NULL; }
		if($this->input->get('reference_no')){ $reference_no = $this->input->get('reference_no'); } else { $reference_no = NULL; }
		if($this->input->get('start_date')){ $start_date = $this->input->get('start_date'); } else { $start_date = NULL; }
		if($this->input->get('end_date')){ $end_date = $this->input->get('end_date'); } else { $end_date = NULL; }
		if($start_date) {
			$start_date = $this->sma->fld($start_date);
			$end_date = $this->sma->fld($end_date);
		}
		if(!$this->Owner && !$this->Admin) {
			$user = $this->session->userdata('user_id');
		}

		if($pdf || $xls) {

			$this->db
			->select("".$this->db->dbprefix('purchases').".date, reference_no, ".$this->db->dbprefix('warehouses').".name as wname, supplier, GROUP_CONCAT(CONCAT(".$this->db->dbprefix('purchase_items').".product_name, ' (', ".$this->db->dbprefix('purchase_items').".quantity, ')') SEPARATOR '\n') as iname, grand_total, paid, ".$this->db->dbprefix('purchases').".status", FALSE)
			->from('purchases')
			->join('purchase_items', 'purchase_items.purchase_id=purchases.id', 'left')
			->join('warehouses', 'warehouses.id=purchases.warehouse_id', 'left')
			->group_by('purchases.id')
			->order_by('purchases.date desc');

			if($user) { $this->db->where('purchases.created_by', $user); }
			if($product) { $this->db->like('purchase_items.product_id', $product); }
			if($supplier) { $this->db->where('purchases.supplier_id', $supplier); }
			if($warehouse) { $this->db->where('purchases.warehouse_id', $warehouse); }
			if($reference_no) { $this->db->like('purchases.reference_no', $reference_no, 'both'); }
			if($start_date) { $this->db->where('purchases.date BETWEEN "'. $start_date. '" and "'.$end_date.'"'); }

			$q = $this->db->get();
			if($q->num_rows() > 0) {
				foreach (($q->result()) as $row) {
					$data[] = $row;
				}
			} else {
				$data = NULL;
			}

			if(!empty($data)) {

				$this->load->library('excel');
				$this->excel->setActiveSheetIndex(0);
				$this->excel->getActiveSheet()->setTitle(lang('purchase_report'));
				$this->excel->getActiveSheet()->SetCellValue('A1', lang('date'));
				$this->excel->getActiveSheet()->SetCellValue('B1', lang('reference_no'));
				$this->excel->getActiveSheet()->SetCellValue('C1', lang('warehouse'));
				$this->excel->getActiveSheet()->SetCellValue('D1', lang('supplier'));
				$this->excel->getActiveSheet()->SetCellValue('E1', lang('product_qty'));
				$this->excel->getActiveSheet()->SetCellValue('F1', lang('grand_total'));
				$this->excel->getActiveSheet()->SetCellValue('G1', lang('paid'));
				$this->excel->getActiveSheet()->SetCellValue('H1', lang('balance'));
				$this->excel->getActiveSheet()->SetCellValue('I1', lang('status'));

				$row = 2;
				$total = 0; $paid = 0; $balance = 0;
				foreach ($data as $data_row) {
					$this->excel->getActiveSheet()->SetCellValue('A' . $row, $this->sma->hrld($data_row->date));
					$this->excel->getActiveSheet()->SetCellValue('B' . $row, $data_row->reference_no);
					$this->excel->getActiveSheet()->SetCellValue('C' . $row, $data_row->wname);
					$this->excel->getActiveSheet()->SetCellValue('D' . $row, $data_row->supplier);
					$this->excel->getActiveSheet()->SetCellValue('E' . $row, $data_row->iname);
					$this->excel->getActiveSheet()->SetCellValue('F' . $row, $data_row->grand_total);
					$this->excel->getActiveSheet()->SetCellValue('G' . $row, $data_row->paid);
					$this->excel->getActiveSheet()->SetCellValue('H' . $row, ($data_row->grand_total-$data_row->paid));
					$this->excel->getActiveSheet()->SetCellValue('I' . $row, $data_row->status);
					$total += $data_row->grand_total;
					$paid += $data_row->paid;
					$balance += ($data_row->grand_total-$data_row->paid);
					$row++;
				}
				$this->excel->getActiveSheet()->getStyle("F".$row.":H".$row)->getBorders()
				->getTop()->setBorderStyle(PHPExcel_Style_Border::BORDER_MEDIUM);
				$this->excel->getActiveSheet()->SetCellValue('F' . $row, $total);
				$this->excel->getActiveSheet()->SetCellValue('G' . $row, $paid);
				$this->excel->getActiveSheet()->SetCellValue('H' . $row, $balance);

				$this->excel->getActiveSheet()->getColumnDimension('A')->setWidth(20);
				$this->excel->getActiveSheet()->getColumnDimension('B')->setWidth(20);
				$this->excel->getActiveSheet()->getColumnDimension('C')->setWidth(20);
				$this->excel->getActiveSheet()->getColumnDimension('D')->setWidth(20);
				$this->excel->getActiveSheet()->getColumnDimension('E')->setWidth(30);
				$this->excel->getActiveSheet()->getColumnDimension('F')->setWidth(15);
				$this->excel->getActiveSheet()->getColumnDimension('G')->setWidth(15);
				$this->excel->getActiveSheet()->getColumnDimension('H')->setWidth(15);
				$this->excel->getActiveSheet()->getColumnDimension('I')->setWidth(20);
				$filename = 'purchase_report';
				$this->excel->getDefaultStyle()->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
				if($pdf) {
					$styleArray = array(
						'borders' => array(
							'allborders' => array(
								'style' => PHPExcel_Style_Border::BORDER_THIN
								)
							)
						);
					$this->excel->getDefaultStyle()->applyFromArray($styleArray);
					$this->excel->getActiveSheet()->getPageSetup()->setOrientation(PHPExcel_Worksheet_PageSetup::ORIENTATION_LANDSCAPE);
					require_once (APPPATH . "third_party".DIRECTORY_SEPARATOR."MPDF".DIRECTORY_SEPARATOR."mpdf.php");
					$rendererName = PHPExcel_Settings::PDF_RENDERER_MPDF;
					$rendererLibrary = 'MPDF';
					$rendererLibraryPath = APPPATH . 'third_party'. DIRECTORY_SEPARATOR . $rendererLibrary;
					if(!PHPExcel_Settings::setPdfRenderer( $rendererName, $rendererLibraryPath )) {
						die('Please set the $rendererName: ' . $rendererName . ' and $rendererLibraryPath: ' . $rendererLibraryPath . ' values' .
							PHP_EOL . ' as appropriate for your directory structure');
					}

					header('Content-Type: application/pdf');
					header('Content-Disposition: attachment;filename="' . $filename . '.pdf"');
					header('Cache-Control: max-age=0');

					$objWriter = PHPExcel_IOFactory::createWriter($this->excel, 'PDF');
					$objWriter->save('php://output');
					exit();
				}
				if($xls) {
					$this->excel->getActiveSheet()->getStyle('E2:E'.$row)->getAlignment()->setWrapText(true);
					ob_clean();
					header('Content-Type: application/vnd.ms-excel');
					header('Content-Disposition: attachment;filename="' . $filename . '.xls"');
					header('Cache-Control: max-age=0');
					ob_clean();
					$objWriter = PHPExcel_IOFactory::createWriter($this->excel, 'Excel5');
					$objWriter->save('php://output');
					exit();
				}

			}
			$this->session->set_flashdata('error', lang('nothing_found'));
			redirect($_SERVER["HTTP_REFERER"]);

		} else {

			$this->load->library('datatables');
			$this->datatables
			->select($this->db->dbprefix('purchases').".date, reference_no, ".$this->db->dbprefix('warehouses').".name as wname, supplier, GROUP_CONCAT(CONCAT(".$this->db->dbprefix('purchase_items').".product_name, '__', ".$this->db->dbprefix('purchase_items').".quantity) SEPARATOR '___') as iname, grand_total, paid, (grand_total-paid) as balance, ".$this->db->dbprefix('purchases').".status", FALSE)
			->from('purchases')
			->join('purchase_items', 'purchase_items.purchase_id=purchases.id', 'left')
			->join('warehouses', 'warehouses.id=purchases.warehouse_id', 'left')
			->group_by('purchases.id');

			if($user) { $this->datatables->where('purchases.created_by', $user); }
			if($product) { $this->datatables->like('purchase_items.product_id', $product); }
			if($supplier) { $this->datatables->where('purchases.supplier_id', $supplier); }
			if($warehouse) { $this->datatables->where('purchases.warehouse_id', $warehouse); }
			if($reference_no) { $this->datatables->like('purchases.reference_no', $reference_no, 'both'); }
			if($start_date) { $this->datatables->where('purchases.date BETWEEN "'. $start_date. '" and "'.$end_date.'"'); }

			echo $this->datatables->generate();

		}

	}

	function payments() {
		$this->sma->checkPermissions('payments');
		$this->data['error'] = (validation_errors()) ? validation_errors() : $this->session->flashdata('error');	   
		$this->data['users'] = $this->reports_model->getStaff();
		$this->data['billers'] = $this->site->getAllCompanies('biller'); 
		$bc = array(array('link' => base_url(), 'page' => lang('home')), array('link' => site_url('reports'), 'page' => lang('reports')), array('link' => '#', 'page' => lang('payments_report')));
		$meta = array('page_title' => lang('payments_report'), 'bc' => $bc);
		$this->page_construct('reports/payments', $meta, $this->data);
	}

	function getPaymentsReport($pdf = NULL, $xls = NULL) {
		$this->sma->checkPermissions('payments', TRUE);
		if($this->input->get('user')){ $user = $this->input->get('user'); } else { $user = NULL; }
		if($this->input->get('supplier')){ $supplier = $this->input->get('supplier'); } else { $supplier = NULL; }
		if($this->input->get('customer')){ $customer = $this->input->get('customer'); } else { $customer = NULL; }
		if($this->input->get('biller')){ $biller = $this->input->get('biller'); } else { $biller = NULL; }
		if($this->input->get('payment_ref')){ $payment_ref = $this->input->get('payment_ref'); } else { $payment_ref = NULL; }
		if($this->input->get('sale_ref')){ $sale_ref = $this->input->get('sale_ref'); } else { $sale_ref = NULL; }
		if($this->input->get('purchase_ref')){ $purchase_ref = $this->input->get('purchase_ref'); } else { $purchase_ref = NULL; }
		if($this->input->get('start_date')){ $start_date = $this->input->get('start_date'); } else { $start_date = NULL; }
		if($this->input->get('end_date')){ $end_date = $this->input->get('end_date'); } else { $end_date = NULL; }
		if($start_date) {
			$start_date = $this->sma->fsd($start_date);
			$end_date = $this->sma->fsd($end_date);
		}
		if(!$this->Owner && !$this->Admin) {
			$user = $this->session->userdata('user_id');
		}
		if($pdf || $xls) {

			$this->db
			->select("".$this->db->dbprefix('payments').".date, ".$this->db->dbprefix('payments').".reference_no as payment_ref, ".$this->db->dbprefix('sales').".reference_no as sale_ref, ".$this->db->dbprefix('purchases').".reference_no as purchase_ref, paid_by, amount, type")
			->from('payments')
			->join('sales', 'payments.sale_id=sales.id', 'left')
			->join('purchases', 'payments.purchase_id=purchases.id', 'left')
			->group_by('payments.id')
			->order_by('payments.date desc');

			if($user) { $this->db->where('payments.created_by', $user); }
			if($customer) { $this->db->where('sales.customer_id', $customer); }
			if($supplier) { $this->db->where('purchases.supplier_id', $supplier); }
			if($biller) { $this->db->where('sales.biller_id', $biller); }
			if($customer) { $this->db->where('sales.customer_id', $customer); }
			if($payment_ref) { $this->db->like('payments.reference_no', $payment_ref, 'both'); }
			if($sale_ref) { $this->db->like('sales.reference_no', $sale_ref, 'both'); }
			if($purchase_ref) { $this->db->like('purchases.reference_no', $purchase_ref, 'both'); }
			if($start_date) { $this->db->where('payments.date BETWEEN "'. $start_date. '" and "'.$end_date.'"'); }

			$q = $this->db->get();
			if($q->num_rows() > 0) {
				foreach (($q->result()) as $row) {
					$data[] = $row;
				}
			} else {
				$data = NULL;
			}

			if(!empty($data)) {

				$this->load->library('excel');
				$this->excel->setActiveSheetIndex(0);
				$this->excel->getActiveSheet()->setTitle(lang('payments_report'));
				$this->excel->getActiveSheet()->SetCellValue('A1', lang('date'));
				$this->excel->getActiveSheet()->SetCellValue('B1', lang('payment_reference'));
				$this->excel->getActiveSheet()->SetCellValue('C1', lang('sale_reference'));
				$this->excel->getActiveSheet()->SetCellValue('D1', lang('purchase_reference'));
				$this->excel->getActiveSheet()->SetCellValue('E1', lang('paid_by'));
				$this->excel->getActiveSheet()->SetCellValue('F1', lang('amount'));
				$this->excel->getActiveSheet()->SetCellValue('G1', lang('type'));

				$row = 2; $total = 0;
				foreach ($data as $data_row) {
					$this->excel->getActiveSheet()->SetCellValue('A' . $row, $this->sma->hrld($data_row->date));
					$this->excel->getActiveSheet()->SetCellValue('B' . $row, $data_row->payment_ref);
					$this->excel->getActiveSheet()->SetCellValue('C' . $row, $data_row->sale_ref);
					$this->excel->getActiveSheet()->SetCellValue('D' . $row, $data_row->purchase_ref);
					$this->excel->getActiveSheet()->SetCellValue('E' . $row, lang($data_row->paid_by));
					$this->excel->getActiveSheet()->SetCellValue('F' . $row, $data_row->amount);
					$this->excel->getActiveSheet()->SetCellValue('G' . $row, $data_row->type);
					if($data_row->type == 'returned' || $data_row->type == 'sent') {
						$total -= $data_row->amount;
					} else {
						$total += $data_row->amount;
					}
					$row++;
				}
				$this->excel->getActiveSheet()->getStyle("F".$row)->getBorders()
				->getTop()->setBorderStyle(PHPExcel_Style_Border::BORDER_MEDIUM);
				$this->excel->getActiveSheet()->SetCellValue('F' . $row, $total);

				$this->excel->getActiveSheet()->getColumnDimension('A')->setWidth(20);
				$this->excel->getActiveSheet()->getColumnDimension('B')->setWidth(25);
				$this->excel->getActiveSheet()->getColumnDimension('C')->setWidth(25);
				$this->excel->getActiveSheet()->getColumnDimension('D')->setWidth(25);
				$this->excel->getActiveSheet()->getColumnDimension('E')->setWidth(15);
				$this->excel->getActiveSheet()->getColumnDimension('F')->setWidth(15);
				$this->excel->getActiveSheet()->getColumnDimension('G')->setWidth(15);
				$filename = 'payments_report';
				$this->excel->getDefaultStyle()->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
				if($pdf) {
					$styleArray = array(
						'borders' => array(
							'allborders' => array(
								'style' => PHPExcel_Style_Border::BORDER_THIN
								)
							)
						);
					$this->excel->getDefaultStyle()->applyFromArray($styleArray);
					$this->excel->getActiveSheet()->getPageSetup()->setOrientation(PHPExcel_Worksheet_PageSetup::ORIENTATION_LANDSCAPE);
					require_once (APPPATH . "third_party".DIRECTORY_SEPARATOR."MPDF".DIRECTORY_SEPARATOR."mpdf.php");
					$rendererName = PHPExcel_Settings::PDF_RENDERER_MPDF;
					$rendererLibrary = 'MPDF';
					$rendererLibraryPath = APPPATH . 'third_party'. DIRECTORY_SEPARATOR . $rendererLibrary;
					if(!PHPExcel_Settings::setPdfRenderer( $rendererName, $rendererLibraryPath )) {
						die('Please set the $rendererName: ' . $rendererName . ' and $rendererLibraryPath: ' . $rendererLibraryPath . ' values' .
							PHP_EOL . ' as appropriate for your directory structure');
					}

					header('Content-Type: application/pdf');
					header('Content-Disposition: attachment;filename="' . $filename . '.pdf"');
					header('Cache-Control: max-age=0');

					$objWriter = PHPExcel_IOFactory::createWriter($this->excel, 'PDF');
					$objWriter->save('php://output');
					exit();
				}
				if($xls) {
					ob_clean();
					header('Content-Type: application/vnd.ms-excel');
					header('Content-Disposition: attachment;filename="' . $filename . '.xls"');
					header('Cache-Control: max-age=0');
					ob_clean();
					$objWriter = PHPExcel_IOFactory::createWriter($this->excel, 'Excel5');
					$objWriter->save('php://output');
					exit();
				}

			}
			$this->session->set_flashdata('error', lang('nothing_found'));
			redirect($_SERVER["HTTP_REFERER"]);

		} else {

			$this->load->library('datatables');
			$this->datatables
			->select($this->db->dbprefix('payments').".date, ".$this->db->dbprefix('payments').".reference_no as payment_ref, ".$this->db->dbprefix('sales').".reference_no as sale_ref, ".$this->db->dbprefix('purchases').".reference_no as purchase_ref, paid_by, amount, type")
			->from('payments')
			->join('sales', 'payments.sale_id=sales.id', 'left')
			->join('purchases', 'payments.purchase_id=purchases.id', 'left')
			->group_by('payments.id');

			if($user) { $this->datatables->where('payments.created_by', $user); }
			if($customer) { $this->datatables->where('sales.customer_id', $customer); }
			if($supplier) { $this->datatables->where('purchases.supplier_id', $supplier); }
			if($biller) { $this->datatables->where('sales.biller_id', $biller); }
			if($customer) { $this->datatables->where('sales.customer_id', $customer); }
			if($payment_ref) { $this->datatables->like('payments.reference_no', $payment_ref, 'both'); }
			if($sale_ref) { $this->datatables->like('sales.reference_no', $sale_ref, 'both'); }
			if($purchase_ref) { $this->datatables->like('purchases.reference_no', $purchase_ref, 'both'); }
			if($start_date) { $this->datatables->where('payments.date BETWEEN "'. $start_date. '" and "'.$end_date.'"'); }

			echo $this->datatables->generate();

		}
		
	}

	function customers() {
		$this->sma->checkPermissions('customers');
		$this->data['error'] = (validation_errors()) ? validation_errors() : $this->session->flashdata('error');	   

		$bc = array(array('link' => base_url(), 'page' => lang('home')), array('link' => site_url('reports'), 'page' => lang('reports')), array('link' => '#', 'page' => lang('customers_report')));
		$meta = array('page_title' => lang('customers_report'), 'bc' => $bc);
		$this->page_construct('reports/customers', $meta, $this->data);
	}

	function getCustomers($pdf = NULL, $xls = NULL) {
		$this->sma->checkPermissions('customers', TRUE);

		if($pdf || $xls) {

			$this->db
			->select($this->db->dbprefix('companies').".id as id, company, name, phone, email, count(".$this->db->dbprefix('sales').".id) as total, COALESCE(sum(grand_total), 0) as total_amount, COALESCE(sum(paid), 0) as paid, ( COALESCE(sum(grand_total), 0) - COALESCE(sum(paid), 0)) as balance", FALSE)
			->from("companies")
			->join('sales', 'sales.customer_id=companies.id')
			->where('companies.group_name', 'customer')
			->order_by('companies.company asc')
			->group_by('companies.id');

			$q = $this->db->get();
			if($q->num_rows() > 0) {
				foreach (($q->result()) as $row) {
					$data[] = $row;
				}
			} else {
				$data = NULL;
			}

			if(!empty($data)) {

				$this->load->library('excel');
				$this->excel->setActiveSheetIndex(0);
				$this->excel->getActiveSheet()->setTitle(lang('customers_report'));
				$this->excel->getActiveSheet()->SetCellValue('A1', lang('company'));
				$this->excel->getActiveSheet()->SetCellValue('B1', lang('name'));
				$this->excel->getActiveSheet()->SetCellValue('C1', lang('phone'));
				$this->excel->getActiveSheet()->SetCellValue('D1', lang('email'));
				$this->excel->getActiveSheet()->SetCellValue('E1', lang('total_sales'));
				$this->excel->getActiveSheet()->SetCellValue('F1', lang('total_amount'));
				$this->excel->getActiveSheet()->SetCellValue('G1', lang('paid'));
				$this->excel->getActiveSheet()->SetCellValue('H1', lang('balance'));

				$row = 2;
				foreach ($data as $data_row) {
					$this->excel->getActiveSheet()->SetCellValue('A' . $row, $data_row->company);
					$this->excel->getActiveSheet()->SetCellValue('B' . $row, $data_row->name);
					$this->excel->getActiveSheet()->SetCellValue('C' . $row, $data_row->phone);
					$this->excel->getActiveSheet()->SetCellValue('D' . $row, $data_row->email);
					$this->excel->getActiveSheet()->SetCellValue('E' . $row, $this->sma->formatNumber($data_row->total));
					$this->excel->getActiveSheet()->SetCellValue('F' . $row, $this->sma->formatMoney($data_row->total_amount));
					$this->excel->getActiveSheet()->SetCellValue('G' . $row, $this->sma->formatMoney($data_row->paid));
					$this->excel->getActiveSheet()->SetCellValue('H' . $row, $this->sma->formatMoney($data_row->balance));
					$row++;
				}

				$this->excel->getActiveSheet()->getColumnDimension('A')->setWidth(20);
				$this->excel->getActiveSheet()->getColumnDimension('B')->setWidth(25);
				$this->excel->getActiveSheet()->getColumnDimension('C')->setWidth(25);
				$this->excel->getActiveSheet()->getColumnDimension('D')->setWidth(25);
				$this->excel->getActiveSheet()->getColumnDimension('E')->setWidth(15);
				$this->excel->getActiveSheet()->getColumnDimension('F')->setWidth(15);
				$this->excel->getActiveSheet()->getColumnDimension('G')->setWidth(15);
				$filename = 'customers_report';
				$this->excel->getDefaultStyle()->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
				if($pdf) {
					$styleArray = array(
						'borders' => array(
							'allborders' => array(
								'style' => PHPExcel_Style_Border::BORDER_THIN
								)
							)
						);
					$this->excel->getDefaultStyle()->applyFromArray($styleArray);
					$this->excel->getActiveSheet()->getPageSetup()->setOrientation(PHPExcel_Worksheet_PageSetup::ORIENTATION_LANDSCAPE);
					require_once (APPPATH . "third_party".DIRECTORY_SEPARATOR."MPDF".DIRECTORY_SEPARATOR."mpdf.php");
					$rendererName = PHPExcel_Settings::PDF_RENDERER_MPDF;
					$rendererLibrary = 'MPDF';
					$rendererLibraryPath = APPPATH . 'third_party'. DIRECTORY_SEPARATOR . $rendererLibrary;
					if(!PHPExcel_Settings::setPdfRenderer( $rendererName, $rendererLibraryPath )) {
						die('Please set the $rendererName: ' . $rendererName . ' and $rendererLibraryPath: ' . $rendererLibraryPath . ' values' .
							PHP_EOL . ' as appropriate for your directory structure');
					}

					header('Content-Type: application/pdf');
					header('Content-Disposition: attachment;filename="' . $filename . '.pdf"');
					header('Cache-Control: max-age=0');

					$objWriter = PHPExcel_IOFactory::createWriter($this->excel, 'PDF');
					$objWriter->save('php://output');
					exit();
				}
				if($xls) {
					ob_clean();
					header('Content-Type: application/vnd.ms-excel');
					header('Content-Disposition: attachment;filename="' . $filename . '.xls"');
					header('Cache-Control: max-age=0');
					ob_clean();
					$objWriter = PHPExcel_IOFactory::createWriter($this->excel, 'Excel5');
					$objWriter->save('php://output');
					exit();
				}

			}
			$this->session->set_flashdata('error', lang('nothing_found'));
			redirect($_SERVER["HTTP_REFERER"]);

		} else {

			$this->load->library('datatables');
			$this->datatables
			->select($this->db->dbprefix('companies').".id as id, company, name, phone, email, count(".$this->db->dbprefix('sales').".id) as total, COALESCE(sum(grand_total), 0) as total_amount, COALESCE(sum(paid), 0) as paid, ( COALESCE(sum(grand_total), 0) - COALESCE(sum(paid), 0)) as balance", FALSE)
			->from("companies")
			->join('sales', 'sales.customer_id=companies.id')
			//->where('sales.sale_status !=', 'pending')
			->where('companies.group_name', 'customer')
			->group_by('companies.id')
			->add_column("Actions", "<div class='text-center'><a class=\"tip\" title='" . lang("view_report") . "' href='" . site_url('reports/customer_report/$1') . "'><span class='label label-primary'>" . lang("view_report") . "</span></a></div>", "id")
			->unset_column('id');
			echo $this->datatables->generate();

		}
		
	}

	function customer_report($user_id = NULL) {
		$this->sma->checkPermissions('customers', TRUE);
		if(!$user_id) { 
			$this->session->set_flashdata('error', lang("no_customer_selected"));
			redirect('reports/customers');
		}

		$this->data['sales'] = $this->reports_model->getSalesTotals($user_id);
		$this->data['total_sales'] = $this->reports_model->getCustomerSales($user_id);
		$this->data['total_quotes'] = $this->reports_model->getCustomerQuotes($user_id);
		$this->data['users'] = $this->reports_model->getStaff();
		$this->data['warehouses'] = $this->site->getAllWarehouses();
		$this->data['billers'] = $this->site->getAllCompanies('biller');

		$this->data['error'] = validation_errors() ? validation_errors() : $this->session->flashdata('error');

		$this->data['user_id'] = $user_id;
		$bc = array(array('link' => base_url(), 'page' => lang('home')), array('link' => site_url('reports'), 'page' => lang('reports')), array('link' => '#', 'page' => lang('customers_report')));
		$meta = array('page_title' => lang('customers_report'), 'bc' => $bc);
		$this->page_construct('reports/customer_report', $meta, $this->data);

	}

	function suppliers() {
		$this->sma->checkPermissions('suppliers');
		$this->data['error'] = (validation_errors()) ? validation_errors() : $this->session->flashdata('error');    

		$bc = array(array('link' => base_url(), 'page' => lang('home')), array('link' => site_url('reports'), 'page' => lang('reports')), array('link' => '#', 'page' => lang('suppliers_report')));
		$meta = array('page_title' => lang('suppliers_report'), 'bc' => $bc);
		$this->page_construct('reports/suppliers', $meta, $this->data);
	}

	function getSuppliers($pdf = NULL, $xls = NULL) {
		$this->sma->checkPermissions('suppliers', TRUE);

		if($pdf || $xls) {

			$this->db
			->select($this->db->dbprefix('companies').".id as id, company, name, phone, email, count(purchases.id) as total, COALESCE(sum(grand_total), 0) as total_amount, COALESCE(sum(paid), 0) as paid, ( COALESCE(sum(grand_total), 0) - COALESCE(sum(paid), 0)) as balance", FALSE)
			->from("companies")
			->join('purchases', 'purchases.supplier_id=companies.id')
			->where('companies.group_name', 'supplier')
			->order_by('companies.company asc')
			->group_by('companies.id');

			$q = $this->db->get();
			if($q->num_rows() > 0) {
				foreach (($q->result()) as $row) {
					$data[] = $row;
				}
			} else {
				$data = NULL;
			}

			if(!empty($data)) {

				$this->load->library('excel');
				$this->excel->setActiveSheetIndex(0);
				$this->excel->getActiveSheet()->setTitle(lang('suppliers_report'));
				$this->excel->getActiveSheet()->SetCellValue('A1', lang('company'));
				$this->excel->getActiveSheet()->SetCellValue('B1', lang('name'));
				$this->excel->getActiveSheet()->SetCellValue('C1', lang('phone'));
				$this->excel->getActiveSheet()->SetCellValue('D1', lang('email'));
				$this->excel->getActiveSheet()->SetCellValue('E1', lang('total_purchases'));
				$this->excel->getActiveSheet()->SetCellValue('F1', lang('total_amount'));
				$this->excel->getActiveSheet()->SetCellValue('G1', lang('paid'));
				$this->excel->getActiveSheet()->SetCellValue('H1', lang('balance'));

				$row = 2;
				foreach ($data as $data_row) {
					$this->excel->getActiveSheet()->SetCellValue('A' . $row, $data_row->company);
					$this->excel->getActiveSheet()->SetCellValue('B' . $row, $data_row->name);
					$this->excel->getActiveSheet()->SetCellValue('C' . $row, $data_row->phone);
					$this->excel->getActiveSheet()->SetCellValue('D' . $row, $data_row->email);
					$this->excel->getActiveSheet()->SetCellValue('E' . $row, $this->sma->formatNumber($data_row->total));
					$this->excel->getActiveSheet()->SetCellValue('F' . $row, $this->sma->formatMoney($data_row->total_amount));
					$this->excel->getActiveSheet()->SetCellValue('G' . $row, $this->sma->formatMoney($data_row->paid));
					$this->excel->getActiveSheet()->SetCellValue('H' . $row, $this->sma->formatMoney($data_row->balance));
					$row++;
				}

				$this->excel->getActiveSheet()->getColumnDimension('A')->setWidth(20);
				$this->excel->getActiveSheet()->getColumnDimension('B')->setWidth(25);
				$this->excel->getActiveSheet()->getColumnDimension('C')->setWidth(25);
				$this->excel->getActiveSheet()->getColumnDimension('D')->setWidth(25);
				$this->excel->getActiveSheet()->getColumnDimension('E')->setWidth(15);
				$this->excel->getActiveSheet()->getColumnDimension('F')->setWidth(15);
				$this->excel->getActiveSheet()->getColumnDimension('G')->setWidth(15);
				$filename = 'suppliers_report';
				$this->excel->getDefaultStyle()->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
				if($pdf) {
					$styleArray = array(
						'borders' => array(
							'allborders' => array(
								'style' => PHPExcel_Style_Border::BORDER_THIN
								)
							)
						);
					$this->excel->getDefaultStyle()->applyFromArray($styleArray);
					$this->excel->getActiveSheet()->getPageSetup()->setOrientation(PHPExcel_Worksheet_PageSetup::ORIENTATION_LANDSCAPE);
					require_once (APPPATH . "third_party".DIRECTORY_SEPARATOR."MPDF".DIRECTORY_SEPARATOR."mpdf.php");
					$rendererName = PHPExcel_Settings::PDF_RENDERER_MPDF;
					$rendererLibrary = 'MPDF';
					$rendererLibraryPath = APPPATH . 'third_party'. DIRECTORY_SEPARATOR . $rendererLibrary;
					if(!PHPExcel_Settings::setPdfRenderer( $rendererName, $rendererLibraryPath )) {
						die('Please set the $rendererName: ' . $rendererName . ' and $rendererLibraryPath: ' . $rendererLibraryPath . ' values' .
							PHP_EOL . ' as appropriate for your directory structure');
					}

					header('Content-Type: application/pdf');
					header('Content-Disposition: attachment;filename="' . $filename . '.pdf"');
					header('Cache-Control: max-age=0');

					$objWriter = PHPExcel_IOFactory::createWriter($this->excel, 'PDF');
					$objWriter->save('php://output');
					exit();
				}
				if($xls) {
					ob_clean();
					header('Content-Type: application/vnd.ms-excel');
					header('Content-Disposition: attachment;filename="' . $filename . '.xls"');
					header('Cache-Control: max-age=0');
					ob_clean();
					$objWriter = PHPExcel_IOFactory::createWriter($this->excel, 'Excel5');
					$objWriter->save('php://output');
					exit();
				}

			}
			$this->session->set_flashdata('error', lang('nothing_found'));
			redirect($_SERVER["HTTP_REFERER"]);

		} else {

			$this->load->library('datatables');
			$this->datatables
			->select($this->db->dbprefix('companies').".id as id, company, name, phone, email, count(".$this->db->dbprefix('purchases').".id) as total, COALESCE(sum(grand_total), 0) as total_amount, COALESCE(sum(paid), 0) as paid, ( COALESCE(sum(grand_total), 0) - COALESCE(sum(paid), 0)) as balance", FALSE)
			->from("companies")
			->join('purchases', 'purchases.supplier_id=companies.id')
			->where('companies.group_name', 'supplier')
			->group_by('companies.id')
			->add_column("Actions", "<div class='text-center'><a class=\"tip\" title='" . lang("view_report") . "' href='" . site_url('reports/supplier_report/$1') . "'><span class='label label-primary'>" . lang("view_report") . "</span></a></div>", "id")
			->unset_column('id');
			echo $this->datatables->generate();

		}
		
	}

	function supplier_report($user_id = NULL) {
		$this->sma->checkPermissions('suppliers', TRUE);
		if(!$user_id) { 
			$this->session->set_flashdata('error', lang("no_supplier_selected"));
			redirect('reports/suppliers');
		}

		$this->data['purchases'] = $this->reports_model->getPurchasesTotals($user_id);
		$this->data['total_purchases'] = $this->reports_model->getSupplierPurchases($user_id);
		$this->data['users'] = $this->reports_model->getStaff();
		$this->data['warehouses'] = $this->site->getAllWarehouses();

		$this->data['error'] = validation_errors() ? validation_errors() : $this->session->flashdata('error');

		$this->data['user_id'] = $user_id;
		$bc = array(array('link' => base_url(), 'page' => lang('home')), array('link' => site_url('reports'), 'page' => lang('reports')), array('link' => '#', 'page' => lang('suppliers_report')));
		$meta = array('page_title' => lang('suppliers_report'), 'bc' => $bc);
		$this->page_construct('reports/supplier_report', $meta, $this->data);

	} 

	function users(){
		$this->data['error'] = (validation_errors()) ? validation_errors() : $this->session->flashdata('error');    
		$bc = array(array('link' => base_url(), 'page' => lang('home')), array('link' => site_url('reports'), 'page' => lang('reports')), array('link' => '#', 'page' => lang('staff_report')));
		$meta = array('page_title' => lang('staff_report'), 'bc' => $bc);
		$this->page_construct('reports/users', $meta, $this->data);
	}

	function getUsers() {
		$this->load->library('datatables');
		$this->datatables
		->select("users.id as id, first_name, last_name, email, company, groups.name, active")
		->from("users")
		->join('groups', 'users.group_id=groups.id', 'left')
		->group_by('users.id')
		->where('company_id', NULL);
		if(!$this->Owner) { $this->datatables->where('group_id !=', 1); }
		$this->datatables
		->edit_column('active', '$1__$2', 'active, id')
		->add_column("Actions", "<div class='text-center'><a class=\"tip\" title='" . lang("view_report") . "' href='" . site_url('reports/staff_report/$1') . "'><span class='label label-primary'>" . lang("view_report") . "</span></a></div>", "id")
		->unset_column('id');
		echo $this->datatables->generate();
	}

	function staff_report($user_id = NULL, $year = NULL, $month = NULL, $pdf = NULL, $cal = 0) {

		if(!$user_id) { 
			$this->session->set_flashdata('error', lang("no_user_selected"));
			redirect('reports/users');
		}
		$this->data['error'] = validation_errors() ? validation_errors() : $this->session->flashdata('error');
		$this->data['purchases'] = $this->reports_model->getStaffPurchases($user_id);
		$this->data['sales'] = $this->reports_model->getStaffSales($user_id);
		$this->data['billers'] = $this->site->getAllCompanies('biller');
		$this->data['warehouses'] = $this->site->getAllWarehouses();

		if(!$year) { $year = date('Y'); }
		if(!$month) { $month = date('m'); }
		if($pdf) {
			if($cal) {
				$this->monthly_sales($year, $pdf, $user_id);
			} else {
				$this->daily_sales($year, $month, $pdf, $user_id);
			}
		}
		$config = array (
			'show_next_prev'  => TRUE,
			'next_prev_url'   => site_url('reports/daily_sales'),
			'month_type'   => 'long',
			'day_type'     => 'long'
			);

		$config['template'] = '{table_open}<table border="0" cellpadding="0" cellspacing="0" class="table table-bordered dfTable">{/table_open}
		{heading_row_start}<tr>{/heading_row_start}
		{heading_previous_cell}<th class="text-center"><a href="{previous_url}">&lt;&lt;</a></th>{/heading_previous_cell}
		{heading_title_cell}<th class="text-center" colspan="{colspan}" id="month_year">{heading}</th>{/heading_title_cell}
		{heading_next_cell}<th class="text-center"><a href="{next_url}">&gt;&gt;</a></th>{/heading_next_cell}
		{heading_row_end}</tr>{/heading_row_end}
		{week_row_start}<tr>{/week_row_start}
		{week_day_cell}<td class="cl_wday">{week_day}</td>{/week_day_cell}
		{week_row_end}</tr>{/week_row_end}
		{cal_row_start}<tr class="days">{/cal_row_start}
		{cal_cell_start}<td class="day">{/cal_cell_start}
		{cal_cell_content}
		<div class="day_num">{day}</div>
		<div class="content">{content}</div>
		{/cal_cell_content}
		{cal_cell_content_today}
		<div class="day_num highlight">{day}</div>
		<div class="content">{content}</div>
		{/cal_cell_content_today}
		{cal_cell_no_content}<div class="day_num">{day}</div>{/cal_cell_no_content}
		{cal_cell_no_content_today}<div class="day_num highlight">{day}</div>{/cal_cell_no_content_today}
		{cal_cell_blank}&nbsp;{/cal_cell_blank}
		{cal_cell_end}</td>{/cal_cell_end}
		{cal_row_end}</tr>{/cal_row_end}
		{table_close}</table>{/table_close}';

		$this->load->library('calendar', $config);
		$sales = $this->reports_model->getStaffDailySales($user_id, $year, $month);

		if(!empty($sales)) {
			foreach($sales as $sale){
				$daily_sale[$sale->date] = "<table class='table table-bordered table-hover table-striped table-condensed data' style='margin:0;'><tr><td>".lang("discount")."</td><td>". $this->sma->formatMoney($sale->discount) ."</td></tr><tr><td>".lang("product_tax")."</td><td>". $this->sma->formatMoney($sale->tax1) ."</td></tr><tr><td>".lang("order_tax")."</td><td>". $this->sma->formatMoney($sale->tax2) ."</td></tr><tr><td>".lang("total")."</td><td>". $this->sma->formatMoney($sale->total) ."</td></tr></table>"; 
			}
		} else { $daily_sale = array(); }
		$this->data['calender'] = $this->calendar->generate($year, $month, $daily_sale);
		if($this->input->get('pdf')) {

		}
		$this->data['year'] = $year;
		$this->data['month'] = $month;
		$this->data['msales'] = $this->reports_model->getStaffMonthlySales($user_id, $year);
		$this->data['targets'] = $this->reports_model->get2($user_id, $year);
		// echo '<pre>';
		// print_r($this->data['targets']);exit;
		$this->data['user_id'] = $user_id;
		$bc = array(array('link' => base_url(), 'page' => lang('home')), array('link' => site_url('reports'), 'page' => lang('reports')), array('link' => '#', 'page' => lang('staff_report')));
		$meta = array('page_title' => lang('staff_report'), 'bc' => $bc);
		$this->page_construct('reports/staff_report', $meta, $this->data);

	} 

	function getUserLogins($id = NULL, $pdf = NULL, $xls = NULL) {
		if($this->input->get('login_start_date')){ $login_start_date = $this->input->get('login_start_date'); } else { $login_start_date = NULL; }
		if($this->input->get('login_end_date')){ $login_end_date = $this->input->get('login_end_date'); } else { $login_end_date = NULL; }
		if($login_start_date) {
			$login_start_date = $this->sma->fld($login_start_date);
			$login_end_date = $login_end_date ? $this->sma->fld($login_end_date) : date('Y-m-d H:i:s');
		}
		if($pdf || $xls) {

			$this->db
			->select("login, ip_address, time")
			->from("user_logins")
			->where('user_id', $id)
			->order_by('time desc');
			if($login_start_date) { $this->datatables->where('time BETWEEN "'. $login_start_date. '" and "'.$login_end_date.'"', FALSE); }

			$q = $this->db->get();
			if($q->num_rows() > 0) {
				foreach (($q->result()) as $row) {
					$data[] = $row;
				}
			} else {
				$data = NULL;
			}

			if(!empty($data)) {

				$this->load->library('excel');
				$this->excel->setActiveSheetIndex(0);
				$this->excel->getActiveSheet()->setTitle(lang('staff_login_report'));
				$this->excel->getActiveSheet()->SetCellValue('A1', lang('email'));
				$this->excel->getActiveSheet()->SetCellValue('B1', lang('ip_address'));
				$this->excel->getActiveSheet()->SetCellValue('C1', lang('time'));

				$row = 2;
				foreach ($data as $data_row) {
					$this->excel->getActiveSheet()->SetCellValue('A' . $row, $data_row->login);
					$this->excel->getActiveSheet()->SetCellValue('B' . $row, $data_row->ip_address);
					$this->excel->getActiveSheet()->SetCellValue('C' . $row, $this->sma->hrld($data_row->time));
					$row++;
				}

				$this->excel->getActiveSheet()->getColumnDimension('A')->setWidth(35);
				$this->excel->getActiveSheet()->getColumnDimension('B')->setWidth(35);
				$this->excel->getActiveSheet()->getColumnDimension('C')->setWidth(35);

				$filename = 'staff_login_report';
				$this->excel->getDefaultStyle()->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
				if($pdf) {
					$styleArray = array(
						'borders' => array(
							'allborders' => array(
								'style' => PHPExcel_Style_Border::BORDER_THIN
								)
							)
						);
					$this->excel->getDefaultStyle()->applyFromArray($styleArray);
					$this->excel->getActiveSheet()->getPageSetup()->setOrientation(PHPExcel_Worksheet_PageSetup::ORIENTATION_LANDSCAPE);
					require_once (APPPATH . "third_party".DIRECTORY_SEPARATOR."MPDF".DIRECTORY_SEPARATOR."mpdf.php");
					$rendererName = PHPExcel_Settings::PDF_RENDERER_MPDF;
					$rendererLibrary = 'MPDF';
					$rendererLibraryPath = APPPATH . 'third_party'. DIRECTORY_SEPARATOR . $rendererLibrary;
					if(!PHPExcel_Settings::setPdfRenderer( $rendererName, $rendererLibraryPath )) {
						die('Please set the $rendererName: ' . $rendererName . ' and $rendererLibraryPath: ' . $rendererLibraryPath . ' values' .
							PHP_EOL . ' as appropriate for your directory structure');
					}

					header('Content-Type: application/pdf');
					header('Content-Disposition: attachment;filename="' . $filename . '.pdf"');
					header('Cache-Control: max-age=0');

					$objWriter = PHPExcel_IOFactory::createWriter($this->excel, 'PDF');
					$objWriter->save('php://output');
					exit();
				}
				if($xls) {
					$this->excel->getActiveSheet()->getStyle('C2:C'.$row)->getAlignment()->setWrapText(true);
					ob_clean();
					header('Content-Type: application/vnd.ms-excel');
					header('Content-Disposition: attachment;filename="' . $filename . '.xls"');
					header('Cache-Control: max-age=0');
					ob_clean();
					$objWriter = PHPExcel_IOFactory::createWriter($this->excel, 'Excel5');
					$objWriter->save('php://output');
					exit();
				}

			}
			$this->session->set_flashdata('error', lang('nothing_found'));
			redirect($_SERVER["HTTP_REFERER"]);

		} else {

			$this->load->library('datatables');
			$this->datatables
			->select("login, ip_address, time")
			->from("user_logins")
			->where('user_id', $id);
			if($login_start_date) { $this->datatables->where('time BETWEEN "'. $login_start_date. '" and "'.$login_end_date.'"', FALSE); }
			echo $this->datatables->generate();

		}
		
	}

	function getCustomerLogins($id = NULL) {
		if($this->input->get('login_start_date')){ $login_start_date = $this->input->get('login_start_date'); } else { $login_start_date = NULL; }
		if($this->input->get('login_end_date')){ $login_end_date = $this->input->get('login_end_date'); } else { $login_end_date = NULL; }
		if($login_start_date) {
			$login_start_date = $this->sma->fld($login_start_date);
			$login_end_date = $login_end_date ? $this->sma->fld($login_end_date) : date('Y-m-d H:i:s');
		}
		$this->load->library('datatables');
		$this->datatables
		->select("login, ip_address, time")
		->from("user_logins")
		->where('customer_id', $id);
		if($login_start_date) { $this->datatables->where('time BETWEEN "'. $login_start_date. '" and "'.$login_end_date.'"'); }
		echo $this->datatables->generate();
	}

	function profit_loss($start_date = NULL, $end_date = NULL) {
		$this->sma->checkPermissions('profit_loss');
		if(!$start_date) { $start = $this->db->escape(date('Y-m').'-1'); $start_date = date('Y-m').'-1'; } else { $start = $this->db->escape(urldecode($start_date)); }
		if(!$end_date) { $end = $this->db->escape(date('Y-m-d H:i')); $end_date = date('Y-m-d H:i'); } else { $end = $this->db->escape(urldecode($end_date)); }
		$this->data['error'] = (validation_errors()) ? validation_errors() : $this->session->flashdata('error');    

		$this->data['total_purchases'] = $this->reports_model->getTotalPurchases($start, $end);
		$this->data['total_sales'] = $this->reports_model->getTotalSales($start, $end);
		$this->data['total_expenses'] = $this->reports_model->getTotalExpenses($start, $end);
		$this->data['total_paid'] = $this->reports_model->getTotalPaidAmount($start, $end);
		$this->data['total_received'] = $this->reports_model->getTotalReceivedAmount($start, $end);
		$this->data['total_received_cash'] = $this->reports_model->getTotalReceivedCashAmount($start, $end);
		$this->data['total_received_cc'] = $this->reports_model->getTotalReceivedCCAmount($start, $end);
		$this->data['total_received_cheque'] = $this->reports_model->getTotalReceivedChequeAmount($start, $end);
		$this->data['total_received_ppp'] = $this->reports_model->getTotalReceivedPPPAmount($start, $end);
		$this->data['total_received_stripe'] = $this->reports_model->getTotalReceivedStripeAmount($start, $end);
		$this->data['total_returned'] = $this->reports_model->getTotalReturnedAmount($start, $end);
		$this->data['start'] = urldecode($start_date);
		$this->data['end'] = urldecode($end_date);

		$bc = array(array('link' => base_url(), 'page' => lang('home')), array('link' => site_url('reports'), 'page' => lang('reports')), array('link' => '#', 'page' => lang('profit_loss')));
		$meta = array('page_title' => lang('profit_loss'), 'bc' => $bc);
		$this->page_construct('reports/profit_loss', $meta, $this->data);
	}

	function profit_loss_pdf($start_date = NULL, $end_date = NULL) {
		$this->sma->checkPermissions('profit_loss');
		if(!$start_date) { $start = $this->db->escape(date('Y-m').'-1'); $start_date = date('Y-m').'-1'; } else { $start = $this->db->escape(urldecode($start_date)); }
		if(!$end_date) { $end = $this->db->escape(date('Y-m-d H:i')); $end_date = date('Y-m-d H:i'); } else { $end = $this->db->escape(urldecode($end_date)); }

		$this->data['total_purchases'] = $this->reports_model->getTotalPurchases($start, $end);
		$this->data['total_sales'] = $this->reports_model->getTotalSales($start, $end);
		$this->data['total_expenses'] = $this->reports_model->getTotalExpenses($start, $end);
		$this->data['total_paid'] = $this->reports_model->getTotalPaidAmount($start, $end);
		$this->data['total_received'] = $this->reports_model->getTotalReceivedAmount($start, $end);
		$this->data['total_received_cash'] = $this->reports_model->getTotalReceivedCashAmount($start, $end);
		$this->data['total_received_cc'] = $this->reports_model->getTotalReceivedCCAmount($start, $end);
		$this->data['total_received_cheque'] = $this->reports_model->getTotalReceivedChequeAmount($start, $end);
		$this->data['total_received_ppp'] = $this->reports_model->getTotalReceivedPPPAmount($start, $end);
		$this->data['total_received_stripe'] = $this->reports_model->getTotalReceivedStripeAmount($start, $end);
		$this->data['total_returned'] = $this->reports_model->getTotalReturnedAmount($start, $end);
		$this->data['start'] = urldecode($start_date);
		$this->data['end'] = urldecode($end_date);

		$html = $this->load->view($this->theme.'reports/profit_loss_pdf', $this->data, true);
		$name = lang("profit_loss") . "-" . str_replace(array('-', ' ', ':'), '_', $this->data['start']) . "-" . str_replace(array('-', ' ', ':'), '_', $this->data['end']) . ".pdf";
		$this->sma->generate_pdf($html, $name, false, false, false, false, false, 'L');
	}

	function register() {
		$this->sma->checkPermissions('register');
		$this->data['error'] = (validation_errors()) ? validation_errors() : $this->session->flashdata('error');    
		$this->data['users'] = $this->reports_model->getStaff();
		$bc = array(array('link' => base_url(), 'page' => lang('home')), array('link' => site_url('reports'), 'page' => lang('reports')), array('link' => '#', 'page' => lang('register_report')));
		$meta = array('page_title' => lang('register_report'), 'bc' => $bc);
		$this->page_construct('reports/register', $meta, $this->data);
	}

	function getRrgisterlogs($pdf = NULL, $xls = NULL) {
		$this->sma->checkPermissions('register', TRUE);
		if($this->input->get('user')){ $user = $this->input->get('user'); } else { $user = NULL; }
		if($this->input->get('start_date')){ $start_date = $this->input->get('start_date'); } else { $start_date = NULL; }
		if($this->input->get('end_date')){ $end_date = $this->input->get('end_date'); } else { $end_date = NULL; }
		if($start_date) {
			$start_date = $this->sma->fld($start_date);
			$end_date = $this->sma->fld($end_date);
		}

		if($pdf || $xls) {

			$this->db
			->select("date, closed_at, CONCAT(".$this->db->dbprefix('users').".first_name, ' ', ".$this->db->dbprefix('users').".last_name, '<br>', users.email) as user, cash_in_hand, total_cc_slips, total_cheques, total_cash, note", FALSE)
			->from("pos_register")
			->join('users', 'users.id=pos_register.user_id', 'left')
			->order_by('date desc');
				//->where('status', 'close');

			if($user) { $this->db->where('pos_register.user_id', $user); }
			if($start_date) { $this->db->where('date BETWEEN "'. $start_date. '" and "'.$end_date.'"'); }

			$q = $this->db->get();
			if($q->num_rows() > 0) {
				foreach (($q->result()) as $row) {
					$data[] = $row;
				}
			} else {
				$data = NULL;
			}

			if(!empty($data)) {

				$this->load->library('excel');
				$this->excel->setActiveSheetIndex(0);
				$this->excel->getActiveSheet()->setTitle(lang('register_report'));
				$this->excel->getActiveSheet()->SetCellValue('A1', lang('open_time'));
				$this->excel->getActiveSheet()->SetCellValue('B1', lang('close_time'));
				$this->excel->getActiveSheet()->SetCellValue('C1', lang('user'));
				$this->excel->getActiveSheet()->SetCellValue('D1', lang('cash_in_hand'));
				$this->excel->getActiveSheet()->SetCellValue('E1', lang('cc_slips'));
				$this->excel->getActiveSheet()->SetCellValue('F1', lang('cheques'));
				$this->excel->getActiveSheet()->SetCellValue('G1', lang('total_cash'));
				$this->excel->getActiveSheet()->SetCellValue('H1', lang('note'));

				$row = 2;
				foreach ($data as $data_row) {
					$this->excel->getActiveSheet()->SetCellValue('A' . $row, $this->sma->hrld($data_row->date));
					$this->excel->getActiveSheet()->SetCellValue('B' . $row, $data_row->closed_at);
					$this->excel->getActiveSheet()->SetCellValue('C' . $row, $data_row->user);
					$this->excel->getActiveSheet()->SetCellValue('D' . $row, $data_row->cash_in_hand);
					$this->excel->getActiveSheet()->SetCellValue('E' . $row, lang($data_row->total_cc_slips));
					$this->excel->getActiveSheet()->SetCellValue('F' . $row, $data_row->total_cheques);
					$this->excel->getActiveSheet()->SetCellValue('G' . $row, $data_row->total_cash);
					$this->excel->getActiveSheet()->SetCellValue('H' . $row, $data_row->note);
					$row++;
				}

				$this->excel->getActiveSheet()->getColumnDimension('A')->setWidth(25);
				$this->excel->getActiveSheet()->getColumnDimension('B')->setWidth(25);
				$this->excel->getActiveSheet()->getColumnDimension('C')->setWidth(25);
				$this->excel->getActiveSheet()->getColumnDimension('D')->setWidth(15);
				$this->excel->getActiveSheet()->getColumnDimension('E')->setWidth(15);
				$this->excel->getActiveSheet()->getColumnDimension('F')->setWidth(15);
				$this->excel->getActiveSheet()->getColumnDimension('G')->setWidth(15);
				$this->excel->getActiveSheet()->getColumnDimension('H')->setWidth(35);
				$filename = 'register_report';
				$this->excel->getDefaultStyle()->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
				if($pdf) {
					$styleArray = array(
						'borders' => array(
							'allborders' => array(
								'style' => PHPExcel_Style_Border::BORDER_THIN
								)
							)
						);
					$this->excel->getDefaultStyle()->applyFromArray($styleArray);
					$this->excel->getActiveSheet()->getPageSetup()->setOrientation(PHPExcel_Worksheet_PageSetup::ORIENTATION_LANDSCAPE);
					require_once (APPPATH . "third_party".DIRECTORY_SEPARATOR."MPDF".DIRECTORY_SEPARATOR."mpdf.php");
					$rendererName = PHPExcel_Settings::PDF_RENDERER_MPDF;
					$rendererLibrary = 'MPDF';
					$rendererLibraryPath = APPPATH . 'third_party'. DIRECTORY_SEPARATOR . $rendererLibrary;
					if(!PHPExcel_Settings::setPdfRenderer( $rendererName, $rendererLibraryPath )) {
						die('Please set the $rendererName: ' . $rendererName . ' and $rendererLibraryPath: ' . $rendererLibraryPath . ' values' .
							PHP_EOL . ' as appropriate for your directory structure');
					}

					header('Content-Type: application/pdf');
					header('Content-Disposition: attachment;filename="' . $filename . '.pdf"');
					header('Cache-Control: max-age=0');

					$objWriter = PHPExcel_IOFactory::createWriter($this->excel, 'PDF');
					$objWriter->save('php://output');
					exit();
				}
				if($xls) {
					$this->excel->getActiveSheet()->getStyle('C2:C'.$row)->getAlignment()->setWrapText(true);
					ob_clean();
					header('Content-Type: application/vnd.ms-excel');
					header('Content-Disposition: attachment;filename="' . $filename . '.xls"');
					header('Cache-Control: max-age=0');
					ob_clean();
					$objWriter = PHPExcel_IOFactory::createWriter($this->excel, 'Excel5');
					$objWriter->save('php://output');
					exit();
				}

			}
			$this->session->set_flashdata('error', lang('nothing_found'));
			redirect($_SERVER["HTTP_REFERER"]);

		} else {

			$this->load->library('datatables');
			$this->datatables
			->select("date, closed_at, CONCAT(".$this->db->dbprefix('users').".first_name, ' ', ".$this->db->dbprefix('users').".last_name, '<br>', ".$this->db->dbprefix('users').".email) as user, cash_in_hand, total_cc_slips, total_cheques, total_cash, note", FALSE)
			->from("pos_register")
			->join('users', 'users.id=pos_register.user_id', 'left');

			if($user) { $this->datatables->where('pos_register.user_id', $user); }
			if($start_date) { $this->datatables->where('date BETWEEN "'. $start_date. '" and "'.$end_date.'"'); }
			
			echo $this->datatables->generate();

		}

	}

	public function owner(){

// 		echo '<pre>';

// 		//print_r($this->getALLUserTargetTable(3,'2015-11-2'));
// 		print_r($this->getALLDistributorTargetTable('2015-11-2'));
// 		echo '<pre>';
// exit;
		$data = $this->getALLDistributorTargetTable(date('Y-m-d'));

		if(!empty($data)){
				$this->load->library('excel');
				$this->excel->setActiveSheetIndex(0);
				$this->excel->getActiveSheet()->setTitle("Target vs Sales");
				$this->excel->getActiveSheet()->SetCellValue('A1', lang('category'));
				$this->excel->getActiveSheet()->SetCellValue('B1', lang('target'));
				$this->excel->getActiveSheet()->SetCellValue('C1', lang('sales'));
				$this->excel->getActiveSheet()->SetCellValue('D1', lang('variance'));

				$row = 2;

				// foreach ($data as $key => $users){
				// 	//echo $key.',';
				// 	$this->excel->getActiveSheet()->SetCellValue('A' . $row, $key);
				// 		$row++;
						foreach ($data as $key2 => $targets) {
							$this->excel->getActiveSheet()->SetCellValue('A' . $row, $key2);
							$row++;
							foreach ($targets as $value) {
								$variance = 0;
								if($value['target'] > 0 && $value['sales'] > 0){
									$variance = $value['sales']/$value['target']*100;
								}
								$this->excel->getActiveSheet()->SetCellValue('A' . $row, $value['code']);
								$this->excel->getActiveSheet()->SetCellValue('B' . $row, $value['target']);
								$this->excel->getActiveSheet()->SetCellValue('C' . $row, $value['sales']);
								$this->excel->getActiveSheet()->SetCellValue('D' . $row, $variance);
								$row++;
							}
							$row += 2;
						}
				// 	$row += 2;
				// }

				$this->excel->getActiveSheet()->getColumnDimension('A')->setWidth(25);
				$this->excel->getActiveSheet()->getColumnDimension('B')->setWidth(25);
				$this->excel->getActiveSheet()->getColumnDimension('C')->setWidth(25);
				$this->excel->getActiveSheet()->getColumnDimension('D')->setWidth(25);

				$filename = 'sales_vs_targets_report';
				$this->excel->getDefaultStyle()->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);

				$this->excel->getActiveSheet()->getStyle('C2:C'.$row)->getAlignment()->setWrapText(true);
					ob_clean();
					header('Content-Type: application/vnd.ms-excel');
					header('Content-Disposition: attachment;filename="' . $filename . '.xls"');
					header('Cache-Control: max-age=0');
					ob_clean();
					$objWriter = PHPExcel_IOFactory::createWriter($this->excel, 'Excel5');
					$objWriter->save('php://output');
					exit();
		}else{
			$this->session->set_flashdata('error', lang('nothing_found'));
			redirect($_SERVER["HTTP_REFERER"]);
		}
	}

		public function distributor($id = null){
			if($id == null){
    		 $this->session->set_flashdata('error',lang('no_biller_selected'));
        redirect("reports/distributor_targets");
    	}

			// $user = $this->session->userdata('user_id');
			// $dist = $this->db->select('biller_id')->from('users')->where('id',$user)->get()->result();

    	$dist = $this->db->where('id',$id)->get('companies')->result();
    	$name = $dist[0]->name;
		$data = $this->getDistributorTargetTable($id,date('Y-m-d'));

		if(!empty($data)){
				$this->load->library('excel');
				$this->excel->setActiveSheetIndex(0);
				$this->excel->getActiveSheet()->setTitle("Target vs Sales");
				$this->excel->getActiveSheet()->SetCellValue('A1', lang('biller'));
				$this->excel->getActiveSheet()->SetCellValue('B1', lang('category'));
				$this->excel->getActiveSheet()->SetCellValue('C1', lang('target'));
				$this->excel->getActiveSheet()->SetCellValue('D1', lang('sales'));
				$this->excel->getActiveSheet()->SetCellValue('E1', lang('variance'));

				$row = 2;
							foreach ($data as $value) {
								$variance = 0;
								if($value['target'] > 0 && $value['sales'] > 0){
									$variance = $value['sales']/$value['target']*100;
								}
								$this->excel->getActiveSheet()->SetCellValue('A' . $row, $name);
								$this->excel->getActiveSheet()->SetCellValue('B' . $row, $value['code']);
								$this->excel->getActiveSheet()->SetCellValue('C' . $row, $value['target']);
								$this->excel->getActiveSheet()->SetCellValue('D' . $row, $value['sales']);
								$this->excel->getActiveSheet()->SetCellValue('E' . $row, $variance);
								$row++;
							}

				$this->excel->getActiveSheet()->getColumnDimension('A')->setWidth(25);
				$this->excel->getActiveSheet()->getColumnDimension('B')->setWidth(25);
				$this->excel->getActiveSheet()->getColumnDimension('C')->setWidth(25);
				$this->excel->getActiveSheet()->getColumnDimension('D')->setWidth(25);

				$filename = '('.$name.')sales_vs_targets_report'.date('Y-m-d');
				$this->excel->getDefaultStyle()->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);

				$this->excel->getActiveSheet()->getStyle('C2:C'.$row)->getAlignment()->setWrapText(true);
					ob_clean();
					header('Content-Type: application/vnd.ms-excel');
					header('Content-Disposition: attachment;filename="' . $filename . '.xls"');
					header('Cache-Control: max-age=0');
					ob_clean();
					$objWriter = PHPExcel_IOFactory::createWriter($this->excel, 'Excel5');
					$objWriter->save('php://output');
					exit();
		}else{
			$this->session->set_flashdata('error', lang('nothing_found'));
			redirect($_SERVER["HTTP_REFERER"]);
		}
	}

	public function user($id = null){
			if($id == null){
    		 $this->session->set_flashdata('error',lang('no_user_selected'));
        redirect("reports/user_targets");
    	}

		$data = $this->getUserTargetTable($id,date('Y-m-d'));

		$name = $this->db->select('concat(first_name, " ", last_name) as full_name',false)->from('users')->where('id',$id)->get()->result();

    	$fullname = $name[0]->full_name;
		//echo '<pre>';print_r($data);echo '<pre>';exit;

		if(!empty($data)){
				$this->load->library('excel');
				$this->excel->setActiveSheetIndex(0);
				$this->excel->getActiveSheet()->setTitle("Target vs Sales");
				$this->excel->getActiveSheet()->SetCellValue('A1', lang('full_name'));
				$this->excel->getActiveSheet()->SetCellValue('B1', lang('category'));
				$this->excel->getActiveSheet()->SetCellValue('C1', lang('target'));
				$this->excel->getActiveSheet()->SetCellValue('D1', lang('sales'));
				$this->excel->getActiveSheet()->SetCellValue('E1', lang('variance'));

				$row = 2;
							foreach ($data as $value) {
								$variance = 0;
								if($value[1] > 0 && $value[2] > 0){
									$variance = $value[2]/$value[1]*100;
								}
								$this->excel->getActiveSheet()->SetCellValue('A' . $row, $fullname);
								$this->excel->getActiveSheet()->SetCellValue('B' . $row, $value[0]);
								$this->excel->getActiveSheet()->SetCellValue('C' . $row, $value[1]);
								$this->excel->getActiveSheet()->SetCellValue('D' . $row, $value[2]);
								$this->excel->getActiveSheet()->SetCellValue('E' . $row, $variance);
								$row++;
							}

				$this->excel->getActiveSheet()->getColumnDimension('A')->setWidth(25);
				$this->excel->getActiveSheet()->getColumnDimension('B')->setWidth(25);
				$this->excel->getActiveSheet()->getColumnDimension('C')->setWidth(25);
				$this->excel->getActiveSheet()->getColumnDimension('D')->setWidth(25);
				$this->excel->getActiveSheet()->getColumnDimension('E')->setWidth(25);

				$filename = '('.$fullname.') sales_vs_targets_report-'.date('Y-m-d');
				$this->excel->getDefaultStyle()->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);

				$this->excel->getActiveSheet()->getStyle('C2:C'.$row)->getAlignment()->setWrapText(true);
					ob_clean();
					header('Content-Type: application/vnd.ms-excel');
					header('Content-Disposition: attachment;filename="' . $filename . '.xls"');
					header('Cache-Control: max-age=0');
					ob_clean();
					$objWriter = PHPExcel_IOFactory::createWriter($this->excel, 'Excel5');
					$objWriter->save('php://output');
					exit();
		}else{
			$this->session->set_flashdata('error', lang('nothing_found'));
			redirect($_SERVER["HTTP_REFERER"]);
		}
	}

	function getUserTargetTable($userId,$date){
		//return all user ids

        $categories = $this->db
        ->get('Categories')->result();

        	
        	$temp = [];

        foreach ($categories as $cat) {

        	$row = [];
        	$ddkio = (array)$cat;
        	//echo $ddkio['code']. ",";
        	$ddkio['code'] = $ddkio['name'];
        	array_push($row,$ddkio['code']);

        	if($this->reports_model->getTag($ddkio['id'],$userId,$date) != ""){
        		array_push($row,$this->reports_model->getTag($ddkio['id'],$userId,$date));
        	}else{
        		//echo "0,";
        		array_push($row,"0");
        	}

        	if($this->reports_model->getSal($ddkio['id'],$userId,$date) != ""){
        		//echo $this->reports_model->getSal($ddkio['id'],$qaz,$date) . "\n" ;
        		array_push($row,$this->reports_model->getSal($ddkio['id'],$userId,$date) );
        	}else{
        		//echo "0,\n";
        		array_push($row,"0");
        	}

        	array_push($temp,$row);


        }
        

        return $temp;
		

	}




//@Erick this method has a problem
	function getALLUserTargetTable($distId,$date){
		//return all user ids

		$result = [];
       $usersObj = $this->db->select('id, concat(first_name, " ",last_name) as full_name',false)
        ->from('users')
        ->where('biller_id',$distId)
        ->where('group_id',5)
        ->get()->result();

        $categories = $this->db
        ->get('Categories')->result();

        //$p = [];
        foreach ($usersObj as $user) {
        	$i = (array)$user;
        	

        	
        	$temp = [];

        foreach ($categories as $cat) {

        	$row = [];
        	$ddkio = (array)$cat;
        	//echo $ddkio['code']. ",";
        	$ddkio['code'] = $ddkio['name'];
        	array_push($row,$ddkio['code']);

        	if($this->reports_model->getTag($ddkio['id'],$i['id'],$date) != ""){
        		array_push($row,$this->reports_model->getTag($ddkio['id'],$i['id'],$date));
        	}else{
        		//echo "0,";
        		array_push($row,"0");
        	}

        	if($this->reports_model->getSal($ddkio['id'],$i['id'],$date) != ""){
        		//echo $this->reports_model->getSal($ddkio['id'],$qaz,$date) . "\n" ;
        		array_push($row,$this->reports_model->getSal($ddkio['id'],$i['id'],$date) );
        	}else{
        		//echo "0,\n";
        		array_push($row,"0");
        	}

        	array_push($temp,$row);


        }

        $result[$i['full_name']] = $temp;

        	
        }

        

        return $result;
		

	}


	function getDistributorTargetTable($distId,$date){

		$finalResult = [];
		$result = [];

       $usersObj = $this->db->select('id, concat(first_name, " ",last_name) as full_name',false)
        ->from('users')
        ->where('biller_id',$distId)
        ->where('group_id',5)
        ->get()->result();

        $p = [];
        foreach ($usersObj as $user) {
        	$i = (array)$user;

        	array_push($p, $i['id']);

        }

        $categories = $this->db
        ->get('Categories')->result();

        //echo $qaz =  implode(",", $p);
        $qaz =  implode(",", $p);

        //echo "\n";


        foreach ($categories as $cat) {

        	$temp = [];
        	$ddkio = (array)$cat;
        	//echo $ddkio['code']. ",";
        	//array_push($temp,$ddkio['code']);
        	$temp['code'] = $ddkio['name'];

        	if($this->reports_model->getTag($ddkio['id'],$qaz,$date) != ""){
        		//array_push($temp,$this->reports_model->getTag($ddkio['id'],$qaz,$date));
        		$temp['target'] = $this->reports_model->getTag($ddkio['id'],$qaz,$date);
        	}else{
        		//echo "0,";
        		//array_push($temp,"0");
        		$temp['target'] = "0";
        	}

        	if($this->reports_model->getSal($ddkio['id'],$qaz,$date) != ""){
        		//echo $this->reports_model->getSal($ddkio['id'],$qaz,$date) . "\n" ;
        		//array_push($temp,$this->reports_model->getSal($ddkio['id'],$qaz,$date) );
        		$temp['sales'] = $this->reports_model->getSal($ddkio['id'],$qaz,$date);
        	}else{
        		//echo "0,\n";
        		$temp['sales'] = "0";
        	}

        	array_push($result,$temp);

        }

        //$finalResult[$name] = $result;

        return $result;


    }


//@Erick this is returning for each distributor only one user
    function getALLDistributorTargetTable($date){
    	$name = "";
    	$result = [];
		$distObj = $this->db->select('id,name')
        ->from('companies')
        ->where('group_name','biller')
        ->get()->result();

        foreach ($distObj as $dis) {
        	$ddkio = (array)$dis;
        	//echo $ddkio['name']  . "\n"; 

      	$result[$ddkio['name']] = $this->getDistributorTargetTable($ddkio['id'],$date);
        	//$result[] = $this->getDistributorTargetTable($ddkio['id'],$date);

        	//echo "\n";
        }

        return $result;

    }

    public function user_targets(){
    	 $this->data['error'] = (validation_errors()) ? validation_errors() : $this->session->flashdata('error');    
        $bc = array(array('link' => base_url(), 'page' => lang('home')), array('link' => site_url('reports'), 'page' => lang('reports')), array('link' => '#', 'page' => lang('list_users')));
        $meta = array('page_title' => lang('target_management'), 'bc' => $bc);
        $this->page_construct('reports/target_user', $meta, $this->data);
    }

        function gettagUsers() {
        $this->load->library('datatables');
        $this->datatables
        ->select("users.id as id, first_name, last_name, email,groups.name")
        ->from("users")
        ->join('groups', 'users.group_id=groups.id', 'left')
        ->group_by('users.id')
        ->where('company_id', NULL)
        ->where('group_id',5);
        //if(!$this->Owner) { $this->datatables->where('group_id !=', 1); }
        if($this->Admin) { $this->datatables->where('warehouse_id', $this->session->userdata('warehouse_id')); }
        $this->datatables
        ->edit_column('active', '$1__$2', 'active, id')
        ->add_column("Actions", "<div class='text-center'><a class=\"tip\" title='" . lang("view_report") . "' href='" . site_url('reports/target_user/$1') . "'><span class='label label-primary'>" . lang("view_report") . "</span></a></div>", "id")
        ->unset_column('id');
        echo $this->datatables->generate();
    }

    public function target_user ($id = null){
    	if($id == null){
    		 $this->session->set_flashdata('error',lang('no_user_selected'));
        redirect("reports/user_targets");
    	}

    	$name = $this->db->select('concat(first_name, " ", last_name) as full_name',false)->from('users')->where('id',$id)->get()->result();

    	$this->data['fullname'] = $name[0]->full_name;

    	$contents = $this->getUserTargetTable($id,date('Y-m-d'));

$this->data['contents'] = $contents;

        $bc = array(array('link' => base_url(), 'page' => lang('home')), array('link' => site_url('reports'), 'page' => lang('user_targets')), array('link' => '#', 'page' => lang('list_targets')));
        $meta = array('page_title' => lang('staff_target'), 'bc' => $bc);
        $this->page_construct('reports/targets', $meta, $this->data);
    }

            function distributor_targets(){
        $this->data['error'] = (validation_errors()) ? validation_errors() : $this->session->flashdata('error');    
        $bc = array(array('link' => base_url(), 'page' => lang('home')), array('link' => site_url('target'), 'page' => lang('target_management')), array('link' => '#', 'page' => lang('list_targets')));
        $meta = array('page_title' => lang('target_management'), 'bc' => $bc);
        $this->page_construct('reports/distributors', $meta, $this->data);
    }

    function getBillers() {
        $this->sma->checkPermissions('index');

        $this->load->library('datatables');
        $this->datatables
                ->select("id, company, name, phone, email, city")
                ->from("companies")
                ->where('group_name', 'biller')
               ->add_column("Actions", "<div class='text-center'><a class=\"tip\" title='" . lang("view_report") . "' href='" . site_url('reports/distributor_target/$1') . "'><span class='label label-primary'>" . lang("view_report") . "</span></a></div>", "id")
                ->unset_column('id');
        echo $this->datatables->generate();
    }

     function distributor_target($id = null){

     	if($id == null){
    		 $this->session->set_flashdata('error',lang('no_user_selected'));
        redirect("reports/user_targets");
    	}

    $this->data['error'] = (validation_errors()) ? validation_errors() : $this->session->flashdata('error'); 
   

$contents = $this->getDistributorTargetTable($id,date('Y-m-d'));
// echo '<pre>';
// //print_r($contents);
// foreach ($contents as $key => $value){
	
// 		echo $value['code'].'<br>';
// }
// exit;

$dist = $this->db->where('id',$id)->get('companies')->result();
    	$this->data['fullname'] = $dist[0]->name;

$this->data['content'] = $contents;

        $bc = array(array('link' => base_url(), 'page' => lang('home')), array('link' => site_url('reports'), 'page' => lang('distributor_targets')), array('link' => '#', 'page' => lang('list_targets')));
        $meta = array('page_title' => lang('distributor_target'), 'bc' => $bc);
        $this->page_construct('reports/billers', $meta, $this->data);
}

}