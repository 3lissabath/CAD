<?php
session_start();
include '../db.php'; 

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $insumos = filter_input(INPUT_POST, 'insumos', FILTER_SANITIZE_STRING);
    $vehiculos = filter_input(INPUT_POST, 'vehiculos', FILTER_SANITIZE_STRING);
    $personal = filter_input(INPUT_POST, 'personal', FILTER_VALIDATE_INT);
    $estatus = filter_input(INPUT_POST, 'estatus', FILTER_SANITIZE_STRING);
    
    $errores = [];
    
    if (empty($insumos)) {
        $errores[] = "El campo Insumos es obligatorio";
    }
    
    if (empty($vehiculos)) {
        $errores[] = "Debe seleccionar la cantidad de vehículos";
    }
    
    if ($personal === false || $personal < 1) {
        $errores[] = "La cantidad de personal debe ser un número válido (mínimo 1)";
    }
    
    if (empty($estatus)) {
        $errores[] = "Debe seleccionar un estatus";
    }
    
    if (empty($errores)) {
        try {
            // Preparar la consulta SQL
            $sql = "INSERT INTO recursos (insumos, vehiculos, personal, estatus, id_usuario, fecha_registro) 
                    VALUES (?, ?, ?, ?, ?, NOW())";
            
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssisi", $insumos, $vehiculos, $personal, $estatus, $_SESSION['id_usuario']);
            
            if ($stmt->execute()) {
                $_SESSION['mensaje_exito'] = "Recurso registrado exitosamente";
                header("Location: /CAD/registrorecursos/exito.php");
                exit();
            } else {
                throw new Exception("Error al ejecutar la consulta: " . $stmt->error);
            }
        } catch (Exception $e) {
            $errores[] = "Error en la base de datos: " . $e->getMessage();
        }
    }
    
    if (!empty($errores)) {
        $_SESSION['errores'] = $errores;
        $_SESSION['datos_formulario'] = $_POST;
        header("Location: /CAD/registrorecursos/registro.php");
        exit();
    }
} else {
    header("Location: /CAD/registrorecursos/registro.php");
    exit();
}
?>