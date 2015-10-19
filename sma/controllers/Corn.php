<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Corn extends CI_Controller {

    function __construct() {
        parent::__construct();
    }

    function index($action = NULL) {
        show_404();
    }

    function run() {
        $this->load->model('corn_model');
        if($m = $this->corn_model->run_corn()) {
            echo '<p>Corn job successfully run</p>'.$m;
        } else {
            echo 'Corn job failed!';
        }
    }

    function log_corn($msg, $val = NULL) {
        $this->load->library('logs');
        return (bool) $this->logs->write('corn', $msg, $val);
    }

}
