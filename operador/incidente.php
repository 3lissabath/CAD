<?php
session_start();

if (empty($_SESSION['id_usuario']) || empty($_SESSION['nombre_usuario'])) {
    header("Location: ../login.php");
    exit();
}

require_once '../db.php';

ini_set('display_errors', 1);
error_reporting(E_ALL);

$mensaje_exito = '';
$mensaje_error = '';
$incidente = null;
$edicion = false;

// Process form
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        if (isset($_POST['delete_id'])) {
            $delete_id = intval($_POST['delete_id']);
            $stmt = $conn->prepare("DELETE FROM incidentes WHERE folio_incidente = ?");
            $stmt->bind_param("i", $delete_id);
            $stmt->execute();
            $mensaje_exito = "Incidente eliminado correctamente";

        } elseif (isset($_POST['edit_id'])) {
            $edicion = true;
            $edit_id = intval($_POST['edit_id']);
            $stmt = $conn->prepare("SELECT * FROM incidentes WHERE folio_incidente = ?");
            $stmt->bind_param("i", $edit_id);
            $stmt->execute();
            $resultado = $stmt->get_result();
            $incidente = $resultado->fetch_assoc();

        } else {
            if (empty($_POST['coordenadas'])) {
                throw new Exception("Debes seleccionar una ubicación en el mapa");
            }

            [$latitud, $longitud] = explode(',', $_POST['coordenadas']);
            $latitud = trim($latitud);
            $longitud = trim($longitud);

            $quepaso = trim($_POST['paso']);
            $tipo_auxilio = trim($_POST['tipo_auxilio']);
            $num_personas = intval($_POST['num_personas']);
            $telefono = trim($_POST['numero']);
            $clasificacion = trim($_POST['clasificacion']);
            $prioridad = trim($_POST['prioridad']);
            $colonia = trim($_POST['colonia']);
            $localidad = trim($_POST['localidad']);
            $municipio = trim($_POST['municipio']);
            $id_usuario_reporta = $_SESSION['id_usuario'] ?? NULL;

            if (isset($_POST['folio_incidente'])) {
                $folio_incidente = intval($_POST['folio_incidente']);
                $stmt = $conn->prepare("UPDATE incidentes SET quepaso=?, tipo_auxilio=?, num_personas=?, telefono=?, clasificacion=?, prioridad=?, latitud=?, longitud=?, colonia=?, localidad=?, municipio=?, fecha_actualizacion=NOW() WHERE folio_incidente=?");
                $stmt->bind_param("ssissssssssi", 
                    $quepaso, 
                    $tipo_auxilio, 
                    $num_personas, 
                    $telefono, 
                    $clasificacion, 
                    $prioridad, 
                    $latitud, 
                    $longitud, 
                    $colonia, 
                    $localidad, 
                    $municipio, 
                    $folio_incidente
                );
                $stmt->execute();
                $mensaje_exito = "Incidente actualizado correctamente";
                $edicion = false;
            } else {
                $stmt = $conn->prepare("INSERT INTO incidentes (quepaso, tipo_auxilio, hora_incidente, fecha_incidente, num_personas, telefono, id_usuario_reporta, clasificacion, prioridad, latitud, longitud, colonia, localidad, municipio) 
VALUES (?, ?, CURTIME(), NOW(), ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("sisissssssss", 
                    $quepaso, 
                    $tipo_auxilio, 
                    $num_personas, 
                    $telefono, 
                    $id_usuario_reporta, 
                    $clasificacion, 
                    $prioridad, 
                    $latitud, 
                    $longitud, 
                    $colonia, 
                    $localidad, 
                    $municipio
                );
                $stmt->execute();
                $mensaje_exito = "Incidente registrado correctamente";
            }
        }
    } catch (Exception $e) {
        $mensaje_error = $e->getMessage();
    }
}

$sql = "SELECT i.*, u.nombre AS nombre_usuario FROM incidentes i LEFT JOIN usuarios u ON i.id_usuario_reporta = u.id_usuario ORDER BY i.fecha_incidente DESC";
$resultado = $conn->query($sql);
$incidentes = $resultado ? $resultado->fetch_all(MYSQLI_ASSOC) : [];
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de Gestión de Incidentes</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.3/dist/leaflet.css" />
    <style>
        :root {
            --primary: #4361ee;
            --primary-dark: #3a56d4;
            --secondary: #3f37c9;
            --success: #4cc9f0;
            --danger: #f72585;
            --warning: #f8961e;
            --info: #4895ef;
            --light: #f8f9fa;
            --dark: #212529;
            --gray: #6c757d;
            --white: #ffffff;
            --shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            --radius: 8px;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f7fa;
            color: var(--dark);
            line-height: 1.6;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }

        header {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: var(--white);
            padding: 20px 0;
            margin-bottom: 30px;
            box-shadow: var(--shadow);
            border-radius: var(--radius);
        }

        .header-content {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        h1 {
            font-size: 1.8rem;
            font-weight: 600;
        }

        .dashboard {
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 30px;
        }

        .card {
            background: var(--white);
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            padding: 25px;
            margin-bottom: 30px;
        }

        .card-title {
            font-size: 1.3rem;
            margin-bottom: 20px;
            color: var(--primary);
            border-bottom: 2px solid var(--light);
            padding-bottom: 10px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: var(--dark);
        }

        input, select, textarea {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: var(--radius);
            font-size: 1rem;
            transition: all 0.3s;
        }

        input:focus, select:focus, textarea:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.2);
        }

        textarea {
            min-height: 100px;
            resize: vertical;
        }

        #map {
            height: 300px;
            width: 100%;
            border-radius: var(--radius);
            margin-bottom: 15px;
            border: 1px solid #ddd;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 12px 24px;
            border: none;
            border-radius: var(--radius);
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
        }

        .btn i {
            margin-right: 8px;
        }

        .btn-primary {
            background-color: var(--primary);
            color: var(--white);
        }

        .btn-primary:hover {
            background-color: var(--primary-dark);
            transform: translateY(-2px);
        }

        .btn-danger {
            background-color: var(--danger);
            color: var(--white);
        }

        .btn-danger:hover {
            background-color: #d1146a;
            transform: translateY(-2px);
        }

        .btn-warning {
            background-color: var(--warning);
            color: var(--white);
        }

        .btn-warning:hover {
            background-color: #e68a1a;
            transform: translateY(-2px);
        }

        .btn-sm {
            padding: 8px 12px;
            font-size: 0.9rem;
        }

        .table-responsive {
            overflow-x: auto;
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
            color: var(--white);
            font-weight: 500;
        }

        tr:hover {
            background-color: rgba(67, 97, 238, 0.05);
        }

        .badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .badge-primary {
            background-color: var(--primary);
            color: var(--white);
        }

        .badge-success {
            background-color: var(--success);
            color: var(--dark);
        }

        .badge-danger {
            background-color: var(--danger);
            color: var(--white);
        }

        .badge-warning {
            background-color: var(--warning);
            color: var(--white);
        }

        .badge-info {
            background-color: var(--info);
            color: var(--white);
        }

        .action-buttons {
            display: flex;
            gap: 8px;
        }

        .alert {
            padding: 15px 20px;
            border-radius: var(--radius);
            margin-bottom: 20px;
            display: flex;
            align-items: center;
        }

        .alert-success {
            background-color: rgba(76, 201, 240, 0.2);
            border-left: 4px solid var(--success);
            color: #0a6e8a;
        }

        .alert-danger {
            background-color: rgba(247, 37, 133, 0.2);
            border-left: 4px solid var(--danger);
            color: #a01b58;
        }

        .alert i {
            margin-right: 10px;
            font-size: 1.2rem;
        }

        @media (max-width: 1024px) {
            .dashboard {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>

<nav style="background: #4361ee; padding: 15px; display: flex; gap: 20px;">
    <a href="incidente.php" style="color: white; text-decoration: none; font-weight: bold;">Incidentes</a>
    <a href="chat.php" style="color: white; text-decoration: none; font-weight: bold;">Chat</a>
    <a href="dashboard.php" style="color: white; text-decoration: none; font-weight: bold;">Dashboard</a>
</nav>

    <header>
        <div class="header-content">
            <h1><i class="fas fa-exclamation-triangle"></i> Sistema de Gestión de Incidentes</h1>
        </div>
    </header>

    <div class="container">
        <?php if ($mensaje_exito): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <span><?= $mensaje_exito ?></span>
            </div>
        <?php endif; ?>

        <?php if ($mensaje_error): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i>
                <span><?= $mensaje_error ?></span>
            </div>
        <?php endif; ?>

        <div class="dashboard">
            <div>
                <div class="card">
                    <h2 class="card-title">
                        <i class="fas fa-<?= $edicion ? 'edit' : 'plus' ?>"></i>
                        <?= $edicion ? 'Editar Incidente' : 'Nuevo Incidente' ?>
                    </h2>
                    <form method="POST" onsubmit="return validarFormulario()">
                        <?php if ($edicion): ?>
                            <input type="hidden" name="folio_incidente" value="<?= $incidente['folio_incidente'] ?>">
                        <?php endif; ?>

                        <div class="form-group">
                            <label for="paso">¿Cuál es su emergencia?</label>
                            <textarea id="paso" name="paso" required><?= htmlspecialchars($incidente['quepaso'] ?? '') ?></textarea>
                        </div>

                        <div class="form-group">
                            <label for="tipo_auxilio">Tipo de auxilio</label>
                            <select id="tipo_auxilio" name="tipo_auxilio" required>
                                <option value="">Seleccione...</option>
                                <option value="Medico" <?= ($incidente['tipo_auxilio'] ?? '') == 'Medico' ? 'selected' : '' ?>>Médico</option>
                                <option value="Proteccion Civil" <?= ($incidente['tipo_auxilio'] ?? '') == 'Proteccion Civil' ? 'selected' : '' ?>>Protección Civil</option>
                                <option value="Seguridad" <?= ($incidente['tipo_auxilio'] ?? '') == 'Seguridad' ? 'selected' : '' ?>>Seguridad</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="colonia">Colonia</label>
                            <input type="text" id="colonia" name="colonia" value="<?= $incidente['colonia'] ?? '' ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="localidad">Localidad</label>
                            <input type="text" id="localidad" name="localidad" value="<?= $incidente['localidad'] ?? '' ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="municipio">Municipio</label>
                            <input type="text" id="municipio" name="municipio" value="<?= $incidente['municipio'] ?? '' ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="num_personas">Número de personas</label>
                            <input type="number" id="num_personas" name="num_personas" value="<?= $incidente['num_personas'] ?? '' ?>" min="1" required>
                        </div>

                        <div class="form-group">
                            <label for="numero">Teléfono</label>
                            <input type="tel" id="numero" name="numero" value="<?= $incidente['telefono'] ?? '' ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="clasificacion">Clasificación</label>
                            <select id="clasificacion" name="clasificacion" required>
                                <option value="">Seleccione...</option>
                                <option value="Broma" <?= ($incidente['clasificacion'] ?? '') == 'Broma' ? 'selected' : '' ?>>Broma</option>
                                <option value="Emergencia Real" <?= ($incidente['clasificacion'] ?? '') == 'Emergencia Real' ? 'selected' : '' ?>>Emergencia Real</option>
                                <option value="TTY" <?= ($incidente['clasificacion'] ?? '') == 'TTY' ? 'selected' : '' ?>>TTY</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="prioridad">Prioridad</label>
                            <select id="prioridad" name="prioridad" required>
                                <option value="">Seleccione...</option>
                                <option value="Alta" <?= ($incidente['prioridad'] ?? '') == 'Alta' ? 'selected' : '' ?>>Alta</option>
                                <option value="Media" <?= ($incidente['prioridad'] ?? '') == 'Media' ? 'selected' : '' ?>>Media</option>
                                <option value="Baja" <?= ($incidente['prioridad'] ?? '') == 'Baja' ? 'selected' : '' ?>>Baja</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Ubicación en el mapa</label>
                            <div id="map"></div>
                            <input type="text" id="coordenadas_display" class="form-control" readonly>
                            <input type="hidden" id="coordenadas" name="coordenadas" value="<?= $edicion ? ($incidente['latitud'] . ',' . $incidente['longitud']) : '' ?>">
                        </div>

                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> <?= $edicion ? 'Actualizar' : 'Registrar' ?>
                        </button>

                        <?php if ($edicion): ?>
                            <a href="incidente.php" class="btn btn-warning" style="margin-left: 10px;">
                                <i class="fas fa-times"></i> Cancelar
                            </a>
                        <?php endif; ?>
                    </form>
                </div>
            </div>

            <div>
                <div class="card">
                    <h2 class="card-title"><i class="fas fa-list"></i> Incidentes Registrados</h2>
                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th>Folio</th>
                                    <th>Descripción</th>
                                    <th>Tipo</th>
                                    <th>Fecha</th>
                                    <th>Prioridad</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($incidentes as $inc): ?>
                                <tr>
                                    <td>#<?= $inc['folio_incidente'] ?></td>
                                    <td><?= htmlspecialchars(substr($inc['quepaso'], 0, 30)) . (strlen($inc['quepaso']) > 30 ? '...' : '') ?></td>
                                    <td>
                                        <span class="badge 
                                            <?= $inc['tipo_auxilio'] == 'Medico' ? 'badge-primary' : 
                                               ($inc['tipo_auxilio'] == 'Proteccion Civil' ? 'badge-info' : 'badge-warning') ?>">
                                            <?= $inc['tipo_auxilio'] ?>
                                        </span>
                                    </td>
                                    <td><?= date('d/m/Y H:i', strtotime($inc['fecha_incidente'])) ?></td>
                                    <td>
                                        <span class="badge 
                                            <?= $inc['prioridad'] == 'Alta' ? 'badge-danger' : 
                                               ($inc['prioridad'] == 'Media' ? 'badge-warning' : 'badge-success') ?>">
                                            <?= $inc['prioridad'] ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <form method="POST" style="display:inline;">
                                                <input type="hidden" name="edit_id" value="<?= $inc['folio_incidente'] ?>">
                                                <button type="submit" class="btn btn-warning btn-sm">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                            </form>
                                            <form method="POST" style="display:inline;" onsubmit="return confirm('¿Estás seguro de eliminar este incidente?');">
                                                <input type="hidden" name="delete_id" value="<?= $inc['folio_incidente'] ?>">
                                                <button type="submit" class="btn btn-danger btn-sm">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php if (empty($incidentes)): ?>
                                <tr>
                                    <td colspan="6" style="text-align: center;">No hay incidentes registrados</td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://unpkg.com/leaflet@1.9.3/dist/leaflet.js"></script>
    <script>
        // Initialize map
        const map = L.map('map').setView([19.4326, -99.1332], 12);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
        }).addTo(map);

        let marker = null;

        // If editing, set the existing marker
        <?php if ($edicion && isset($incidente['latitud']) && isset($incidente['longitud'])): ?>
            marker = L.marker([<?= $incidente['latitud'] ?>, <?= $incidente['longitud'] ?>]).addTo(map);
            map.setView([<?= $incidente['latitud'] ?>, <?= $incidente['longitud'] ?>], 15);
            document.getElementById('coordenadas_display').value = '<?= $incidente['latitud'] ?>, <?= $incidente['longitud'] ?>';
        <?php endif; ?>

        // Handle map clicks
        map.on('click', function(e) {
            if (marker) map.removeLayer(marker);
            marker = L.marker(e.latlng).addTo(map);
            const coords = e.latlng.lat.toFixed(6) + ',' + e.latlng.lng.toFixed(6);
            document.getElementById('coordenadas').value = coords;
            document.getElementById('coordenadas_display').value = coords;
        });

        // Form val<?php
session_start();

if (empty($_SESSION['id_usuario']) || empty($_SESSION['nombre_usuario']))
?>idation
        function validarFormulario() {
            if (!document.getElementById('coordenadas').value) {
                alert('Debes seleccionar una ubicación en el mapa');
                return false;
            }
            return true;
        }

        // Handle window resize to properly redraw map
        window.addEventListener('resize', function() {
            map.invalidateSize();
            <?php if ($edicion && isset($incidente['latitud']) && isset($incidente['longitud'])): ?>
                map.setView([<?= $incidente['latitud'] ?>, <?= $incidente['longitud'] ?>], 15);
            <?php endif; ?>
        });
    </script>
</body>
</html>
