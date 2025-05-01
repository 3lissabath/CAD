<?php
session_start();

if (empty($_SESSION['id_usuario']) || empty($_SESSION['nombre_usuario'])) {
    header("Location: ../login.php");
    exit();
}

require_once '../db.php';

ini_set('display_errors', 1);
error_reporting(E_ALL);

// Check if logged in
if (!isset($_SESSION['id_usuario']) || !isset($_SESSION['nombre_usuario'])) {
    header("Location: ../login.php");


    exit();
}

$mensaje_error = '';
$mensaje_exito = '';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $mensaje = trim($_POST['mensaje'] ?? '');
    $user_id = $_SESSION['id_usuario'];
    $username = $_SESSION['nombre_usuario'];

    if ($mensaje !== '') {
        $stmt = $conn->prepare("INSERT INTO chat_messages (user_id, username, message) VALUES (?, ?, ?)");
        $stmt->bind_param("iss", $user_id, $username, $mensaje);
        $stmt->execute();
        $mensaje_exito = "Mensaje enviado";
    } else {
        $mensaje_error = "No puedes enviar un mensaje vacío.";
    }
}

// Fetch messages
$sql = "SELECT * FROM chat_messages ORDER BY timestamp DESC LIMIT 50";
$result = $conn->query($sql);
$mensajes = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Chat General</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 30px;
            background-color: #f5f7fa;
        }

        .chat-container {
            max-width: 800px;
            margin: 0 auto;
        }

        .chat-header {
            background: #4361ee;
            padding: 20px;
            color: white;
            font-size: 1.5rem;
            border-radius: 8px 8px 0 0;
            text-align: center;
        }

        .chat-messages {
            background: white;
            border: 1px solid #ccc;
            height: 500px;
            overflow-y: auto;
            padding: 20px;
            border-top: none;
        }

        .message {
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }

        .message strong {
            color: #3f37c9;
        }

        .timestamp {
            font-size: 0.8rem;
            color: #888;
        }

        .chat-form {
            margin-top: 20px;
            display: flex;
            gap: 10px;
        }

        textarea {
            flex: 1;
            resize: none;
            padding: 10px;
            border-radius: 8px;
            border: 1px solid #ccc;
            font-size: 1rem;
        }

        button {
            padding: 10px 20px;
            border: none;
            background: #4361ee;
            color: white;
            font-size: 1rem;
            border-radius: 8px;
            cursor: pointer;
            transition: background 0.3s;
        }

        button:hover {
            background: #3a56d4;
        }
    </style>
</head>
<body>

<nav style="background: #4361ee; padding: 15px; display: flex; gap: 20px;">
    <a href="incidente.php" style="color: white; text-decoration: none; font-weight: bold;">Incidentes</a>
    <a href="chat.php" style="color: white; text-decoration: none; font-weight: bold;">Chat</a>
    <a href="dashboard.php" style="color: white; text-decoration: none; font-weight: bold;">Dashboard</a>
</nav>

<div class="chat-container">
    <div class="chat-header">
        <i class="fas fa-comments"></i> Chat General
    </div>

    <div class="chat-messages" id="chat-messages">
        <?php foreach (array_reverse($mensajes) as $msg): ?>
            <div class="message">
                <strong><?= htmlspecialchars($msg['username']) ?></strong>:
                <?= htmlspecialchars($msg['message']) ?><br>
                <span class="timestamp"><?= date('d/m/Y H:i', strtotime($msg['timestamp'])) ?></span>
            </div>
        <?php endforeach; ?>
    </div>

    <form method="POST" class="chat-form">
        <textarea name="mensaje" id="mensaje" rows="2" placeholder="Escribe tu mensaje aquí..." required></textarea>
        <button type="submit"><i class="fas fa-paper-plane"></i> Enviar</button>
    </form>
</div>

<script>
// Auto-scroll to the bottom
const chatMessages = document.getElementById('chat-messages');
chatMessages.scrollTop = chatMessages.scrollHeight;

// Auto-refresh messages every 10 seconds
setInterval(() => {
    fetch('chat_refresh.php')
        .then(response => response.text())
        .then(html => {
            chatMessages.innerHTML = html;
            chatMessages.scrollTop = chatMessages.scrollHeight;
        });
}, 10000);
</script>

</body>
</html>
