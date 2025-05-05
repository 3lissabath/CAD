<?php
session_start();
require_once '../db.php';

// Check authentication
if (empty($_SESSION['id_usuario'])) {
    header("Location: ../login.php");
    exit();
}

// Get all calls that need attention
$query = "SELECT l.*, i.quepaso, i.tipo_auxilio, i.latitud, i.longitud 
          FROM llamadas l
          LEFT JOIN incidentes i ON l.id_incidente = i.folio_incidente
          WHERE l.estatus = 'Atender'
          ORDER BY l.fecha DESC";
$result = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Llamadas Pendientes</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #4361ee;
            --primary-dark: #3a56d4;
            --danger: #f72585;
            --success: #4cc9f0;
            --warning: #f8961e;
            --light: #f8f9fa;
            --dark: #212529;
            --border-radius: 8px;
            --box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #f5f7fa;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            padding: 20px;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
        }
        
        h1 {
            color: var(--primary);
            margin-top: 0;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        
        th, td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        
        th {
            background-color: var(--primary);
            color: white;
        }
        
        tr:hover {
            background-color: var(--light);
        }
        
        .btn {
            padding: 8px 12px;
            border: none;
            border-radius: var(--border-radius);
            cursor: pointer;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        
        .btn-attend {
            background-color: var(--success);
            color: white;
        }
        
        .btn-complete {
            background-color: var(--primary);
            color: white;
        }
        
        .status-badge {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: 500;
        }
        
        .status-pending {
            background-color: var(--warning);
            color: var(--dark);
        }
        
        .status-in-progress {
            background-color: var(--success);
            color: white;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1><i class="fas fa-phone"></i> Llamadas Pendientes</h1>
        
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Fecha</th>
                    <th>Operador</th>
                    <th>Descripción</th>
                    <th>Tipo</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($row['id_llamada']) ?></td>
                    <td><?= date('d/m/Y H:i', strtotime($row['fecha'])) ?></td>
                    <td><?= htmlspecialchars($row['id_operador']) ?></td>
                    <td><?= htmlspecialchars($row['quepaso'] ?? 'N/A') ?></td>
                    <td><?= htmlspecialchars($row['tipo_auxilio'] ?? 'N/A') ?></td>
                    <td>
                        <span class="status-badge status-pending">Pendiente</span>
                    </td>
                    <td>
                        <form action="incidente.php" method="GET" style="display: inline;">
                            <input type="hidden" name="call_id" value="<?= $row['id_llamada'] ?>">
                            <input type="hidden" name="lat" value="<?= $row['latitud'] ?>">
                            <input type="hidden" name="lng" value="<?= $row['longitud'] ?>">
                            <button type="submit" class="btn btn-attend">
                                <i class="fas fa-headset"></i> Atender
                            </button>
                        </form>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</body>
</html>
