<?php
session_start();

require_once '../db.php';

$llamadas = [];
$incidentes = [];

try {
    $sql_llamadas = "SELECT l.id_llamada, l.estatus, 
                    CASE 
                        WHEN l.estatus = 'En curso' THEN 'status-encurso'
                        WHEN l.estatus = 'Finalizada' THEN 'status-finalizada'
                        ELSE 'status-atender'
                    END as clase_estatus
                    FROM llamadas l
                    JOIN usuarios u ON l.id_operador = u.id_usuario
                    ORDER BY l.fecha DESC LIMIT 10";
    
    $stmt_llamadas = $conn->prepare($sql_llamadas);
    $stmt_llamadas->execute();
    $llamadas = $stmt_llamadas->get_result()->fetch_all(MYSQLI_ASSOC);

    $sql_incidentes = "SELECT i.folio_incidente, i.hora_incidente, i.prioridad, 
                       CONCAT(u.nombre, ' ', u.apellido) as nombre_usuario,
                       CASE 
                           WHEN i.prioridad = 'Alta' THEN 'prioridad-alta'
                           WHEN i.prioridad = 'Media' THEN 'prioridad-media'
                           ELSE 'prioridad-baja'
                       END as clase_prioridad
                       FROM incidentes i
                       JOIN usuarios u ON i.id_usuario_reporta = u.id_usuario
                       ORDER BY i.fecha_incidente DESC LIMIT 10";
    
    $stmt_incidentes = $conn->prepare($sql_incidentes);
    $stmt_incidentes->execute();
    $incidentes = $stmt_incidentes->get_result()->fetch_all(MYSQLI_ASSOC);

} catch (Exception $e) {
    $error = "Error al obtener datos: " . $e->getMessage();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de Supervisión</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f4f4f4;
        }
        
        .blue-bar {
            background-color: #93D8E8;
            color: white;
            padding: 40px;
            text-align: center;
            font-size: 24px;
            font-weight: bold;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
            position: relative;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        
        .logo {
            position: absolute;
            top: 50%;
            right: 20px;
            transform: translateY(-50%);
            max-height: 70px;
            width: auto;
        }
        
        .search-container {
            max-width: 1200px;
            margin: 20px auto;
            padding: 20px;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        
        .search-box {
            display: flex;
            margin-bottom: 20px;
        }
        
        .search-box input {
            flex: 1;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px 0 0 4px;
            font-size: 16px;
        }
        
        .search-box button {
            padding: 10px 20px;
            background-color: #93D8E8;
            color: white;
            border: none;
            border-radius: 0 4px 4px 0;
            cursor: pointer;
        }
        
        .dashboard-container {
            max-width: 1200px;
            margin: 0 auto 30px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        
        .panel {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            padding: 15px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        th, td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        
        th {
            background-color: #f8f8f8;
        }
        
        .status-circle, .priority-circle {
            display: inline-block;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            margin-right: 10px;
        }
        
        .status-encurso { background-color: #FFA500; }
        .status-finalizada { background-color: #4CAF50; }
        .status-atender { background-color: #F44336; }
        
        .prioridad-alta { background-color: #F44336; }
        .prioridad-media { background-color: #FFA500; }
        .prioridad-baja { background-color: #4CAF50; }
        
        .action-btn {
            padding: 6px 12px;
            background-color: #93D8E8;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
        }
        
        .action-btn:hover {
            background-color: #82c8d8;
        }
    </style>
</head>
<body>
    <div class="blue-bar">
        <img src="../logo.png" alt="Logo" class="logo">
        Panel de Supervisión 
    </div>
    
    <div class="search-container">
        <form method="GET" class="search-box">
            <input type="text" name="busqueda" placeholder="Buscar en llamadas e incidentes...">
            <button type="submit">Buscar</button>
        </form>
    </div>
    
    <div class="dashboard-container">
        <div class="panel">
            <h3>Llamadas Recientes</h3>
            <table>
                <thead>
                    <tr>
                        <th>ID Llamada</th>
                        <th>Estatus</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($llamadas as $llamada): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($llamada['id_llamada']); ?></td>
                        <td>
                            <span class="status-circle <?php echo htmlspecialchars($llamada['clase_estatus']); ?>"></span>
                            <?php echo htmlspecialchars($llamada['estatus']); ?>
                        </td>
                       
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <div class="panel">
            <h3>Incidentes Recientes</h3>
            <table>
                <thead>
                    <tr>
                        <th>Folio</th>
                        <th>Hora</th>
                        <th>Prioridad</th>
                        <th>Reportado por</th>
                        <th>Acciones</th>
                        <th>Calificar</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($incidentes as $incidente): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($incidente['folio_incidente']); ?></td>
                        <td><?php echo htmlspecialchars($incidente['hora_incidente']); ?></td>
                        <td>
                            <span class="priority-circle <?php echo htmlspecialchars($incidente['clase_prioridad']); ?>"></span>
                            <?php echo htmlspecialchars($incidente['prioridad']); ?>
                        </td>
                        <td><?php echo htmlspecialchars($incidente['nombre_usuario']); ?></td>
                        <td>
                            <button class="action-btn" 
                                    onclick="window.location.href='detalle_incidente.php?id=<?php echo $incidente['folio_incidente']; ?>'">
                                Ver Detalle
                            </button>
                        </td>
                        <td>
                            <button class="action-btn" 
                                    onclick="window.location.href='calificar.php?id=<?php echo $incidente['folio_incidente']; ?>'">
                                Calificar
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>