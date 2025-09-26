<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Slack_chat extends AdminController
{
    public function __construct()
    {
        parent::__construct();
        if (!is_admin()) {
            access_denied('Slack Chat');
        }
    }

    public function index()
    {
        $data['title'] = _l('Chat Module Dashboard');
        $this->load->view('slack_chat/views/admin/dashboard', $data);
    }
}
