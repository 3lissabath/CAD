<?php
include('db.php'); 

if (!isset($conn)) {
    die("Error: No se pudo conectar a la base de datos.");
}

$sql = "SELECT * FROM usuarios"; 

$result = $conn->query($sql);

if ($result === false) {
    die("Error en la consulta: " . $conn->error);
} else {
    echo "Consulta exitosa. Número de filas: " . $result->num_rows;
    while ($row = $result->fetch_assoc()) {
        print_r($row); 
    }
}

$conn->close();
?>