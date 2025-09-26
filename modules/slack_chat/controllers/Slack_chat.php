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
        $this->load->model('slack_chat/Chat_model');
    }

    public function index()
    {
        $data['title'] = _l('Chat Module Dashboard');
        $this->load->view('slack_chat/admin/dashboard', $data);
    }

    // Show chat interface
    public function chat($channel_id = null)
    {
        $data['title'] = _l('Chat');
        $data['channels'] = $this->Chat_model->get_channels();
        $data['active_channel'] = $channel_id;
        $data['messages'] = $channel_id ? $this->Chat_model->get_messages($channel_id) : [];
        $this->load->view('slack_chat/admin/chat', $data);
    }

    // Handle AJAX message sending
    public function send_message()
    {
        if ($this->input->is_ajax_request()) {
            $channel_id = $this->input->post('channel_id');
            $message = $this->input->post('message');
            $user_id = get_staff_user_id();
            $msg_id = $this->Chat_model->send_message($channel_id, $user_id, $message);
            if ($msg_id) {
                echo json_encode(['success' => true, 'id' => $msg_id]);
            } else {
                echo json_encode(['success' => false]);
            }
            return;
        }
        show_404();
    }

    // Handle AJAX message retrieval
    public function get_messages($channel_id)
    {
        if ($this->input->is_ajax_request()) {
            $messages = $this->Chat_model->get_messages($channel_id);
            echo json_encode(['messages' => $messages]);
            return;
        }
        show_404();
    }
}
