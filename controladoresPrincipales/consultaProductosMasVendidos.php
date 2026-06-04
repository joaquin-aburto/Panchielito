<?php
header("Content-Type: application/json");

// Incluimos la conexión a la base de datos
require_once 'conexion.php';

// Consulta para obtener los productos más vendidos que sean visibles (des_gak = 1)
$query = "
    SELECT p.id, p.nombre, p.precio, p.imagenes, p.categoria, SUM(dv.cantidad) as total_vendido
    FROM detalles_venta dv
    JOIN productos p ON dv.producto_id = p.id
    WHERE p.des_gak = 1
    GROUP BY p.id, p.nombre, p.precio, p.imagenes, p.categoria
    ORDER BY total_vendido DESC
    LIMIT 4
";

$result = $mysqli->query($query);

$productos = [];
if($result){
    while($row = $result->fetch_assoc()){
        // Convertir el campo JSON de imágenes a array PHP
        $row['imagenes'] = json_decode($row['imagenes'], true);
        $productos[] = $row;
    }
}

echo json_encode($productos);
$mysqli->close();
?>
