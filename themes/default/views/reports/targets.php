<script>
    $(document).ready(function() {
        var table = $('#staffTable').DataTable();
        var data = table.column( 3 ).data().sum();
    });

    alert(data);

</script>
<style>.table td:nth-child(6) { text-align: center; }</style>
<?php if($Owner){ echo form_open('auth/user_actions', 'id="action-form"'); } ?>
<div class="box">
    <div class="box-header">
        <h2 class="blue"><i class="fa-fw fa fa-users"></i><?= lang('target_management')." for ".$fullname; ?></h2>

                <div class="box-icon">
                <ul class="btn-tasks">
                    <li class="dropdown"><a href="<?=site_url('reports/user').'/'.$this->uri->segment(3);?>" id="xls" class="tip" title="<?=lang('download_xls')?>"><i class="icon fa fa-file-excel-o"></i></a></li>
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
                                <th><?php echo lang('category'); ?></th>
                                <th>Target</th>
                                <th>Sales</th>                             
                                <th>Variance</th>
                            </tr>
                        </thead>
                        <tbody>
<?php if (isset($contents)){
                            for ($i = 0; $i < count($contents)-1; $i++){
                                echo '<tr>';
                                //echo '<td>'.$targets[0]->full_name.'</td>';
                                for($j = 0; $j <= 2; $j++){
                                    echo '<td>';
                                    if(is_numeric($contents[$i][$j])){
                                    echo number_format($contents[$i][$j],2);
                                }else{
                                    echo $contents[$i][$j];
                                }
                                    echo '</td>';
                                }
                            echo '<td>';
                                if($contents[$i][1] > 0 && $contents[$i][2] > 0){
                                    $var = $contents[$i][2]/$contents[$i][1]*100;
                                    echo $var."%";
                                }else{
                                    echo 0;
                                }
                            echo '</td>';
                                echo '</tr>';
                            }

                   }
                     else{?>
                            <tr>
                                <td colspan="8" class="dataTables_empty"><?=lang('loading_data_from_server')?></td>
                            </tr>
                            <?php }?>
                        </tbody>
                      <!--  <tfoot>
                            <tr class="active">
                            <?php
                            $count  = count($contents);
                            for ($l = 0; $l < 13; $l++){
                                if($l == 1){
                                    echo "<th></th>";
                                }
                                echo '<th>';
                                if(is_numeric($contents[$count-1][$l])){
                                echo number_format($contents[$count-1][$l],2);
                                }else{
                                    echo $contents[$count-1][$l];
                                }
                                echo '</th>';
                            }
                            ?>

                            </tr>
                        </tfoot> -->
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

<script type="text/javascript">
    $(document).ready(function() {
        $('#pdf').click(function(event) {
            event.preventDefault();
            window.location.href = "<?=site_url('target/export/0/pdf')?>";
            return false;
        });
        $('#xls').click(function(event) {
             event.preventDefault();
            window.location.href = "<?=site_url('target/export/0/0/xls')?>";
            return false;
            alert('you clicked xsl');
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