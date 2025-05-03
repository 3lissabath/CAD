<?php
// [Previous PHP code remains exactly the same until the style section]
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

        html, body {
            height: 100%;
            background-color: #f5f7fa;
            color: var(--dark);
            line-height: 1.6;
        }

        body {
            display: flex;
            flex-direction: column;
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
            flex-shrink: 0;
        }

        .navbar-brand {
            color: white;
            font-size: 1.5rem;
            font-weight: bold;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .navbar-links {
            display: flex;
            gap: 1.5rem;
        }

        .nav-link {
            color: white;
            text-decoration: none;
            font-weight: 500;
            padding: 0.5rem 1rem;
            border-radius: var(--border-radius);
            transition: var(--transition);
        }

        .nav-link:hover {
            background-color: rgba(255, 255, 255, 0.2);
        }

        .header-content {
            text-align: center;
            padding: 1rem;
            background: white;
            box-shadow: var(--box-shadow);
            flex-shrink: 0;
        }

        .header-content h1 {
            color: var(--primary);
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }

        .header-content i {
            margin-right: 0.5rem;
        }

        .main-content {
            flex: 1;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        .content-wrapper {
            display: grid;
            grid-template-columns: 1fr 1fr;
            height: 100%;
            gap: 0;
        }

        .form-column {
            display: flex;
            flex-direction: column;
            padding: 1.5rem;
            overflow-y: auto;
        }

        .map-column {
            display: flex;
            flex-direction: column;
            height: 100%;
            padding: 0;
        }

        .card {
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }

        .map-card {
            height: 100%;
            border-radius: 0;
            box-shadow: none;
            padding: 0;
            margin: 0;
        }

        .card-title {
            color: var(--primary);
            margin-bottom: 1.5rem;
            font-size: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
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
        }

        input[type="text"]:focus,
        input[type="tel"]:focus,
        input[type="number"]:focus,
        textarea:focus,
        select:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.2);
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

        .btn-warning:hover {
            background-color: #e07f0e;
        }

        .alert {
            padding: 1rem;
            border-radius: var(--border-radius);
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .alert-success {
            background-color: #d1fae5;
            color: #065f46;
            border-left: 4px solid #10b981;
        }

        .alert-danger {
            background-color: #fee2e2;
            color: #b91c1c;
            border-left: 4px solid #ef4444;
        }

        #map {
            height: 100%;
            width: 100%;
            min-height: 300px;
        }

        .coords-display {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background: rgba(255, 255, 255, 0.9);
            padding: 1rem;
            border-top: 1px solid #ddd;
            z-index: 1000;
        }

        .coords-display input {
            width: 100%;
            padding: 0.5rem;
            border: 1px solid #ddd;
            border-radius: 4px;
        }

        @media (max-width: 768px) {
            .navbar {
                flex-direction: column;
                padding: 1rem;
            }

            .navbar-links {
                margin-top: 1rem;
                width: 100%;
                justify-content: space-around;
            }

            .content-wrapper {
                grid-template-columns: 1fr;
                height: auto;
            }

            .form-column {
                padding: 1rem;
            }

            .map-column {
                height: 50vh;
            }

            .form-row {
                grid-template-columns: 1fr !important;
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

<div class="header-content">
    <h1><i class="fas fa-exclamation-triangle"></i> Sistema de Gestión de Incidentes</h1>
    <p>Registro y seguimiento de incidentes en tiempo real</p>
</div>

<?php if ($mensaje_exito): ?>
    <div class="alert alert-success">
        <i class="fas fa-check-circle"></i>
        <span><?= htmlspecialchars($mensaje_exito) ?></span>
    </div>
<?php endif; ?>

<?php if ($mensaje_error): ?>
    <div class="alert alert-danger">
        <i class="fas fa-exclamation-circle"></i>
        <span><?= htmlspecialchars($mensaje_error) ?></span>
    </div>
<?php endif; ?>

<div class="main-content">
    <div class="content-wrapper">
        <div class="form-column">
            <div class="card">
                <h2 class="card-title">
                    <i class="fas fa-<?= $edicion ? 'edit' : 'plus' ?>"></i>
                    <?= $edicion ? 'Editar Incidente' : 'Nuevo Incidente' ?>
                </h2>
                <form method="POST" onsubmit="return validarFormulario()">
                    <?php if ($edicion): ?>
                        <input type="hidden" name="folio_incidente" value="<?= htmlspecialchars($incidente['folio_incidente']) ?>">
                    <?php endif; ?>

                    <div class="form-group">
                        <label for="paso"><i class="fas fa-question-circle"></i> ¿Cuál es su emergencia?</label>
                        <textarea id="paso" name="paso" required><?= htmlspecialchars($incidente['quepaso'] ?? '') ?></textarea>
                    </div>

                    <div class="form-row" style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
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
                    </div>

                    <div class="form-row" style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1.5rem;">
                        <div class="form-group">
                            <label for="colonia"><i class="fas fa-map-marker-alt"></i> Colonia</label>
                            <input type="text" id="colonia" name="colonia" value="<?= htmlspecialchars($incidente['colonia'] ?? '') ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="localidad"><i class="fas fa-city"></i> Localidad</label>
                            <input type="text" id="localidad" name="localidad" value="<?= htmlspecialchars($incidente['localidad'] ?? '') ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="municipio"><i class="fas fa-building"></i> Municipio</label>
                            <input type="text" id="municipio" name="municipio" value="<?= htmlspecialchars($incidente['municipio'] ?? '') ?>" required>
                        </div>
                    </div>

                    <div class="form-row" style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                        <div class="form-group">
                            <label for="numero"><i class="fas fa-phone"></i> Teléfono</label>
                            <input type="tel" id="numero" name="numero" value="<?= htmlspecialchars($incidente['telefono'] ?? '') ?>" required>
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

                    <div style="display: flex; gap: 1rem; margin-top: 1.5rem;">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> <?= $edicion ? 'Actualizar' : 'Registrar' ?>
                        </button>

                        <?php if ($edicion): ?>
                            <a href="incidente.php" class="btn btn-warning">
                                <i class="fas fa-times"></i> Cancelar
                            </a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>

        <div class="map-column">
            <div class="card map-card">
                <div id="map"></div>
                <div class="coords-display">
                    <input type="text" id="coordenadas_display" readonly>
                    <input type="hidden" id="coordenadas" name="coordenadas" 
                       value="<?= $edicion ? htmlspecialchars($incidente['latitud']) . ',' . htmlspecialchars($incidente['longitud']) : '' ?>">
                    <small>Haz clic en el mapa para seleccionar la ubicación</small>
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

// If editing, place marker at existing location
<?php if ($edicion && isset($incidente['latitud']) && isset($incidente['longitud'])): ?>
    marker = L.marker([<?= $incidente['latitud'] ?>, <?= $incidente['longitud'] ?>]).addTo(map);
    document.getElementById('coordenadas_display').value = '<?= $incidente['latitud'] ?>, <?= $incidente['longitud'] ?>';
<?php endif; ?>

// Handle map clicks
map.on('click', function(e) {
    if (marker) map.removeLayer(marker);
    marker = L.marker(e.latlng).addTo(map);

    const lat = e.latlng.lat.toFixed(6);
    const lon = e.latlng.lng.toFixed(6);
    const coords = lat + ',' + lon;

    document.getElementById('coordenadas').value = coords;
    document.getElementById('coordenadas_display').value = coords;

    // Use OpenStreetMap Nominatim API to reverse geocode
    fetch(`https://nominatim.openstreetmap.org/reverse?lat=${lat}&lon=${lon}&format=json&addressdetails=1`)
        .then(response => response.json())
        .then(data => {
            if (data && data.address) {
                const address = data.address;
                document.getElementById('colonia').value = address.suburb || address.neighbourhood || '';
                document.getElementById('localidad').value = address.city || address.town || address.village || '';
                document.getElementById('municipio').value = address.state || '';
            }
        })
        .catch(error => console.error('Error al obtener la ubicación:', error));
});

// Form validation
function validarFormulario() {
    if (!document.getElementById('coordenadas').value) {
        alert('Debes seleccionar una ubicación en el mapa');
        return false;
    }
    return true;
}
</script>
</body>
</html>
