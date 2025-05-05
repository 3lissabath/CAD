<?php
session_start();

if (empty($_SESSION['id_usuario'])) {
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
        if (isset($_POST['edit_id'])) {
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
            $telefono = trim($_POST['telefono']);
            $clasificacion = trim($_POST['clasificacion']);
            $prioridad = trim($_POST['prioridad']);
            $colonia = trim($_POST['colonia']);
            $localidad = trim($_POST['localidad']);
            $municipio = trim($_POST['municipio']);
            $id_usuario_reporta = $_SESSION['id_usuario'] ?? NULL;
            $id_unidad_asignada = $_POST['id_unidad_asignada'] ?? NULL;

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
                $stmt = $conn->prepare("INSERT INTO incidentes 
                    (quepaso, tipo_auxilio, hora_incidente, fecha_incidente, fecha_actualizacion, num_personas, latitud, longitud, telefono, id_usuario_reporta, clasificacion, prioridad, id_unidad_asignada, colonia, localidad, municipio) 
                    VALUES (?, ?, CURTIME(), NOW(), NOW(), ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

                $stmt->bind_param("ssisssississs", 
                    $quepaso,
                    $tipo_auxilio,
                    $num_personas,
                    $latitud,
                    $longitud,
                    $telefono,
                    $id_usuario_reporta,
                    $clasificacion,
                    $prioridad,
                    $id_unidad_asignada,
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
?>

<?php
// [Previous PHP code remains exactly the same until the HTML starts]
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
            overflow: hidden;
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

        .main-container {
            display: flex;
            flex: 1;
            overflow: hidden;
        }

        .form-container {
            width: 40%;
            padding: 2rem;
            overflow-y: auto;
            background: white;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }

        .map-container {
            width: 60%;
            height: 100%;
            position: relative;
        }

        #map {
            position: absolute;
            top: 0;
            bottom: 0;
            left: 0;
            right: 0;
            border-left: 1px solid #ddd;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: var(--dark);
        }

        input[type="text"],
        input[type="tel"],
        input[type="number"],
        textarea,
        select {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: var(--border-radius);
            font-size: 1rem;
            transition: var(--transition);
            background-color: white;
        }

        textarea {
            min-height: 120px;
            resize: vertical;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: var(--border-radius);
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            transition: var(--transition);
            gap: 0.5rem;
        }

        .btn-primary {
            background-color: var(--primary);
            color: white;
        }

        .btn-primary:hover {
            background-color: var(--primary-dark);
        }

        .btn-warning {
            background-color: var(--warning);
            color: white;
        }

        .location-details {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: var(--border-radius);
            margin-top: 1rem;
            border: 1px solid #eee;
        }

        .location-details p {
            margin: 0.5rem 0;
            font-size: 0.9rem;
        }

        .location-details strong {
            color: var(--primary);
        }

        @media (max-width: 768px) {
            .main-container {
                flex-direction: column;
            }
            
            .form-container, .map-container {
                width: 100%;
                height: 50%;
            }
        }
    </style>
</head>
<body>

<nav class="navbar">
    <a href="incidente.php" class="navbar-brand">
        <i class="fas fa-exclamation-triangle"></i> SGI
    </a>
    <div class="navbar-links">
        <a href="incidente.php" class="nav-link"><i class="fas fa-list"></i> Incidentes</a>
        <a href="chat.php" class="nav-link"><i class="fas fa-comments"></i> Chat</a>
        <a href="dashboard.php" class="nav-link"><i class="fas fa-chart-line"></i> Dashboard</a>
        <a href="../logout.php" class="nav-link"><i class="fas fa-sign-out-alt"></i> Salir</a>
    </div>
</nav>

<div class="main-container">
    <div class="form-container">
        <?php if ($mensaje_exito): ?>
            <div class="alert alert-success"><?= htmlspecialchars($mensaje_exito) ?></div>
        <?php endif; ?>
        
        <?php if ($mensaje_error): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($mensaje_error) ?></div>
        <?php endif; ?>

        <h2>Formulario de Incidente</h2>
        <form method="POST" onsubmit="return validarFormulario()">
            <?php if ($edicion): ?>
                <input type="hidden" name="folio_incidente" value="<?= htmlspecialchars($incidente['folio_incidente']) ?>">
            <?php endif; ?>

            <div class="form-group">
                <label for="paso"><i class="fas fa-question-circle"></i> ¿Cuál es su emergencia?</label>
                <textarea id="paso" name="paso" required><?= htmlspecialchars($incidente['quepaso'] ?? '') ?></textarea>
            </div>

            <div class="form-group">
                <label for="tipo_auxilio"><i class="fas fa-ambulance"></i> Tipo de auxilio</label>
                <select id="tipo_auxilio" name="tipo_auxilio" required>
                    <option value="">Seleccione...</option>
                    <option value="Medico" <?= ($incidente['tipo_auxilio'] ?? '') == 'Medico' ? 'selected' : '' ?>>Médico</option>
                    <option value="Proteccion Civil" <?= ($incidente['tipo_auxilio'] ?? '') == 'Proteccion Civil' ? 'selected' : '' ?>>Protección Civil</option>
                    <option value="Seguridad" <?= ($incidente['tipo_auxilio'] ?? '') == 'Seguridad' ? 'selected' : '' ?>>Seguridad</option>
                </select>
            </div>

            <div class="form-group">
                <label for="num_personas"><i class="fas fa-users"></i> Número de personas</label>
                <input type="number" id="num_personas" name="num_personas" value="<?= htmlspecialchars($incidente['num_personas'] ?? '') ?>" min="1" required>
            </div>

            <div class="form-group">
                <label for="telefono"><i class="fas fa-phone"></i> Teléfono</label>
                <input type="tel" id="telefono" name="telefono" value="<?= htmlspecialchars($incidente['telefono'] ?? '') ?>" required>
            </div>

            <div class="form-group">
                <label for="clasificacion"><i class="fas fa-filter"></i> Clasificación</label>
                <select id="clasificacion" name="clasificacion" required>
                    <option value="">Seleccione...</option>
                    <option value="Broma" <?= ($incidente['clasificacion'] ?? '') == 'Broma' ? 'selected' : '' ?>>Broma</option>
                    <option value="Emergencia Real" <?= ($incidente['clasificacion'] ?? '') == 'Emergencia Real' ? 'selected' : '' ?>>Emergencia Real</option>
                    <option value="TTY" <?= ($incidente['clasificacion'] ?? '') == 'TTY' ? 'selected' : '' ?>>TTY</option>
                </select>
            </div>

            <div class="form-group">
                <label for="prioridad"><i class="fas fa-exclamation"></i> Prioridad</label>
                <select id="prioridad" name="prioridad" required>
                    <option value="">Seleccione...</option>
                    <option value="Alta" <?= ($incidente['prioridad'] ?? '') == 'Alta' ? 'selected' : '' ?>>Alta</option>
                    <option value="Media" <?= ($incidente['prioridad'] ?? '') == 'Media' ? 'selected' : '' ?>>Media</option>
                    <option value="Baja" <?= ($incidente['prioridad'] ?? '') == 'Baja' ? 'selected' : '' ?>>Baja</option>
                </select>
            </div>

            <input type="hidden" id="colonia" name="colonia" value="<?= htmlspecialchars($incidente['colonia'] ?? '') ?>">
            <input type="hidden" id="localidad" name="localidad" value="<?= htmlspecialchars($incidente['localidad'] ?? '') ?>">
            <input type="hidden" id="municipio" name="municipio" value="<?= htmlspecialchars($incidente['municipio'] ?? '') ?>">

            <div class="form-group">
                <label><i class="fas fa-map-marked-alt"></i> Ubicación en el mapa</label>
                <input type="text" id="coordenadas_display" class="form-control" readonly 
                       value="<?= $edicion ? htmlspecialchars($incidente['latitud'] . ', ' . $incidente['longitud']) : '' ?>">
                <input type="hidden" id="coordenadas" name="coordenadas" 
                       value="<?= $edicion ? htmlspecialchars($incidente['latitud'] . ',' . $incidente['longitud']) : '' ?>">
                
                <div class="location-details" id="location-details" style="<?= $edicion ? '' : 'display:none;' ?>">
                    <p><strong>Colonia:</strong> <span id="colonia-display"><?= htmlspecialchars($incidente['colonia'] ?? '') ?></span></p>
                    <p><strong>Localidad:</strong> <span id="localidad-display"><?= htmlspecialchars($incidente['localidad'] ?? '') ?></span></p>
                    <p><strong>Municipio:</strong> <span id="municipio-display"><?= htmlspecialchars($incidente['municipio'] ?? '') ?></span></p>
                </div>
                
                <input type="hidden" id="colonia" name="colonia" value="<?= htmlspecialchars($incidente['colonia'] ?? '') ?>">
                <input type="hidden" id="localidad" name="localidad" value="<?= htmlspecialchars($incidente['localidad'] ?? '') ?>">
                <input type="hidden" id="municipio" name="municipio" value="<?= htmlspecialchars($incidente['municipio'] ?? '') ?>">
                
                <small class="text-muted">Haz clic en el mapa para seleccionar la ubicación</small>
            </div>

            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save"></i> <?= $edicion ? 'Actualizar' : 'Registrar' ?>
            </button>
        </form>
    </div>
    
    <div class="map-container">
        <div id="map"></div>
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

    <?php if ($edicion && isset($incidente['latitud']) && isset($incidente['longitud'])): ?>
        marker = L.marker([<?= $incidente['latitud'] ?>, <?= $incidente['longitud'] ?>]).addTo(map);
        document.getElementById('location-details').style.display = 'block';
    <?php endif; ?>

    // Handle map clicks
    map.on('click', function(e) {
        if (marker) map.removeLayer(marker);
        marker = L.marker(e.latlng).addTo(map);

        const lat = e.latlng.lat.toFixed(6);
        const lon = e.latlng.lng.toFixed(6);
        const coords = lat + ', ' + lon;

        document.getElementById('coordenadas').value = lat + ',' + lon;
        document.getElementById('coordenadas_display').value = coords;

        // Show loading state
        const locationDetails = document.getElementById('location-details');
        locationDetails.style.display = 'block';
        locationDetails.innerHTML = '<p>Obteniendo detalles de ubicación...</p>';

        // Reverse geocode
        fetch(`https://nominatim.openstreetmap.org/reverse?lat=${lat}&lon=${lon}&format=json&accept-language=es`)
            .then(response => response.json())
            .then(data => {
                if (data.address) {
                    const colonia = data.address.suburb || data.address.neighbourhood || 'No especificado';
                    const localidad = data.address.city || data.address.town || data.address.village || 'No especificado';
                    const municipio = data.address.state || 'No especificado';

                    document.getElementById('colonia').value = colonia;
                    document.getElementById('localidad').value = localidad;
                    document.getElementById('municipio').value = municipio;

                    locationDetails.innerHTML = `
                        <p><strong>Colonia:</strong> ${colonia}</p>
                        <p><strong>Localidad:</strong> ${localidad}</p>
                        <p><strong>Municipio:</strong> ${municipio}</p>
                    `;
                } else {
                    locationDetails.innerHTML = '<p>No se pudieron obtener los detalles de la ubicación</p>';
                }
            })
            .catch(error => {
                locationDetails.innerHTML = '<p>Error al obtener la ubicación</p>';
                console.error('Error:', error);
            });
    });

    function validarFormulario() {
        if (!document.getElementById('coordenadas').value) {
            alert('Debes seleccionar una ubicación en el mapa');
            return false;
        }
        return true;
    }

    // Fix map display on load
    setTimeout(() => {
        map.invalidateSize();
    }, 100);
</script>

</body>
</html>
