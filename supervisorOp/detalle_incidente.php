<?php
session_start();


if (!isset($_GET['id'])) {
    header("Location: supervisorOp.php");
    exit();
}

$folio_incidente = $_GET['id'];

require_once '../db.php';

$sql = "SELECT i.*, 
               CONCAT(u.nombre, ' ', u.apellido) as nombre_usuario,
               GROUP_CONCAT(DISTINCT au.id_unidad) as unidades_asignadas,
               GROUP_CONCAT(DISTINCT tu.nombre_tipo) as tipos_unidades
        FROM incidentes i
        JOIN usuarios u ON i.id_usuario_reporta = u.id_usuario
        LEFT JOIN asignaciones_unidades au ON i.folio_incidente = au.id_incidente
        LEFT JOIN unidades un ON au.id_unidad = un.id_unidad
        LEFT JOIN tipos_unidad tu ON un.id_tipo_unidad = tu.id_tipo
        WHERE i.folio_incidente = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $folio_incidente);
$stmt->execute();
$incidente = $stmt->get_result()->fetch_assoc();

$conn->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalle de Incidente</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f4f4f4;
            color: #333;
        }
        
        .blue-bar {
            background-color: #93D8E8;
            color: white;
            padding: 30px;
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
            max-height: 60px;
            width: auto;
        }
        
        .container {
            max-width: 900px;
            margin: 30px auto;
            padding: 0 20px;
        }
        
        .card {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
            padding: 25px;
            margin-bottom: 30px;
        }
        
        .card-header {
            border-bottom: 2px solid #93D8E8;
            padding-bottom: 15px;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .card-title {
            font-size: 20px;
            color: #2c3e50;
            margin: 0;
        }
        
        .priority-badge {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: bold;
            text-transform: uppercase;
        }
        
        .priority-high {
            background-color: #ffebee;
            color: #f44336;
        }
        
        .priority-medium {
            background-color: #fff8e1;
            color: #ffa000;
        }
        
        .priority-low {
            background-color: #e8f5e9;
            color: #4caf50;
        }
        
        .detail-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .detail-item {
            margin-bottom: 15px;
        }
        
        .detail-label {
            font-weight: bold;
            color: #7f8c8d;
            margin-bottom: 5px;
            font-size: 14px;
        }
        
        .detail-value {
            font-size: 16px;
            padding: 8px 0;
            border-bottom: 1px solid #ecf0f1;
        }
        
        .description-box {
            background-color: #f9f9f9;
            border-left: 4px solid #93D8E8;
            padding: 15px;
            margin: 20px 0;
            border-radius: 0 4px 4px 0;
        }
        
        .units-container {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 10px;
        }
        
        .unit-badge {
            background-color: #e3f2fd;
            color: #1976d2;
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 14px;
        }
        
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            transition: background-color 0.3s;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn-primary {
            background-color: #93D8E8;
            color: white;
        }
        
        .btn-primary:hover {
            background-color: #82c8d8;
        }
        
        .btn-secondary {
            background-color: #f5f5f5;
            color: #333;
            border: 1px solid #ddd;
        }
        
        .btn-secondary:hover {
            background-color: #e0e0e0;
        }
        
        .btn-group {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }
        
        @media (max-width: 768px) {
            .detail-grid {
                grid-template-columns: 1fr;
            }
            
            .blue-bar {
                font-size: 20px;
                padding: 20px;
            }
            
            .logo {
                max-height: 50px;
            }
        }
    </style>
</head>
<body>
    <div class="blue-bar">
        <img src="../logo.png" alt="Logo" class="logo">
        Detalles del Incidente #<?php echo htmlspecialchars($folio_incidente); ?>
    </div>

    <div class="container">
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Información General</h2>
                <span class="priority-badge priority-<?php echo strtolower($incidente['prioridad']); ?>">
                    <?php echo htmlspecialchars($incidente['prioridad']); ?>
                </span>
            </div>
            
            <div class="detail-grid">
                <div class="detail-item">
                    <div class="detail-label">Reportado por</div>
                    <div class="detail-value"><?php echo htmlspecialchars($incidente['nombre_usuario']); ?></div>
                </div>
                
                <div class="detail-item">
                    <div class="detail-label">Fecha y Hora</div>
                    <div class="detail-value"><?php echo htmlspecialchars($incidente['fecha_incidente']); ?></div>
                </div>
                
                <div class="detail-item">
                    <div class="detail-label">Tipo de Auxilio</div>
                    <div class="detail-value"><?php echo htmlspecialchars($incidente['tipo_auxilio']); ?></div>
                </div>
                
                <div class="detail-item">
                    <div class="detail-label">Clasificación</div>
                    <div class="detail-value"><?php echo htmlspecialchars($incidente['clasificacion']); ?></div>
                </div>
                
                <div class="detail-item">
                    <div class="detail-label">Personas Involucradas</div>
                    <div class="detail-value"><?php echo htmlspecialchars($incidente['num_personas']); ?></div>
                </div>
                
                <div class="detail-item">
                    <div class="detail-label">Teléfono Reporte</div>
                    <div class="detail-value"><?php echo htmlspecialchars($incidente['telefono']); ?></div>
                </div>
            </div>
            
            <div class="detail-item">
                <div class="detail-label">Ubicación</div>
                <div class="detail-value"><?php echo htmlspecialchars($incidente['ubicacion']); ?></div>
            </div>
            
            <?php if ($incidente['unidades_asignadas']): ?>
            <div class="detail-item">
                <div class="detail-label">Unidades Asignadas</div>
                <div class="units-container">
                    <?php 
                    $unidades = explode(',', $incidente['unidades_asignadas']);
                    $tipos = explode(',', $incidente['tipos_unidades']);
                    foreach(array_combine($unidades, $tipos) as $unidad => $tipo): 
                    ?>
                    <span class="unit-badge"><?php echo htmlspecialchars($tipo); ?> #<?php echo htmlspecialchars($unidad); ?></span>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
            
            <div class="detail-item">
                <div class="detail-label">Descripción del Incidente</div>
                <div class="description-box">
                    <?php echo htmlspecialchars($incidente['quepaso']); ?>
                </div>
            </div>
            
            <div class="btn-group">
                <a href="supervisor.php" class="btn btn-secondary">Volver al Panel</a>
            </div>
        </div>
    </div>
</body>
</html>