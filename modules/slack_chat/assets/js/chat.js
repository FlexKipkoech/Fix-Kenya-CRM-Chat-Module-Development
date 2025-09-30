(function($){
    'use strict';

    var config = window.slackChatConfig || {};
    var pollingInterval = 2500; // ms
    var pollingTimer = null;

    function getCsrf() {
        // Try to read from form hidden input first
        var tokenName = config.csrfName || $('form#chat-form input[type=hidden]').filter(function(){ return this.name && this.name.indexOf('csrf') !== -1; }).attr('name');
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
        var cls = (m.user_id == window.staffId) ? 'chat-message mine' : 'chat-message theirs';
        var wrap = $('<div>').addClass(cls).append(who).append(body).append(time);
        return wrap.prop('outerHTML');
    }

    function escapeHtml(text) {
        if (!text) return '';
        return text.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
    }

    function scrollToBottom() {
        var box = $('#chat-messages');
        box.scrollTop(box.prop('scrollHeight'));
    }

    function loadRecent() {
        if (!config.channelId) return;
    var data = addCsrf({limit:200});
    $.get(config.baseUrl + '/get_messages/' + config.channelId, data, function(resp){
            if (resp && resp.messages) {
                var html = '';
                resp.messages.forEach(function(m){
                    html += formatMessageHtml(m);
                });
                $('#chat-messages').html(html);
                scrollToBottom();
            }
        }, 'json');
    }

    function pollSince(since) {
        if (!config.channelId) return;
        var data = addCsrf({since: since});
        $.get(config.baseUrl + '/poll_messages/' + config.channelId, data, function(resp){
            if (resp && resp.success && resp.messages && resp.messages.length) {
                var html = '';
                resp.messages.forEach(function(m){ html += formatMessageHtml(m); });
                $('#chat-messages').append(html);
                scrollToBottom();
                // update CSRF token if server returned one
                if (resp.csrf && resp.csrf.name && resp.csrf.hash) {
                    config.csrfName = resp.csrf.name;
                    config.csrfHash = resp.csrf.hash;
                    // update form hidden input if present
                    $('form#chat-form input[name="'+resp.csrf.name+'"]').val(resp.csrf.hash);
                }
            }
        }, 'json');
    }

    $(document).ready(function(){
        // staffId from global Perfex var if available
        window.staffId = window.staffId || (typeof(slackChatStaffId) !== 'undefined' ? slackChatStaffId : null);

        // initial load
        loadRecent();

        // set up polling
        var lastTimestamp = null;
        pollingTimer = setInterval(function(){
            // determine last timestamp from last message
            var last = $('#chat-messages .chat-message').last().find('.chat-message-time').text();
            if (last) {
                pollSince(last);
            } else {
                loadRecent();
            }
        }, pollingInterval);

        // send message handler
        $('#chat-form').on('submit', function(e){
            e.preventDefault();
            var $form = $(this);
            var msg = $.trim($('#chat-input').val());
            if (!msg) return;
            var data = addCsrf({channel_id: config.channelId, message: msg});
            $('#chat-send').prop('disabled', true);
            $.post(config.baseUrl + '/send_message', data, function(resp){
                $('#chat-send').prop('disabled', false);
                if (resp && resp.success && resp.message) {
                    var html = formatMessageHtml(resp.message);
                    $('#chat-messages').append(html);
                    $('#chat-input').val('').focus();
                    scrollToBottom();
                    // update CSRF token if server returned one
                    if (resp.csrf && resp.csrf.name && resp.csrf.hash) {
                        config.csrfName = resp.csrf.name;
                        config.csrfHash = resp.csrf.hash;
                        $('form#chat-form input[name="'+resp.csrf.name+'"]').val(resp.csrf.hash);
                    }
                }
            }, 'json').fail(function(){
                $('#chat-send').prop('disabled', false);
            });
        });

        // change channel handler (sidebar)
        $('.chat-channels-list').on('click', '.channel-item a', function(e){
            // let link navigate normally (full page load) for simplicity
        });
    });

})(jQuery);
