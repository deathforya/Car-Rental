<?php
require_once __DIR__ . '/../config/db.php';
session_start();

if (empty($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: ../auth/login.html'); exit;
}

$page_css = '../assets/css/admin.css';
$page_class = 'admin-chat';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="admin-container" style="display:flex;height:calc(100vh - 100px);gap:20px;">
    
    <!-- Left: User List -->
    <div class="user-list" style="width:300px;background:#fff;border-right:1px solid #ddd;overflow-y:auto;">
        <h3 style="padding:15px;margin:0;border-bottom:1px solid #eee;">Messages</h3>
        <div id="users-container">
            <div class="loading" style="padding:15px;">Loading users...</div>
        </div>
    </div>

    <!-- Right: Chat Area -->
    <div class="chat-area" style="flex:1;display:flex;flex-direction:column;background:#fff;">
        <div id="chat-header" style="padding:15px;border-bottom:1px solid #eee;font-weight:bold;display:none;">
            Chat with <span id="current-user-name">...</span>
        </div>
        
        <div id="chat-box" style="flex:1;overflow-y:auto;padding:20px;background:#f9f9f9;">
            <div style="text-align:center;color:#888;margin-top:50px;">Select a conversation to start chatting</div>
        </div>

        <form id="admin-chat-form" style="padding:15px;border-top:1px solid #eee;display:flex;background:#fff;display:none;">
            <input type="text" id="admin-msg-input" placeholder="Type reply..." required style="flex:1;padding:12px;border:1px solid #ddd;border-radius:6px;margin-right:10px;">
            <button type="submit" class="btn-primary">Send</button>
        </form>
    </div>

</div>

<style>
.user-item { padding:15px; border-bottom:1px solid #eee; cursor:pointer; transition:background 0.2s; }
.user-item:hover, .user-item.active { background:#f0f4ff; }
.user-item .name { font-weight:bold; display:block; }
.user-item .preview { font-size:12px; color:#666; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; display:block; margin-top:4px; }
.user-item .time { font-size:11px; color:#999; float:right; }
.badge-unread { background:red; color:white; font-size:10px; padding:2px 6px; border-radius:10px; float:right; margin-left:5px; }

/* Reusing chat message styles from user chat */
.message { max-width:75%; padding:10px 14px; border-radius:12px; font-size:14px; line-height:1.4; margin-bottom:10px; position:relative; word-wrap:break-word; clear:both; }
.message.admin { float:right; background:#0d6efd; color:#fff; border-bottom-right-radius:2px; }
.message.user { float:left; background:#e9ecef; color:#333; border-bottom-left-radius:2px; }
.message .time { display:block; font-size:10px; opacity:0.7; margin-top:4px; text-align:right;}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    let currentUserId = 0;
    let lastMsgId = 0;
    const chatBox = document.getElementById('chat-box');
    const usersContainer = document.getElementById('users-container');
    const form = document.getElementById('admin-chat-form');
    const input = document.getElementById('admin-msg-input');

    // Load Users
    function loadUsers() {
        fetch('../api/chat.php?action=get_conversations')
        .then(res => res.json())
        .then(data => {
            if(data.status === 'success') {
                usersContainer.innerHTML = '';
                data.users.forEach(u => {
                    const div = document.createElement('div');
                    div.className = 'user-item' + (u.id == currentUserId ? ' active' : '');
                    div.onclick = () => selectUser(u.id, u.name);
                    
                    const unreadHtml = u.unread > 0 ? `<span class="badge-unread">${u.unread}</span>` : '';
                    
                    div.innerHTML = `
                         <span class="time">${new Date(u.last_time).toLocaleTimeString([], {hour:'2-digit', minute:'2-digit'})}</span>
                         ${unreadHtml}
                         <span class="name">${u.name}</span>
                         <span class="preview">${u.last_msg}</span>
                    `;
                    usersContainer.appendChild(div);
                });
            }
        });
    }

    // Select User
    window.selectUser = function(uid, name) {
        currentUserId = uid;
        lastMsgId = 0;
        document.getElementById('current-user-name').innerText = name;
        document.getElementById('chat-header').style.display = 'block';
        form.style.display = 'flex';
        chatBox.innerHTML = '<div class="loading">Loading conversation...</div>';
        loadMessages();
    };

    // Load Messages
    function loadMessages() {
        if(currentUserId === 0) return;

        fetch(`../api/chat.php?action=get_messages&target_user_id=${currentUserId}&after_id=${lastMsgId}`)
        .then(res => res.json())
        .then(data => {
            if(data.status === 'success') {
                if(data.messages.length > 0) {
                    if(lastMsgId === 0) chatBox.innerHTML = '';
                    
                    data.messages.forEach(msg => {
                        const div = document.createElement('div');
                        div.className = 'message ' + (msg.sender === 'admin' ? 'admin' : 'user');
                        const timeStr = new Date(msg.created_at).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
                        div.innerHTML = `${msg.message.replace(/</g, "&lt;")} <span class="time">${timeStr}</span>`;
                        chatBox.appendChild(div);
                        lastMsgId = msg.id;
                    });
                    
                    if(data.messages.length > 0) chatBox.scrollTop = chatBox.scrollHeight;
                } else if(lastMsgId === 0) {
                     chatBox.innerHTML = '<div style="text-align:center;color:#888;margin-top:20px;">No messages found.</div>';
                }
            }
        });
    }

    // Send Reply
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        const msg = input.value.trim();
        if(!msg || currentUserId === 0) return;

        const formData = new FormData();
        formData.append('action', 'send_message');
        formData.append('target_user_id', currentUserId);
        formData.append('message', msg);
        
        // Optimistic
        const div = document.createElement('div');
        div.className = 'message admin';
        div.innerHTML = `${msg.replace(/</g, "&lt;")} <span class="time">Sending...</span>`;
        chatBox.appendChild(div);
        chatBox.scrollTop = chatBox.scrollHeight;
        input.value = '';

        fetch('../api/chat.php', { method: 'POST', body: formData })
        .then(res => res.json())
        .then(data => {
            if(data.status === 'success') {
                div.remove(); // Fix: Remove optimistic msg, let poll load real one
                loadMessages(); 
            } else {
                alert('Error: ' + data.message);
                div.style.background = 'red';
            }
        });
    });

    setInterval(() => {
        loadUsers();
        if(currentUserId !== 0) loadMessages();
    }, 3000);

    loadUsers();
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
