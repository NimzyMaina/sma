<?php

    $v = "";
    /* if($this->input->post('name')){
      $v .= "&product=".$this->input->post('product');
      } */
    if ($this->input->post('reference_no')) {
        $v .= "&reference_no=" . $this->input->post('reference_no');
    }
    if ($this->input->post('customer')) {
        $v .= "&customer=" . $this->input->post('customer');
    }
    if ($this->input->post('biller')) {
        $v .= "&biller=" . $this->input->post('biller');
    }
    if ($this->input->post('warehouse')) {
        $v .= "&warehouse=" . $this->input->post('warehouse');
    }
    if ($this->input->post('user')) {
        $v .= "&user=" . $this->input->post('user');
    }
    if ($this->input->post('start_date')) {
        $v .= "&start_date=" . $this->input->post('start_date');
    }
    if ($this->input->post('end_date')) {
        $v .= "&end_date=" . $this->input->post('end_date');
    }
    if ($this->input->post('product_code')) {
        $v .= "&product_code=" . $this->input->post('product_code');
    }

?>

<script>
    $(document).ready(function () {
        var oTable = $('#SlRData').dataTable({
            "aaSorting": [[0, "desc"]],
            "aLengthMenu": [[10, 25, 50, 100, -1], [10, 25, 50, 100, "<?= lang('all') ?>"]],
            "iDisplayLength": <?= $Settings->rows_per_page ?>,
            'bProcessing': true, 'bServerSide': true,
            'sAjaxSource': '<?= site_url('reports/getSalesReport/?v=1' . $v) ?>',
            'fnServerData': function (sSource, aoData, fnCallback) {
                aoData.push({"name": "<?= $this->security->get_csrf_token_name() ?>", "value": "<?= $this->security->get_csrf_hash() ?>"});
                $.ajax({'dataType': 'json', 'type': 'POST', 'url': sSource, 'data': aoData, 'success': fnCallback});
            },
            "aoColumns": [{"mRender": fld}, null, null, null, null, null,  null, null, null, null,null,null,null,{"mRender": currencyFormat}],
            "fnFooterCallback": function(nRow, aaData, iStart, iEnd, aiDisplay) {
                 var gtotal = 0, paid = 0, balance = 0;
                 for (var i = 0; i < aaData.length; i++) {
                     gtotal += parseFloat(aaData[ aiDisplay[i] ][13]);
                //     paid += parseFloat(aaData[ aiDisplay[i] ][6]);
                //     balance += parseFloat(aaData[ aiDisplay[i] ][7]);
                 }
                 var nCells = nRow.getElementsByTagName('th');
                 nCells[13].innerHTML = currencyFormat(parseFloat(gtotal));
                // nCells[6].innerHTML = currencyFormat(parseFloat(paid));
                // nCells[7].innerHTML = currencyFormat(parseFloat(balance));
            }
        }).fnSetFilteringDelay().dtFilter([
         {column_number : 0, filter_default_label: "[<?=lang('date');?> (yyyy-mm-dd)]", filter_type: "text" },
         {column_number : 1, filter_default_label: "[<?=lang('reference_no');?>]", filter_type: "text" },
         {column_number : 2, filter_default_label: "[<?=lang('biller');?>]", filter_type: "text"},
         {column_number : 3, filter_default_label: "[<?=lang('first_name');?>]", filter_type: "text" },
         {column_number : 4, filter_default_label: "[<?=lang('last_name');?>]", filter_type: "text" },
         {column_number : 5, filter_default_label: "[<?=lang('route_id');?>]", filter_type: "text" },
         {column_number : 6, filter_default_label: "[<?=lang('outlet_id');?>]", filter_type: "text" },
         {column_number : 7, filter_default_label: "[<?=lang('type');?>]", filter_type: "text" },
         {column_number : 8, filter_default_label: "[<?=lang('receipt_no');?>]", filter_type: "text" },
         {column_number : 9, filter_default_label: "[<?=lang('category_name');?>]", filter_type: "text" },
         {column_number : 10, filter_default_label: "[<?=lang('product_code');?>]", filter_type: "text" },
         {column_number : 11, filter_default_label: "[<?=lang('product_name');?>]", filter_type: "text" },
         {column_number : 12, filter_default_label: "[<?=lang('quantity');?>]", filter_type: "text" },
         //{column_number : 13, filter_default_label: "[<?=lang('val');?>]", filter_type: "text" },
         ], "footer");
    });
</script>
<script type="text/javascript">
$(document).ready(function () {
        $('#form').hide();
        <?php if ($this->input->post('customer')) { ?>
            $('#customer').val(<?= $this->input->post('customer') ?>).select2({
                minimumInputLength: 1,
                data: [],
                initSelection: function (element, callback) {
                    $.ajax({
                        type: "get", async: false,
                        url: site.base_url + "customers/suggestions/" + $(element).val(),
                        dataType: "json",
                        success: function (data) {
                            callback(data.results[0]);
                        }
                    });
                },
                ajax: {
                    url: site.base_url + "customers/suggestions",
                    dataType: 'json',
                    quietMillis: 15,
                    data: function (term, page) {
                        return {
                            term: term,
                            limit: 10
                        };
                    },
                    results: function (data, page) {
                        if (data.results != null) {
                            return {results: data.results};
                        } else {
                            return {results: [{id: '', text: 'No Match Found'}]};
                        }
                    }
                }
            });

        $('#customer').val(<?= $this->input->post('customer') ?>);
        <?php } ?>
        $('.toggle_down').click(function () {
            $("#form").slideDown();
            return false;
        });
        $('.toggle_up').click(function () {
            $("#form").slideUp();
            return false;
        });
    });
</script>


<div class="box">
    <div class="box-header">
        <h2 class="blue"><i class="fa-fw fa fa-heart"></i><?= lang('sales_report'); ?> <?php
if ($this->input->post('start_date')) {
    echo "From " . $this->input->post('start_date') . " to " . $this->input->post('end_date');
}
?></h2>
        <div class="box-icon">
            <ul class="btn-tasks">
                <li class="dropdown"><a href="#" class="toggle_up tip" title="<?=lang('hide_form')?>"><i class="icon fa fa-toggle-up"></i></a></li>
                <li class="dropdown"><a href="#" class="toggle_down tip" title="<?=lang('show_form')?>"><i class="icon fa fa-toggle-down"></i></a></li>
            </ul>
        </div>
        <div class="box-icon">
                <ul class="btn-tasks">
                    <li class="dropdown"><a href="#" id="pdf" class="tip" title="<?=lang('download_pdf')?>"><i class="icon fa fa-file-pdf-o"></i></a></li>
                    <li class="dropdown"><a href="#" id="xls" class="tip" title="<?=lang('download_xls')?>"><i class="icon fa fa-file-excel-o"></i></a></li>
                    <li class="dropdown"><a href="#" id="image" class="tip" title="<?=lang('save_image')?>"><i class="icon fa fa-file-picture-o"></i></a></li>
                </ul>
            </div>
    </div>
    <div class="box-content">  
        <div class="row">            
            <div class="col-lg-12">

                <p class="introtext"><?= lang('customize_report'); ?></p>

                <div id="form">

<?php echo form_open("reports/sales"); ?>
                    <div class="row">
                    <div class="col-sm-4"><div class="form-group">
                            <label class="control-label" for="reference_no"><?= lang("reference_no"); ?></label>
<?php echo form_input('reference_no', (isset($_POST['reference_no']) ? $_POST['reference_no'] : ""), 'class="form-control tip" id="reference_no"'); ?>

                        </div> </div>

                    <div class="col-sm-4"><div class="form-group">
                            <label class="control-label" for="user"><?= lang("created_by"); ?></label>
                            <?php
                            $us[""] = "";
                            foreach ($users as $user) {
                                $us[$user->id] = $user->first_name . " " . $user->last_name;
                            }
                            echo form_dropdown('user', $us, (isset($_POST['user']) ? $_POST['user'] : ""), 'class="form-control" id="user" data-placeholder="' . $this->lang->line("select") . " " . $this->lang->line("user") . '"');
                            ?> 
                        </div> </div>
                    <div class="col-sm-4"><div class="form-group">
                            <label class="control-label" for="customer"><?= lang("customer"); ?></label>
                            <?php echo form_input('customer', (isset($_POST['customer']) ? $_POST['customer'] : ""), 'class="form-control" id="customer" data-placeholder="' . $this->lang->line("select") . " " . $this->lang->line("customer") . '"'); ?> 
                        </div> </div>
                    <div class="col-sm-4"><div class="form-group">
                            <label class="control-label" for="biller"><?= lang("biller"); ?></label>
                            <?php
                            $bl[""] = "";
                            foreach ($billers as $biller) {
                                $bl[$biller->id] = $biller->company != '-' ? $biller->company : $biller->name;
                            }
                            echo form_dropdown('biller', $bl, (isset($_POST['biller']) ? $_POST['biller'] : ""), 'class="form-control" id="biller" data-placeholder="' . $this->lang->line("select") . " " . $this->lang->line("biller") . '"');
                            ?> 
                        </div> </div>
                    <div class="col-sm-4"><div class="form-group">
                            <label class="control-label" for="warehouse"><?= lang("warehouse"); ?></label>
                            <?php
                            $wh[""] = "";
                            foreach ($warehouses as $warehouse) {
                                $wh[$warehouse->id] = $warehouse->name;
                            }
                            echo form_dropdown('warehouse', $wh, (isset($_POST['warehouse']) ? $_POST['warehouse'] : ""), 'class="form-control" id="warehouse" data-placeholder="' . $this->lang->line("select") . " " . $this->lang->line("warehouse") . '"');
                            ?> 
                        </div> </div>
                         <div class="col-sm-4"><div class="form-group">
                            <label class="control-label" for="product_code"><?= lang("product_code"); ?></label>
<?php echo form_input('product_code', (isset($_POST['product_code']) ? $_POST['product_code'] : ""), 'class="form-control tip" id="product_code"'); ?>

                        </div> </div>
                    <div class="col-sm-4">
                        <div class="form-group">
                            <?= lang("start_date", "start_date"); ?>
                            <?php echo form_input('start_date', (isset($_POST['start_date']) ? $_POST['start_date'] : ""), 'class="form-control datetime" id="start_date"'); ?> 
                        </div>
                    </div>
                    <div class="col-sm-4"><div class="form-group">
                            <?= lang("end_date", "end_date"); ?>
                            <?php echo form_input('end_date', (isset($_POST['end_date']) ? $_POST['end_date'] : ""), 'class="form-control datetime" id="end_date"'); ?>
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    <div class="controls"> <?php echo form_submit('submit_report', $this->lang->line("submit"), 'class="btn btn-primary"'); ?> </div>
                </div>
                <?php echo form_close(); ?>

            </div>
            <div class="clearfix"></div>

            <div class="table-responsive">
                <table id="SlRData" class="table table-bordered table-hover table-striped table-condensed reports-table">
                    <thead>
                        <tr>
                            <th><?= lang("date"); ?></th>
                            <th><?= lang("reference_no"); ?></th>
                            <th><?= lang("biller"); ?></th>
                            <th><?= lang("first_name"); ?></th>
                            <th><?= lang("last_name"); ?></th>
                            <th><?= lang("route_id"); ?></th>
                            <th><?= lang("outlet_id"); ?></th>
                            <th><?= lang("type"); ?></th>
                            <th><?= lang("receipt_no"); ?></th>
                            <th><?= lang("category_name"); ?></th>
                            <th><?= lang("product_code"); ?></th>
                            <th><?= lang("product_name"); ?></th>
                            <th><?= lang("quantity"); ?></th>
                            <th><?= lang("val"); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td colspan="9" class="dataTables_empty"><?=lang('loading_data_from_server')?></td>
                        </tr>
                    </tbody>
                    <tfoot class="dtFilter">
                        <tr class="active">
                            <th></th><th></th><th></th><th></th>
                            <th></th><th></th><th></th><th></th><th></th>
                            <th></th><th></th><th></th><th></th><th></th>
                        </tr>
                    </tfoot>
                </table>
            </div>
            </div>                        
        </div>
    </div>
</div>
<script type="text/javascript" src="<?=$assets?>js/html2canvas.min.js"></script>
<script type="text/javascript">
    $(document).ready(function() {
        $('#pdf').click(function(event) {
            event.preventDefault();
            window.location.href = "<?=site_url('reports/getSalesReport/pdf/?v=1'.$v)?>";
            return false;
        });
        $('#xls').click(function(event) {
            event.preventDefault();
            window.location.href = "<?=site_url('reports/getSalesReport/0/xls/?v=1'.$v)?>";
            return false;
        });
        $('#image').click(function(event) {
            event.preventDefault();
            html2canvas($('.box'), {
                onrendered: function(canvas) {
                    var img = canvas.toDataURL()
                    window.open(img);
                }
            });
            return false;
        });
    });
</script>