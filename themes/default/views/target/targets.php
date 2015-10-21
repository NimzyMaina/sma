<?php
// foreach ($contents as $content){
//     echo $content[0];
//     }
//print_r($contents);
//exit;
//$staff = array();

function getMonths($no = 0){
    $month = date('m');
    $year = date('Y');

    $x = $month + $no;
    if($x > 12){
        $x = $x - 12;
        $year++;
    }
    $data = mktime(0, 0, 0, $x, 1, $year);
    return $data;
}

foreach($targets as $target){
for($i = 0; $i < count($contents); $i++){
    for($j=0; $j < 12;$j++){
    if($contents[$i][0] == $target->name){
        if($target->month == date('n',getMonths($j)) && date('Y',strtotime($target->date)) == date('Y',getMonths($j))){
            $contents[$i][$j+1] = $target->target;
        }
        else{
            $contents[$i][$j+1] = 0;
        }
    }
    // else{
    //     $contents[$i][$j+1] = 0;
    // }
    }//contents for loop
}//months for loop
}//foreach loop
echo "<pre>";
print_r($targets);
echo "<br>";
print_r($contents);
exit;

?>
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
        <h2 class="blue"><i class="fa-fw fa fa-users"></i><?= lang('users'); ?></h2>
    </div>
    <div class="box-content">  
        <div class="row">            
            <div class="col-lg-12">
                <p class="introtext"><?= lang('view_report_staff'); ?></p>
                <div class="table-responsive">
                    <table id="staffTable" cellpadding="0" cellspacing="0" border="0" class="table table-bordered table-hover table-striped reports-table">
                        <thead>
                            <tr>
                                <th><?php echo lang('full_name'); ?></th>
                                <th><?php echo lang('category'); ?></th>
                                <th><?=date('M-Y',getMonths());?></th>
                                <th><?=date('M-Y',getMonths(1));?></th>
                                <th><?=date('M-Y',getMonths(2));?></th>
                                <th><?=date('M-Y',getMonths(3));?></th>
                                <th><?=date('M-Y',getMonths(4));?></th>
                                <th><?=date('M-Y',getMonths(5));?></th>
                                <th><?=date('M-Y',getMonths(6));?></th>
                                <th><?=date('M-Y',getMonths(7));?></th>
                                <th><?=date('M-Y',getMonths(8));?></th>
                                <th><?=date('M-Y',getMonths(9));?></th>
                                <th><?=date('M-Y',getMonths(10));?></th>
                                <th><?=date('M-Y',getMonths(11));?></th>
                            </tr>
                        </thead>
                        <tbody>
<?php if (isset($targets)){
                            foreach($targets as $target){
                            ?>
                            <tr>
                                <td><?=$target->full_name;?></td>
                                <td><?=$target->name.'|'.date('Y',strtotime($target->date)).'|'.$target->month;?></td>
                                <?php for($i = 0; $i < 12; $i++):?>
                                <td><?php
                                if($target->month == date('n',getMonths($i)) && date('Y',strtotime($target->date)) == date('Y',getMonths($i))){
                                    echo $target->target;
                                }else{
                                    echo 0;
                                }
                                ?></td>
                            <?php endfor;?>
                            </tr>

                    <?php }
                    } else{?>
                            <tr>
                                <td colspan="8" class="dataTables_empty"><?=lang('loading_data_from_server')?></td>
                            </tr>
                            <?php }?>
                        </tbody>
                        <tfoot>
                            <tr class="active">
                                <th>TOTAL</th><th></th><th></th><th></th><th></th>
                                <th></th><th></th><th></th><th></th><th></th>
                                <th></th><th></th><th></th><th></th>
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