<?php
header("Content-Type: application/json");
session_start();

// Verificar si se recibió el nombre del producto
if (!isset($_GET['producto'])) {
    echo json_encode(['estado' => 'error', 'mensaje' => 'Nombre de producto no especificado']);
    exit;
}

// Incluir el archivo de conexión
require_once 'conexion.php';

$nombreProducto = $_GET['producto'];

// Consultar la disponibilidad y visibilidad del producto
$stmt = $mysqli->prepare("SELECT unidades_disponibles, des_gak FROM productos WHERE nombre = ?");
$stmt->bind_param("s", $nombreProducto);
$stmt->execute();
$result = $stmt->get_result();
$producto = $result->fetch_assoc();

if (!$producto) {
    echo json_encode(['estado' => 'error', 'mensaje' => 'Producto no encontrado', 'visible' => false, 'disponibles' => 0]);
    exit;
}

// Devolver la información
echo json_encode([
    'estado' => 'exito',
    'visible' => $producto['des_gak'] == 1,
    'disponibles' => $producto['unidades_disponibles']
]);

$mysqli->close();
?>

