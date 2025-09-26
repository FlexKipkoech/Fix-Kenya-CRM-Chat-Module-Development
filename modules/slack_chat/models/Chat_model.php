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

    /**
     * Ensure default channels exist. Returns array of channel name => id.
     */
    public function create_default_channels()
    {
        if (!$this->table_exists_or_log('chat_channels')) {
            return [];
        }

        $existing = $this->get_channels();
        $map = [];
        foreach ($existing as $c) {
            $map[$c['name']] = $c['id'];
        }

        $defaults = [
            'General' => 'General channel',
            'Random'  => 'Random channel'
        ];

        foreach ($defaults as $name => $desc) {
            if (!isset($map[$name])) {
                $id = $this->create_channel($name, $desc);
                if ($id) {
                    $map[$name] = $id;
                }
            }
        }

        return $map;
    }

    public function get_channel_by_id($id)
    {
        if (!$this->table_exists_or_log('chat_channels')) {
            return null;
        }
        $this->db->where('id', $id);
        $q = $this->db->get(db_prefix() . 'chat_channels');
        return $q->row_array();
    }

    public function join_channel($channel_id, $user_id)
    {
        if (!$this->table_exists_or_log('chat_members')) {
            return false;
        }
        // avoid duplicates
        $this->db->where('channel_id', $channel_id);
        $this->db->where('user_id', $user_id);
        $exists = $this->db->get(db_prefix() . 'chat_members')->row_array();
        if ($exists) {
            return $exists['id'];
        }
        $data = [
            'channel_id' => $channel_id,
            'user_id'    => $user_id,
            'joined_at'  => date('Y-m-d H:i:s'),
        ];
        $this->db->insert(db_prefix() . 'chat_members', $data);
        return $this->db->insert_id();
    }

    public function get_user_channels($user_id)
    {
        if (!$this->table_exists_or_log('chat_members') || !$this->table_exists_or_log('chat_channels')) {
            return [];
        }
        $this->db->select('c.*');
        $this->db->from(db_prefix() . 'chat_channels c');
        $this->db->join(db_prefix() . 'chat_members m', 'm.channel_id = c.id');
        $this->db->where('m.user_id', $user_id);
        $this->db->order_by('c.name', 'ASC');
        return $this->db->get()->result_array();
    }
}
