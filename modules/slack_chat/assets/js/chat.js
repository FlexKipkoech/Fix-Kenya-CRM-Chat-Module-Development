(function($){
    'use strict';

    if (typeof $ === 'undefined') {
        console.error('jQuery not loaded. Slack Chat module cannot initialize.');
        return;
    }

    var config = window.slackChatConfig || {};
    var pollingInterval = 2500; // ms
    var pollingTimer = null;

    function getCsrf() {
        var tokenName = config.csrfName || $('form#chat-form input[type=hidden][name*="csrf"]').attr('name');
        var tokenVal = config.csrfHash || $('form#chat-form input[name="'+tokenName+'"]').val();
        return {name: tokenName, value: tokenVal};
    }

    function addCsrf(data) {
        var csrf = getCsrf();
        if (csrf && csrf.name) {
            data[csrf.name] = csrf.value;
        }
        return data;
    }

    function formatMessageHtml(m) {
        var who = $('<div>').addClass('chat-message-user').text(m.user_name || ('User ' + m.user_id));
        var body = $('<div>').addClass('chat-message-body').html(escapeHtml(m.message).replace(/\n/g, '<br/>'));
        var time = $('<div>').addClass('chat-message-time text-muted').text(m.created_at);
        var cls = (m.user_id == (window.staffId || (typeof(slackChatStaffId) !== 'undefined' ? slackChatStaffId : null))) ? 'chat-message mine' : 'chat-message theirs';
        var wrap = $('<div>').addClass(cls).attr('data-message-id', m.id).append(who).append(body).append(time);
        return wrap.prop('outerHTML');
    }

    function escapeHtml(text) {
        if (!text) return '';
        return text.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
    }

    function scrollToBottom() {
        var box = $('#chat-messages');
        if(box.length) {
            box.scrollTop(box.prop('scrollHeight'));
        }
    }

    function loadRecent() {
        if (!config.channelId) return;
        var data = addCsrf({limit:200});
        var url = config.baseUrl + '/get_messages/' + config.channelId;
        
        $.get(url, data, function(resp){
            if (resp && resp.messages) {
                var html = '';
                resp.messages.forEach(function(m){
                    html += formatMessageHtml(m);
                });
                $('#chat-messages').html(html);
                scrollToBottom();
                if (resp.csrf && resp.csrf.hash) {
                    updateCsrf(resp.csrf.name, resp.csrf.hash);
                }
            }
        }, 'json').fail(function(xhr){
            console.error('[Chat] GET recent failed', xhr.status, xhr.responseText);
        });
    }

    function pollSince(since) {
        if (!config.channelId) return;
        var data = addCsrf({since: since});
        var url = config.baseUrl + '/poll_messages/' + config.channelId;
        $.get(url, data, function(resp){
            if (resp && resp.success && resp.messages && resp.messages.length) {
                var html = '';
                resp.messages.forEach(function(m){ html += formatMessageHtml(m); });
                $('#chat-messages').append(html);
                scrollToBottom();
                if (resp.csrf && resp.csrf.name && resp.csrf.hash) {
                    updateCsrf(resp.csrf.name, resp.csrf.hash);
                }
            }
        }, 'json').fail(function(xhr){
            console.error('[Chat] Poll failed', xhr.status, xhr.responseText);
        });
    }

    function updateCsrf(name, hash) {
        config.csrfName = name;
        config.csrfHash = hash;
        $('form#chat-form input[name="'+name+'"]').val(hash);
    }

    $(document).ready(function(){
        console.log('[Chat] Initialising chat module');
        window.staffId = window.staffId || (typeof(slackChatStaffId) !== 'undefined' ? slackChatStaffId : null);

        loadRecent();

        if(pollingTimer) clearInterval(pollingTimer);
        pollingTimer = setInterval(function(){
            var last = $('#chat-messages .chat-message').last().find('.chat-message-time').text();
            if (last) {
                pollSince(last);
            } else {
                loadRecent();
            }
        }, pollingInterval);

        $('#chat-form').on('submit', function(e){
            e.preventDefault();
            var $form = $(this);
            var $input = $form.find('#chat-input');
            var msg = $.trim($input.val());
            if (!msg) { return; }

            $input.val('');

            var data = addCsrf({channel_id: config.channelId, message: msg});
            var postUrl = config.baseUrl + '/send_message';
            $('#chat-send').prop('disabled', true);

            $.post(postUrl, data, function(resp){
                if (resp && resp.success && resp.message) {
                    var html = formatMessageHtml(resp.message);
                    $('#chat-messages').append(html);
                    scrollToBottom();
                    if (resp.csrf && resp.csrf.name && resp.csrf.hash) {
                        updateCsrf(resp.csrf.name, resp.csrf.hash);
                    }
                    // Upload file if selected
                    var fileInput = $('#file-input')[0];
                    if (fileInput.files.length > 0) {
                        uploadFile(resp.message.id, fileInput.files[0]);
                        fileInput.value = ''; // Clear file input
                    }
                } else {
                    alert('Message failed to send: ' + (resp.error || 'Unknown error'));
                    $input.val(msg); // Restore message on failure
                }
            }, 'json').fail(function(xhr){
                console.error('[Chat] POST send failed', xhr.status, xhr.responseText);
                alert('Error sending message: ' + (xhr.responseJSON && xhr.responseJSON.error ? xhr.responseJSON.error : 'Server error'));
                $input.val(msg);
            }).always(function(){
                $('#chat-send').prop('disabled', false);
                $input.focus();
            });
        });

        // File upload function
        function uploadFile(messageId, file) {
            var formData = new FormData();
            formData.append('message_id', messageId);
            formData.append('file', file);
            formData.append(config.csrfName, config.csrfHash);

            $.ajax({
                url: config.baseUrl + '/upload_file',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(resp) {
                    if (resp.success) {
                        // Update message with file attachment
                        var $msg = $('#chat-messages .chat-message[data-message-id="' + messageId + '"]');
                        var fileHtml = '<div class="file-attachment"><a href="' + config.baseUrl + '/download_file/' + resp.file_id + '" target="_blank"><i class="fa fa-file"></i> ' + resp.filename + '</a></div>';
                        $msg.find('.chat-message-body').after(fileHtml);
                    } else {
                        alert('File upload failed: ' + resp.error);
                    }
                },
                error: function(xhr) {
                    alert('File upload error: ' + xhr.responseJSON.error);
                }
            });
        }

        // Typing indicator
        var typingTimer;
        $('#chat-input').on('input', function(){
            clearTimeout(typingTimer);
            setTyping(true);
            typingTimer = setTimeout(function(){
                setTyping(false);
            }, 3000);
        });

        function setTyping(isTyping) {
            $.post(config.baseUrl + '/typing_status', addCsrf({channel_id: config.channelId, is_typing: isTyping ? '1' : '0'}), function(resp){
                if (resp.success) {
                    updateTypingIndicator(resp.typing_users);
                }
            }, 'json');
        }

        function updateTypingIndicator(typingUsers) {
            var $indicator = $('#typing-indicator');
            if (typingUsers.length > 0) {
                var names = typingUsers.map(function(id){ return 'User ' + id; }).join(', ');
                $indicator.text(names + ' is typing...').show();
            } else {
                $indicator.hide();
            }
        }

        // Reaction buttons
        $(document).on('click', '.reaction-btn', function(){
            var $btn = $(this);
            var emoji = $btn.data('emoji');
            var messageId = $btn.closest('.chat-message').data('message-id');

            $.post(config.baseUrl + '/add_reaction', addCsrf({message_id: messageId, emoji: emoji}), function(resp){
                if (resp.success) {
                    updateReactions(messageId, resp.reactions);
                }
            }, 'json');
        });

        function updateReactions(messageId, reactions) {
            var $msg = $('#chat-messages .chat-message[data-message-id="' + messageId + '"]');
            var $reactions = $msg.find('.chat-message-reactions');
            if ($reactions.length === 0) {
                $reactions = $('<div class="chat-message-reactions"></div>');
                $msg.append($reactions);
            }
            $reactions.empty();
            reactions.forEach(function(r){
                var html = '<span class="reaction-bubble" data-emoji="' + r.emoji + '">' + r.emoji + ' ' + r.count + '</span>';
                $reactions.append(html);
            });
        }

        // Thread buttons
        $(document).on('click', '.thread-btn', function(){
            var messageId = $(this).closest('.chat-message').data('message-id');
            // For simplicity, just alert. In a full implementation, open a modal or panel
            alert('Thread replies for message ' + messageId + ' - implement modal here');
        });

        $('#chat-channels').on('click', '.channel-item a', function(e){
            e.preventDefault();
            var $link = $(this);
            var newChannelId = $link.parent().data('id');
            
            // Update URL without reloading page
            var newUrl = config.baseUrl + '/chat/' + newChannelId;
            history.pushState({path: newUrl}, '', newUrl);

            // Visually update active channel
            $('.channel-item.active').removeClass('active');
            $link.parent().addClass('active');

            $('#chat-messages').html('<div class="text-center text-muted">Loading...</div>');
            config.channelId = newChannelId;
            var channelName = $link.find('.channel-name').text().trim();
            $('#chat-channel-name').text(channelName);
            $('input[name="channel_id"]').val(newChannelId);

            loadRecent();
        });
    });

})(jQuery);
