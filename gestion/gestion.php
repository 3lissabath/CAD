<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "base_cad";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['usuario_id']) && isset($_POST['horario_id'])) {
    $usuario_id = $_POST['usuario_id'];
    $horario_id = $_POST['horario_id'];
    
    $sql_horario = "SELECT nombre_horario, hora_inicio, hora_fin FROM horarios WHERE id_horario = ?";
    $stmt_horario = $conn->prepare($sql_horario);
    $stmt_horario->bind_param("i", $horario_id);
    $stmt_horario->execute();
    $result_horario = $stmt_horario->get_result();
    $horario = $result_horario->fetch_assoc();
    
    $descripcion_horario = $horario['nombre_horario'] . " (" . $horario['hora_inicio'] . " - " . $horario['hora_fin'] . ")";
    
    $sql_update_usuario = "UPDATE usuarios SET id_horario = ? WHERE id_usuario = ?";
    $stmt_update_usuario = $conn->prepare($sql_update_usuario);
    $stmt_update_usuario->bind_param("ii", $horario_id, $usuario_id);
    
    $sql_update_personal = "UPDATE gestion_personal gp
                           JOIN usuarios u ON gp.id_personal = u.id_personal
                           SET gp.id_horario = ?
                           WHERE u.id_usuario = ?";
    $stmt_update_personal = $conn->prepare($sql_update_personal);
    $stmt_update_personal->bind_param("ii", $horario_id, $usuario_id);
    
    if ($stmt_update_usuario->execute() && $stmt_update_personal->execute()) {
        $success_message = "Horario asignado correctamente: " . $descripcion_horario;
    } else {
        $error_message = "Error al asignar horario: " . $conn->error;
    }
}

$sql_usuarios = "SELECT u.id_usuario, u.nombre, u.apellido, 
                 CONCAT(u.nombre, ' ', u.apellido) AS nombre_completo,
                 u.id_horario, h.nombre_horario, h.hora_inicio, h.hora_fin,
                 gp.id_personal, r.nombre_rol
                 FROM usuarios u
                 LEFT JOIN horarios h ON u.id_horario = h.id_horario
                 LEFT JOIN gestion_personal gp ON u.id_personal = gp.id_personal
                 LEFT JOIN roles r ON u.rol_id = r.id_rol
                 WHERE u.activo = 1
                 ORDER BY u.nombre, u.apellido";
$result_usuarios = $conn->query($sql_usuarios);
$usuarios = [];
if ($result_usuarios->num_rows > 0) {
    while($row = $result_usuarios->fetch_assoc()) {
        $usuarios[] = $row;
    }
}

$sql_horarios = "SELECT id_horario, nombre_horario, hora_inicio, hora_fin, dias_semana 
                 FROM horarios 
                 WHERE activo = 1
                 ORDER BY hora_inicio";
$result_horarios = $conn->query($sql_horarios);
$horarios = [];
if ($result_horarios->num_rows > 0) {
    while($row = $result_horarios->fetch_assoc()) {
        $horarios[] = $row;
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Asignación de Horarios</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --azul-principal: #93D8E8;
            --azul-hover: #7fc8d8;
            --sombra: 0 4px 6px rgba(0, 0, 0, 0.1);
            --borde: 1px solid #e0e0e0;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background-color: #f5f7fa;
            min-height: 100vh;
        }
        
        .container {
            display: flex;
            min-height: 100vh;
        }
        
        .sidebar {
            width: 280px;
            background: white;
            box-shadow: var(--sombra);
            padding: 25px;
            display: flex;
            flex-direction: column;
            height: 100vh;
            position: fixed;
        }
        
        .logo-container {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .logo-container img {
            max-width: 80%;
            height: auto;
        }
        
        .main-content {
            margin-left: 280px;
            flex: 1;
            padding: 30px;
        }
        
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }
        
        .title {
            font-size: 24px;
            color: #333;
            font-weight: 600;
        }
        
        .panel {
            background: white;
            border-radius: 10px;
            padding: 25px;
            box-shadow: var(--sombra);
            margin-bottom: 30px;
        }
        
        .panel-title {
            font-size: 18px;
            margin-bottom: 20px;
            color: #444;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .panel-title i {
            color: var(--azul-principal);
        }
        
        .workers-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        
        .workers-table th, 
        .workers-table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: var(--borde);
        }
        
        .workers-table th {
            background-color: #f8f9fa;
            font-weight: 600;
            color: #555;
        }
        
        .workers-table tr:hover {
            background-color: #f5f5f5;
        }
        
        .schedule-container {
            display: flex;
            gap: 20px;
            margin-top: 20px;
        }
        
        .schedule-info {
            flex: 1;
            background: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
            border: var(--borde);
        }
        
        .schedule-info p {
            margin-bottom: 10px;
            font-size: 15px;
        }
        
        .schedule-info strong {
            color: #333;
        }
        
        .btn {
            padding: 10px 20px;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            border: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn-primary {
            background-color: var(--azul-principal);
            color: white;
        }
        
        .btn-primary:hover {
            background-color: var(--azul-hover);
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        
        .btn-sm {
            padding: 6px 12px;
            font-size: 14px;
        }
        
        .assign-form {
            margin-top: 30px;
            padding-top: 20px;
            border-top: var(--borde);
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #555;
        }
        
        .form-control {
            width: 100%;
            padding: 10px 12px;
            border: var(--borde);
            border-radius: 6px;
            font-size: 15px;
        }
        
        .time-slots {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 15px;
        }
        
        .time-slot {
            padding: 8px 15px;
            background: #f0f0f0;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.2s;
            min-width: 200px;
        }
        
        .time-slot:hover {
            background: #e0e0e0;
        }
        
        .time-slot.selected {
            background-color: var(--azul-principal);
            color: white;
        }
        
        .badge-rol {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
            background-color: #e0e0e0;
            color: #333;
        }
        
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 6px;
        }
        
        .alert-success {
            background-color: #d4edda;
            color: #155724;
        }
        
        .alert-error {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        @media (max-width: 992px) {
            .container {
                flex-direction: column;
            }
            
            .sidebar {
                width: 100%;
                position: relative;
                height: auto;
            }
            
            .main-content {
                margin-left: 0;
            }
            
            .schedule-container {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="sidebar">
            <div class="logo-container">
                <img src="../logo.png" alt="Logo de la empresa">
            </div>
            
            <div style="flex-grow: 1;"></div>
            
            <button class="btn btn-primary" onclick="window.location.reload()">
                <i class="fas fa-sync-alt"></i>
                Actualizar Lista
            </button>
        </div>
        
        <div class="main-content">
            <div class="header">
                <h1 class="title">Asignación de Horarios</h1>
                <div class="search-box">
                    <input type="text" class="form-control" placeholder="Buscar trabajador..." style="width: 250px;">
                </div>
            </div>
            
            <?php if (isset($success_message)): ?>
                <div class="alert alert-success">
                    <?php echo $success_message; ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($error_message)): ?>
                <div class="alert alert-error">
                    <?php echo $error_message; ?>
                </div>
            <?php endif; ?>
            
            <div class="panel">
                <h2 class="panel-title">
                    <i class="fas fa-users"></i>
                    Lista de Personal
                </h2>
                
                <table class="workers-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nombre</th>
                            <th>Rol</th>
                            <th>Horario Actual</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($usuarios as $usuario): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($usuario['id_usuario']); ?></td>
                            <td><?php echo htmlspecialchars($usuario['nombre_completo']); ?></td>
                            <td><span class="badge-rol"><?php echo htmlspecialchars($usuario['nombre_rol']); ?></span></td>
                            <td>
                                <?php if ($usuario['id_horario']): ?>
                                    <?php echo htmlspecialchars($usuario['nombre_horario'] . " (" . $usuario['hora_inicio'] . " - " . $usuario['hora_fin'] . ")"); ?>
                                <?php else: ?>
                                    No asignado
                                <?php endif; ?>
                            </td>
                            <td>
                                <button class="btn btn-primary btn-sm assign-btn" 
                                        data-id="<?php echo htmlspecialchars($usuario['id_usuario']); ?>"
                                        data-name="<?php echo htmlspecialchars($usuario['nombre_completo']); ?>"
                                        data-schedule="<?php echo $usuario['id_horario'] ? htmlspecialchars($usuario['nombre_horario'] . " (" . $usuario['hora_inicio'] . " - " . $usuario['hora_fin'] . ")") : ''; ?>">
                                    <i class="fas fa-edit"></i> Asignar
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <div class="panel">
                <h2 class="panel-title">
                    <i class="fas fa-user-clock"></i>
                    Asignar Nuevo Horario
                </h2>
                
                <div class="schedule-container">
                    <div class="schedule-info">
                        <p><strong>Usuario seleccionado:</strong> <span id="selected-worker">Ninguno</span></p>
                        <p><strong>Horario actual:</strong> <span id="current-schedule">No asignado</span></p>
                        <p><strong>Días de trabajo:</strong> <span id="current-days">-</span></p>
                    </div>
                    
                    <form method="POST" class="assign-form" id="schedule-form">
                        <input type="hidden" name="usuario_id" id="usuario-id-input">
                        
                        <div class="form-group">
                            <label>Seleccionar horario:</label>
                            <div class="time-slots">
                                <?php foreach ($horarios as $horario): ?>
                                <div class="time-slot" 
                                     data-value="<?php echo $horario['id_horario']; ?>"
                                     data-desc="<?php echo htmlspecialchars($horario['nombre_horario'] . " (" . $horario['hora_inicio'] . " - " . $horario['hora_fin'] . ")"); ?>"
                                     data-days="<?php echo htmlspecialchars($horario['dias_semana']); ?>">
                                    <strong><?php echo htmlspecialchars($horario['nombre_horario']); ?></strong><br>
                                    <?php echo $horario['hora_inicio']; ?> - <?php echo $horario['hora_fin']; ?><br>
                                    <small><?php echo htmlspecialchars($horario['dias_semana']); ?></small>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <input type="hidden" name="horario_id" id="selected-horario-input">
                        </div>
                        
                        <button type="submit" class="btn btn-primary" style="margin-top: 20px;">
                            <i class="fas fa-save"></i> Guardar Asignación
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>

    document.querySelectorAll('.assign-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const id = this.getAttribute('data-id');
                const name = this.getAttribute('data-name');
                const schedule = this.getAttribute('data-schedule') || 'No asignado';
                
                document.getElementById('selected-worker').textContent = `${name} (ID: ${id})`;
                document.getElementById('current-schedule').textContent = schedule;
                document.getElementById('usuario-id-input').value = id;
                
                // Desplazarse al formulario de asignación
                document.querySelector('.assign-form').scrollIntoView({
                    behavior: 'smooth'
                });
            });
        });
        
        // Selección de horario
        document.querySelectorAll('.time-slot').forEach(slot => {
            slot.addEventListener('click', function() {
                document.querySelectorAll('.time-slot').forEach(s => {
                    s.classList.remove('selected');
                });
                this.classList.add('selected');
                document.getElementById('selected-horario-input').value = this.getAttribute('data-value');
                document.getElementById('current-days').textContent = this.getAttribute('data-days');
            });
        });
        
        // Validación del formulario
        document.getElementById('schedule-form').addEventListener('submit', function(e) {
            const usuarioId = document.getElementById('usuario-id-input').value;
            const horarioId = document.getElementById('selected-horario-input').value;
            
            if (!usuarioId || usuarioId === '') {
                alert('Por favor, selecciona un usuario primero');
                e.preventDefault();
                return;
            }
            
            if (!horarioId || horarioId === '') {
                alert('Por favor, selecciona un horario');
                e.preventDefault();
                return;
            }
        });
    </script>
</body>
</html>
