<script>
    $(document).ready(function() {
        oTable = $('#dmpData').dataTable({
            "aaSorting": [[2, "asc"], [3, "asc"]],
            "aLengthMenu": [[10, 25, 50, 100, -1], [10, 25, 50, 100, "<?= lang('all') ?>"]],
            "iDisplayLength": <?= $Settings->rows_per_page ?>,
            'bProcessing': true, 'bServerSide': true,
            'sAjaxSource': '<?= site_url('products/getdamageproducts'); ?>',
            'fnServerData': function(sSource, aoData, fnCallback) {
                aoData.push({"name": "<?= $this->security->get_csrf_token_name() ?>", "value": "<?= $this->security->get_csrf_hash() ?>"});
                $.ajax({'dataType': 'json', 'type': 'POST', 'url': sSource, 'data': aoData, 'success': fnCallback});
            },
            "aoColumns": [{"mRender": fld}, null, null, null, {"mRender": decimalFormat}, null, {"bSortable": false}]
        }).fnSetFilteringDelay().dtFilter([
        {column_number : 0, filter_default_label: "[<?=lang('date');?> (yyyy-mm-dd)]", filter_type: "text" },
        {column_number : 1, filter_default_label: "[<?=lang('product_code');?>]", filter_type: "text" },
        {column_number : 2, filter_default_label: "[<?=lang('product_name');?>]", filter_type: "text" },
        {column_number : 3, filter_default_label: "[<?=lang('product_variant');?>]", filter_type: "text"},
        {column_number : 4, filter_default_label: "[<?=lang('quantity');?>]", filter_type: "text" },
        {column_number : 5, filter_default_label: "[<?=lang('warehouse');?>]", filter_type: "text" },
        ], "footer");
    });
</script>

<div class="box">
    <div class="box-header">
        <h2 class="blue"><i class="fa-fw fa fa-barcode"></i><?= lang('damage_products'); ?></h2>
    </div>
    <div class="box-content">  
        <div class="row">            
            <div class="col-lg-12">
                <p class="introtext"><?= lang('list_results'); ?></p>
                <div class="table-responsive">
                    <table id="dmpData" class="table table-bordered table-condensed table-hover table-striped">
                        <thead>
                            <tr>
                                <th class="col-xs-2"><?php echo $this->lang->line("date"); ?></th>
                                <th class="col-xs-2"><?php echo $this->lang->line("product_code"); ?></th>
                                <th class="col-xs-2"><?php echo $this->lang->line("product_name"); ?></th>
                                <th class="col-xs-2"><?php echo $this->lang->line("product_variant"); ?></th>
                                <th class="col-xs-1"><?php echo $this->lang->line("quantity"); ?></th>
                                <th class="col-xs-2"><?php echo $this->lang->line("warehouse"); ?></th>
                                <th style="min-width:115px; text-align:center;"><?php echo $this->lang->line("actions"); ?></th> 
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td colspan="6" class="dataTables_empty"><?=lang('loading_data_from_server')?></td>
                            </tr>
                        </tbody>
                        <tfoot class="dtFilter">
                            <tr class="active">
                                <th></th><th></th><th></th><th></th><th></th><th></th>
                                <th style="width:115px; text-align:center;"><?php echo $this->lang->line("actions"); ?></th> 
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>                        
        </div>
    </div>
</div>