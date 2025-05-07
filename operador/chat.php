<?php
session_start();
require_once '../db.php';

// Check authentication
if (empty($_SESSION['id_usuario'])) {
    header("Location: ../login.php");
    exit();
}

$current_user_id = $_SESSION['id_usuario'];

// Handle creating new chat
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['new_chat'])) {
    $participants = $_POST['participants'] ?? [];
    $chat_name = trim($_POST['chat_name'] ?? '');
    
    // Validate participants (must include at least one other user)
    if (count($participants) < 1) {
        $_SESSION['error'] = "Debes seleccionar al menos un participante";
    } else {
        try {
            $conn->begin_transaction();
            
            // Create the chat
            $is_group = (count($participants) > 1 || !empty($chat_name));
            $stmt = $conn->prepare("INSERT INTO chats (chat_name, is_group) VALUES (?, ?)");
            $chat_name = $is_group ? $chat_name : '';
            $stmt->bind_param("si", $chat_name, $is_group);
            $stmt->execute();
            $chat_id = $conn->insert_id;
            
            // Add current user to chat
            $stmt = $conn->prepare("INSERT INTO chat_participants (id_chat, id_usuario) VALUES (?, ?)");
            $stmt->bind_param("ii", $chat_id, $current_user_id);
            $stmt->execute();
            
            // Add other participants
            foreach ($participants as $participant_id) {
                $stmt->bind_param("ii", $chat_id, $participant_id);
                $stmt->execute();
            }
            
            $conn->commit();
            $_SESSION['success'] = "Chat creado exitosamente";
        } catch (Exception $e) {
            $conn->rollback();
            $_SESSION['error'] = "Error al crear el chat: " . $e->getMessage();
        }
    }
}

// Handle sending message
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['send_message'])) {
    $chat_id = intval($_POST['chat_id']);
    $message = trim($_POST['message']);
    
    if (empty($message)) {
        $_SESSION['error'] = "El mensaje no puede estar vacío";
    } else {
        // Verify user is a participant in this chat
        $stmt = $conn->prepare("SELECT 1 FROM chat_participants WHERE id_chat = ? AND id_usuario = ?");
        $stmt->bind_param("ii", $chat_id, $current_user_id);
        $stmt->execute();
        
        if ($stmt->get_result()->num_rows > 0) {
            $stmt = $conn->prepare("INSERT INTO messages (id_chat, id_usuario, mensaje) VALUES (?, ?, ?)");
            $stmt->bind_param("iis", $chat_id, $current_user_id, $message);
            $stmt->execute();
        } else {
            $_SESSION['error'] = "No tienes permiso para enviar mensajes a este chat";
        }
    }
}

// Get all chats the user is part of
$chats = [];
$stmt = $conn->prepare("SELECT c.id_chat, c.chat_name, c.is_group 
                       FROM chats c
                       JOIN chat_participants cp ON c.id_chat = cp.id_chat
                       WHERE cp.id_usuario = ?
                       ORDER BY (SELECT MAX(fecha_envio) FROM messages m WHERE m.id_chat = c.id_chat) DESC");
$stmt->bind_param("i", $current_user_id);
$stmt->execute();
$result = $stmt->get_result();
$chats = $result->fetch_all(MYSQLI_ASSOC);

// Get all users for new chat selection
$users = [];
$stmt = $conn->prepare("SELECT u.id_usuario, u.nombre, u.apellido, r.nombre_rol 
                       FROM usuarios u
                       JOIN roles r ON u.rol_id = r.id_rol
                       WHERE u.id_usuario != ? AND u.activo = 1
                       ORDER BY r.nombre_rol, u.nombre, u.apellido");
$stmt->bind_param("i", $current_user_id);
$stmt->execute();
$result = $stmt->get_result();
$users = $result->fetch_all(MYSQLI_ASSOC);

// Get messages for selected chat
$selected_chat_id = $_GET['chat_id'] ?? ($chats[0]['id_chat'] ?? null);
$messages = [];
$chat_participants = [];

if ($selected_chat_id) {
    // Get messages
    $stmt = $conn->prepare("SELECT m.*, u.nombre, u.apellido 
                           FROM messages m
                           JOIN usuarios u ON m.id_usuario = u.id_usuario
                           WHERE m.id_chat = ?
                           ORDER BY m.fecha_envio ASC");
    $stmt->bind_param("i", $selected_chat_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $messages = $result->fetch_all(MYSQLI_ASSOC);
    
    // Get participants for the selected chat
    $stmt = $conn->prepare("SELECT u.id_usuario, u.nombre, u.apellido, r.nombre_rol
                           FROM chat_participants cp
                           JOIN usuarios u ON cp.id_usuario = u.id_usuario
                           JOIN roles r ON u.rol_id = r.id_rol
                           WHERE cp.id_chat = ?");
    $stmt->bind_param("i", $selected_chat_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $chat_participants = $result->fetch_all(MYSQLI_ASSOC);
    
    // Update chat name if it's empty (private chat)
    foreach ($chats as &$chat) {
        if ($chat['id_chat'] == $selected_chat_id && empty($chat['chat_name'])) {
            $other_participants = array_filter($chat_participants, function($p) use ($current_user_id) {
                return $p['id_usuario'] != $current_user_id;
            });
            
            if (count($other_participants) == 1) {
                $participant = reset($other_participants);
                $chat['chat_name'] = $participant['nombre'] . ' ' . $participant['apellido'];
            }
        }
    }
    unset($chat);
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de Chat</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #4361ee;
            --primary-dark: #3a56d4;
            --secondary: #3f37c9;
            --success: #4cc9f0;
            --danger: #f72585;
            --warning: #f8961e;
            --light: #f8f9fa;
            --dark: #212529;
            --gray: #6c757d;
            --border-radius: 8px;
            --box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            --transition: all 0.3s ease;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            margin: 0;
            padding: 0;
            height: 100vh;
            display: flex;
            flex-direction: column;
            background-color: #f5f7fa;
        }

        .navbar {
            background: var(--primary);
            padding: 1rem 2rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: var(--box-shadow);
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .chat-container {
            display: flex;
            flex: 1;
            overflow: hidden;
        }

        .sidebar {
            width: 300px;
            background: white;
            border-right: 1px solid #ddd;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        .chat-header {
            padding: 1rem;
            border-bottom: 1px solid #ddd;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: var(--primary);
            color: white;
        }

        .chat-list {
            flex: 1;
            overflow-y: auto;
        }

        .chat-item {
            padding: 1rem;
            border-bottom: 1px solid #eee;
            cursor: pointer;
            transition: var(--transition);
        }

        .chat-item:hover {
            background-color: var(--light);
        }

        .chat-item.active {
            background-color: #e3f2fd;
            border-left: 3px solid var(--primary);
        }

        .chat-name {
            font-weight: 600;
            margin-bottom: 0.25rem;
        }

        .chat-preview {
            font-size: 0.85rem;
            color: var(--gray);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .chat-content {
            flex: 1;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        .messages-container {
            flex: 1;
            padding: 1rem;
            overflow-y: auto;
            background-color: #f0f2f5;
        }

        .message {
            margin-bottom: 1rem;
            max-width: 70%;
            padding: 0.75rem 1rem;
            border-radius: var(--border-radius);
            position: relative;
        }

        .message.sent {
            background-color: var(--primary);
            color: white;
            margin-left: auto;
            border-bottom-right-radius: 0;
        }

        .message.received {
            background-color: white;
            margin-right: auto;
            border-bottom-left-radius: 0;
            box-shadow: var(--box-shadow);
        }

        .message-sender {
            font-weight: 600;
            margin-bottom: 0.25rem;
            font-size: 0.85rem;
        }

        .message-time {
            font-size: 0.75rem;
            color: var(--gray);
            text-align: right;
            margin-top: 0.25rem;
        }

        .message-input {
            padding: 1rem;
            border-top: 1px solid #ddd;
            background: white;
            display: flex;
            gap: 0.5rem;
        }

        .message-input textarea {
            flex: 1;
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: var(--border-radius);
            resize: none;
            height: 60px;
        }

        .message-input button {
            padding: 0 1.5rem;
            background-color: var(--primary);
            color: white;
            border: none;
            border-radius: var(--border-radius);
            cursor: pointer;
            transition: var(--transition);
        }

        .message-input button:hover {
            background-color: var(--primary-dark);
        }

        .new-chat-btn {
            background-color: var(--success);
            color: white;
            border: none;
            border-radius: var(--border-radius);
            padding: 0.5rem 1rem;
            cursor: pointer;
            transition: var(--transition);
        }

        .new-chat-btn:hover {
            background-color: #3ab7d8;
        }

        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }

        .modal-content {
            background-color: white;
            padding: 2rem;
            border-radius: var(--border-radius);
            width: 500px;
            max-width: 90%;
            box-shadow: var(--box-shadow);
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .modal-close {
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: var(--gray);
        }

        .user-list {
            max-height: 300px;
            overflow-y: auto;
            margin: 1rem 0;
        }

        .user-item {
            display: flex;
            align-items: center;
            padding: 0.75rem;
            border-bottom: 1px solid #eee;
        }

        .user-item input {
            margin-right: 1rem;
        }

        .user-info {
            flex: 1;
        }

        .user-name {
            font-weight: 600;
        }

        .user-role {
            font-size: 0.85rem;
            color: var(--gray);
        }

        .alert {
            padding: 0.75rem 1rem;
            margin-bottom: 1rem;
            border-radius: var(--border-radius);
        }

        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .participants-count {
            font-size: 0.85rem;
            color: var(--gray);
        }
    </style>
</head>
<body>

<nav class="navbar">
    <a href="incidente.php" class="navbar-brand">
        <i class="fas fa-exclamation-triangle"></i> SGI - Chat
    </a>
    <div class="navbar-links">
        <a href="calls.php" class="nav-link"><i class="fas fa-phone"></i> Llamadas</a>
        <a href="incidente.php" class="nav-link"><i class="fas fa-list"></i> Incidentes</a>
        <a href="chat.php" class="nav-link"><i class="fas fa-comments"></i> Chat</a>
        <a href="dashboard.php" class="nav-link"><i class="fas fa-chart-line"></i> Dashboard</a>
        <a href="../logout.php" class="nav-link"><i class="fas fa-sign-out-alt"></i> Salir</a>
    </div>
</nav>

<div class="chat-container">
    <div class="sidebar">
        <div class="chat-header">
            <h3>Chats</h3>
            <button class="new-chat-btn" id="newChatBtn">
                <i class="fas fa-plus"></i> Nuevo chat
            </button>
        </div>
        <div class="chat-list">
            <?php foreach ($chats as $chat): ?>
                <a href="chat.php?chat_id=<?= $chat['id_chat'] ?>">
                    <div class="chat-item <?= $selected_chat_id == $chat['id_chat'] ? 'active' : '' ?>">
                        <div class="chat-name">
                            <?= htmlspecialchars($chat['chat_name'] ?? 'Chat privado') ?>
                        </div>
                        <?php if (!empty($chat['chat_name'])): ?>
                            <div class="participants-count">
                                <?php
                                $stmt = $conn->prepare("SELECT COUNT(*) as count FROM chat_participants WHERE id_chat = ?");
                                $stmt->bind_param("i", $chat['id_chat']);
                                $stmt->execute();
                                $result = $stmt->get_result();
                                $count = $result->fetch_assoc()['count'];
                                ?>
                                <?= $count ?> participante<?= $count != 1 ? 's' : '' ?>
                            </div>
                        <?php endif; ?>
                        <?php
                        // Get last message preview
                        $stmt = $conn->prepare("SELECT m.mensaje, u.nombre 
                                              FROM messages m
                                              JOIN usuarios u ON m.id_usuario = u.id_usuario
                                              WHERE m.id_chat = ?
                                              ORDER BY m.fecha_envio DESC
                                              LIMIT 1");
                        $stmt->bind_param("i", $chat['id_chat']);
                        $stmt->execute();
                        $result = $stmt->get_result();
                        $last_message = $result->fetch_assoc();
                        ?>
                        <?php if ($last_message): ?>
                            <div class="chat-preview">
                                <strong><?= htmlspecialchars($last_message['nombre']) ?>:</strong> 
                                <?= htmlspecialchars(substr($last_message['mensaje'], 0, 30)) ?>
                                <?= strlen($last_message['mensaje']) > 30 ? '...' : '' ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="chat-content">
        <?php if ($selected_chat_id): ?>
            <div class="chat-header">
                <h3>
                    <?= htmlspecialchars(
                        $chats[array_search($selected_chat_id, array_column($chats, 'id_chat'))]['chat_name'] ?? 'Chat privado'
                    ) ?>
                </h3>
                <div class="participants-count">
                    <?= count($chat_participants) ?> participante<?= count($chat_participants) != 1 ? 's' : '' ?>
                </div>
            </div>
            
            <div class="messages-container" id="messagesContainer">
                <?php foreach ($messages as $message): ?>
                    <div class="message <?= $message['id_usuario'] == $current_user_id ? 'sent' : 'received' ?>">
                        <?php if ($message['id_usuario'] != $current_user_id): ?>
                            <div class="message-sender">
                                <?= htmlspecialchars($message['nombre'] . ' ' . $message['apellido']) ?>
                            </div>
                        <?php endif; ?>
                        <div class="message-text">
                            <?= nl2br(htmlspecialchars($message['mensaje'])) ?>
                        </div>
                        <div class="message-time">
                            <?= date('H:i', strtotime($message['fecha_envio'])) ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <form method="POST" class="message-input">
                <input type="hidden" name="chat_id" value="<?= $selected_chat_id ?>">
                <textarea name="message" placeholder="Escribe un mensaje..." required></textarea>
                <button type="submit" name="send_message">
                    <i class="fas fa-paper-plane"></i>
                </button>
            </form>
        <?php else: ?>
            <div style="display: flex; justify-content: center; align-items: center; height: 100%;">
                <div style="text-align: center;">
                    <i class="fas fa-comments" style="font-size: 3rem; color: var(--gray); margin-bottom: 1rem;"></i>
                    <h3>No hay chats disponibles</h3>
                    <p>Selecciona un chat existente o crea uno nuevo</p>
                    <button class="new-chat-btn" id="newChatBtn2">
                        <i class="fas fa-plus"></i> Nuevo chat
                    </button>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- New Chat Modal -->
<div class="modal" id="newChatModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Nuevo chat</h3>
            <button class="modal-close" id="closeModal">&times;</button>
        </div>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-error">
                <?= $_SESSION['error'] ?>
                <?php unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success">
                <?= $_SESSION['success'] ?>
                <?php unset($_SESSION['success']); ?>
            </div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-group">
                <label for="chat_name">Nombre del chat (opcional para chats grupales)</label>
                <input type="text" id="chat_name" name="chat_name" placeholder="Ej: Equipo de respuesta">
            </div>
            
            <div class="form-group">
                <label>Selecciona participantes:</label>
                <div class="user-list">
                    <?php foreach ($users as $user): ?>
                        <div class="user-item">
                            <input type="checkbox" name="participants[]" value="<?= $user['id_usuario'] ?>" id="user_<?= $user['id_usuario'] ?>">
                            <div class="user-info">
                                <div class="user-name">
                                    <?= htmlspecialchars($user['nombre'] . ' ' . $user['apellido']) ?>
                                </div>
                                <div class="user-role">
                                    <?= htmlspecialchars($user['nombre_rol']) ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <button type="submit" name="new_chat" class="new-chat-btn" style="width: 100%;">
                <i class="fas fa-comments"></i> Crear chat
            </button>
        </form>
    </div>
</div>

<script>
    // Modal functionality
    const modal = document.getElementById('newChatModal');
    const newChatBtn = document.getElementById('newChatBtn');
    const newChatBtn2 = document.getElementById('newChatBtn2');
    const closeModal = document.getElementById('closeModal');
    
    if (newChatBtn) {
        newChatBtn.addEventListener('click', () => {
            modal.style.display = 'flex';
        });
    }
    
    if (newChatBtn2) {
        newChatBtn2.addEventListener('click', () => {
            modal.style.display = 'flex';
        });
    }
    
    closeModal.addEventListener('click', () => {
        modal.style.display = 'none';
    });
    
    window.addEventListener('click', (e) => {
        if (e.target == modal) {
            modal.style.display = 'none';
        }
    });
    
    // Auto-scroll to bottom of messages
    const messagesContainer = document.getElementById('messagesContainer');
    if (messagesContainer) {
        messagesContainer.scrollTop = messagesContainer.scrollHeight;
    }
    
    // Auto-refresh messages every 5 seconds
    setInterval(() => {
        if (window.location.href.indexOf('chat_id=') > -1) {
            window.location.reload();
        }
    }, 5000);
</script>

</body>
</html>
