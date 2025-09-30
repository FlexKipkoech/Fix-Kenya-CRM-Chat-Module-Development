# Testing Guide for Slack Chat Module

## Pre-Testing Checklist

Before testing, ensure:
- [ ] Module is copied to `modules/slack_chat/` in Perfex CRM
- [ ] Module is activated via Setup → Modules
- [ ] You are logged in as an admin user
- [ ] Database tables have been created (check via install.php)

## Test Suite

### 1. Layout & Styling Tests

#### Test 1.1: Admin Sidebar Visibility
**Steps:**
1. Navigate to Admin → Chat
2. Check that the Perfex CRM admin sidebar is visible on the left
3. Check that top navigation bar is present

**Expected Result:** 
- ✅ Admin sidebar is visible
- ✅ Top navigation is present
- ✅ Chat interface is within the content area

#### Test 1.2: CSS Styling Applied
**Steps:**
1. On the chat page, observe the styling
2. Check message bubbles
3. Check channel sidebar
4. Check input area

**Expected Result:**
- ✅ Blue message bubbles for sent messages
- ✅ Gray message bubbles for received messages
- ✅ Channel sidebar has hover effects
- ✅ Rounded input box with blue focus border
- ✅ Modern, clean appearance

### 2. Channel Management Tests

#### Test 2.1: Auto-Created Default Channels
**Steps:**
1. Navigate to Chat for the first time
2. Check the channel sidebar

**Expected Result:**
- ✅ "General" channel exists
- ✅ "Random" channel exists
- ✅ Channels show hashtag (#) icon
- ✅ You are auto-joined to General channel

#### Test 2.2: Create Public Channel
**Steps:**
1. Click "Create Channel" button
2. Enter channel name: "Test Public"
3. Enter description: "Test public channel"
4. Leave "Private Channel" unchecked
5. Click Create

**Expected Result:**
- ✅ Success message appears
- ✅ Redirected to the new channel
- ✅ Channel appears in sidebar with # icon
- ✅ You can send messages immediately

#### Test 2.3: Create Private Channel
**Steps:**
1. Click "Create Channel" button
2. Enter channel name: "Test Private"
3. Enter description: "Test private channel"
4. Check "Private Channel" checkbox
5. Click Create

**Expected Result:**
- ✅ Success message appears
- ✅ Redirected to the new channel
- ✅ Channel appears in sidebar with 🔒 lock icon
- ✅ Channel is only visible to you (creator)

### 3. Messaging Tests

#### Test 3.1: Send a Message
**Steps:**
1. Select any channel
2. Type a test message: "Hello, this is a test message"
3. Press Enter or click Send

**Expected Result:**
- ✅ Message appears immediately in chat area
- ✅ Message is on the right side (blue bubble)
- ✅ Your name appears above the message
- ✅ Timestamp is formatted nicely (e.g., "2024-01-15 10:30 AM")
- ✅ Input field clears after sending
- ✅ Input field receives focus
- ✅ Chat scrolls to bottom

#### Test 3.2: No 419 CSRF Error
**Steps:**
1. Send a message
2. Wait 1 second
3. Send another message
4. Repeat 5 times

**Expected Result:**
- ✅ All messages send successfully
- ✅ No "419 Page Expired" error appears
- ✅ No CSRF errors in browser console

#### Test 3.3: Message Polling (Two Users)
**Setup:** Open chat in two different browsers/sessions as different admin users

**Steps:**
1. In Browser 1: Send a message in General channel
2. In Browser 2: Wait up to 3 seconds

**Expected Result:**
- ✅ Message appears in Browser 2 within 2-3 seconds
- ✅ Message is on the left side (gray bubble) in Browser 2
- ✅ Sender's name is displayed
- ✅ Timestamp is shown

### 4. Private Channel Access Tests

#### Test 4.1: Private Channel Access Restriction
**Setup:** Create a private channel as User A

**Steps:**
1. As User A: Create private channel "Secret Project"
2. As User B: Navigate to Chat
3. As User B: Check channel list

**Expected Result:**
- ✅ User B cannot see "Secret Project" in the channel list
- ✅ User B cannot access the channel via direct URL

#### Test 4.2: Public Channel Access
**Steps:**
1. As User A: Create public channel "Team Updates"
2. As User B: Navigate to Chat
3. As User B: Check channel list

**Expected Result:**
- ✅ User B can see "Team Updates" in the channel list
- ✅ User B can click and access the channel
- ✅ User B can send messages in the channel

### 5. UI/UX Tests

#### Test 5.1: Channel Switching
**Steps:**
1. Select General channel
2. Send a message: "Message in General"
3. Select Random channel
4. Send a message: "Message in Random"
5. Switch back to General

**Expected Result:**
- ✅ Messages persist in each channel
- ✅ Correct messages appear when switching
- ✅ Active channel is highlighted in sidebar
- ✅ Channel name shows in main area header

#### Test 5.2: Typing Indicator
**Steps:**
1. Select any channel
2. Start typing in the input box
3. Pause for 1 second

**Expected Result:**
- ✅ Typing indicator infrastructure is present (visible in code)
- ℹ️ Note: Full real-time typing requires WebSocket (future enhancement)

#### Test 5.3: Empty State
**Steps:**
1. Create a new channel
2. View the channel without sending messages

**Expected Result:**
- ✅ "No messages yet. Say hello!" message appears
- ✅ Message is centered and styled appropriately

#### Test 5.4: Message Scrolling
**Steps:**
1. In a channel, send 20+ messages (can be short messages)
2. Observe the message area

**Expected Result:**
- ✅ Scrollbar appears when content exceeds height
- ✅ Auto-scrolls to bottom on new message
- ✅ Scrollbar is styled (not default browser style)

### 6. Error Handling Tests

#### Test 6.1: Empty Message
**Steps:**
1. Select a channel
2. Try to send an empty message (just press Enter)

**Expected Result:**
- ✅ Message is not sent
- ✅ No error appears
- ✅ Input stays focused

#### Test 6.2: Session Expiry
**Steps:**
1. Open chat
2. Clear cookies or wait for session to expire
3. Try to send a message

**Expected Result:**
- ✅ Error message appears about session expiry
- ✅ User is prompted to reload page

### 7. Cross-Browser Tests

Test in the following browsers:
- [ ] Chrome/Edge (latest)
- [ ] Firefox (latest)
- [ ] Safari (if on Mac)

**For each browser:**
1. Check layout rendering
2. Test message sending
3. Test channel switching
4. Check CSS styling consistency

### 8. Performance Tests

#### Test 8.1: Message Loading Performance
**Steps:**
1. Create a channel with 100+ messages (can use DB insert)
2. Open the channel

**Expected Result:**
- ✅ Page loads within 2 seconds
- ✅ Messages render correctly
- ✅ Scrollbar works smoothly

#### Test 8.2: Polling Performance
**Steps:**
1. Open chat in browser
2. Monitor Network tab in developer tools
3. Observe polling requests

**Expected Result:**
- ✅ Polling request fires every 2.5 seconds
- ✅ Request completes quickly (< 500ms)
- ✅ No memory leaks over 5 minutes

## Bug Report Template

If you find issues, report using this template:

```
**Issue:** [Brief description]

**Steps to Reproduce:**
1. 
2. 
3. 

**Expected Result:**


**Actual Result:**


**Environment:**
- Perfex CRM Version: 
- Browser: 
- PHP Version: 

**Screenshots/Console Errors:**
[Attach if applicable]
```

## Known Limitations

1. **Polling vs WebSocket**: Currently uses polling (2.5s interval) instead of WebSocket for real-time updates
2. **Typing Indicator**: Infrastructure is ready but requires WebSocket for real-time propagation
3. **File Attachments**: Not yet implemented
4. **@Mentions**: Not yet implemented
5. **Message Editing**: Not yet implemented

## Success Criteria

All critical tests (1.x, 2.x, 3.x) should pass for production deployment.
Tests 4.x, 5.x are important for security and UX.
Tests 6.x, 7.x, 8.x ensure robustness.

## Post-Testing

After successful testing:
- [ ] Document any found issues
- [ ] Verify fixes for critical issues
- [ ] Consider performance optimizations
- [ ] Plan for WebSocket migration if needed
- [ ] Gather user feedback
