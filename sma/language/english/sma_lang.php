<?php defined('BASEPATH') OR exit('No direct script access allowed');

/*
 * Module: General Language File for common lang keys
 * Language: English
 * 
 * Last edited:
 * 30th April 2015
 *
 * Package:
 * Stock Manage Advance v3.0
 * 
 * You can translate this file to your language. 
 * For instruction on new language setup, please visit the documentations. 
 * You also can share your language files by emailing to saleem@tecdiary.com 
 * Thank you 
 */

/* --------------------- CUSTOM FIELDS ------------------------ */
/*
* Below are custome field labels
* Please only change the part after = and make sure you change the the words in between ""; 
* $lang['bcf1']                         = "Biller Custom Field 1";
* Don't change this                     = "You can change this part";
* For support email contact@tecdiary.com Thank you!
*/

$lang['bcf1']                           = "Distributor Custom Field 1";
$lang['bcf2']                           = "Distributor Custom Field 2";
$lang['bcf3']                           = "Distributor Custom Field 3";
$lang['bcf4']                           = "Distributor Custom Field 4";
$lang['bcf5']                           = "Distributor Custom Field 5";
$lang['bcf6']                           = "Distributor Custom Field 6";
$lang['pcf1']                           = "Product Custom Field 1";
$lang['pcf2']                           = "Product Custom Field 2";
$lang['pcf3']                           = "Product Custom Field 3";
$lang['pcf4']                           = "Product Custom Field 4";
$lang['pcf5']                           = "Product Custom Field 5";
$lang['pcf6']                           = "Product Custom Field 6";
$lang['ccf1']                           = "Customer Custom Field 1";
$lang['ccf2']                           = "Customer Custom Field 2";
$lang['ccf3']                           = "Customer Custom Field 3";
$lang['ccf4']                           = "Customer Custom Field 4";
$lang['ccf5']                           = "Customer Custom Field 5";
$lang['ccf6']                           = "Customer Custom Field 6";
$lang['scf1']                           = "Supplier Custom Field 1";
$lang['scf2']                           = "Supplier Custom Field 2";
$lang['scf3']                           = "Supplier Custom Field 3";
$lang['scf4']                           = "Supplier Custom Field 4";
$lang['scf5']                           = "Supplier Custom Field 5";
$lang['scf6']                           = "Supplier Custom Field 6";

/* ----------------- DATATABLES LANGUAGE ---------------------- */
/*
* Below are datatables language entries
* Please only change the part after = and make sure you change the the words in between ""; 
* 'sEmptyTable'                     => "No data available in table",
* Don't change this                 => "You can change this part but not the word between and ending with _ like _START_;
* For support email support@tecdiary.com Thank you!
*/

$lang['datatables_lang']        = array(
    'sEmptyTable'                   => "No data available in table",
    'sInfo'                         => "Showing _START_ to _END_ of _TOTAL_ entries",
    'sInfoEmpty'                    => "Showing 0 to 0 of 0 entries",
    'sInfoFiltered'                 => "(filtered from _MAX_ total entries)",
    'sInfoPostFix'                  => "",
    'sInfoThousands'                => ",",
    'sLengthMenu'                   => "Show _MENU_ ",
    'sLoadingRecords'               => "Loading...",
    'sProcessing'                   => "Processing...",
    'sSearch'                       => "Search",
    'sZeroRecords'                  => "No matching records found",
    'sFirst'                        => "<< First",
    'sLast'                         => "Last >>",
    'sNext'                         => "Next >",
    'sPrevious'                     => "< Previous",
    'sSortAscending'                => ": activate to sort column ascending",
    'sSortDescending'               => ": activate to sort column descending"
    );

/* ----------------- Select2 LANGUAGE ---------------------- */
/*
* Below are select2 lib language entries
* Please only change the part after = and make sure you change the the words in between ""; 
* 's2_errorLoading'                 => "The results could not be loaded",
* Don't change this                 => "You can change this part but not the word between {} like {t};
* For support email support@tecdiary.com Thank you!
*/

$lang['select2_lang']               = array(
    'formatMatches_s'               => "One result is available, press enter to select it.",
    'formatMatches_p'               => "results are available, use up and down arrow keys to navigate.",
    'formatNoMatches'               => "No matches found",
    'formatInputTooShort'           => "Please type {n} or more characters",
    'formatInputTooLong_s'          => "Please delete {n} character",
    'formatInputTooLong_p'          => "Please delete {n} characters",
    'formatSelectionTooBig_s'       => "You can only select {n} item",
    'formatSelectionTooBig_p'       => "You can only select {n} items",
    'formatLoadMore'                => "Loading more results...",
    'formatAjaxError'               => "Ajax request failed",
    'formatSearching'               => "Searching..."
    );


/* ----------------- SMA GENERAL LANGUAGE KEYS -------------------- */

$lang['home']                               = "Home";
$lang['dashboard']                          = "Dashboard";
$lang['username']                           = "Username";
$lang['password']                           = "Password";
$lang['first_name']                         = "First Name";
$lang['full_name']                          = "Full Name";
$lang['last_name']                          = "Last Name";
$lang['confirm_password']                   = "Confirm Password";
$lang['email']                              = "Email";
$lang['phone']                              = "Phone";
$lang['company']                            = "Company";
$lang['product_code']                       = "Product Code";
$lang['product_name']                       = "Product Name";
$lang['cname']                              = "Customer Name";
$lang['barcode_symbology']                  = "Barcode Symbology";
$lang['product_unit']                       = "Product Unit";
$lang['product_price']                      = "Product Price";
$lang['contact_person']                     = "Contact Person";
$lang['email_address']                      = "Email Address";
$lang['address']                            = "Address";
$lang['city']                               = "City";
$lang['today']                              = "Today";
$lang['welcome']                            = "Welcome";
$lang['profile']                            = "Profile";
$lang['change_password']                    = "Change Password";
$lang['logout']                             = "Logout";
$lang['notifications']                      = "Notifications";
$lang['calendar']                           = "Calendar";
$lang['messages']                           = "Messages";
$lang['styles']                             = "Styles";
$lang['language']                           = "Language";
$lang['alerts']                             = "Alerts";
$lang['list_products']                      = "List Products";
$lang['add_product']                        = "Add Product";
$lang['print_barcodes']                     = "Print Barcodes";
$lang['print_labels']                       = "Print Labels";
$lang['import_products']                    = "Import Products";
$lang['update_price']                       = "Update Price";
$lang['damage_products']                    = "Damage Product";
$lang['sales']                              = "Sales";
$lang['list_sales']                         = "List Sales";
$lang['add_sale']                           = "Add Sale";
$lang['deliveries']                         = "Deliveries";
$lang['gift_cards']                         = "Gift Cards";
$lang['quotes']                             = "Quotations";
$lang['list_quotes']                        = "List Quotation";
$lang['add_quote']                          = "Add Quotation";
$lang['purchases']                          = "Purchases";
$lang['list_purchases']                     = "List Purchases";
$lang['add_purchase']                       = "Add Purchase";
$lang['add_purchase_by_csv']                = "Add Purchase by CSV";
$lang['transfers']                          = "Transfers";
$lang['list_transfers']                     = "List Transfers";
$lang['add_transfer']                       = "Add Transfer";
$lang['add_transfer_by_csv']                = "Add Transfer by CSV";
$lang['people']                             = "People";
$lang['list_users']                         = "List Users";
$lang['new_user']                           = "Add User";
$lang['list_billers']                       = "List Distributors";
$lang['add_biller']                         = "Add Distributor";
$lang['list_customers']                     = "List Customers";
$lang['add_customer']                       = "Add Customer";
$lang['list_suppliers']                     = "List Suppliers";
$lang['add_supplier']                       = "Add Supplier";
$lang['settings']                           = "Settings";
$lang['system_settings']                    = "System Settings";
$lang['change_logo']                        = "Change Logo";
$lang['currencies']                         = "Currencies";
$lang['attributes']                         = "Product Variants";
$lang['customer_groups']                    = "Customer Groups";
$lang['categories']                         = "Categories";
$lang['subcategories']                      = "Sub Categories";
$lang['tax_rates']                          = "Tax Rates";
$lang['warehouses']                         = "Locations";
$lang['email_templates']                    = "Email Templates";
$lang['group_permissions']                  = "Group Permissions";
$lang['backup_database']                    = "Backup Database";
$lang['reports']                            = "Reports";
$lang['overview_chart']                     = "Overview Chart";
$lang['warehouse_stock']                    = "Location Stock Chart";
$lang['product_quantity_alerts']            = "Product Quantity Alerts";
$lang['product_expiry_alerts']              = "Product Expiry Alerts";
$lang['products_report']                    = "Products Report";
$lang['daily_sales']                        = "Daily Sales";
$lang['monthly_sales']                      = "Monthly Sales";
$lang['sales_report']                       = "Sales Report";
$lang['payments_report']                    = "Payments Report";
$lang['profit_and_loss']                    = "Profit and/or Loss";
$lang['purchases_report']                   = "Purchases Report";
$lang['customers_report']                   = "Customers Report";
$lang['suppliers_report']                   = "Suppliers Report";
$lang['staff_report']                       = "Staff Report";
$lang['your_ip']                            = "Your IP Address";
$lang['last_login_at']                      = "Last login at";
$lang['notification_post_at']               = "Notification posted at";
$lang['quick_links']                        = "Quick Links";
$lang['date']                               = "Date";
$lang['reference_no']                       = "Reference No";
$lang['products']                           = "Products";
$lang['customers']                          = "Customers";
$lang['suppliers']                          = "Suppliers";
$lang['users']                              = "Users";
$lang['latest_five']                        = "Latest Five";
$lang['total']                              = "Total";
$lang['payment_status']                     = "Payment Status";
$lang['route_id']                           = "Route ID";
$lang['outlet_id']                           = "Outlet ID";
$lang['category_id']                        ="Category ID";
$lang['product_details']                    = "Product Details";
$lang['quantity']                           = "Quantity";
$lang['val']                                = "Value";
$lang['type']                                = "Type";
$lang['receipt_no']                          = "Receipt No";
$lang['paid']                               = "Paid";
$lang['customer']                           = "Customer";
$lang['status']                             = "Status";
$lang['amount']                             = "Amount";
$lang['supplier']                           = "Supplier";
$lang['from']                               = "From";
$lang['to']                                 = "To";
$lang['name']                               = "Name";
$lang['create_user']                        = "Add User";
$lang['gender']                             = "Gender";
$lang['biller']                             = "Distributor";
$lang['select']                             = "Select";
$lang['warehouse']                          = "Location";
$lang['active']                             = "Active";
$lang['inactive']                           = "Inactive";
$lang['all']                                = "All";
$lang['list_results']                       = "Please use the table below to navigate or filter the results. You can download the table as excel and pdf.";
$lang['actions']                            = "Actions";
$lang['pos']                                = "POS";
$lang['access_denied']                      = "Access Denied! You don't have right to access the requested page. If you think, it's by mistake, please contact administrator.";
$lang['add']                                = "Add";
$lang['edit']                               = "Edit";
$lang['delete']                             = "Delete";
$lang['view']                               = "View";
$lang['update']                             = "Update";
$lang['save']                               = "Save";
$lang['login']                              = "Login";
$lang['submit']                             = "Submit";
$lang['no']                                 = "No";
$lang['yes']                                = "Yes";
$lang['disable']                            = "Disable";
$lang['enable']                             = "Enable";
$lang['enter_info']                         = "Please fill in the information below. The field labels marked with * are required input fields.";
$lang['update_info']                        = "Please update the information below. The field labels marked with * are required input fields.";
$lang['no_suggestions']                     = "Unable to get data for suggestions, Please check your input";
$lang['i_m_sure']                           = 'Yes I\'m sure';
$lang['r_u_sure']                           = 'Are you sure?';
$lang['export_to_excel']                    = "Export to Excel file";
$lang['export_to_pdf']                      = "Export to PDF file";
$lang['image']                              = "Image";
$lang['sale']                               = "Sale";
$lang['quote']                              = "Quotation";
$lang['purchase']                           = "Purchase";
$lang['transfer']                           = "Transfer";
$lang['payment']                            = "Payment";
$lang['payments']                           = "Payments";
$lang['orders']                             = "Orders";
$lang['pdf']                                = "PDF";
$lang['vat_no']                             = "VAT Number";
$lang['country']                            = "Country";
$lang['add_user']                           = "Add User";
$lang['type']                               = "Type";
$lang['person']                             = "Person";
$lang['state']                              = "Territory";
$lang['postal_code']                        = "Postal Code";
$lang['id']                                 = "ID";
$lang['close']                              = "Close";
$lang['male']                               = "Male";
$lang['female']                             = "Female";
$lang['notify_user']                        = "Notify User";
$lang['notify_user_by_email']               = "Notify User by Email";
$lang['billers']                            = "Distributors";
$lang['all_warehouses']                     = "All Locations";
$lang['category']                           = "Category";
$lang['product_cost']                       = "Product Cost";
$lang['quantity']                           = "Quantity";
$lang['loading_data_from_server']           = "Loading data from server";
$lang['excel']                              = "Excel";
$lang['print']                              = "Print";
$lang['ajax_error']                         = "Ajax error occurred, Please tray again.";
$lang['product_tax']                        = "Product Tax";
$lang['order_tax']                          = "Order Tax";
$lang['upload_file']                        = "Upload File";
$lang['download_sample_file']               = "Download Sample File";
$lang['csv1']                               = "The first line in downloaded csv file should remain as it is. Please do not change the order of columns.";
$lang['csv2']                               = "The correct column order is";
$lang['csv3']                               = "&amp; you must follow this. If you are using any other language then English, please make sure the csv file is UTF-8 encoded and not saved with byte order mark (BOM)";
$lang['import']                             = "Import";
$lang['note']                               = "Note";
$lang['grand_total']                        = "Grand Total";
$lang['download_pdf']                       = "Download as PDF";
$lang['no_zero_required']                   = "The %s field is required";
$lang['no_product_found']                   = "No product found";
$lang['pending']                            = "Pending";
$lang['sent']                               = "Sent";
$lang['completed']                          = "Completed";
$lang['shipping']                           = "Shipping";
$lang['add_product_to_order']               = "Please add products to order list";
$lang['order_items']                        = "Order Items";
$lang['net_unit_cost']                      = "Net Unit Cost";
$lang['net_unit_price']                     = "Net Unit Price";
$lang['expiry_date']                        = "Expiry Date";
$lang['subtotal']                           = "Subtotal";
$lang['reset']                              = "Reset";
$lang['items']                              = "Items";
$lang['au_pr_name_tip']                     = "Please start typing code/name for suggestions or just scan barcode";
$lang['no_match_found']                     = "No matching result found! Product might be out of stock in the selected Location.";
$lang['csv_file']                           = "CSV File";
$lang['document']                           = "Attach Document";
$lang['product']                            = "Product";
$lang['user']                               = "User";
$lang['created_by']                         = "Created by";
$lang['loading_data']                       = "Loading table data from server";
$lang['tel']                                = "Tel";
$lang['ref']                                = "Reference";
$lang['description']                        = "Description";
$lang['code']                               = "Code";
$lang['tax']                                = "Tax";
$lang['unit_price']                         = "Unit Price";
$lang['discount']                           = "Discount";
$lang['order_discount']                     = "Order Discount";
$lang['total_amount']                       = "Total Amount";
$lang['download_excel']                     = "Download as Excel";
$lang['subject']                            = "Subject";
$lang['cc']                                 = "CC";
$lang['bcc']                                = "BCC";
$lang['message']                            = "Message";
$lang['show_bcc']                           = "Show/Hide BCC";
$lang['price']                              = "Price";
$lang['add_product_manually']               = "Add Product Manually";
$lang['currency']                           = "Currency";
$lang['product_discount']                   = "Product Discount";
$lang['email_sent']                         = "Email successfully sent";
$lang['add_event']                          = "Add Event";
$lang['add_modify_event']                   = "Add / Modify the Event";
$lang['adding']                             = "Adding...";
$lang['delete']                             = "Delete";
$lang['deleting']                           = "Deleting...";
$lang['calendar_line']                      = "Please click the date to add/modify the event.";
$lang['discount_label']                     = "Discount (5/5%)";
$lang['product_expiry']                     = "product_expiry";
$lang['unit']                               = "Unit";
$lang['cost']                               = "Cost";
$lang['tax_method']                         = "Tax Method";
$lang['inclusive']                          = "Inclusive";
$lang['exclusive']                          = "Exclusive";
$lang['expiry']                             = "Expiry";
$lang['customer_group']                     = "Customer Group";
$lang['is_required']                        = "is required";
$lang['form_action']                        = "Form Action";
$lang['return_sales']                       = "Return Sales";
$lang['list_return_sales']                  = "List Return Sales";
$lang['no_data_available']                  = "No data available";
$lang['disabled_in_demo']                   = "We are sorry but this feature is disabled in demo.";
$lang['payment_reference_no']               = "Payment Reference No";
$lang['gift_card_no']                       = "Gift Card No";
$lang['paying_by']                          = "Paying by";
$lang['cash']                               = "Cash";
$lang['gift_card']                          = "Gift Card";
$lang['CC']                                 = "Credit Card";
$lang['cheque']                             = "Cheque";
$lang['cc_no']                              = "Credit Card No";
$lang['cc_holder']                          = "Holder Name";
$lang['card_type']                          = "Card Type";
$lang['Visa']                               = "Visa";
$lang['MasterCard']                         = "MasterCard";
$lang['Amex']                               = "Amex";
$lang['Discover']                           = "Discover";
$lang['month']                              = "Month";
$lang['year']                               = "Year";
$lang['cvv2']                               = "CVV2";
$lang['cheque_no']                          = "Cheque No";
$lang['Visa']                               = "Visa";
$lang['MasterCard']                         = "MasterCard";
$lang['Amex']                               = "Amex";
$lang['Discover']                           = "Discover";
$lang['send_email']                         = "Send Email";
$lang['order_by']                           = "Ordered by";
$lang['updated_by']                         = "Updated by";
$lang['update_at']                          = "Updated at";
$lang['error_404']                          = "404 Page Not Found ";
$lang['default_customer_group']             = "Default Customer Group";
$lang['pos_settings']                       = "POS Settings";
$lang['pos_sales']                          = "POS Sales";
$lang['seller']                             = "Seller";
$lang['ip:']                                = "IP:";
$lang['sp_tax']                             = "Sold Product Tax";
$lang['pp_tax']                             = "Purchased Product Tax";
$lang['overview_chart_heading']             = "Stock Overview Chart including monthly sales with product tax and  order tax (columns), purchases (line) and current stock value by cost and price (pie). You can save the graph as jpg, png and pdf.";
$lang['stock_value']                        = "Stock Value";
$lang['stock_value_by_price']               = "Stock Value by Price";
$lang['stock_value_by_cost']                = "Stock Value by Cost";
$lang['sold']                               = "Sold";
$lang['purchased']                          = "Purchased";
$lang['chart_lable_toggle']                 = "You can change chart by clicking the chart legend. Click any legend above to show/hide it in chart.";
$lang['register_report']                    = "Register Report";
$lang['sEmptyTable']                        = "No data available in table";
$lang['upcoming_events']                    = "Upcoming Events";
$lang['clear_ls']                           = "Clear all locally saved data";
$lang['clear']                              = "Clear";
$lang['edit_order_discount']                = "Edit Order Discount";
$lang['product_variant']                    = "Product Variant";
$lang['product_variants']                   = "Product Variants";
$lang['prduct_not_found']                   = "Product not found";
$lang['list_open_registers']                = "List Open Registers";
$lang['delivery']                           = "Delivery";
$lang['serial_no']                          = "Serial Number";
$lang['logo']                               = "Logo";
$lang['attachment']                         = "Attachment";
$lang['balance']                            = "Balance";
$lang['nothing_found']                      = "No matching records found";
$lang['db_restored']                        = "Database successfully restored.";
$lang['backups']                            = "Backups";
$lang['best_seller']                        = "Best Seller";
$lang['chart']                              = "Chart";
$lang['received']                           = "Received";
$lang['returned']                           = "Returned";
$lang['award_points']                       = 'Award Points';
$lang['expenses']                           = "Expenses";
$lang['add_expense']                        = "Add Expense";
$lang['other']                              = "Other";
$lang['none']                               = "None";
$lang['calculator']                         = "Calculator";
$lang['updates']                            = "Updates";
$lang['update_available']                   = "New update available, please update now.";

$lang['please_select_these_before_adding_product'] = "Please select these before adding any product";
$lang['route_id'] = 'Route ID';
$lang['outlet_id'] = 'Outlet ID';
$lang['type'] = "Type";
$lang['select']= 'Please Select Type';
$lang['duka'] = 'Duka';
$lang['supermarket'] ='Supermarket';
$lang['wholesale'] = 'Wholesale';
$lang['receipt_no'] = 'Receipt No';
$lang['custom_fields'] = 'Custom Fields';
$lang['list_routes'] = 'List Routes';
$lang['add_route'] = 'Add Route';
$lang['list_outlets'] = 'List Outlets';
$lang['add_outlet'] = 'Add Outlet';
$lang['edit_route'] = "Edit Route";
$lang['delete_route'] = 'Delete Route';
$lang['delete_routes'] = 'Delete Routes';
$lang['route_added'] = 'Route Added!!';
$lang['route_error'] = 'Sorry, the Route is Linked to a Sale';
$lang['route_deleted'] = 'The Route was successfully DELETED!';
$lang['routes_deleted'] = 'The Selected Routes were Deleted';


$lang['edit_outlet'] = "Edit Outlet";
$lang['delete_outlet'] = 'Delete Outlet';
$lang['delete_outlets'] = 'Delete Outlets';
$lang['outlet_added'] = 'Outlet Added!!';
$lang['outlet_error'] = 'Sorry, the Outlet is Linked to a Sale';
$lang['outlet_deleted'] = 'The Outlet was successfully DELETED!';
$lang['outlets_deleted'] = 'The Selected Outlet were Deleted';
$lang['push'] = "Push to BackOffice";
$lang['sales_pushed'] = 'Sales successfully pushed to backoffice';
$lang['user_Code'] = "User Code";
$lang['target_management'] = "Targets";
$lang['add_target'] = "Add Target";
$lang['list_targets'] = "List Targets";
$lang['target'] = "Target";
$lang['set_target'] = "Set Target";
$lang['target_set'] = "Target was set successfully";
$lang['target_not_set'] = "Target was NOT set successfully";
$lang['staff_target'] = "Staff Target";
$lang['distributor_targets'] = "Distributor Targets";

$lang['van'] = "Van";
$lang['vans'] = "Vans";
$lang['list_vans'] = "List Vans";
$lang['edit_van'] = "Edit Van";
$lang['van_updated'] = "Van Updated";
$lang['add_van'] ="Add Van";
$lang['van_added'] = "Van Added";
$lang['no_van_selected'] = "No Van Selected";
$lang['van_transfers'] = "Van Transfers";
$lang['to_van'] = "To Van";
$lang['edit_van'] = "Edit Van";
$lang['view_stock'] = "View Stock";


$lang['conversions'] = "Conversions";
$lang['conversion'] = "Conversion";
$lang['add_conversion'] = "Add Conversion";
$lang['conversion_added'] = "Conversion Added to Database!";
$lang['edit_conversion'] =  "Edit Conversion";
$lang['factor'] = "Factor";
$lang['product_conversions'] = "Product Conversions";
$lang['import_conversions_by_csv'] = "Import Conversions By CSV";
$lang['product_conversion'] = "Product Conversion";