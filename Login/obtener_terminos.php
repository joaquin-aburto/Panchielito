<?php
    header("Access-Control-Allow-Origin: https://panchielito.com");
    header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
    header("Access-Control-Allow-Headers: Content-Type, Authorization");
    header('Content-Type: application/json');
    
    require_once 'conexion.php';
    
    // Obtener los términos y condiciones de la base de datos
    $sql = "SELECT id, nombre, descripcion FROM terminos_condiciones ORDER BY id DESC LIMIT 1";
    $result = $mysqli->query($sql);
    
    if ($result && $result->num_rows > 0) {
        $terminos = $result->fetch_assoc();
        echo json_encode($terminos);
    } else {
        echo json_encode([
            'id' => 0,
            'nombre' => 'Términos y Condiciones',
            'descripcion' => 'No se encontraron términos y condiciones.'
        ]);
    }
    
    $mysqli->close();
?>