<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<div class="panel_s">
    <div class="panel-body">
        <h4 class="no-margin"><?php echo _l('Chat'); ?></h4>
        <hr />
        <div class="row">
            <div class="col-md-3">
                <h5><?php echo _l('Channels'); ?></h5>
                <ul class="list-group">
                    <?php foreach ($channels as $channel): ?>
                        <li class="list-group-item <?php echo ($active_channel == $channel['id']) ? 'active' : ''; ?>">
                            <a href="<?php echo admin_url('slack_chat/chat/' . $channel['id']); ?>" style="color:inherit; text-decoration:none;">
                                <?php echo htmlspecialchars($channel['name']); ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <div class="col-md-9">
                <?php if ($active_channel): ?>
                    <div id="chat-messages" style="height:300px; overflow-y:auto; border:1px solid #ddd; padding:10px; background:#fafafa;">
                        <?php foreach ($messages as $msg): ?>
                            <div><strong>User <?php echo $msg['user_id']; ?>:</strong> <?php echo htmlspecialchars($msg['message']); ?> <span class="text-muted" style="font-size:11px;">[<?php echo $msg['created_at']; ?>]</span></div>
                        <?php endforeach; ?>
                    </div>
                    <form id="chat-form" class="mt-2" method="post" action="<?php echo admin_url('slack_chat/send_message'); ?>">
                        <div class="input-group">
                            <input type="hidden" name="channel_id" value="<?php echo $active_channel; ?>">
                            <input type="text" name="message" class="form-control" placeholder="Type a message..." autocomplete="off" required>
                            <span class="input-group-btn">
                                <button class="btn btn-info" type="submit"><?php echo _l('Send'); ?></button>
                            </span>
                        </div>
                    </form>
                    <script>
                        // AJAX send message
                        document.getElementById('chat-form').onsubmit = function(e) {
                            e.preventDefault();
                            var form = this;
                            var data = new FormData(form);
                            fetch(form.action, {
                                method: 'POST',
                                body: data,
                                headers: {'X-Requested-With': 'XMLHttpRequest'}
                            })
                            .then(r => r.json())
                            .then(resp => {
                                if (resp.success) {
                                    form.message.value = '';
                                    loadMessages();
                                }
                            });
                        };
                        // AJAX load messages
                        function loadMessages() {
                            fetch('<?php echo admin_url('slack_chat/get_messages/' . $active_channel); ?>', {
                                headers: {'X-Requested-With': 'XMLHttpRequest'}
                            })
                            .then(r => r.json())
                            .then(resp => {
                                var box = document.getElementById('chat-messages');
                                box.innerHTML = resp.messages.map(function(msg) {
                                    return `<div><strong>User ${msg.user_id}:</strong> ${msg.message.replace(/</g,'&lt;')} <span class=\"text-muted\" style=\"font-size:11px;\">[${msg.created_at}]</span></div>`;
                                }).join('');
                                box.scrollTop = box.scrollHeight;
                            });
                        }
                        // Poll for new messages every 5 seconds
                        setInterval(loadMessages, 5000);
                    </script>
                <?php else: ?>
                    <div class="alert alert-info"><?php echo _l('Select a channel to start chatting.'); ?></div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
