
<script>
    $(document).ready(function() {
        var oTable = $('#staffTable').dataTable({
            "aaSorting": [[2, "asc"], [3, "asc"]],
            "aLengthMenu": [[10, 25, 50, 100, -1], [10, 25, 50, 100, "<?= lang('all') ?>"]],
            "iDisplayLength": <?= $Settings->rows_per_page ?>,
            'bProcessing': true, 'bServerSide': true,
            'sAjaxSource': '<?= site_url('reports/getBillers') ?>',
            'fnServerData': function(sSource, aoData, fnCallback) {
                aoData.push({"name": "<?= $this->security->get_csrf_token_name() ?>", "value": "<?= $this->security->get_csrf_hash() ?>"});
                $.ajax({'dataType': 'json', 'type': 'POST', 'url': sSource, 'data': aoData, 'success': fnCallback});
            },
            "aoColumns": [ null, null, null, null,null, {"bSortable": false}]
        }).fnSetFilteringDelay().dtFilter([
        {column_number : 0, filter_default_label: "[<?=lang('company');?>]", filter_type: "text" },
        {column_number : 1, filter_default_label: "[<?=lang('name');?>]", filter_type: "text" },
        {column_number : 2, filter_default_label: "[<?=lang('phone');?>]", filter_type: "text" },
        {column_number : 3, filter_default_label: "[<?=lang('email_address');?>]", filter_type: "text"},
        {column_number : 4, filter_default_label: "[<?=lang('city');?>]", filter_type: "text"},

        ], "footer");
    });
</script>
<style>.table td:nth-child(6) { text-align: center; }</style>
<?php if($Owner){ echo form_open('auth/user_actions', 'id="action-form"'); } ?>
<div class="box">
    <div class="box-header">
        <h2 class="blue"><i class="fa-fw fa fa-users"></i><?= lang('billers'); ?></h2>

          <div class="box-icon">
                <ul class="btn-tasks">
                    <li class="dropdown"><a href="<?=site_url('reports/owner');?>" id="xls" class="tip" title="<?=lang('download_xls')?>"><i class="icon fa fa-file-excel-o"></i></a></li>
                </ul>
            </div>
    </div>
    <div class="box-content">  
        <div class="row">            
            <div class="col-lg-12">
                <p class="introtext"><?= lang('view_report_staff'); ?></p>
                <div class="table-responsive">
                    <table id="staffTable" cellpadding="0" cellspacing="0" border="0" class="table table-bordered table-hover table-striped reports-table">
                        <thead>
                            <tr>
                                <th><?= lang("company"); ?></th>
                                <th><?= lang("name"); ?></th>
                                <th><?= lang("phone"); ?></th>
                                <th><?= lang("email_address"); ?></th>
                                <th><?= lang("city"); ?></th>
                                <th style="width:80px;"><?php echo lang('actions'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td colspan="8" class="dataTables_empty"><?=lang('loading_data_from_server')?></td>
                            </tr>
                        </tbody>
                        <tfoot>
                            <tr class="active">
                                <th></th><th></th><th></th><th></th><th></th>
                                <th style="width:85px; text-align:center;"><?= lang("actions"); ?></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
               
            </div>                           

        </div>
    </div>
</div>
<?php if($Owner){ ?>
    <div style="display: none;">
        <input type="hidden" name="form_action" value="" id="form_action" />
        <?= form_submit('performAction', 'performAction', 'id="action-form-submit"') ?>
    </div>
<?= form_close() ?>

<script language="javascript">
    $(document).ready(function() {
        $('#set_admin').click(function(){
            $('#usr-form-btn').trigger('click');   
        });
        
    });
</script>

<?php } ?>