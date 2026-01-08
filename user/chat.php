<?php
require_once __DIR__ . '/../config/db.php';
session_start();

if (empty($_SESSION['user_id']) || $_SESSION['user_type'] !== 'customer') {
    header('Location: ../auth/login.html'); exit;
}

$page_css = '../assets/css/chat.css'; 
$page_class = 'chat-page';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container" style="max-width:800px;margin-top:20px;">
    <h2>Support Chat</h2>
    <p>Talk directly with our admin team. Messages are encrypted and deleted after 48 hours.</p>

    <div class="chat-container">
        <div id="chat-box" class="chat-box">
            <div class="loading">Loading messages...</div>
        </div>
        
        <form id="chat-form" style="display:flex;gap:10px;margin-top:15px;">
            <input type="text" id="msg-input" placeholder="Type your message..." required style="flex:1;padding:12px;border:1px solid #ddd;border-radius:6px;">
            <button type="submit" class="btn-primary" style="padding:12px 20px;">Send</button>
        </form>
    </div>
</div>

<style>
.chat-container { background:#fff; border:1px solid #ddd; border-radius:8px; padding:20px; box-shadow:0 2px 10px rgba(0,0,0,0.05); }
.chat-box { height:400px; overflow-y:auto; border:1px solid #eee; padding:15px; background:#f9f9f9; border-radius:6px; margin-bottom:10px; display:flex; flex-direction:column; gap:10px; }
.message { max-width:75%; padding:10px 14px; border-radius:12px; font-size:14px; line-height:1.4; position:relative; word-wrap:break-word; }
.message.user { align-self:flex-end; background:#0d6efd; color:#fff; border-bottom-right-radius:2px; }
.message.admin { align-self:flex-start; background:#e9ecef; color:#333; border-bottom-left-radius:2px; }
.message .time { display:block; font-size:10px; opacity:0.7; margin-top:4px; text-align:right; }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const chatBox = document.getElementById('chat-box');
    const form = document.getElementById('chat-form');
    const input = document.getElementById('msg-input');
    let lastId = 0;

    // Scroll to bottom
    function scrollToBottom() {
        chatBox.scrollTop = chatBox.scrollHeight;
    }

    // Load Messages
    function loadMessages() {
        fetch('../api/chat.php?action=get_messages&after_id=' + lastId)
        .then(res => res.json())
        .then(data => {
            if(data.status === 'success') {
                if(data.messages.length > 0) {
                    if(lastId === 0) chatBox.innerHTML = ''; // Clear loading
                    
                    data.messages.forEach(msg => {
                        const div = document.createElement('div');
                        div.className = 'message ' + (msg.sender === 'user' ? 'user' : 'admin');
                        
                        // Format time
                        const date = new Date(msg.created_at);
                        const timeStr = date.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
                        
                        div.innerHTML = `
                            ${msg.message.replace(/</g, "&lt;")}
                            <span class="time">${timeStr}</span>
                        `;
                        chatBox.appendChild(div);
                        lastId = msg.id;
                    });
                    scrollToBottom();
                } else if (lastId === 0) {
                     chatBox.innerHTML = '<div style="text-align:center;color:#888;margin-top:20px;">No messages yet. Start the conversation!</div>';
                }
            }
        });
    }

    // Send Message
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        const msg = input.value.trim();
        if(!msg) return;

        const formData = new FormData();
        formData.append('action', 'send_message');
        formData.append('message', msg);

        // Optimistic UI update
        const tempDiv = document.createElement('div');
        tempDiv.className = 'message user';
        tempDiv.innerHTML = `${msg.replace(/</g, "&lt;")} <span class="time">Sending...</span>`;
        chatBox.appendChild(tempDiv);
        scrollToBottom();
        input.value = '';

        fetch('../api/chat.php', { method: 'POST', body: formData })
        .then(res => res.json())
        .then(data => {
            if(data.status === 'success') {
                tempDiv.remove(); // Remove optimistic msg, real one will load on next poll
                loadMessages();
            } else {
                alert('Error: ' + data.message);
                tempDiv.style.background = 'red';
            }
        });
    });

    // Poll every 3 seconds
    loadMessages();
    setInterval(loadMessages, 3000);
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
