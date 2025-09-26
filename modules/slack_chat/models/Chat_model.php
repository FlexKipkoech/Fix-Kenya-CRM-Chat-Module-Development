<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Chat_model extends CI_Model
{
    private function table_exists_or_log($table_suffix)
    {
        $table = db_prefix() . $table_suffix;
        if (!$this->db->table_exists($table)) {
            log_message('error', 'Slack Chat Module: Required table missing: ' . $table);
            return false;
        }
        return true;
    }

    public function get_channels()
    {
        if (!$this->table_exists_or_log('chat_channels')) {
            return [];
        }
        return $this->db->get(db_prefix() . 'chat_channels')->result_array();
    }

    public function get_messages($channel_id)
    {
        if (!$this->table_exists_or_log('chat_messages')) {
            return [];
        }
        $this->db->where('channel_id', $channel_id);
        $this->db->order_by('created_at', 'ASC');
        return $this->db->get(db_prefix() . 'chat_messages')->result_array();
    }

    public function send_message($channel_id, $user_id, $message)
    {
        if (!$this->table_exists_or_log('chat_messages')) {
            return false;
        }
        $data = [
            'channel_id' => $channel_id,
            'user_id'    => $user_id,
            'message'    => $message,
            'created_at' => date('Y-m-d H:i:s'),
        ];
        $this->db->insert(db_prefix() . 'chat_messages', $data);
        return $this->db->insert_id();
    }

    public function create_channel($name, $description)
    {
        if (!$this->table_exists_or_log('chat_channels')) {
            return false;
        }
        $data = [
            'name'        => $name,
            'description' => $description,
            'is_private'  => 0,
            'created_by'  => get_staff_user_id(),
            'created_at'  => date('Y-m-d H:i:s'),
        ];
        $this->db->insert(db_prefix() . 'chat_channels', $data);
        return $this->db->insert_id();
    }
}
