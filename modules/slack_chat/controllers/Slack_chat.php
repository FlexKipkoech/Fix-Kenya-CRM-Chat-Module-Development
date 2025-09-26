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

        // Ensure default channels exist on first load
        $this->Chat_model->create_default_channels();

        $data['channels'] = $this->Chat_model->get_channels();

        // Auto-join current user to General channel
        $user_id = get_staff_user_id();
        $general = $this->Chat_model->get_channel_by_id(array_values(array_filter(array_column($data['channels'], 'id'), function($v){return true;}))[0] ?? null);
        // Better: find General channel
        $general_channel = null;
        foreach ($data['channels'] as $c) {
            if (strtolower($c['name']) === 'general') {
                $general_channel = $c;
                break;
            }
        }
        if ($general_channel) {
            $this->Chat_model->join_channel($general_channel['id'], $user_id);
            if (!$channel_id) {
                $channel_id = $general_channel['id'];
            }
        }

        $data['active_channel'] = $channel_id;
        $data['messages'] = $channel_id ? $this->Chat_model->get_messages($channel_id) : [];
        $data['user_channels'] = $this->Chat_model->get_user_channels($user_id);

        $this->load->view('slack_chat/admin/chat', $data);
    }

    public function create_channel()
    {
        if ($this->input->post()) {
            $name = $this->input->post('name');
            $description = $this->input->post('description');
            $is_private = $this->input->post('is_private') ? 1 : 0;
            $id = $this->Chat_model->create_channel($name, $description);
            if ($id) {
                // if public, auto-join creator
                $this->Chat_model->join_channel($id, get_staff_user_id());
                set_alert('success', _l('added_successfully'));
            } else {
                set_alert('warning', _l('problem_adding'));
            }
            redirect(admin_url('slack_chat/chat/' . $id));
        }
        $this->load->view('slack_chat/admin/create_channel');
    }

    public function join_channel($channel_id)
    {
        if ($this->input->is_ajax_request()) {
            $user_id = get_staff_user_id();
            $res = $this->Chat_model->join_channel($channel_id, $user_id);
            echo json_encode(['success' => (bool)$res]);
            return;
        }
        show_404();
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
