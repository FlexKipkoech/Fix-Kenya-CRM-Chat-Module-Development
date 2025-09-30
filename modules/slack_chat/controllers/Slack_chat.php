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
            echo json_encode(['success' => (bool)$res, 'csrf' => [
                'name' => $this->security->get_csrf_token_name(),
                'hash' => $this->security->get_csrf_hash()
            ]]);
            return;
        }
        show_404();
    }

    // Handle AJAX message sending
    public function send_message()
    {
        if ($this->input->is_ajax_request()) {
            $channel_id = $this->input->post('channel_id');
            $message = trim($this->input->post('message'));
            $user_id = get_staff_user_id();
            if (empty($message) || empty($channel_id)) {
                echo json_encode(['success' => false, 'error' => 'invalid_input']);
                return;
            }
            $msg_id = $this->Chat_model->send_message($channel_id, $user_id, $message);
            if ($msg_id) {
                $msg = $this->Chat_model->get_message_with_user($msg_id);
                echo json_encode(['success' => true, 'message' => $msg, 'csrf' => [
                    'name' => $this->security->get_csrf_token_name(),
                    'hash' => $this->security->get_csrf_hash()
                ]]);
            } else {
                echo json_encode(['success' => false, 'csrf' => [
                    'name' => $this->security->get_csrf_token_name(),
                    'hash' => $this->security->get_csrf_hash()
                ]]);
            }
            return;
        }
        show_404();
    }

    // Handle AJAX message retrieval
    public function get_messages($channel_id)
    {
        if ($this->input->is_ajax_request()) {
            $limit = (int)$this->input->get('limit') ?: 50;
            $messages = $this->Chat_model->get_recent_messages($channel_id, $limit);
            // add user display name if possible
            foreach ($messages as &$m) {
                if (isset($m['user_id'])) {
                    $staff = $this->db->get_where(db_prefix() . 'staff', ['staffid' => $m['user_id']])->row_array();
                    if ($staff) {
                        $m['user_name'] = trim($staff['firstname'] . ' ' . $staff['lastname']);
                    } else {
                        $m['user_name'] = 'User ' . $m['user_id'];
                    }
                }
            }
            echo json_encode(['messages' => $messages, 'csrf' => [
                'name' => $this->security->get_csrf_token_name(),
                'hash' => $this->security->get_csrf_hash()
            ]]);
            return;
        }
        show_404();
    }

    // Poll for messages after a timestamp
    public function poll_messages($channel_id)
    {
        if ($this->input->is_ajax_request()) {
            $since = $this->input->get('since'); // expected YYYY-MM-DD HH:MM:SS
            if (empty($since)) {
                echo json_encode(['success' => false, 'error' => 'missing_since']);
                return;
            }
            $messages = $this->Chat_model->get_messages_after($channel_id, $since);
            foreach ($messages as &$m) {
                $staff = $this->db->get_where(db_prefix() . 'staff', ['staffid' => $m['user_id']])->row_array();
                $m['user_name'] = $staff ? trim($staff['firstname'].' '.$staff['lastname']) : ('User '.$m['user_id']);
            }
            echo json_encode(['success' => true, 'messages' => $messages, 'csrf' => [
                'name' => $this->security->get_csrf_token_name(),
                'hash' => $this->security->get_csrf_hash()
            ]]);
            return;
        }
        show_404();
    }
}
