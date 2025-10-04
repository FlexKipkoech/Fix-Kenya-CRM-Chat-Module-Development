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
        // Directly redirect to chat interface (General channel decided in chat method)
        redirect(admin_url('slack_chat/chat'));
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
        if ($this->input->method() !== 'post') {
            show_404();
            return;
        }

        $channel_id = $this->input->post('channel_id');
        $message = trim($this->input->post('message'));
        $user_id = get_staff_user_id();

        if (empty($message) || empty($channel_id)) {
            if ($this->input->is_ajax_request()) {
                return $this->output
                    ->set_content_type('application/json')
                    ->set_output(json_encode([
                        'success' => false,
                        'error'   => 'invalid_input',
                        'csrf'    => [
                            'name' => $this->security->get_csrf_token_name(),
                            'hash' => $this->security->get_csrf_hash()
                        ]
                    ]));
            } else {
                set_alert('warning', _l('Invalid message or channel'));
                redirect(admin_url('slack_chat/chat/' . $channel_id));
            }
        }

        $msg_id = $this->Chat_model->send_message($channel_id, $user_id, $message);

        if ($msg_id) {
            $msg = $this->Chat_model->get_message_with_user($msg_id);
            if ($this->input->is_ajax_request()) {
                $resp = ['success' => true, 'message' => $msg];
                $resp['csrf'] = [
                    'name' => $this->security->get_csrf_token_name(),
                    'hash' => $this->security->get_csrf_hash()
                ];
                return $this->output->set_content_type('application/json')->set_output(json_encode($resp));
            } else {
                set_alert('success', _l('Message sent'));
                redirect(admin_url('slack_chat/chat/' . $channel_id));
            }
        } else {
            if ($this->input->is_ajax_request()) {
                $resp = ['success' => false, 'error' => 'send_failed'];
                $resp['csrf'] = [
                    'name' => $this->security->get_csrf_token_name(),
                    'hash' => $this->security->get_csrf_hash()
                ];
                return $this->output->set_content_type('application/json')->set_output(json_encode($resp));
            } else {
                set_alert('warning', _l('Failed to send message'));
                redirect(admin_url('slack_chat/chat/' . $channel_id));
            }
        }
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

    // File upload endpoint
    public function upload_file()
    {
        if ($this->input->method() !== 'post' || !$this->input->is_ajax_request()) {
            show_404();
            return;
        }

        $message_id = $this->input->post('message_id');
        if (empty($message_id)) {
            echo json_encode(['success' => false, 'error' => 'missing_message_id']);
            return;
        }

        if (empty($_FILES['file'])) {
            echo json_encode(['success' => false, 'error' => 'no_file_uploaded']);
            return;
        }

        $file = $_FILES['file'];
        $max_size = 10 * 1024 * 1024; // 10MB
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'application/pdf', 'text/plain', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];

        if ($file['size'] > $max_size) {
            echo json_encode(['success' => false, 'error' => 'file_too_large']);
            return;
        }

        if (!in_array($file['type'], $allowed_types)) {
            echo json_encode(['success' => false, 'error' => 'invalid_file_type']);
            return;
        }

        // Create upload directory if not exists
        $upload_dir = FCPATH . 'modules/slack_chat/uploads/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }

        $filename = uniqid() . '_' . basename($file['name']);
        $filepath = $upload_dir . $filename;

        if (move_uploaded_file($file['tmp_name'], $filepath)) {
            $file_data = [
                'filename'    => $file['name'],
                'filepath'    => 'modules/slack_chat/uploads/' . $filename,
                'filesize'    => $file['size'],
                'filetype'    => $file['type'],
                'uploaded_by' => get_staff_user_id(),
            ];
            $file_id = $this->Chat_model->upload_file($message_id, $file_data);
            if ($file_id) {
                echo json_encode(['success' => true, 'file_id' => $file_id, 'filename' => $file['name'], 'filetype' => $file['type']]);
            } else {
                echo json_encode(['success' => false, 'error' => 'db_error']);
            }
        } else {
            echo json_encode(['success' => false, 'error' => 'upload_failed']);
        }
    }

    // Download file
    public function download_file($file_id)
    {
        $file = $this->Chat_model->get_file_by_id($file_id);
        if (!$file) {
            show_404();
            return;
        }

        $filepath = FCPATH . $file['filepath'];
        if (!file_exists($filepath)) {
            show_404();
            return;
        }

        // Force download
        header('Content-Type: ' . $file['filetype']);
        header('Content-Disposition: attachment; filename="' . $file['filename'] . '"');
        header('Content-Length: ' . $file['filesize']);
        readfile($filepath);
        exit;
    }

    // Add reaction
    public function add_reaction()
    {
        if ($this->input->method() !== 'post' || !$this->input->is_ajax_request()) {
            show_404();
            return;
        }

        $message_id = $this->input->post('message_id');
        $emoji = $this->input->post('emoji');
        $user_id = get_staff_user_id();

        if (empty($message_id) || empty($emoji)) {
            echo json_encode(['success' => false, 'error' => 'missing_params']);
            return;
        }

        $result = $this->Chat_model->add_reaction($message_id, $user_id, $emoji);
        echo json_encode(['success' => true, 'added' => ($result !== false), 'reactions' => $this->Chat_model->get_reactions($message_id)]);
    }

    // Typing status
    public function typing_status()
    {
        if ($this->input->method() !== 'post' || !$this->input->is_ajax_request()) {
            show_404();
            return;
        }

        $channel_id = $this->input->post('channel_id');
        $is_typing = $this->input->post('is_typing') == '1';
        $user_id = get_staff_user_id();

        if ($is_typing) {
            $this->Chat_model->set_typing($channel_id, $user_id);
        } else {
            $this->Chat_model->clear_typing($channel_id, $user_id);
        }

        $typing_users = $this->Chat_model->get_typing_users($channel_id);
        echo json_encode(['success' => true, 'typing_users' => $typing_users]);
    }

    // Get thread replies
    public function get_thread($message_id)
    {
        if (!$this->input->is_ajax_request()) {
            show_404();
            return;
        }

        $replies = $this->Chat_model->get_thread_replies($message_id);
        foreach ($replies as &$r) {
            $staff = $this->db->get_where(db_prefix() . 'staff', ['staffid' => $r['user_id']])->row_array();
            $r['user_name'] = $staff ? trim($staff['firstname'] . ' ' . $staff['lastname']) : ('User ' . $r['user_id']);
        }
        echo json_encode(['success' => true, 'replies' => $replies]);
    }

    // Add thread reply
    public function add_thread_reply()
    {
        if ($this->input->method() !== 'post' || !$this->input->is_ajax_request()) {
            show_404();
            return;
        }

        $parent_id = $this->input->post('parent_message_id');
        $channel_id = $this->input->post('channel_id');
        $message = trim($this->input->post('message'));
        $user_id = get_staff_user_id();

        if (empty($parent_id) || empty($message) || empty($channel_id)) {
            echo json_encode(['success' => false, 'error' => 'missing_params']);
            return;
        }

        $message_data = [
            'channel_id' => $channel_id,
            'user_id'    => $user_id,
            'message'    => $message,
        ];

        $message_id = $this->Chat_model->add_thread_reply($parent_id, $message_data);
        if ($message_id) {
            $msg = $this->Chat_model->get_message_with_user($message_id);
            echo json_encode(['success' => true, 'message' => $msg]);
        } else {
            echo json_encode(['success' => false, 'error' => 'failed_to_add_reply']);
        }
    }
}
