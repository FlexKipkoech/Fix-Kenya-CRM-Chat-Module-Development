(function($){
    'use strict';

    var config = window.slackChatConfig || {};
    var pollingInterval = 2500; // ms
    var pollingTimer = null;
    var lastMessageTimestamp = null;

    /**
     * Get CSRF token from config or form
     */
    function getCsrf() {
        var tokenName = config.csrfName;
        var tokenVal = config.csrfHash;
        
        // Try to get from form if not in config
        if (!tokenName || !tokenVal) {
            var $hiddenInput = $('form#chat-form input[type=hidden]').filter(function(){ 
                return this.name && this.name.indexOf('csrf') !== -1; 
            }).first();
            tokenName = $hiddenInput.attr('name');
            tokenVal = $hiddenInput.val();
        }
        
        return {name: tokenName, value: tokenVal};
    }

    /**
     * Add CSRF token to data object
     */
    function addCsrf(data) {
        var csrf = getCsrf();
        if (csrf && csrf.name && csrf.value) {
            data[csrf.name] = csrf.value;
        }
        return data;
    }

    /**
     * Update CSRF token from server response
     */
    function updateCsrf(response) {
        if (response && response.csrf && response.csrf.name && response.csrf.hash) {
            config.csrfName = response.csrf.name;
            config.csrfHash = response.csrf.hash;
            // Update form hidden input
            $('form#chat-form input[name="'+response.csrf.name+'"]').val(response.csrf.hash);
        }
    }

    /**
     * Escape HTML to prevent XSS
     */
    function escapeHtml(text) {
        if (!text) return '';
        return String(text)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    /**
     * Format a message object as HTML
     */
    function formatMessageHtml(m) {
        var userName = m.user_name || m.firstname || m.lastname ? 
            (m.firstname + ' ' + m.lastname).trim() : 
            ('User ' + m.user_id);
        
        var who = $('<div>').addClass('chat-message-user').text(userName);
        var body = $('<div>').addClass('chat-message-body').html(escapeHtml(m.message).replace(/\n/g, '<br/>'));
        
        // Use formatted timestamp if available, otherwise use raw timestamp
        var timestamp = m.created_at_formatted || m.created_at;
        var time = $('<div>').addClass('chat-message-time text-muted').text(timestamp);
        
        // Determine if message is from current user
        var currentUserId = config.currentUserId || window.staffId;
        var cls = (m.user_id == currentUserId) ? 'chat-message mine' : 'chat-message theirs';
        
        var wrap = $('<div>').addClass(cls).append(who).append(body).append(time);
        return wrap.prop('outerHTML');
    }

    /**
     * Scroll chat container to bottom
     */
    function scrollToBottom() {
        var $box = $('#chat-messages');
        if ($box.length) {
            $box.scrollTop($box.prop('scrollHeight'));
        }
    }

    /**
     * Load recent messages for the channel
     */
    function loadRecent() {
        if (!config.channelId) return;
        
        var data = addCsrf({limit: 50});
        $.get(config.baseUrl + '/get_messages/' + config.channelId, data, function(resp){
            if (resp && resp.messages) {
                var html = '';
                resp.messages.forEach(function(m){
                    html += formatMessageHtml(m);
                    // Track last timestamp
                    if (m.created_at) {
                        lastMessageTimestamp = m.created_at;
                    }
                });
                $('#chat-messages').html(html);
                scrollToBottom();
                updateCsrf(resp);
            }
        }, 'json').fail(function(xhr, status, error) {
            console.error('Failed to load messages:', error);
        });
    }

    /**
     * Poll for new messages since timestamp
     */
    function pollSince(since) {
        if (!config.channelId) return;
        
        var data = addCsrf({since: since});
        $.get(config.baseUrl + '/poll_messages/' + config.channelId, data, function(resp){
            if (resp && resp.success && resp.messages && resp.messages.length > 0) {
                var html = '';
                resp.messages.forEach(function(m){ 
                    html += formatMessageHtml(m);
                    // Update last timestamp
                    if (m.created_at) {
                        lastMessageTimestamp = m.created_at;
                    }
                });
                $('#chat-messages').append(html);
                scrollToBottom();
                updateCsrf(resp);
            } else if (resp) {
                updateCsrf(resp);
            }
        }, 'json').fail(function(xhr, status, error) {
            console.error('Polling failed:', error);
        });
    }

    /**
     * Send a message
     */
    function sendMessage(message) {
        if (!message || !config.channelId) return;
        
        var data = addCsrf({
            channel_id: config.channelId, 
            message: message
        });
        
        $('#chat-send').prop('disabled', true);
        
        $.post(config.baseUrl + '/send_message', data, function(resp){
            $('#chat-send').prop('disabled', false);
            
            if (resp && resp.success) {
                if (resp.message) {
                    var html = formatMessageHtml(resp.message);
                    $('#chat-messages').append(html);
                    scrollToBottom();
                    
                    // Update last timestamp
                    if (resp.message.created_at) {
                        lastMessageTimestamp = resp.message.created_at;
                    }
                }
                
                $('#chat-input').val('').focus();
                updateCsrf(resp);
            } else {
                alert('Failed to send message. Please try again.');
            }
        }, 'json').fail(function(xhr, status, error) {
            $('#chat-send').prop('disabled', false);
            
            // Check for CSRF error
            if (xhr.status === 419 || (xhr.responseText && xhr.responseText.indexOf('419') !== -1)) {
                alert('Session expired. Please reload the page.');
            } else {
                alert('Error sending message: ' + (error || 'Unknown error'));
            }
        });
    }

    /**
     * Initialize chat functionality
     */
    $(document).ready(function(){
        // Set current user ID for message styling
        if (typeof(get_staff_user_id) !== 'undefined') {
            config.currentUserId = get_staff_user_id();
        }
        
        // Only initialize if we have a channel selected
        if (!config.channelId) {
            return;
        }
        
        // Initial load of messages
        loadRecent();
        
        // Set up polling for new messages
        pollingTimer = setInterval(function(){
            if (lastMessageTimestamp) {
                pollSince(lastMessageTimestamp);
            } else {
                // Fallback: get last timestamp from DOM
                var $lastTime = $('#chat-messages .chat-message').last().find('.chat-message-time');
                if ($lastTime.length) {
                    lastMessageTimestamp = $lastTime.text().trim();
                    if (lastMessageTimestamp) {
                        pollSince(lastMessageTimestamp);
                    } else {
                        loadRecent();
                    }
                } else {
                    loadRecent();
                }
            }
        }, pollingInterval);
        
        // Handle message form submission
        $('#chat-form').on('submit', function(e){
            e.preventDefault();
            var msg = $.trim($('#chat-input').val());
            if (msg) {
                sendMessage(msg);
            }
            return false;
        });
        
        // Focus input on load
        $('#chat-input').focus();
        
        // Clean up polling on page unload
        $(window).on('beforeunload', function(){
            if (pollingTimer) {
                clearInterval(pollingTimer);
            }
        });
    });

})(jQuery);
