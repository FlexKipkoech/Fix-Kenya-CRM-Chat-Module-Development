# Slack-like Chat Module for Perfex CRM

A real-time chat module for Perfex CRM that provides Slack-like messaging functionality with channels, private messaging, and modern UI.

## Features

### ✅ Implemented Features

#### Phase 1: Core Functionality
- ✅ Channel creation and management
- ✅ Public and private channels
- ✅ Real-time message polling (2.5 second interval)
- ✅ User-friendly chat interface
- ✅ Message history

#### Phase 2: Security & Access Control
- ✅ CSRF token protection on all AJAX requests
- ✅ Private channel access enforcement
- ✅ Channel visibility based on membership
- ✅ Automatic token refresh

#### Phase 3: UI/UX Enhancements
- ✅ Modern Slack-like styling with blue message bubbles
- ✅ Distinct styling for sent vs received messages
- ✅ Channel sidebar with hover effects
- ✅ Lock icons for private channels
- ✅ Hashtag icons for public channels
- ✅ Auto-scroll to latest messages
- ✅ Responsive design

#### Phase 4: Advanced Features
- ✅ Timestamp formatting using Perfex helpers (`_dt()`)
- ✅ Typing indicator UI (local infrastructure ready)
- ✅ User name display with proper formatting
- ✅ XSS protection with HTML escaping

### 🚧 Future Enhancements

- WebSocket support for true real-time updates
- @mentions and notifications
- File attachments
- Message editing and deletion
- Thread replies
- Emoji reactions
- User presence (online/offline status)
- Message search
- Unread message count badges

## Installation

1. Copy the `slack_chat` folder to your Perfex CRM's `modules/` directory
2. Navigate to Setup → Modules in your Perfex CRM admin panel
3. Activate the "Slack Chat" module
4. The module will automatically create the required database tables

## Database Tables

The module creates three tables:

- `chat_channels` - Stores channel information
- `chat_messages` - Stores all messages
- `chat_members` - Tracks channel membership

## Usage

### Creating Channels

1. Navigate to the Chat menu in the admin sidebar
2. Click "Create Channel"
3. Enter channel name and description
4. Optionally mark as private
5. Click Create

### Sending Messages

1. Select a channel from the sidebar
2. Type your message in the input box
3. Press Enter or click Send
4. Messages appear instantly for you and within 2-3 seconds for others

### Private Channels

- Private channels show a 🔒 lock icon
- Only members can see and access private channels
- Channel creator is automatically added as a member
- Use the join_channel endpoint to add more members (future UI planned)

## Technical Details

### Architecture

```
modules/slack_chat/
├── controllers/
│   └── Slack_chat.php       # Main controller with AJAX endpoints
├── models/
│   └── Chat_model.php        # Database operations
├── views/
│   └── admin/
│       ├── chat.php          # Main chat interface
│       ├── create_channel.php # Channel creation form
│       └── dashboard.php     # Module dashboard
├── assets/
│   ├── css/
│   │   └── chat.css          # Chat styling
│   └── js/
│       └── chat.js           # Frontend chat logic
├── install.php               # Database schema creation
└── slack_chat.php            # Module registration
```

### Key Functions

#### Controller Methods
- `chat($channel_id)` - Main chat interface
- `send_message()` - AJAX endpoint to send messages
- `get_messages($channel_id)` - Get recent messages
- `poll_messages($channel_id)` - Poll for new messages
- `create_channel()` - Create new channels
- `join_channel($channel_id)` - Join a channel
- `typing_indicator()` - Typing indicator endpoint (ready for WebSocket)

#### Model Methods
- `get_accessible_channels($user_id)` - Get channels user can access
- `user_can_access_channel($channel_id, $user_id)` - Check access rights
- `send_message($channel_id, $user_id, $message)` - Insert message
- `get_recent_messages($channel_id, $limit)` - Fetch messages
- `get_messages_after($channel_id, $timestamp)` - Poll for new messages

### Security Features

1. **CSRF Protection**
   - All AJAX requests include CSRF tokens
   - Tokens automatically refresh after each request
   - 419 errors are caught and user is notified

2. **Access Control**
   - Private channels enforce membership
   - All endpoints verify user access before operations
   - XSS protection via HTML escaping

3. **Input Validation**
   - Messages are trimmed and validated
   - Channel IDs are cast to integers
   - SQL injection protection via CodeIgniter's Active Record

## Configuration

### Polling Interval

To change the message polling frequency, edit `assets/js/chat.js`:

```javascript
var pollingInterval = 2500; // Change this value (in milliseconds)
```

### Message Limit

To change the number of messages loaded, edit the AJAX call in `chat.js`:

```javascript
var data = addCsrf({limit: 50}); // Change 50 to desired limit
```

## Troubleshooting

### 419 Page Expired Error
- This indicates CSRF token issues
- Solution: The module now automatically refreshes tokens
- If persistent, check that CSRF is enabled in CodeIgniter config

### Admin Sidebar Not Showing
- Ensure `init_head()` and `init_tail()` are called in views
- Check that views are wrapped in proper Perfex layout structure

### CSS Not Loading
- Verify the `module_dir_url()` helper is available
- Check file permissions on `assets/css/chat.css`
- Clear browser cache

### Messages Not Appearing
- Check browser console for JavaScript errors
- Verify AJAX endpoints are accessible
- Check database tables exist and have data
- Ensure user has access to the channel

## Development

### Adding Custom Features

1. **New Endpoints**: Add methods to `Slack_chat.php` controller
2. **Database Changes**: Modify `install.php` and create migration
3. **Frontend**: Update `chat.js` and `chat.css`
4. **Access Control**: Use `user_can_access_channel()` for security

### Coding Standards

- Follow CodeIgniter 3 conventions
- Use `db_prefix()` for all table names
- Use `_l()` for translatable strings
- Use Perfex helpers: `admin_url()`, `get_staff_user_id()`, etc.
- Always check `is_ajax_request()` for AJAX endpoints

## Support

For issues and feature requests, please contact the module maintainer.

## License

This module is part of the Perfex CRM ecosystem.

## Version History

### v1.0.0 (Current)
- Initial release with core chat functionality
- Channel management (public/private)
- Real-time polling-based messaging
- Modern Slack-like UI
- CSRF protection and access control
- Timestamp formatting
- Typing indicator infrastructure
