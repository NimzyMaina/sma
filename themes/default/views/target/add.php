<div class="box">
    <div class="box-header">
        <h2 class="blue"><i class="fa-fw fa fa-users"></i><?= lang('set_target'); ?></h2>
    </div>
    <div class="box-content"> 
        <div class="row">            
            <div class="col-lg-12">
                <p class="introtext"><?php echo lang('set_target'); ?></p>

                <?php $attrib = array('class' => 'form-horizontal', 'data-toggle' => 'validator', 'role' => 'form');
                echo form_open("target/add", $attrib);
                ?>
                <div class="row">
                    <div class="col-md-12">
                        <div class="col-md-6 multi-field-wrapper">
                        <div align="right">
                        <button type="button" class="add-field btn btn-success" onclick="loadDoc()" id="select">Add field</button>
                        </div>

                        <div class="form-group">
                                <?php echo lang('full_name', 'full_name'); ?> 
                                <div class="controls">
                                    <?php echo form_dropdown('user',$agents,set_value('user'),'class="form-control" id="full_name" required="required" pattern=".{3,10}"'); ?>
                                </div>
                            </div>
                        <div class="form-group">
                            <?= lang("date", "date"); ?>
                            <?php echo form_input('date', (isset($_POST['date']) ? $_POST['date'] : ""), 'class="form-control date" required="required" id="date"'); ?> 
                        </div>                            
                                 <div class="form-group">
                                <?php echo lang('category', 'category'); ?> 
                                <div class="controls">
                                    <?php echo form_dropdown('category[]',$categories,set_value('category[]'),'class="form-control" required="required" '); ?>
                                </div>
                                <div class="form-groujp">
                                            <?php echo lang('target', 'target'); ?> 
                                            <div class="controls">
                                                <input type="number" id="target[]" name="target[]" class="form-control" required="required"/>
                                            </div>
                                        </div>
                            </div> 
 

                             <div id="point"></div> 

                        </div>
                    </div>
                </div> 

                <p><?php echo form_submit('set_target', lang('set_target'), 'class="btn btn-primary"'); ?></p>

                <?php echo form_close(); ?>
            </div>
        </div>
    </div>
</div>
<script type="text/javascript" charset="utf-8">
    $(document).ready(function() {
        $('#group').change(function(event) {
            var group = $(this).val();
            if(group == 1) {
                $('.no').slideUp();
                $('form[data-toggle="validator"]').bootstrapValidator('removeField', 'biller');
                $('form[data-toggle="validator"]').bootstrapValidator('removeField', 'warehouse');
            } else {
                $('.no').slideDown();
                $('form[data-toggle="validator"]').bootstrapValidator('addField', 'biller');
                $('form[data-toggle="validator"]').bootstrapValidator('addField', 'warehouse');
            }
        });
    });
</script>


<script>
function loadDoc() {
  var xhttp = new XMLHttpRequest();
  xhttp.onreadystatechange = function() {
    if (xhttp.readyState == 4 && xhttp.status == 200) {
      var div = document.getElementById("point");
      div.innerHTML = div.innerHTML +  xhttp.responseText
    }
  }
  xhttp.open("GET", "index.php/target/getcat", true);
  xhttp.send();
}
</script>

<script type="text/javascript">
$(document).ready(function {
            $(".remove").click(function(e){
                    $(this).parent('div').remove();
            });

        });
</script>

