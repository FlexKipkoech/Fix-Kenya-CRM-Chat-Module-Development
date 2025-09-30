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
