<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/functions.php';

// DDoS protection removed for simplicity

// Check authentication
if (!isLoggedIn()) {
    $_SESSION['redirect_after_login'] = 'messages.php';
    redirect('login.php');
}

$current_user_id = $_SESSION['user_id'];
$current_username = $_SESSION['username'];

// Handle message sending
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_message'])) {
    $csrf_token = $_POST['csrf_token'] ?? '';
    
    if (!verifyCSRFToken($csrf_token)) {
        $_SESSION['error'] = "Token de seguridad inválido.";
        redirect('messages.php');
    }
    
    $recipient_username = sanitizeInput($_POST['recipient_username']);
    $subject = sanitizeInput($_POST['subject']);
    $message_content = sanitizeInput($_POST['message_content']);
    
    // Rate limiting for message sending
    if (!checkRateLimit('send_message', $current_user_id, 10, 300)) {
        $_SESSION['error'] = "Demasiados mensajes enviados. Espera unos minutos.";
        redirect('messages.php');
    }
    
    if (empty($recipient_username) || empty($subject) || empty($message_content)) {
        $_SESSION['error'] = "Todos los campos son requeridos.";
    } else {
        try {
            // Find recipient user
            $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
            $stmt->execute([$recipient_username]);
            $recipient = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$recipient) {
                $_SESSION['error'] = "Usuario no encontrado.";
            } else if ($recipient['id'] == $current_user_id) {
                $_SESSION['error'] = "No puedes enviarte mensajes a ti mismo.";
            } else {
                // Insert encrypted message
                $encrypted_subject = encryptData($subject);
                $encrypted_content = encryptData($message_content);
                
                $stmt = $conn->prepare("
                    INSERT INTO messages (sender_id, recipient_id, subject, message_content, is_encrypted, created_at) 
                    VALUES (?, ?, ?, ?, 1, NOW())
                ");
                
                if ($stmt->execute([$current_user_id, $recipient['id'], $encrypted_subject, $encrypted_content])) {
                    $_SESSION['success'] = "Mensaje enviado con éxito.";
                    
                    // Log the message
                    logSecurityEvent('message_sent', $current_user_id, true, [
                        'recipient_id' => $recipient['id'],
                        'recipient_username' => $recipient_username
                    ]);
                } else {
                    $_SESSION['error'] = "Error al enviar el mensaje.";
                }
            }
        } catch (Exception $e) {
            $_SESSION['error'] = "Error del sistema: " . $e->getMessage();
        }
    }
}

// Handle message actions
if (isset($_GET['action']) && isset($_GET['id'])) {
    $action = $_GET['action'];
    $message_id = intval($_GET['id']);
    
    if ($action === 'mark_read') {
        try {
            $stmt = $conn->prepare("
                UPDATE messages 
                SET is_read = 1 
                WHERE id = ? AND recipient_id = ?
            ");
            $stmt->execute([$message_id, $current_user_id]);
            
            // Redirect back to avoid refresh issues
            redirect('messages.php');
        } catch (Exception $e) {
            $_SESSION['error'] = "Error al marcar el mensaje.";
        }
    } elseif ($action === 'delete') {
        try {
            $stmt = $conn->prepare("
                DELETE FROM messages 
                WHERE id = ? AND (sender_id = ? OR recipient_id = ?)
            ");
            $stmt->execute([$message_id, $current_user_id, $current_user_id]);
            
            $_SESSION['success'] = "Mensaje eliminado.";
            redirect('messages.php');
        } catch (Exception $e) {
            $_SESSION['error'] = "Error al eliminar el mensaje.";
        }
    }
}

// Get messages
$filter = $_GET['filter'] ?? 'inbox';
$page = intval($_GET['page'] ?? 1);
$per_page = 15;
$offset = ($page - 1) * $per_page;

try {
    if ($filter === 'sent') {
        // Sent messages
        $stmt = $conn->prepare("
            SELECT m.id, m.recipient_id, m.subject, m.message_content, m.is_encrypted, 
                   m.is_read, m.created_at, u.username as recipient_username
            FROM messages m
            JOIN users u ON m.recipient_id = u.id
            WHERE m.sender_id = ?
            ORDER BY m.created_at DESC
            LIMIT ? OFFSET ?
        ");
        $stmt->execute([$current_user_id, $per_page, $offset]);
    } else {
        // Inbox (received messages)
        $stmt = $conn->prepare("
            SELECT m.id, m.sender_id, m.subject, m.message_content, m.is_encrypted, 
                   m.is_read, m.created_at, u.username as sender_username
            FROM messages m
            JOIN users u ON m.sender_id = u.id
            WHERE m.recipient_id = ?
            ORDER BY m.created_at DESC
            LIMIT ? OFFSET ?
        ");
        $stmt->execute([$current_user_id, $per_page, $offset]);
    }
    
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Decrypt messages
    foreach ($messages as &$message) {
        if ($message['is_encrypted']) {
            $message['subject'] = decryptData($message['subject']);
            $message['message_content'] = decryptData($message['message_content']);
        }
    }
    
    // Get unread count
    $stmt = $conn->prepare("
        SELECT COUNT(*) as count 
        FROM messages 
        WHERE recipient_id = ? AND is_read = 0
    ");
    $stmt->execute([$current_user_id]);
    $unread_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
} catch (Exception $e) {
    $messages = [];
    $unread_count = 0;
    $_SESSION['error'] = "Error al cargar mensajes.";
}

$csrf_token = generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mensajes - Market-X</title>
    <link rel="stylesheet" href="assets/styles.css">
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background: linear-gradient(135deg, #0a0a0a 0%, #1a1a1a 25%, #2d1810 50%, #1a1a1a 75%, #0a0a0a 100%);
            margin: 0;
            padding: 0;
            min-height: 100vh;
            color: #e5e5e5;
        }
        
        /* Booting Screen */
        .booting-screen {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, #0a0a0a, #1a1a1a);
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            z-index: 9999;
            color: #e5e5e5;
        }
        
        .booting-screen h1 {
            color: #ff6b35;
            font-size: 2.5em;
            margin-bottom: 30px;
            text-shadow: 0 0 20px rgba(255, 107, 53, 0.5);
        }
        
        .loading-bar {
            width: 300px;
            height: 4px;
            background: #333;
            border-radius: 2px;
            overflow: hidden;
            margin-bottom: 20px;
        }
        
        .loading-bar span {
            display: block;
            height: 100%;
            background: linear-gradient(90deg, #ff6b35, #ff8c42);
            border-radius: 2px;
            animation: loading 2.5s ease-in-out;
        }
        
        @keyframes loading {
            0% { width: 0%; }
            50% { width: 70%; }
            100% { width: 100%; }
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            opacity: 0;
            transition: opacity 0.5s ease;
        }
        
        .container.show {
            opacity: 1;
        }
        
        /* XP-style window */
        .xp-window {
            background: linear-gradient(145deg, #2b2b2b, #3a3a3a);
            border-radius: 15px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
            overflow: hidden;
            border: 1px solid #ff6b35;
        }
        
        .xp-titlebar {
            background: linear-gradient(135deg, #ff6b35, #ff8c42);
            padding: 15px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            color: white;
        }
        
        .title-content {
            display: flex;
            align-items: center;
            gap: 12px;
            font-weight: 700;
            font-size: 18px;
        }
        
        .close-button {
            background: rgba(255, 255, 255, 0.2);
            border: none;
            border-radius: 50%;
            width: 32px;
            height: 32px;
            cursor: pointer;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .close-button:hover {
            background: rgba(255, 255, 255, 0.3);
        }
        
        .navigation {
            background: linear-gradient(145deg, #1f1f1f, #2a2a2a);
            padding: 15px 20px;
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            border-bottom: 1px solid #3a3a3a;
        }
        
        .xp-button {
            background: linear-gradient(145deg, #3a3a3a, #2a2a2a);
            color: #e5e5e5;
            padding: 8px 16px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            text-decoration: none;
            font-size: 13px;
            font-weight: 500;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        
        .xp-button:hover {
            background: linear-gradient(145deg, #ff6b35, #ff8c42);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(255, 107, 53, 0.3);
        }
        
        .xp-button.active {
            background: linear-gradient(145deg, #ff6b35, #ff8c42);
            box-shadow: inset 0 2px 5px rgba(0, 0, 0, 0.3);
        }
        
        .content {
            padding: 25px;
        }
        
        .messages-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            flex-wrap: wrap;
            gap: 15px;
        }
        
        .messages-tabs {
            display: flex;
            gap: 10px;
        }
        
        .tab-button {
            background: linear-gradient(145deg, #3a3a3a, #2a2a2a);
            color: #e5e5e5;
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .tab-button.active {
            background: linear-gradient(135deg, #ff6b35, #ff8c42);
        }
        
        .tab-button:hover {
            transform: translateY(-1px);
        }
        
        .compose-button {
            background: linear-gradient(135deg, #ff6b35, #ff8c42);
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: bold;
            transition: all 0.3s ease;
        }
        
        .compose-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(255, 107, 53, 0.4);
        }
        
        .message-list {
            background: linear-gradient(145deg, #1f1f1f, #2a2a2a);
            border-radius: 10px;
            overflow: hidden;
        }
        
        .message-item {
            padding: 18px 20px;
            border-bottom: 1px solid #3a3a3a;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
        }
        
        .message-item:last-child {
            border-bottom: none;
        }
        
        .message-item:hover {
            background: rgba(255, 107, 53, 0.1);
        }
        
        .message-item.unread {
            background: linear-gradient(90deg, rgba(255, 107, 53, 0.1), transparent);
            border-left: 4px solid #ff6b35;
        }
        
        .message-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 8px;
        }
        
        .message-from {
            font-weight: bold;
            color: #ff8c42;
        }
        
        .message-date {
            font-size: 12px;
            color: #b8b8b8;
        }
        
        .message-subject {
            font-weight: 600;
            color: #e5e5e5;
            margin-bottom: 6px;
        }
        
        .message-preview {
            color: #b8b8b8;
            font-size: 14px;
            line-height: 1.4;
        }
        
        .message-actions {
            display: flex;
            gap: 8px;
            margin-top: 10px;
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        
        .message-item:hover .message-actions {
            opacity: 1;
        }
        
        .action-btn {
            padding: 4px 8px;
            background: linear-gradient(135deg, #ff6b35, #ff8c42);
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 11px;
            text-decoration: none;
        }
        
        .action-btn:hover {
            transform: translateY(-1px);
        }
        
        .action-btn.delete {
            background: linear-gradient(135deg, #f44336, #d32f2f);
        }
        
        /* Compose Message Modal */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.8);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }
        
        .modal-content {
            background: linear-gradient(145deg, #2a2a2a, #1f1f1f);
            border-radius: 15px;
            width: 90%;
            max-width: 600px;
            max-height: 80vh;
            overflow: hidden;
            border: 1px solid #ff6b35;
        }
        
        .modal-header {
            background: linear-gradient(135deg, #ff6b35, #ff8c42);
            padding: 15px 20px;
            color: white;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .modal-body {
            padding: 20px;
            max-height: 60vh;
            overflow-y: auto;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #e5e5e5;
            font-weight: 600;
        }
        
        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 2px solid #3a3a3a;
            border-radius: 8px;
            background: linear-gradient(145deg, #1a1a1a, #0f0f0f);
            color: #e5e5e5;
            font-size: 14px;
            resize: vertical;
        }
        
        .form-group input:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #ff6b35;
            box-shadow: 0 0 10px rgba(255, 107, 53, 0.2);
        }
        
        .form-group textarea {
            min-height: 120px;
        }
        
        .modal-buttons {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
            margin-top: 20px;
        }
        
        .modal-btn {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
        }
        
        .modal-btn.primary {
            background: linear-gradient(135deg, #ff6b35, #ff8c42);
            color: white;
        }
        
        .modal-btn.secondary {
            background: linear-gradient(145deg, #3a3a3a, #2a2a2a);
            color: #e5e5e5;
        }
        
        .notification {
            padding: 15px 20px;
            margin-bottom: 20px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 600;
        }
        
        .notification.success {
            background: linear-gradient(135deg, rgba(76, 175, 80, 0.15), rgba(76, 175, 80, 0.05));
            color: #4caf50;
            border: 1px solid rgba(76, 175, 80, 0.3);
        }
        
        .notification.error {
            background: linear-gradient(135deg, rgba(244, 67, 54, 0.15), rgba(244, 67, 54, 0.05));
            color: #f44336;
            border: 1px solid rgba(244, 67, 54, 0.3);
        }
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #b8b8b8;
        }
        
        .empty-state-icon {
            font-size: 4em;
            margin-bottom: 20px;
            opacity: 0.5;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .container {
                padding: 10px;
            }
            
            .navigation {
                flex-direction: column;
                gap: 8px;
            }
            
            .messages-header {
                flex-direction: column;
                align-items: stretch;
            }
            
            .messages-tabs {
                justify-content: center;
            }
            
            .modal-content {
                width: 95%;
                margin: 20px;
            }
        }
    </style>
</head>
<body>
    <!-- Booting Screen -->
    <div class="booting-screen" id="bootingScreen">
        <h1>💬 Market-X Messages</h1>
        <div class="loading-bar">
            <span></span>
        </div>
        <p>Cargando sistema de mensajería...</p>
    </div>

    <!-- Main Content -->
    <div class="container" id="mainContainer">
        <div class="xp-window">
            <!-- Title Bar -->
            <div class="xp-titlebar">
                <div class="title-content">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
                        <polyline points="22,6 12,13 2,6"/>
                    </svg>
                    <span>💬 Mensajes Seguros</span>
                    <?php if ($unread_count > 0): ?>
                        <span style="background: #f44336; color: white; padding: 2px 6px; border-radius: 10px; font-size: 11px;">
                            <?php echo $unread_count; ?>
                        </span>
                    <?php endif; ?>
                </div>
                <button class="close-button" onclick="window.location.href='index.php'">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M18 6 6 18"/>
                        <path d="m6 6 12 12"/>
                    </svg>
                </button>
            </div>
            
            <!-- Navigation -->
            <div class="navigation">
                <a href="index.php" class="xp-button">🏠 Inicio</a>
                <a href="messages.php" class="xp-button active">💬 Mensajes</a>
                <a href="cart.php" class="xp-button">🛒 Carrito</a>
                <a href="orders.php" class="xp-button">📦 Pedidos</a>
                <?php if (isset($_SESSION['is_vendor']) && $_SESSION['is_vendor']): ?>
                    <a href="vendor_dashboard.php" class="xp-button">🏪 Panel Vendedor</a>
                <?php endif; ?>
            </div>
            
            <div class="content">
                <!-- Notifications -->
                <?php if (isset($_SESSION['success'])): ?>
                    <div class="notification success">
                        <span>✓</span>
                        <span><?php echo htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?></span>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($_SESSION['error'])): ?>
                    <div class="notification error">
                        <span>✗</span>
                        <span><?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?></span>
                    </div>
                <?php endif; ?>
                
                <!-- Messages Header -->
                <div class="messages-header">
                    <div class="messages-tabs">
                        <a href="messages.php?filter=inbox" class="tab-button <?php echo $filter === 'inbox' ? 'active' : ''; ?>">
                            📥 Recibidos (<?php echo $unread_count; ?>)
                        </a>
                        <a href="messages.php?filter=sent" class="tab-button <?php echo $filter === 'sent' ? 'active' : ''; ?>">
                            📤 Enviados
                        </a>
                    </div>
                    <button class="compose-button" onclick="showComposeModal()">
                        ✍️ Nuevo Mensaje
                    </button>
                </div>
                
                <!-- Messages List -->
                <div class="message-list">
                    <?php if (empty($messages)): ?>
                        <div class="empty-state">
                            <div class="empty-state-icon">📭</div>
                            <h3>No hay mensajes</h3>
                            <p><?php echo $filter === 'sent' ? 'No has enviado mensajes aún.' : 'Tu bandeja está vacía.'; ?></p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($messages as $message): ?>
                            <div class="message-item <?php echo ($filter === 'inbox' && !$message['is_read']) ? 'unread' : ''; ?>" 
                                 onclick="toggleMessageActions(<?php echo $message['id']; ?>)">
                                <div class="message-header">
                                    <div class="message-from">
                                        <?php if ($filter === 'sent'): ?>
                                            Para: <?php echo htmlspecialchars($message['recipient_username']); ?>
                                        <?php else: ?>
                                            De: <?php echo htmlspecialchars($message['sender_username']); ?>
                                        <?php endif; ?>
                                        <?php if ($filter === 'inbox' && !$message['is_read']): ?>
                                            <span style="color: #ff6b35; font-size: 12px;">● NUEVO</span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="message-date">
                                        <?php echo date('d/m/Y H:i', strtotime($message['created_at'])); ?>
                                    </div>
                                </div>
                                <div class="message-subject">
                                    <?php echo htmlspecialchars($message['subject']); ?>
                                </div>
                                <div class="message-preview">
                                    <?php echo htmlspecialchars(substr($message['message_content'], 0, 120)); ?>
                                    <?php if (strlen($message['message_content']) > 120): ?>...<?php endif; ?>
                                </div>
                                <div class="message-actions" id="actions-<?php echo $message['id']; ?>">
                                    <button onclick="viewMessage(<?php echo $message['id']; ?>, event)" class="action-btn">
                                        👁️ Ver
                                    </button>
                                    <?php if ($filter === 'inbox' && !$message['is_read']): ?>
                                        <a href="messages.php?action=mark_read&id=<?php echo $message['id']; ?>" class="action-btn">
                                            ✓ Marcar leído
                                        </a>
                                    <?php endif; ?>
                                    <?php if ($filter === 'inbox'): ?>
                                        <button onclick="replyMessage('<?php echo htmlspecialchars($message['sender_username']); ?>', 'Re: <?php echo htmlspecialchars($message['subject']); ?>', event)" class="action-btn">
                                            ↩️ Responder
                                        </button>
                                    <?php endif; ?>
                                    <a href="messages.php?action=delete&id=<?php echo $message['id']; ?>" 
                                       onclick="return confirm('¿Eliminar este mensaje?')" 
                                       class="action-btn delete">
                                        🗑️ Eliminar
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Compose Message Modal -->
    <div class="modal" id="composeModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>✍️ Nuevo Mensaje Seguro</h3>
                <button class="close-button" onclick="hideComposeModal()">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M18 6 6 18"/>
                        <path d="m6 6 12 12"/>
                    </svg>
                </button>
            </div>
            <div class="modal-body">
                <form method="POST" action="" id="composeForm">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    
                    <div class="form-group">
                        <label for="recipient_username">👤 Para (Usuario):</label>
                        <input type="text" name="recipient_username" id="recipient_username" 
                               placeholder="Nombre de usuario del destinatario" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="subject">📝 Asunto:</label>
                        <input type="text" name="subject" id="subject" 
                               placeholder="Asunto del mensaje" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="message_content">💬 Mensaje:</label>
                        <textarea name="message_content" id="message_content" 
                                  placeholder="Escribe tu mensaje aquí..." required></textarea>
                    </div>
                    
                    <div class="modal-buttons">
                        <button type="button" class="modal-btn secondary" onclick="hideComposeModal()">
                            Cancelar
                        </button>
                        <button type="submit" name="send_message" class="modal-btn primary">
                            🚀 Enviar Mensaje
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Message View Modal -->
    <div class="modal" id="viewModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>👁️ Ver Mensaje</h3>
                <button class="close-button" onclick="hideViewModal()">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M18 6 6 18"/>
                        <path d="m6 6 12 12"/>
                    </svg>
                </button>
            </div>
            <div class="modal-body" id="messageViewContent">
                <!-- Content loaded dynamically -->
            </div>
        </div>
    </div>

    <script>
        // Booting animation
        window.addEventListener('load', function() {
            setTimeout(function() {
                document.getElementById('bootingScreen').style.display = 'none';
                document.getElementById('mainContainer').classList.add('show');
            }, 2500);
        });
        
        // Show/hide compose modal
        function showComposeModal() {
            document.getElementById('composeModal').style.display = 'flex';
            document.getElementById('recipient_username').focus();
        }
        
        function hideComposeModal() {
            document.getElementById('composeModal').style.display = 'none';
            document.getElementById('composeForm').reset();
        }
        
        // Reply to message
        function replyMessage(username, subject, event) {
            event.stopPropagation();
            document.getElementById('recipient_username').value = username;
            document.getElementById('subject').value = subject;
            showComposeModal();
        }
        
        // Toggle message actions
        function toggleMessageActions(messageId) {
            const actions = document.getElementById('actions-' + messageId);
            const isVisible = actions.style.opacity === '1';
            
            // Hide all actions first
            document.querySelectorAll('.message-actions').forEach(el => {
                el.style.opacity = '0';
            });
            
            // Show current if it wasn't visible
            if (!isVisible) {
                actions.style.opacity = '1';
            }
        }
        
        // View full message
        function viewMessage(messageId, event) {
            event.stopPropagation();
            
            // Find message data
            const messageItem = event.target.closest('.message-item');
            const subject = messageItem.querySelector('.message-subject').textContent;
            const preview = messageItem.querySelector('.message-preview').textContent;
            const from = messageItem.querySelector('.message-from').textContent;
            const date = messageItem.querySelector('.message-date').textContent;
            
            document.getElementById('messageViewContent').innerHTML = `
                <div style="margin-bottom: 20px;">
                    <strong style="color: #ff8c42;">${from}</strong>
                    <div style="color: #b8b8b8; font-size: 12px;">${date}</div>
                </div>
                <div style="margin-bottom: 15px;">
                    <strong>Asunto:</strong> ${subject}
                </div>
                <div style="color: #e5e5e5; line-height: 1.6; white-space: pre-wrap;">${preview}</div>
            `;
            
            document.getElementById('viewModal').style.display = 'flex';
            
            // Mark as read if unread
            if (messageItem.classList.contains('unread')) {
                setTimeout(() => {
                    window.location.href = 'messages.php?action=mark_read&id=' + messageId;
                }, 1000);
            }
        }
        
        function hideViewModal() {
            document.getElementById('viewModal').style.display = 'none';
        }
        
        // Close modals on outside click
        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('modal')) {
                e.target.style.display = 'none';
            }
        });
        
        // Enhanced security features
        document.addEventListener('DOMContentLoaded', function() {
            // Disable right-click
            document.addEventListener('contextmenu', function(e) {
                e.preventDefault();
            });
            
            // Form validation
            document.getElementById('composeForm').addEventListener('submit', function(e) {
                const recipient = document.getElementById('recipient_username').value.trim();
                const subject = document.getElementById('subject').value.trim();
                const content = document.getElementById('message_content').value.trim();
                
                if (!recipient || !subject || !content) {
                    e.preventDefault();
                    alert('Todos los campos son requeridos.');
                    return false;
                }
                
                if (recipient === '<?php echo $current_username; ?>') {
                    e.preventDefault();
                    alert('No puedes enviarte mensajes a ti mismo.');
                    return false;
                }
            });
        });
        
        // Auto-refresh every 30 seconds
        setInterval(function() {
            // Only refresh if no modals are open
            if (!document.querySelector('.modal[style*="flex"]')) {
                window.location.reload();
            }
        }, 30000);
    </script>
</body>
</html>