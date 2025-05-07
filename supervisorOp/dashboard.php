
<?php
session_start();
if (empty($_SESSION['id_usuario']) || empty($_SESSION['nombre_usuario'])) {
    header("Location: ../login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Dashboard - Visual Priority Queues</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Same CSS theme you already use -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* REUSING your color palette */
        :root {
            --primary: #4361ee;
            --secondary: #3f37c9;
            --success: #4cc9f0;
            --danger: #f72585;
            --warning: #f8961e;
            --info: #4895ef;
            --light: #f8f9fa;
            --dark: #212529;
            --gray: #6c757d;
            --white: #ffffff;
            --radius: 8px;
            --shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            background-color: #f5f7fa;
        }

        nav {
            background: var(--primary);
            padding: 15px;
            display: flex;
            gap: 20px;
        }

        nav a {
            color: white;
            text-decoration: none;
            font-weight: bold;
        }

        .container {
            padding: 20px;
            max-width: 1400px;
            margin: auto;
        }

        .queues {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
        }

        .queue {
            flex: 1;
            background: var(--white);
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            padding: 20px;
        }

        .queue h2 {
            text-align: center;
            margin-bottom: 10px;
            color: var(--primary);
        }

        ul {
            list-style: none;
            padding: 0;
            margin: 0;
            min-height: 200px;
        }

        li {
            padding: 10px;
            margin-bottom: 5px;
            background-color: var(--light);
            border-radius: var(--radius);
            transition: all 0.3s;
        }

        .log {
            background: var(--white);
            padding: 20px;
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            height: 150px;
            overflow-y: auto;
            font-size: 0.9rem;
            color: var(--dark);
        }

        .controls {
            margin-top: 20px;
            text-align: center;
        }

        .controls button {
            margin: 0 10px;
            padding: 10px 20px;
            font-size: 1rem;
            border: none;
            border-radius: var(--radius);
            cursor: pointer;
            color: var(--white);
        }

        .pause-btn {
            background-color: var(--danger);
        }

        .resume-btn {
            background-color: var(--success);
        }
    </style>
</head>

<body>

<nav>
    <a href="calls.php" class="nav-link"><i class="fas fa-phone"></i> Llamadas</a>
    <a href="incidente.php">Incidentes</a>
    <a href="chat.php">Chat</a>
    <a href="dashboard.php">Dashboard</a>
</nav>

<div class="container">
    <div class="queues">
        <div class="queue" id="queue1">
            <h2>Fila de llamadas 1</h2>
            <ul id="list1"></ul>
        </div>
        <div class="queue" id="queue2">
            <h2>Fila de llamadas 2</h2>
            <ul id="list2"></ul>
        </div>
        <div class="queue" id="queue3">
            <h2>Fila de llamadas 3</h2>
            <ul id="list3"></ul>
        </div>
    </div>

    <div class="log" id="log">
        <strong>Logs:</strong><br>
    </div>

    <div class="controls">
        <button class="pause-btn" id="pauseBtn">Pause</button>
        <button class="resume-btn" id="resumeBtn" disabled>Resume</button>
    </div>
</div>

<script>
let running = true;
let inputInterval;
let processingIntervals = [];

function generateCall(queueId) {
    const value = Math.floor(Math.random() * 100) + 1;
    const processingTime = Math.floor(Math.random() * 5) + 3; // 3 to 7 seconds realistic

    const li = document.createElement('li');
    li.textContent = `${value} (${processingTime}s)`;
    li.style.backgroundColor = 'lightgreen';
    li.style.opacity = '0';
    const list = document.getElementById(`list${queueId}`);
    list.prepend(li);

    // Smooth fade-in
    setTimeout(() => {
        li.style.opacity = '1';
    }, 50);

    // Turn back to white after 1 second
    setTimeout(() => {
        li.style.backgroundColor = '';
    }, 1000);

    // Simulate some fake coordinates
    const lat = (19.4 + Math.random() * 0.01).toFixed(6);
    const lng = (-99.1 + Math.random() * 0.01).toFixed(6);

    // Send to server via AJAX
    fetch('insert_call.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: new URLSearchParams({
            quepaso: `Llamada simulada ${value}`,
            tipo_auxilio: ["Medico", "Proteccion Civil", "Seguridad"][Math.floor(Math.random() * 3)],
            num_personas: Math.floor(Math.random() * 5) + 1,
            telefono: '5551234567',
            clasificacion: ["Emergencia Real", "Broma", "TTY"][Math.floor(Math.random() * 3)],
            prioridad: ["Alta", "Media", "Baja"][Math.floor(Math.random() * 3)],
            latitud: lat,
            longitud: lng
        })
    })
    .then(response => response.json())
    .then(data => {
        if (!data.success) {
            console.error('Error al guardar en la base de datos:', data.error);
        }
    })
    .catch(error => {
        console.error('Error de red:', error);
    });

    // Schedule processing (dequeue)
    const processTimeout = setTimeout(() => {
        if (li.parentElement) {
            li.style.backgroundColor = '#f8d7da'; // Light red
            li.style.opacity = '0'; // Start fade-out
            setTimeout(() => {
                if (li.parentElement) {
                    li.remove();
                    addLog(`📞 Llamada ${value} recibida por la Fila de Llamadas ${queueId}`);
                }
            }, 500);
        }
    }, processingTime * 1000);

    processingIntervals.push(processTimeout);
}


function generateRandomCalls() {
    if (!running) return;

    // Generate one call randomly for only one operator (not all 3 at once)
    const randomQueue = Math.floor(Math.random() * 3) + 1;
    generateCall(randomQueue);
}

function addLog(message) {
    const logBox = document.getElementById('log');
    const timestamp = new Date().toLocaleTimeString();
    logBox.innerHTML += `[${timestamp}] ${message}<br>`;
}

function startSimulation() {
    running = true;
    inputInterval = setInterval(generateRandomCalls, 1500); // Every 1.5 seconds
}

function pauseSimulation() {
    running = false;
    clearInterval(inputInterval);
    processingIntervals.forEach(clearTimeout);
    processingIntervals = [];

    document.getElementById('pauseBtn').disabled = true;
    document.getElementById('resumeBtn').disabled = false;
    addLog("⏸️ Simulación pausada.");
}

function resumeSimulation() {
    startSimulation();
    document.getElementById('pauseBtn').disabled = false;
    document.getElementById('resumeBtn').disabled = true;
    addLog("▶️ Simulación reanudada.");
}

// Button listeners
document.getElementById('pauseBtn').addEventListener('click', pauseSimulation);
document.getElementById('resumeBtn').addEventListener('click', resumeSimulation);

// Start automatically
startSimulation();


</script>

</body>
</html>
