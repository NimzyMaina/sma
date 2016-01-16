
<div class="modal-dialog">
    <div class="modal-content">
        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-hidden="true"><i class="fa fa-2x">&times;</i></button>
            <h4 class="modal-title" id="myModalLabel"><?php echo lang('add_van'); ?></h4>
        </div>
        <?php $attrib = array('data-toggle' => 'validator', 'role' => 'form'); 
        echo form_open_multipart("van/add_van", $attrib); ?>  
        <div class="modal-body">
            <p><?= lang('enter_info'); ?></p>
        <div class="form-group">
                <label class="control-label" for="code"><?php echo $this->lang->line("code"); ?></label>
                <div class="controls"> <?php echo form_input('code', set_value('code'), 'class="form-control" id="code" required="required"'); ?> </div>
            </div>
            <div class="form-group">
                <label class="control-label" for="name"><?php echo $this->lang->line("name"); ?></label>
                <div class="controls"> <?php echo form_input('name', set_value('name'), 'class="form-control" id="name" required="required"'); ?> </div>
            </div>
             <div class="form-group">
                                <?php echo lang('full_name', 'full_name'); ?> 
                                <div class="controls">
                                    <?php echo form_dropdown('user',$agents,set_value('user'),'class="form-control" id="full_name" required="required"'); ?>
                                </div>
                            </div>
        </div>
        <div class="modal-footer">
            <?php echo form_submit('add_warehouse', lang('add_van'), 'class="btn btn-primary"'); ?>
        </div>
    </div>
    <?php echo form_close(); ?>  
</div>
<script type="text/javascript" src="<?=$assets?>js/custom.js"></script>
<?=$modal_js?>
