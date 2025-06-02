<?php
// messages.php

require_once 'includes/init.php';
require_once 'config/database.php';

// Set PDO to throw exceptions for better error handling
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];



// Function to check if any conversation exists between two users
function conversationExists($pdo, $user_id, $other_user_id) {
    $query = "
    SELECT COUNT(*) AS count 
    FROM messages 
    WHERE 
        (sender_id = ? AND receiver_id = ?) 
        OR (sender_id = ? AND receiver_id = ?)
    ";
    $stmt = $pdo->prepare($query);
    $stmt->execute([$user_id, $other_user_id, $other_user_id, $user_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result['count'] > 0;
}

// Function to get all conversations for the current user
function getConversations($pdo, $user_id) {
    $query = "
    SELECT 
        CASE 
            WHEN m.sender_id = ? THEN m.receiver_id 
            ELSE m.sender_id 
        END AS other_user_id,
        u.username AS other_username,
        IFNULL(u.avatar_url, 'uploads/avatars/default-avatar.png') AS avatar_url,
        MAX(m.created_at) AS last_message_time,
        SUM(CASE WHEN m.receiver_id = ? AND m.is_read = FALSE THEN 1 ELSE 0 END) AS unread_count
    FROM messages m
    JOIN users u ON 
        (m.sender_id = ? AND u.id = m.receiver_id) 
        OR 
        (m.receiver_id = ? AND u.id = m.sender_id)
    WHERE m.sender_id = ? OR m.receiver_id = ?
    GROUP BY other_user_id, u.username, u.avatar_url
    ORDER BY last_message_time DESC
    ";

    $stmt = $pdo->prepare($query);
    // Bind parameters in the order they appear
    $stmt->execute([$user_id, $user_id, $user_id, $user_id, $user_id, $user_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Function to get messages for a specific conversation
function getMessages($pdo, $user_id, $other_user_id, $post_id = null) {
    $query = "
    SELECT m.*, 
           CASE WHEN m.sender_id = ? THEN 'sent' ELSE 'received' END AS message_type
    FROM messages m
    WHERE (
            (m.sender_id = ? AND m.receiver_id = ?)
         OR (m.sender_id = ? AND m.receiver_id = ?)
          )
      AND " . ($post_id ? "m.post_messages_id = ?" : "m.post_messages_id IS NULL") . "
    ORDER BY m.created_at ASC
    ";

    $params = [
        $user_id,        // For CASE WHEN m.sender_id = ? THEN 'sent'
        $user_id, $other_user_id, // For (m.sender_id = ? AND m.receiver_id = ?)
        $other_user_id, $user_id // For (m.sender_id = ? AND m.receiver_id = ?)
    ];

    if ($post_id) {
        $params[] = $post_id;
    }

    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Handle new conversation initialization from post
$new_conversation_user = $_GET['author_id'] ?? $_GET['conversation_user'] ?? null;
$post_id = $_GET['post_id'] ?? null;

// If starting a new conversation from a post
if ($new_conversation_user && $post_id) {
    try {
        // Fetch post details
        $stmt = $pdo->prepare("
            SELECT p.title, p.created_at 
            FROM posts p 
            WHERE p.id = ?
        ");
        $stmt->execute([$post_id]);
        $post_details = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($post_details) {
            // Prepare the prefill message with a clickable link
            $post_url = 'post.php?id=' . urlencode($post_id);
            $prefill_message = sprintf(
                '"<a href="%s" target="_blank">%s</a>" from %s',
                htmlspecialchars($post_url, ENT_QUOTES, 'UTF-8'),
                htmlspecialchars($post_details['title'], ENT_QUOTES, 'UTF-8'),
                date('d.m.Y H:i', strtotime($post_details['created_at']))
            );

            // Store the prefill_message in session to set it in the textarea
            $_SESSION['initial_message'] = $prefill_message;

            // Check if any conversation exists between the users
            if (!conversationExists($pdo, $_SESSION['user_id'], $new_conversation_user)) {
                // Initialize conversation with empty message to establish the connection
                $stmt = $pdo->prepare("INSERT INTO messages (sender_id, receiver_id, content, is_read) VALUES (?, ?, '', 1)");
                $stmt->execute([$_SESSION['user_id'], $new_conversation_user]);
            }

            // Redirect to messages.php with the conversation context
            header("Location: messages.php?conversation_user=" . $new_conversation_user);
            exit();
        }
    } catch (PDOException $e) {
        error_log("Error initializing conversation: " . $e->getMessage());
        header('Location: messages.php');
        exit();
    }
}

// Fetch conversations
$conversations = getConversations($pdo, $user_id);

// Handle AJAX requests
if (isset($_GET['action']) || (isset($_POST['action']))) {
    $action = $_GET['action'] ?? $_POST['action'];
    
    // For all AJAX requests that return JSON
    if (in_array($action, ['delete_conversation', 'send_message', 'mark_as_read'])) {
        header('Content-Type: application/json');
    }

    switch ($action) {
        case 'get_messages':
            if (isset($_GET['other_user_id'])) {
                $other_user_id = $_GET['other_user_id'];
                $post_id = isset($_GET['post_id']) ? intval($_GET['post_id']) : null;
                $messages = getMessages($pdo, $user_id, $other_user_id, $post_id);
                echo json_encode($messages);
                exit();
            }
            break;

            case 'send_message':
                if (isset($_POST['receiver_id']) && isset($_POST['content'])) {
                    $receiver_id = $_POST['receiver_id'];
                    $content = $_POST['content'];
            
                    // Check if recipient exists and allows messages
                    $stmt = $pdo->prepare("SELECT messages_active FROM users WHERE id = ?");
                    $stmt->execute([$receiver_id]);
                    $recipient = $stmt->fetch(PDO::FETCH_ASSOC);
            
                    if ($recipient && $recipient['messages_active']) {
                        // Check if recipient has blocked the sender
                        $stmt = $pdo->prepare("SELECT COUNT(*) FROM user_blocks WHERE blocker_id = ? AND blocked_id = ?");
                        $stmt->execute([$receiver_id, $user_id]);
                        $is_blocked = $stmt->fetchColumn() > 0;
            
                        // Check if sender has blocked the recipient
                        $stmt = $pdo->prepare("SELECT COUNT(*) FROM user_blocks WHERE blocker_id = ? AND blocked_id = ?");
                        $stmt->execute([$user_id, $receiver_id]);
                        $sender_blocked_recipient = $stmt->fetchColumn() > 0;
            
                        if (!$is_blocked && !$sender_blocked_recipient) {
                            try {
                                // Start transaction
                                $pdo->beginTransaction();
            
                                // Send message
                                $stmt = $pdo->prepare("INSERT INTO messages (sender_id, receiver_id, content) VALUES (?, ?, ?)");
                                $stmt->execute([$user_id, $receiver_id, $content]);
                                
                                // Get the message ID for the notification reference
                                $message_id = $pdo->lastInsertId();
            
                                // Create notification for receiver
                                require_once 'includes/notification_helpers.php';
                                createNotification($pdo, $receiver_id, 'private_message', $message_id);
            
                                $pdo->commit();
                                
                                header('Content-Type: application/json');
                                echo json_encode(['success' => true]);
                            } catch (Exception $e) {
                                $pdo->rollBack();
                                error_log('Error sending message: ' . $e->getMessage());
                                header('Content-Type: application/json');
                                echo json_encode(['success' => false, 'error' => 'Failed to send message']);
                            }
                        } else {
                            header('Content-Type: application/json');
                            echo json_encode(['success' => false, 'error' => 'You cannot send messages to this user.']);
                        }
                    } else {
                        header('Content-Type: application/json');
                        echo json_encode(['success' => false, 'error' => 'This user is not accepting messages.']);
                    }
                    exit;
                }
                break;

                case 'mark_as_read':
                    if (isset($_POST['other_user_id'])) {
                        $other_user_id = $_POST['other_user_id'];
                        $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : null;
                        
                        try {
                            $pdo->beginTransaction();
                
                            // Mark messages as read
                            $query = "UPDATE messages SET is_read = TRUE WHERE sender_id = ? AND receiver_id = ? ";
                            $params = [$other_user_id, $user_id];
                            if ($post_id) {
                                $query .= "AND post_messages_id = ?";
                                $params[] = $post_id;
                            } else {
                                $query .= "AND post_messages_id IS NULL";
                            }
                            $stmt = $pdo->prepare($query);
                            $stmt->execute($params);
                
                            // Also mark notifications as read
                            $notificationQuery = "UPDATE notifications 
                                                SET is_read = 1 
                                                WHERE user_id = ? 
                                                AND type = 'private_message' 
                                                AND reference_id IN (
                                                    SELECT id FROM messages 
                                                    WHERE sender_id = ? AND receiver_id = ?
                                                )";
                            $stmt = $pdo->prepare($notificationQuery);
                            $stmt->execute([$user_id, $other_user_id, $user_id]);
                
                            $pdo->commit();
                            echo json_encode(['success' => true]);
                        } catch (PDOException $e) {
                            $pdo->rollBack();
                            error_log('Error marking messages/notifications as read: ' . $e->getMessage());
                            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
                        }
                        exit;
                    }
                    break;

        case 'get_conversations':
            $conversations = getConversations($pdo, $user_id);
            echo json_encode($conversations);
            exit;
            break;

        case 'search_users':
            if (isset($_GET['query'])) {
                $query_search = $_GET['query'];
                // Prepare a statement to prevent SQL injection
                $stmt = $pdo->prepare("SELECT id, username, IFNULL(avatar_url, 'uploads/avatars/default-avatar.png') AS avatar_url FROM users WHERE username LIKE ? AND id != ? LIMIT 5");
                $stmt->execute(['%' . $query_search . '%', $user_id]);
                $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
                echo json_encode($users);
                exit;
            }
            break;

        case 'check_conversation':
            // Implemented to handle existing conversation checks
            if (isset($_GET['other_user_id'])) {
                $other_user_id = $_GET['other_user_id'];
                // Check if any conversation exists between the users
                $exists = conversationExists($pdo, $user_id, $other_user_id);
                echo json_encode(['exists' => $exists]);
                exit;
            }
            break;

        case 'delete_conversation':
            if (isset($_POST['other_user_id'])) {
                $other_user_id = $_POST['other_user_id'];
                try {
                    // Start transaction
                    $pdo->beginTransaction();
                    
                    // Delete messages for this conversation
                    $stmt = $pdo->prepare("
                        DELETE FROM messages 
                        WHERE (sender_id = ? AND receiver_id = ?)
                            OR (sender_id = ? AND receiver_id = ?)
                    "); // Changed to positional parameters (?) instead of named parameters (:user_id)
                    
                    $result = $stmt->execute([
                        $user_id, $other_user_id,    // First pair (sender = user, receiver = other)
                        $other_user_id, $user_id     // Second pair (sender = other, receiver = user)
                    ]);
                    
                    if ($result) {
                        $pdo->commit();
                        echo json_encode(['success' => true]);
                    } else {
                        throw new Exception('Failed to delete messages');
                    }
                    
                } catch (Exception $e) {
                    $pdo->rollBack();
                    error_log("Error deleting conversation: " . $e->getMessage());
                    echo json_encode([
                        'success' => false, 
                        'error' => 'Failed to delete conversation: ' . $e->getMessage()
                    ]);
                }
            } else {
                echo json_encode([
                    'success' => false, 
                    'error' => 'No user ID provided'
                ]);
            }
            exit;
            break;

        default:
            // Handle unknown actions
            echo json_encode(['success' => false, 'error' => 'Unknown action.']);
            exit;
    }
}

// Include header and navbar
require_once 'includes/header.php';
require_once 'navbar.php';

// Retrieve the prefill message from session if it exists
$prefill_message = $_SESSION['initial_message'] ?? null;
if ($prefill_message) {
    unset($_SESSION['initial_message']);
}
?>
<!-- Include Bootstrap Icons -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">

<!-- HTML Structure -->
<div class="container messages-container col-lg-10 col-md-12 mt-5">
    <div class="row">
        <!-- Conversations List -->
        <div class="messages-conversation-list col-md-3">
            <h2>Conversations</h2>

            <div class="mb-3 position-relative">
                <input type="text" id="user-search-input" class="form-control message-user-search" placeholder="Search for users...">
                <ul class="list-group" id="user-search-results" style="position: absolute; z-index: 1000; width: 100%; display: none;"></ul>
            </div>
   
            <ul class="list-group list-group-flush" id="conversation-list" style="max-height: 500px; overflow-y: auto;">
                <?php foreach ($conversations as $conv): ?>
                    <li class="list-group-item d-flex align-items-center conversation-item" 
                        data-user-id="<?= htmlspecialchars($conv['other_user_id']) ?>">
                        <img src="<?= htmlspecialchars($conv['avatar_url']) ?>" alt="Avatar" class="avatar-img me-2" style="width:40px; height:40px; border-radius:50%;">
                        <div>
                            <span class="flex-grow-1"><?= htmlspecialchars($conv['other_username']) ?></span>
                        </div>
                        <?php if ($conv['unread_count'] > 0): ?>
                            <span class="badge bg-primary rounded-pill ms-auto"><?= $conv['unread_count'] ?></span>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>

        <!-- Messages Container -->
        <div class="messages-conversation col-md-8">
            <div id="message-container">
                <h3 id="conversation-header">Select a conversation</h3>
                    <!-- Delete button, initially hidden -->
                    <button id="delete-conversation" class="btn btn-outline-danger btn-sm" style="display: none !important;">
                        <i class="bi bi-trash"></i>
                    </button>
                <div class="messages-wrapper">    
                    <div id="messages" style="height: 400px; overflow-y: auto;">

                    </div>
                    <form id="message-form" style="display: none;">
                        <div class="input-group mb-3 position-relative">
                            <textarea id="message-input" name="message_content" class="form-control message-sent-form" placeholder="Type your message..." required><?php 
                                if ($prefill_message) {
                                    echo htmlspecialchars($prefill_message);
                                }
                            ?></textarea>
                            <i class="bi bi-send send-icon-inside" aria-label="Send Message" style="cursor:pointer;"></i>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Include jQuery and Bootstrap JS -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>

<!-- Messages JavaScript -->
<script>
$(document).ready(function() {
    // Initialize currentReceiverId from URL parameter if present
    let currentReceiverId = <?php 
        echo isset($_GET['author_id']) ? $_GET['author_id'] : 
            (isset($_GET['conversation_user']) ? $_GET['conversation_user'] : 'null'); 
    ?>;

    // Make loadConversations return a proper Promise
    function loadConversations() {
        return new Promise((resolve, reject) => {
            $.get('messages.php?action=get_conversations')
                .done(function(data) {
                    const conversations = JSON.parse(data);
                    let convHtml = '';
                    conversations.forEach(function(conv) {
                        const avatarUrl = conv.avatar_url ? conv.avatar_url : 'uploads/avatars/default-avatar.png';
                        convHtml += `
                            <li class="list-group-item d-flex align-items-center conversation-item" 
                                data-user-id="${conv.other_user_id}">
                                <img src="${avatarUrl}" alt="Avatar" class="avatar-img me-2">
                                <span class="flex-grow-1">${conv.other_username}</span>
                                ${conv.unread_count > 0 ? 
                                    `<span class="badge bg-primary rounded-pill">${conv.unread_count}</span>` : 
                                    ''}
                            </li>
                        `;
                    });
                    $('#conversation-list').html(convHtml);

                    // Re-bind click event to the new conversation items
                    $('.conversation-item').off('click').on('click', function() {
                        const userId = $(this).data('user-id');
                        const username = $(this).find('span.flex-grow-1').text().trim();
                        openConversation(userId, username);
                    });

                    resolve(conversations);
                })
                .fail(reject);
        });
    }

    // Initialize function to handle conversation loading
    function initializeConversation() {
        if (currentReceiverId) {
            loadConversations()
                .then(() => {
                    const conversationItem = $(`.conversation-item[data-user-id="${currentReceiverId}"]`);
                    if (conversationItem.length) {
                        conversationItem.trigger('click');
                    } else {
                        // Handle case where conversation doesn't exist yet
                        return new Promise((resolve) => {
                            setTimeout(() => {
                                loadConversations().then(() => {
                                    const newConversationItem = $(`.conversation-item[data-user-id="${currentReceiverId}"]`);
                                    if (newConversationItem.length) {
                                        newConversationItem.trigger('click');
                                    }
                                    resolve();
                                });
                            }, 1000); // Give more time for the conversation to be created
                        });
                    }
                })
                .catch(error => {
                    console.error('Error loading conversation:', error);
                });
        }
    }

    <?php if (isset($initialize_message)): ?>
    // Initialize new conversation with post author
    const authorData = <?php echo json_encode($initialize_message); ?>;
    
    // Always set currentReceiverId to the author_id
    currentReceiverId = authorData.recipient_id;
    
    // Add to conversation list if not exists
    addConversationToList(
        authorData.recipient_id, 
        authorData.recipient_name, 
        authorData.recipient_avatar
    );
    
    // Open conversation and set message
    openConversation(authorData.recipient_id, authorData.recipient_name);
    $('#message-input').val(authorData.message_text);
    <?php endif; ?>

    // Initial conversation item click binding
    $('.conversation-item').on('click', function() {
    const userId = $(this).data('user-id');
    const username = $(this).find('span.flex-grow-1').text().trim();
    openConversation(userId, username); // Use the openConversation function
});

    // Handle click on the send icon inside the input field
    $(document).on('click', '.send-icon-inside', function() {
        $('#message-form').submit();
    });

    // Ensure the 'Enter' key submits the form
    $('#message-input').on('keypress', function(e) {
        if (e.which == 13 && !e.shiftKey) { // 'Enter' key without 'Shift'
            e.preventDefault();
            $('#message-form').submit();
        }
    });

     // User search functionality
     $('#user-search-input').on('input', function() {
        const query = $(this).val().trim();
        if (query.length > 0) {
            $.get('messages.php?action=search_users&query=' + encodeURIComponent(query), function(data) {
                const users = JSON.parse(data);
                let resultsHtml = '';
                users.forEach(function(user) {
                    resultsHtml += `
                        <li class="list-group-item user-search-item d-flex align-items-center" data-user-id="${user.id}">
                            <img src="${user.avatar_url}" alt="Avatar" class="avatar-img me-2">
                            <span>${user.username}</span>
                        </li>
                    `;
                });
                $('#user-search-results').html(resultsHtml).show();
            });
        } else {
            $('#user-search-results').hide();
        }
    });

    // Hide search results when clicking outside
    $(document).on('click', function(event) {
        if (!$(event.target).closest('#user-search-input, #user-search-results').length) {
            $('#user-search-results').hide();
        }
    });

    // Handle click on a search result
    $(document).on('click', '.user-search-item', function() {
        const userId = $(this).data('user-id');
        const username = $(this).find('span').text().trim();
        const avatarUrl = $(this).find('img').attr('src');
        currentReceiverId = userId;
        $('#user-search-results').hide();
        $('#user-search-input').val('');

        // Check if conversation already exists
        $.get('messages.php?action=check_conversation&other_user_id=' + userId, function(data) {
            const exists = JSON.parse(data).exists;
            if (!exists) {
                // Add new conversation to the list
                addConversationToList(userId, username, avatarUrl);
            }
            // Open the conversation
            openConversation(userId, username);
        });
    });

    function addConversationToList(userId, username, avatarUrl) {
        const convHtml = `
            <li class="list-group-item d-flex align-items-center conversation-item" data-user-id="${userId}">
                <img src="${avatarUrl}" alt="Avatar" class="avatar-img me-2">
                <span class="flex-grow-1">${username}</span>
            </li>
        `;
        // Prepend to the conversation list
        $('#conversation-list').prepend(convHtml);

        // Re-bind click event to the new conversation item
        $('.conversation-item').off('click').on('click', function() {
            const userId = $(this).data('user-id');
            currentReceiverId = userId;
            loadMessages(userId);
            const username = $(this).find('span.flex-grow-1').text().trim();
            $('#conversation-header').text('Chat with ' + username);
            $('#message-form').show();
        });
    }

    function openConversation(userId, username) {
    currentReceiverId = userId;
    loadMessages(userId);
    $('#conversation-header').text('Chat with ' + username);
    $('#message-form').show();
    $('#delete-conversation').show();
    
    // Highlight the selected conversation
    $('.conversation-item').removeClass('active');
    $(`.conversation-item[data-user-id="${userId}"]`).addClass('active');

    // Scroll message input into view
    $('#message-input').focus();
}

    // Load messages for a conversation
    function loadMessages(userId) {
        $.get('messages.php?action=get_messages&other_user_id=' + userId, function(data) {
            const messages = JSON.parse(data);
            let messageHtml = '';
            messages.forEach(function(msg) {
                const alignClass = msg.message_type === 'sent' ? 'text-end' : 'text-start';
                const messageClass = msg.message_type === 'sent' ? 'sent-message' : 'received-message';

                // Parse the created_at timestamp
                const messageTime = new Date(msg.created_at);
                const now = new Date();
                const timeDiff = now - messageTime;

                let formattedTime = '';
                if (timeDiff < 24 * 60 * 60 * 1000) {
                    // Less than 24 hours old, display time in 24h format
                    formattedTime = messageTime.getHours().toString().padStart(2, '0') + ':' + messageTime.getMinutes().toString().padStart(2, '0') + 'h';
                } else {
                    // More than 24 hours old, display weekday
                    const weekdays = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
                    formattedTime = weekdays[messageTime.getDay()];
                }

                messageHtml += `
                    <div class="${alignClass}">
                        <div class="${messageClass} mb-1">
                            ${msg.content}
                        </div>
                        <div class="message-timestamp">
                            ${formattedTime}
                        </div>
                    </div>`;
            });

            $('#messages').html(messageHtml);

            // Delay scrolling to ensure content is rendered
            setTimeout(function() {
                $('#messages').scrollTop($('#messages')[0].scrollHeight);
            }, 100); // Adjust delay as needed

            // Mark messages as read
            $.post('messages.php?action=mark_as_read', {
                other_user_id: userId
            });
        });
    }

    // Handle form submission
    $('#message-form').submit(function(e) {
    e.preventDefault();
    const content = $('#message-input').val().trim(); // Trim whitespace
    if (content && currentReceiverId) {
        $.ajax({
            url: 'messages.php?action=send_message',
            type: 'POST',
            data: {
                receiver_id: currentReceiverId,
                content: content
            },
            dataType: 'json', // Specify that we expect JSON response
            success: function(response) {
                if (response.success) {
                    // Clear input immediately
                    $('#message-input').val('');
                    
                    // Add the new message to the messages container immediately
                    const now = new Date();
                    const hours = now.getHours().toString().padStart(2, '0');
                    const minutes = now.getMinutes().toString().padStart(2, '0');
                    const timeString = `${hours}:${minutes}h`;
                    
                    const newMessageHtml = `
                        <div class="text-end">
                            <div class="sent-message mb-1">
                                ${content}
                            </div>
                            <div class="message-timestamp">
                                ${timeString}
                            </div>
                        </div>
                    `;
                    
                    $('#messages').append(newMessageHtml);
                    
                    // Scroll to bottom
                    $('#messages').scrollTop($('#messages')[0].scrollHeight);
                    
                    // Refresh conversation list
                    loadConversations();
                } else {
                    console.error('Failed to send message:', response.error);
                    alert('Failed to send message. Please try again.');
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                console.error('AJAX error:', textStatus, errorThrown);
                console.error('Response:', jqXHR.responseText);
                alert('Failed to send message. Please try again.');
            }
        });
    }
});

    // Delete conversation functionality
    $('#delete-conversation').on('click', function() {
    if (!currentReceiverId) {
        return;
    }

    if (confirm('Are you sure you want to delete this conversation? This action cannot be undone.')) {
        $.ajax({
            url: 'messages.php',
            type: 'POST',
            data: {
                action: 'delete_conversation',
                other_user_id: currentReceiverId
            },
            dataType: 'json',
            success: function(response) {
                console.log('Delete response:', response); // Debug log
                
                if (response.success) {
                    // Remove conversation from list
                    $(`.conversation-item[data-user-id="${currentReceiverId}"]`).remove();
                    
                    // Clear messages area
                    $('#messages').empty();
                    
                    // Reset header and hide delete button
                    $('#conversation-header').text('Select a conversation');
                    $('#delete-conversation').hide();
                    
                    // Hide message form
                    $('#message-form').hide();
                    
                    // Reset currentReceiverId
                    currentReceiverId = null;
                    
                    // Load conversations to refresh the list
                    loadConversations();
                    
                    alert('Conversation deleted successfully');
                } else {
                    alert('Failed to delete conversation: ' + (response.error || 'Unknown error'));
                }
            },
            error: function(xhr, status, error) {
                console.error('Delete error:', error);
                console.error('Response text:', xhr.responseText);
                alert('Failed to delete conversation. Please try again.');
            }
        });
    }
});

// Show/hide delete button when conversation is selected
$('.conversation-item').on('click', function() {
    $('#delete-conversation').show();
});

    // Load conversations
    function loadConversations() {
        $.get('messages.php?action=get_conversations', function(data) {
            const conversations = JSON.parse(data);
            let convHtml = '';
            conversations.forEach(function(conv) {
                const avatarUrl = conv.avatar_url ? conv.avatar_url : 'uploads/avatars/default-avatar.png';
                convHtml += `
                    <li class="list-group-item d-flex align-items-center conversation-item" data-user-id="${conv.other_user_id}">
                        <img src="${avatarUrl}" alt="Avatar" class="avatar-img me-2">
                        <span class="flex-grow-1">${conv.other_username}</span>
                        ${conv.unread_count > 0 ? `<span class="badge bg-primary rounded-pill">${conv.unread_count}</span>` : ''}
                    </li>
                `;
            });
            $('#conversation-list').html(convHtml);

            // Re-bind click event to the new conversation items with updated behavior
            $('.conversation-item').off('click').on('click', function() {
                const userId = $(this).data('user-id');
                const username = $(this).find('span.flex-grow-1').text().trim();
                openConversation(userId, username); // Use the openConversation function
            });

            // Automatically open the most recent conversation
            openMostRecentConversation();
        });
    }

    // Retrieve the prefill message from PHP session
    <?php if (isset($_SESSION['initial_message'])): ?>
    const prefill_message = <?php echo json_encode($_SESSION['initial_message']); ?>;
    if (prefill_message) {
        $('#message-input').val(prefill_message);
    }
    <?php 
        // Clear the session variable after use
        unset($_SESSION['initial_message']); 
    endif; 
    ?>

    // If we have a conversation_user parameter, open that conversation
    const urlParams = new URLSearchParams(window.location.search);
    const conversationUser = urlParams.get('conversation_user');
    if (conversationUser) {
        // Try to find and click the conversation item
        setTimeout(() => {
            const conversationItem = $(`.conversation-item[data-user-id="${conversationUser}"]`);
            if (conversationItem.length) {
                conversationItem.trigger('click');
            }
        }, 500); // Small delay to ensure conversations are loaded
    }

    // Open the most recent conversation on page load
    function openMostRecentConversation() {
        const firstConversation = $('.conversation-item').first();
        if (firstConversation.length > 0) {
            firstConversation.trigger('click');
        } else {
            // No conversations exist
            $('#conversation-header').text('No conversations yet');
        }
    }

    // Initialize by loading conversations
    loadConversations();
});
</script>

<!-- Optional: Custom Styles for Messages -->
<style>
.sent-message {
    background-color: #0d6efd;
    color: white;
    border-radius: 15px;
    padding: 10px;
    display: inline-block;
    max-width: 80%;
}

.received-message {
    background-color: #f1f0f0;
    color: black;
    border-radius: 15px;
    padding: 10px;
    display: inline-block;
    max-width: 80%;
}

.message-timestamp {
    font-size: 0.8em;
    color: gray;
}

.avatar-img {
    width: 40px;
    height: 40px;
    border-radius: 50%;
}

/* Responsive styles for the delete button */
@media (max-width: 768px) {
    #delete-conversation {
        padding: 0.25rem 0.5rem;
    }
    
    /* Make the trash icon bigger on mobile for better touch target */
    #delete-conversation .bi-trash {
        font-size: 1.2em;
    }
}

/* Optional: Add a hover effect for desktop */
@media (min-width: 769px) {
    #delete-conversation:hover {
        background-color: #dc3545;
        color: white;
    }
}

/* Make sure the header area doesn't break on small screens */
.messages-conversation .d-flex {
    flex-wrap: nowrap;
    gap: 1rem;
}

#delete-conversation {
    position: absolute;
    right: 15px;
    top: 15px;
    z-index: 1000;
}

.messages-conversation {
    position: relative;
}
</style>

</body>
</html>
