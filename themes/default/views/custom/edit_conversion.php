<div class="modal-dialog">
    <div class="modal-content">
        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-hidden="true"><i class="fa fa-2x">&times;</i></button>
            <h4 class="modal-title" id="myModalLabel"><?php echo lang('edit_conversion'); ?></h4>
        </div>
        <?php $attrib = array('data-toggle' => 'validator', 'role' => 'form');
        echo form_open_multipart("custom/edit_conversion/" . $id, $attrib); ?>  
        <div class="modal-body">
            <p><?= lang('enter_info'); ?></p>

            <div class="form-group">
                <?php echo lang('from', 'from'); ?>
                <div class="controls">
                <?php echo form_input($from); ?>
                </div>
            </div>

             <div class="form-group">
                <?php echo lang('to', 'to'); ?>
                <div class="controls">
                <?php echo form_input($to); ?>
                </div>
            </div>

             <div class="form-group">
                <?php echo lang('factor', 'factor'); ?>
                <div class="controls">
                <?php echo form_input($factor); ?>
                </div>
            </div>

        </div>
        <div class="modal-footer">
        <?php echo form_submit('add_conversion', lang('edit_conversion'), 'class="btn btn-primary"'); ?>
        </div>
    </div>
    <?php echo form_close(); ?>  
</div>
<script type="text/javascript" src="<?=$assets?>js/custom.js"></script>
<?=$modal_js?>