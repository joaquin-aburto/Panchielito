<?php
header("Content-Type: application/json");
session_start();

// Verificar si el usuario está autenticado
if (!isset($_SESSION['id'])) {
    echo json_encode(['estado' => 'error', 'mensaje' => 'Usuario no autenticado']);
    exit;
}

// Incluir el archivo de conexión
require_once 'conexion.php';

// Ruta al archivo JSON del carrito
$rutaArchivo = __DIR__ . '../desProductoscarritos.json';

// Verificar si el archivo existe
if (!file_exists($rutaArchivo)) {
    echo json_encode(['estado' => 'error', 'mensaje' => 'Archivo del carrito no encontrado']);
    exit;
}

// Obtener los datos del carrito
$contenido = file_get_contents($rutaArchivo);
$carritos = json_decode($contenido, true) ?: [];
$userId = $_SESSION['id'];
$carrito = isset($carritos[$userId]) ? $carritos[$userId] : [];

// Verificar disponibilidad de cada producto
$productosNoDisponibles = [];
foreach ($carrito as $index => $item) {
    $stmt = $mysqli->prepare("SELECT des_gak, unidades_disponibles FROM productos WHERE nombre = ?");
    $stmt->bind_param("s", $item['nombre']);
    $stmt->execute();
    $result = $stmt->get_result();
    $producto = $result->fetch_assoc();
    
    if (!$producto) {
        $productosNoDisponibles[] = [
            'index' => $index,
            'nombre' => $item['nombre'],
            'motivo' => 'no_existe'
        ];
    } else if ($producto['des_gak'] != 1) {
        $productosNoDisponibles[] = [
            'index' => $index,
            'nombre' => $item['nombre'],
            'motivo' => 'no_visible'
        ];
    } else if ($producto['unidades_disponibles'] < $item['cantidad']) {
        $productosNoDisponibles[] = [
            'index' => $index,
            'nombre' => $item['nombre'],
            'solicitado' => $item['cantidad'],
            'disponible' => $producto['unidades_disponibles'],
            'motivo' => 'stock_insuficiente'
        ];
    }
}

// Devolver el resultado
echo json_encode([
    'estado' => 'exito',
    'productosNoDisponibles' => $productosNoDisponibles
]);

$mysqli->close();
?>
