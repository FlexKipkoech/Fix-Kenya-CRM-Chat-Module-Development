<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<link href="<?php echo module_dir_url('slack_chat', 'assets/css/chat.css'); ?>" rel="stylesheet">

<div id="_wrapper" class="container-fluid">
    <div class="content">
        <div class="panel_s">
            <div class="panel-body">
                <h4 class="no-margin"><?php echo _l('Chat'); ?></h4>
                <hr />
                <div class="chat-wrapper">
                    <div class="chat-sidebar">
                        <div class="clearfix mbot10">
                            <h5 class="pull-left"><?php echo _l('Channels'); ?></h5>
                            <a href="<?php echo admin_url('slack_chat/create_channel'); ?>" class="btn btn-primary btn-sm pull-right" style="margin-top:-6px;"><?php echo _l('Create Channel'); ?></a>
                        </div>
                        <ul class="list-group chat-channels-list">
                            <?php foreach ($channels as $channel): ?>
                                <?php $active = ($active_channel == $channel['id']); ?>
                                <li class="list-group-item <?php echo $active ? 'active' : ''; ?> channel-item" data-id="<?php echo $channel['id']; ?>">
                                    <a href="<?php echo admin_url('slack_chat/chat/' . $channel['id']); ?>" style="color:inherit; text-decoration:none; display:block;">
                                        <?php echo htmlspecialchars($channel['name']); ?>
                                        <div class="text-muted" style="font-size:11px;"><?php echo htmlspecialchars($channel['description']); ?></div>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <div class="chat-main">
                        <h4 class="mt-0"><?php if ($active_channel) { $ch = $this->Chat_model->get_channel_by_id($active_channel); echo htmlspecialchars($ch['name']); } else { echo _l('No channel selected'); } ?></h4>
                        <?php if ($active_channel): ?>
                            <div id="chat-messages" class="chat-messages">
                                <?php if (empty($messages)): ?>
                                    <div class="text-center text-muted"><?php echo _l('No messages yet. Say hello!'); ?></div>
                                <?php else: ?>
                                    <?php foreach ($messages as $msg): ?>
                                        <div class="chat-message <?php echo ($msg['user_id'] == get_staff_user_id()) ? 'mine' : 'theirs'; ?>" data-message-id="<?php echo $msg['id']; ?>">
                                            <div class="chat-message-user"><?php echo isset($msg['user_name']) ? htmlspecialchars($msg['user_name']) : 'User ' . $msg['user_id']; ?></div>
                                            <div class="chat-message-body"><?php echo nl2br(htmlspecialchars($msg['message'])); ?></div>
                                            <?php $files = $this->Chat_model->get_message_files($msg['id']); ?>
                                            <?php if (!empty($files)): ?>
                                                <div class="chat-message-files">
                                                    <?php foreach ($files as $file): ?>
                                                        <div class="file-attachment">
                                                            <a href="<?php echo admin_url('slack_chat/download_file/' . $file['id']); ?>" target="_blank">
                                                                <i class="fa fa-file"></i> <?php echo htmlspecialchars($file['filename']); ?>
                                                            </a>
                                                        </div>
                                                    <?php endforeach; ?>
                                                </div>
                                            <?php endif; ?>
                                            <div class="chat-message-time text-muted"><?php echo $msg['created_at']; ?></div>
                                            <div class="chat-message-actions">
                                                <button class="btn btn-sm btn-link reaction-btn" data-emoji="👍">👍</button>
                                                <button class="btn btn-sm btn-link reaction-btn" data-emoji="❤️">❤️</button>
                                                <button class="btn btn-sm btn-link reaction-btn" data-emoji="😂">😂</button>
                                                <button class="btn btn-sm btn-link reaction-btn" data-emoji="😮">😮</button>
                                                <button class="btn btn-sm btn-link reaction-btn" data-emoji="😢">😢</button>
                                                <button class="btn btn-sm btn-link reaction-btn" data-emoji="🎉">🎉</button>
                                                <button class="btn btn-sm btn-link thread-btn">Reply in thread</button>
                                            </div>
                                            <?php $reactions = $this->Chat_model->get_reactions($msg['id']); ?>
                                            <?php if (!empty($reactions)): ?>
                                                <div class="chat-message-reactions">
                                                    <?php foreach ($reactions as $reaction): ?>
                                                        <span class="reaction-bubble" data-emoji="<?php echo $reaction['emoji']; ?>">
                                                            <?php echo $reaction['emoji']; ?> <?php echo $reaction['count']; ?>
                                                        </span>
                                                    <?php endforeach; ?>
                                                </div>
                                            <?php endif; ?>
                                            <?php $thread_count = $this->Chat_model->get_thread_count($msg['id']); ?>
                                            <?php if ($thread_count > 0): ?>
                                                <div class="thread-indicator">
                                                    <i class="fa fa-reply"></i> <?php echo $thread_count; ?> replies
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                            <div id="typing-indicator" class="text-muted" style="height:18px;margin-top:6px;display:none;">typing...</div>
                            <form id="chat-form" class="mt-2" method="post" action="<?php echo admin_url('slack_chat/send_message'); ?>" enctype="multipart/form-data">
                                <?php echo form_hidden('channel_id', $active_channel); ?>
                                <?php echo form_hidden($this->security->get_csrf_token_name(), $this->security->get_csrf_hash()); ?>
                                <div class="input-group">
                                    <textarea name="message" id="chat-input" class="form-control" placeholder="Type a message..." rows="2" required></textarea>
                                    <div class="input-group-btn">
                                        <label class="btn btn-default btn-file">
                                            <i class="fa fa-paperclip"></i>
                                            <input type="file" id="file-input" style="display: none;">
                                        </label>
                                        <button class="btn btn-info" id="chat-send" type="submit"><?php echo _l('Send'); ?></button>
                                    </div>
                                </div>
                            </form>
                            <script>
                                var slackChatConfig = {
                                    baseUrl: '<?php echo admin_url('slack_chat'); ?>',
                                    channelId: <?php echo (int)$active_channel; ?>,
                                    csrfName: '<?php echo $this->security->get_csrf_token_name(); ?>',
                                    csrfHash: '<?php echo $this->security->get_csrf_hash(); ?>'
                                };
                            </script>

                        <?php else: ?>
                            <div class="alert alert-info"><?php echo _l('Select a channel to start chatting.'); ?></div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="<?php echo module_dir_url('slack_chat', 'assets/js/chat.js'); ?>"></script>
<?php init_tail(); ?>
