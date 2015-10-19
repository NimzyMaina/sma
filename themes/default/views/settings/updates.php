<div class="box">
    <div class="box-header">
        <h2 class="blue"><i class="fa-fw fa fa-upload"></i><?= lang('updates'); ?></h2>
    </div>
    <div class="box-content"> 
        <div class="row">            
            <div class="col-lg-12">
                <p class="introtext"><?= lang('update_heading'); ?></p>

                <div class="row">            
                    <div class="col-md-12">
                        <?php 
                        if(! empty($updates->data->updates)) {
                            foreach ($updates->data->updates as $update) {
                                $update->mversion = 0;
                                echo '<ul class="list-group"><li class="list-group-item">';
                                echo '<h3><strong>'.lang('version').' '.$update->version.'</strong> ';
                                echo anchor('system_settings/install_update/'.substr($update->filename, 0, -4).'/'.($update->mversion ? $update->mversion : 0).'/'.$update->version, '<i class="fa fa-upload"></i> ' . lang('install'), 'class="btn btn-xs btn-primary"').'</h3>';
                                echo '<h3>'.lang('changelog').'<h3><pre>'.$update->changelog.'</pre>';
                                echo '</li></ul>';
                            }
                        } else {
                            echo '<div class="well"><strong>'.lang('using_latest_update').'</strong></div>';
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>                         
    </div>
</div>