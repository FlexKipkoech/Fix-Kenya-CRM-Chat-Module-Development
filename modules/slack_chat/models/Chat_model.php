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

    /**
     * Get the most recent messages for a channel (limit newest first, returned oldest->newest)
     */
    public function get_recent_messages($channel_id, $limit = 50)
    {
        if (!$this->table_exists_or_log('chat_messages')) {
            return [];
        }
        $this->db->where('channel_id', $channel_id);
        $this->db->order_by('created_at', 'DESC');
        $this->db->limit($limit);
        $rows = $this->db->get(db_prefix() . 'chat_messages')->result_array();
        return array_reverse($rows);
    }

    /**
     * Get messages after a given timestamp (YYYY-MM-DD HH:MM:SS) for polling
     */
    public function get_messages_after($channel_id, $timestamp)
    {
        if (!$this->table_exists_or_log('chat_messages')) {
            return [];
        }
        $this->db->where('channel_id', $channel_id);
        $this->db->where('created_at >', $timestamp);
        $this->db->order_by('created_at', 'ASC');
        return $this->db->get(db_prefix() . 'chat_messages')->result_array();
    }

    /**
     * Get single message with user details (if staff table exists)
     */
    public function get_message_with_user($message_id)
    {
        if (!$this->table_exists_or_log('chat_messages')) {
            return null;
        }
        // try to join staff table for user info
        $staff_table = db_prefix() . 'staff';
        if ($this->db->table_exists($staff_table)) {
            $this->db->select(db_prefix() . "chat_messages.*,")
                ->select($staff_table . ".staffid as user_id, " . $staff_table . ".firstname, " . $staff_table . ".lastname", false);
            $this->db->from(db_prefix() . 'chat_messages');
            $this->db->join($staff_table, $staff_table . '.staffid = ' . db_prefix() . 'chat_messages.user_id', 'left');
            $this->db->where(db_prefix() . 'chat_messages.id', $message_id);
            return $this->db->get()->row_array();
        }

        // fallback: return message only
        $this->db->where('id', $message_id);
        return $this->db->get(db_prefix() . 'chat_messages')->row_array();
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

    // File upload methods
    public function upload_file($message_id, $file_data)
    {
        if (!$this->table_exists_or_log('chat_files')) {
            return false;
        }
        $data = [
            'message_id'  => $message_id,
            'filename'    => $file_data['filename'],
            'filepath'    => $file_data['filepath'],
            'filesize'    => $file_data['filesize'],
            'filetype'    => $file_data['filetype'],
            'uploaded_by' => $file_data['uploaded_by'],
            'created_at'  => date('Y-m-d H:i:s'),
        ];
        $this->db->insert(db_prefix() . 'chat_files', $data);
        return $this->db->insert_id();
    }

    public function get_message_files($message_id)
    {
        if (!$this->table_exists_or_log('chat_files')) {
            return [];
        }
        $this->db->where('message_id', $message_id);
        return $this->db->get(db_prefix() . 'chat_files')->result_array();
    }

    public function get_file_by_id($file_id)
    {
        if (!$this->table_exists_or_log('chat_files')) {
            return null;
        }
        $this->db->where('id', $file_id);
        return $this->db->get(db_prefix() . 'chat_files')->row_array();
    }

    // Reaction methods
    public function add_reaction($message_id, $user_id, $emoji)
    {
        if (!$this->table_exists_or_log('chat_reactions')) {
            return false;
        }
        // Check if reaction already exists
        $this->db->where('message_id', $message_id);
        $this->db->where('user_id', $user_id);
        $this->db->where('emoji', $emoji);
        $exists = $this->db->get(db_prefix() . 'chat_reactions')->row_array();
        if ($exists) {
            // Remove reaction if it exists (toggle)
            $this->db->where('id', $exists['id']);
            $this->db->delete(db_prefix() . 'chat_reactions');
            return false; // Removed
        } else {
            $data = [
                'message_id' => $message_id,
                'user_id'    => $user_id,
                'emoji'      => $emoji,
                'created_at' => date('Y-m-d H:i:s'),
            ];
            $this->db->insert(db_prefix() . 'chat_reactions', $data);
            return $this->db->insert_id(); // Added
        }
    }

    public function get_reactions($message_id)
    {
        if (!$this->table_exists_or_log('chat_reactions')) {
            return [];
        }
        $this->db->where('message_id', $message_id);
        $this->db->order_by('emoji', 'ASC');
        $reactions = $this->db->get(db_prefix() . 'chat_reactions')->result_array();
        // Group by emoji and count
        $grouped = [];
        foreach ($reactions as $r) {
            if (!isset($grouped[$r['emoji']])) {
                $grouped[$r['emoji']] = ['emoji' => $r['emoji'], 'count' => 0, 'users' => []];
            }
            $grouped[$r['emoji']]['count']++;
            $grouped[$r['emoji']]['users'][] = $r['user_id'];
        }
        return array_values($grouped);
    }

    // Typing indicator methods
    public function set_typing($channel_id, $user_id)
    {
        if (!$this->table_exists_or_log('chat_typing')) {
            return false;
        }
        $data = [
            'channel_id' => $channel_id,
            'user_id'    => $user_id,
            'updated_at' => date('Y-m-d H:i:s'),
        ];
        $this->db->replace(db_prefix() . 'chat_typing', $data); // REPLACE INTO
        return true;
    }

    public function clear_typing($channel_id, $user_id)
    {
        if (!$this->table_exists_or_log('chat_typing')) {
            return false;
        }
        $this->db->where('channel_id', $channel_id);
        $this->db->where('user_id', $user_id);
        $this->db->delete(db_prefix() . 'chat_typing');
        return true;
    }

    public function get_typing_users($channel_id)
    {
        if (!$this->table_exists_or_log('chat_typing')) {
            return [];
        }
        // Get users who have typed in the last 5 seconds
        $this->db->where('channel_id', $channel_id);
        $this->db->where('updated_at >', date('Y-m-d H:i:s', time() - 5));
        $typing = $this->db->get(db_prefix() . 'chat_typing')->result_array();
        $users = [];
        foreach ($typing as $t) {
            $users[] = $t['user_id'];
        }
        return $users;
    }

    // Thread methods
    public function add_thread_reply($parent_message_id, $message_data)
    {
        if (!$this->table_exists_or_log('chat_threads') || !$this->table_exists_or_log('chat_messages')) {
            return false;
        }
        // First, insert the message
        $msg_data = [
            'channel_id' => $message_data['channel_id'],
            'user_id'    => $message_data['user_id'],
            'message'    => $message_data['message'],
            'created_at' => date('Y-m-d H:i:s'),
        ];
        $this->db->insert(db_prefix() . 'chat_messages', $msg_data);
        $message_id = $this->db->insert_id();
        if (!$message_id) {
            return false;
        }
        // Then, link to thread
        $thread_data = [
            'parent_message_id' => $parent_message_id,
            'message_id'        => $message_id,
            'created_at'        => date('Y-m-d H:i:s'),
        ];
        $this->db->insert(db_prefix() . 'chat_threads', $thread_data);
        return $message_id;
    }

    public function get_thread_replies($parent_message_id)
    {
        if (!$this->table_exists_or_log('chat_threads') || !$this->table_exists_or_log('chat_messages')) {
            return [];
        }
        $this->db->select('m.*, t.parent_message_id');
        $this->db->from(db_prefix() . 'chat_messages m');
        $this->db->join(db_prefix() . 'chat_threads t', 't.message_id = m.id');
        $this->db->where('t.parent_message_id', $parent_message_id);
        $this->db->order_by('m.created_at', 'ASC');
        return $this->db->get()->result_array();
    }

    public function get_thread_count($parent_message_id)
    {
        if (!$this->table_exists_or_log('chat_threads')) {
            return 0;
        }
        $this->db->where('parent_message_id', $parent_message_id);
        return $this->db->count_all_results(db_prefix() . 'chat_threads');
    }
}
