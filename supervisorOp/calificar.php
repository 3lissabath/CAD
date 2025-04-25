<?php
session_start();


if (!isset($_GET['id_llamada']) || !isset($_GET['id_operador'])) {
    header("Location: supervisorOp.php");
    exit();
}

$id_llamada = $_GET['id_llamada'];
$id_operador = $_GET['id_operador'];

require_once '../db.php';

$sql_operador = "SELECT CONCAT(nombre, ' ', apellido) as nombre_completo 
                 FROM usuarios 
                 WHERE id_usuario = ?";
$stmt_operador = $conn->prepare($sql_operador);
$stmt_operador->bind_param("i", $id_operador);
$stmt_operador->execute();
$operador = $stmt_operador->get_result()->fetch_assoc();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $calificacion = (int)$_POST['calificacion'];
    
    if ($calificacion >= 1 && $calificacion <= 10) {
        $sql_update = "UPDATE usuarios 
                       SET calificacion = ?
                       WHERE id_usuario = ?";
        
        $stmt_update = $conn->prepare($sql_update);
        $stmt_update->bind_param("ii", $calificacion, $id_operador);
        
        if ($stmt_update->execute()) {
            $_SESSION['mensaje_exito'] = "Calificación registrada exitosamente";
            header("Location: supervisor.php");
            exit();
        } else {
            $error = "Error al registrar la calificación: " . $conn->error;
        }
    } else {
        $error = "La calificación debe ser entre 1 y 10";
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Calificar Llamada | CAD</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f4f4f4;
        }
        
        .container {
            max-width: 600px;
            margin: 30px auto;
            padding: 20px;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        
        h2 {
            color: #333;
            border-bottom: 2px solid #93D8E8;
            padding-bottom: 10px;
        }
        
        .info-box {
            background-color: #f9f9f9;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
        }
        
        select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
        }
        
        .btn {
            background-color: #93D8E8;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
        }
        
        .btn:hover {
            background-color: #82c8d8;
        }
        
        .error {
            color: #e74c3c;
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Calificar Llamada</h2>
        
        <div class="info-box">
            <p><strong>ID Llamada:</strong> <?php echo htmlspecialchars($id_llamada); ?></p>
            <p><strong>Operador:</strong> <?php echo htmlspecialchars($operador['nombre_completo']); ?></p>
        </div>
        
        <form method="POST">
            <div class="form-group">
                <label for="calificacion">Seleccione una calificación (1-10):</label>
                <select id="calificacion" name="calificacion" required>
                    <option value="">-- Seleccione --</option>
                    <?php for($i = 1; $i <= 10; $i++): ?>
                    <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                    <?php endfor; ?>
                </select>
            </div>
            
            <?php if(isset($error)): ?>
            <div class="error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <button type="submit" class="btn">Guardar Calificación</button>
            <a href="supervisorOp.php" class="btn" style="background-color: #95a5a6; margin-left: 10px;">Cancelar</a>
        </form>
    </div>
</body>
</html>