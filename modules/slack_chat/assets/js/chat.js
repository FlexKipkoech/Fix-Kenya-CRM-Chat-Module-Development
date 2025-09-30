// Slack Chat Module JS (enhanced for robust init & reliable AJAX form handling)
(function(){
    'use strict';

    var config = window.slackChatConfig || {};
    var pollingInterval = 2500; // ms
    var pollingTimer = null;
    var initialised = false;

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
        var url = config.baseUrl + '/get_messages/' + config.channelId;
        console.log('[Chat] GET recent URL:', url, data);
        $.get(url, data, function(resp){
            if (resp && resp.messages) {
                var html = '';
                resp.messages.forEach(function(m){
                    html += formatMessageHtml(m);
                });
                $('#chat-messages').html(html);
                scrollToBottom();
                if (resp.csrf && resp.csrf.hash) {
                    config.csrfName = resp.csrf.name;
                    config.csrfHash = resp.csrf.hash;
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
                // update CSRF token if server returned one
                if (resp.csrf && resp.csrf.name && resp.csrf.hash) {
                    config.csrfName = resp.csrf.name;
                    config.csrfHash = resp.csrf.hash;
                    // update form hidden input if present
                    $('form#chat-form input[name="'+resp.csrf.name+'"]').val(resp.csrf.hash);
                }
            }
        }, 'json').fail(function(xhr){
            console.error('[Chat] Poll failed', xhr.status, xhr.responseText);
        });
    }

    function initChat(){
        if (initialised || typeof window.jQuery === 'undefined') {
            return;
        }
        var $ = window.jQuery;
        initialised = true;
        console.log('[Chat] Initialising chat module');

        // staffId from global Perfex var if available
        window.staffId = window.staffId || (typeof(slackChatStaffId) !== 'undefined' ? slackChatStaffId : null);

        // initial load
        loadRecent();

        // set up polling
        pollingTimer = setInterval(function(){
            var last = jQuery('#chat-messages .chat-message').last().find('.chat-message-time').text();
            if (last) {
                pollSince(last);
            } else {
                loadRecent();
            }
        }, pollingInterval);

        // Robust delegated handler (in case form is re-rendered)
        jQuery(document).on('submit', '#chat-form', function(e){
            e.preventDefault(); // ensure no full page POST
            console.log('[Chat] Submit intercepted');
            var $form = jQuery(this);
            var $input = $form.find('#chat-input');
            var msg = jQuery.trim($input.val());
            if (!msg) { return false; }

            var data = addCsrf({channel_id: config.channelId, message: msg});
            var postUrl = config.baseUrl + '/send_message';
            jQuery('#chat-send').prop('disabled', true);
            console.log('[Chat] POST send URL:', postUrl, data);

            jQuery.post(postUrl, data, function(resp){
                jQuery('#chat-send').prop('disabled', false);
                console.log('[Chat] POST response:', resp);
                if (resp && resp.success && resp.message) {
                    // Ensure own message displays with explicit label
                    var mine = resp.message;
                    if (mine) {
                        mine.user_name = mine.user_name || 'You';
                        var html = formatMessageHtml(mine).replace('User '+mine.user_id+':', 'You:');
                        jQuery('#chat-messages').append(html);
                        scrollToBottom();
                    }
                    $input.val('').focus();
                    if (resp.csrf && resp.csrf.name && resp.csrf.hash) {
                        config.csrfName = resp.csrf.name;
                        config.csrfHash = resp.csrf.hash;
                        $form.find('input[name="'+resp.csrf.name+'"]').val(resp.csrf.hash);
                    }
                } else {
                    alert('Message failed to send.');
                }
            }, 'json').fail(function(xhr){
                jQuery('#chat-send').prop('disabled', false);
                console.error('[Chat] POST send failed', xhr.status, xhr.responseText);
                alert('Error sending message: ' + (xhr.responseJSON && xhr.responseJSON.error ? xhr.responseJSON.error : xhr.status));
            });

            return false; // safety
        });
    }

    // Attempt immediate init, fallback if jQuery not yet loaded
    if (typeof window.jQuery !== 'undefined') {
        window.jQuery(function(){ initChat(); });
    } else {
        var jqWait = setInterval(function(){
            if (typeof window.jQuery !== 'undefined') {
                clearInterval(jqWait);
                window.jQuery(function(){ initChat(); });
            }
        }, 100);
        setTimeout(function(){ clearInterval(jqWait); }, 10000); // stop after 10s
    }

})();
