
<div class="modal-dialog">
    <div class="modal-content">
        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-hidden="true"><i class="fa fa-2x">&times;</i></button>
            <h4 class="modal-title" id="myModalLabel"><?php echo lang('edit_outlet'); ?></h4>
        </div>
        <?php $attrib = array('data-toggle' => 'validator', 'role' => 'form');
        echo form_open_multipart("custom/edit_outlet/" . $id, $attrib); ?>  
        <div class="modal-body">
            <p><?= lang('update_info'); ?></p>

            <div class="form-group">
                <?php echo lang('name', 'name'); ?>
                <div class="controls">
                <?php echo form_input($name); ?>
                </div>
            </div>
            <?php echo form_hidden('id', $id); ?>
        </div>
        <div class="modal-footer">
        <?php echo form_submit('edit_outlet', lang('edit_outlet'), 'class="btn btn-primary"'); ?>
        </div>
    </div>
    <?php echo form_close(); ?>  
</div>
<script type="text/javascript" src="<?=$assets?>js/custom.js"></script>
<?=$modal_js?>