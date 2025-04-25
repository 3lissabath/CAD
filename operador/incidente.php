<?php
session_start();
require_once '../db.php';

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$mensaje_exito = '';
$mensaje_error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        
        if (empty($_POST['coordenadas'])) {
            throw new Exception("Debes seleccionar una ubicación en el mapa");
        }

        $coordenadas = trim($_POST['coordenadas']);
        $coords = explode(',', $coordenadas);
        
        if (count($coords) != 2) {
            throw new Exception("Formato de coordenadas inválido. Debe ser 'latitud,longitud'");
        }
        
        $latitud = trim($coords[0]);
        $longitud = trim($coords[1]);
        
        if (!is_numeric($latitud) || !is_numeric($longitud)) {
            throw new Exception("Las coordenadas deben ser valores numéricos");
        }

    
        $quepaso = trim($_POST['paso'] ?? '');
        $tipo_auxilio = trim($_POST['tipo_auxilio'] ?? '');
        $num_personas = intval($_POST['num_personas'] ?? 0);
        $telefono = trim($_POST['numero'] ?? '');
        $clasificacion = trim($_POST['clasificacion'] ?? '');
        $prioridad = trim($_POST['prioridad'] ?? '');
        $id_usuario_reporta = $_SESSION['id_usuario'] ?? null;
        
        $sql = "INSERT INTO incidentes (
            quepaso, tipo_auxilio, hora_incidente, fecha_incidente, 
            num_personas, telefono, id_usuario_reporta, 
            clasificacion, prioridad, latitud, longitud
        ) VALUES (?, ?, CURTIME(), NOW(), ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            throw new Exception("Error al preparar la consulta: " . $conn->error);
        }
        
        $stmt->bind_param(
            "sssissssss", 
            $quepaso, 
            $tipo_auxilio, 
            $num_personas, 
            $telefono, 
            $id_usuario_reporta,
            $clasificacion, 
            $prioridad,
            $latitud,
            $longitud
        );
        
        if ($stmt->execute()) {
            $folio_incidente = $stmt->insert_id;
            $mensaje_exito = "Incidente registrado correctamente (Folio #$folio_incidente)";
            
            echo '<script>
                document.getElementById("form-incidente").reset();
                document.getElementById("coordenadas_display").value = "";
                document.getElementById("coordenadas").value = "";
                if (window.marker) window.map.removeLayer(window.marker);
            </script>';
        } else {
            throw new Exception("Error al registrar: " . $stmt->error);
        }
    } catch (Exception $e) {
        $mensaje_error = $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro del incidente</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f4f4f4;
            display: flex;
            flex-wrap: wrap;
        }
        
        .blue-bar {
            background-color: #93D8E8;
            color: white;
            padding: 20px;
            text-align: center;
            font-size: 24px;
            font-weight: bold;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
            position: relative;
            display: flex;
            justify-content: center;
            align-items: center;
            width: 100%;
        }
        
        .logo {
            position: absolute;
            top: 50%;
            right: 20px;
            transform: translateY(-50%);
            max-height: 50px;
            width: auto;
        }
        
        .map-panel {
            flex: 1;
            min-width: 300px;
            padding: 20px;
            background-color: white;
            margin: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        
        #map {
            height: 400px;
            width: 100%;
            border-radius: 8px;
            margin-bottom: 15px;
            border: 1px solid #ddd;
        }
        
        .search-box {
            display: flex;
            margin-bottom: 15px;
        }
        
        .search-box input {
            flex: 1;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px 0 0 4px;
            font-size: 14px;
        }
        
        .search-box button {
            padding: 10px 15px;
            background-color: #93D8E8;
            color: white;
            border: none;
            border-radius: 0 4px 4px 0;
            cursor: pointer;
        }
        
        .form-panel {
            flex: 1;
            min-width: 300px;
            max-width: 600px;
            padding: 20px;
            background-color: white;
            margin: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
        }
        
        input[type="text"],
        input[type="number"],
        select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }
        
        #clasificacion option[value="broma"] {
            background-color: #c300ff; 
            color: #000;
        }
        
        #clasificacion option[value="emergencia_real"] {
            background-color: #ff8800; 
            color: white;
        }
        
        #clasificacion option[value="tty"] {
            background-color: #4682B4; 
            color: white;
        }
        
        #prioridad option[value="grave"] {
            background-color: #ff1100;
            color: #000;
        }
        
        #prioridad option[value="media"] {
            background-color: #80f894; 
            color: white;
        }
        
        #prioridad option[value="baja"] {
            background-color: #eee454; 
            color: white;
        }

        .submit-btn {
            background-color: #93D8E8;
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            width: 100%;
        }
        
        .submit-btn:hover {
            background-color: #82c8d8;
        }
        
        .mensaje-exito {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 15px;
            background: #4CAF50;
            color: white;
            border-radius: 5px;
            z-index: 1000;
        }

        .mensaje-error {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 15px;
            background: #f44336;
            color: white;
            border-radius: 5px;
            z-index: 1000;
        }
        
        @media (max-width: 768px) {
            .map-panel, .form-panel {
                margin: 10px;
                min-width: calc(100% - 20px);
            }
        }
    </style>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.7.1/dist/leaflet.css" />
</head>
<body>
    <div class="blue-bar">
        <img src="../logo.png" alt="Logo" class="logo">
        REGISTRO DE INCIDENTE
    </div>
    
    
    <div class="map-panel">
        <h3>Ubicación del Incidente</h3>
        <div id="map"></div>
        <div class="search-box">
            <input type="text" id="address-input" placeholder="Buscar ubicación...">
            <button id="search-button">Buscar</button>
        </div>
        <div class="form-group">
    <label for="coordenadas_display">Coordenadas:</label>
    <input type="text" id="coordenadas_display" readonly>
    <input type="hidden" id="coordenadas" name="coordenadas" value="">
</div>
    </div>
    <div class="form-panel">
    <form id="form-incidente" action="incidente.php" method="post" onsubmit="return validarFormulario()">                <div class="form-group">
                <label for="paso">¿Qué pasó?:</label>
                <input type="text" id="paso" name="paso" required value="<?= htmlspecialchars($_POST['paso'] ?? '') ?>">
            </div>
            
            <div class="form-group">
                <label for="tipo_auxilio">Tipo de auxilio:</label>
                <select id="tipo_auxilio" name="tipo_auxilio" required>
                    <option value="">Seleccione un tipo</option>
                    <option value="medico" <?= ($_POST['tipo_auxilio'] ?? '') == 'medico' ? 'selected' : '' ?>>Médico</option>
                    <option value="proteccion_civil" <?= ($_POST['tipo_auxilio'] ?? '') == 'proteccion_civil' ? 'selected' : '' ?>>Protección Civil</option>
                    <option value="seguridad" <?= ($_POST['tipo_auxilio'] ?? '') == 'seguridad' ? 'selected' : '' ?>>Seguridad</option>
                    <option value="servicios_publicos" <?= ($_POST['tipo_auxilio'] ?? '') == 'servicios_publicos' ? 'selected' : '' ?>>Servicios Públicos</option>
                    <option value="otros" <?= ($_POST['tipo_auxilio'] ?? '') == 'otros' ? 'selected' : '' ?>>Otros servicios</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="num_personas">Número de personas involucradas:</label>
                <input type="number" id="num_personas" name="num_personas" min="1" required value="<?= htmlspecialchars($_POST['num_personas'] ?? '') ?>">
            </div>
            
            <div class="form-group">
                <label for="numero">Número celular:</label>
                <input type="text" id="numero" name="numero" required value="<?= htmlspecialchars($_POST['numero'] ?? '') ?>">
            </div>
            
            <div class="form-group">
                <label for="clasificacion">Clasificación de la llamada:</label>
                <select id="clasificacion" name="clasificacion" required>
                    <option value="">Seleccione un tipo</option>
                    <option value="broma" <?= ($_POST['clasificacion'] ?? '') == 'broma' ? 'selected' : '' ?>>Broma</option>
                    <option value="emergencia_real" <?= ($_POST['clasificacion'] ?? '') == 'emergencia_real' ? 'selected' : '' ?>>Emergencia Real</option>
                    <option value="tty" <?= ($_POST['clasificacion'] ?? '') == 'tty' ? 'selected' : '' ?>>TTY</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="prioridad">Prioridad:</label>
                <select id="prioridad" name="prioridad" required>
                    <option value="">Seleccione la prioridad</option>
                    <option value="grave" <?= ($_POST['prioridad'] ?? '') == 'grave' ? 'selected' : '' ?>>Grave</option>
                    <option value="media" <?= ($_POST['prioridad'] ?? '') == 'media' ? 'selected' : '' ?>>Media</option>
                    <option value="baja" <?= ($_POST['prioridad'] ?? '') == 'baja' ? 'selected' : '' ?>>Baja</option>
                </select>
            </div>

            <button type="submit" class="submit-btn">ENVIAR REPORTE</button>
        </form>
    </div>

    <script src="https://unpkg.com/leaflet@1.7.1/dist/leaflet.js"></script>
    <script>
  // Configuración del mapa
const map = L.map('map').setView([19.4326, -99.1332], 12);
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map);

let marker = null;

// Función para actualizar coordenadas
function actualizarCoordenadas(lat, lng) {
    const coords = lat + ',' + lng;
    const coordsInput = document.getElementById('coordenadas');
    
    coordsInput.value = coords;
    document.getElementById('coordenadas_display').value = coords;
    
    console.log('Coordenadas establecidas:', coordsInput.value);
}

// Evento click en el mapa
map.on('click', function(e) {
    if (marker) map.removeLayer(marker);
    marker = L.marker(e.latlng).addTo(map);
    actualizarCoordenadas(
        e.latlng.lat.toFixed(6), 
        e.latlng.lng.toFixed(6)
    );
});

// Evento de búsqueda
document.getElementById('search-button').addEventListener('click', function() {
    const address = document.getElementById('address-input').value;
    if (!address.trim()) return;
    
    fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(address)}`)
        .then(response => response.json())
        .then(data => {
            if (data.length > 0) {
                const lat = data[0].lat;
                const lon = data[0].lon;
                
                if (marker) map.removeLayer(marker);
                map.setView([lat, lon], 15);
                marker = L.marker([lat, lon]).addTo(map);
                actualizarCoordenadas(lat, lon);
            } else {
                alert('Ubicación no encontrada');
            }
        });
});

function validarFormulario() {
    const coords = document.getElementById('coordenadas').value;
    if (!coords) {
        alert('Por favor selecciona una ubicación en el mapa');
        return false;
    }
    return true;
}
    </script>
</body>
</html>