<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Configuración de conexión
$host = 'localhost';
$user = 'root';
$pass = '';
$db = 'panchielito';

// Conexión a la base de datos
$conexion = new mysqli($host, $user, $pass, $db);

if ($conexion->connect_error) {
    die(json_encode([
        'error' => 'Error de conexión: ' . $conexion->connect_error,
        'detalles' => 'Verifica tus credenciales de base de datos'
    ]));
}

// Validar entrada
if (!isset($_GET['producto'])) {
    die(json_encode([
        'error' => 'Nombre de producto no proporcionado',
        'solución' => 'Asegúrate de enviar el parámetro "producto" en la URL'
    ]));
}

$nombreProducto = trim($_GET['producto']);

// Consulta preparada con búsqueda flexible
$sql = "SELECT unidades_disponibles FROM productos WHERE 
        nombre = ? OR 
        nombre LIKE ? OR 
        REPLACE(nombre, ' ', '') LIKE ? OR
        REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(nombre, 'á', 'a'), 'é', 'e'), 'í', 'i'), 'ó', 'o'), 'ú', 'u') LIKE ?";

$stmt = $conexion->prepare($sql);
if (!$stmt) {
    die(json_encode([
        'error' => 'Error en la consulta SQL',
        'detalles' => $conexion->error
    ]));
}

// Preparamos parámetros para búsqueda flexible
$param1 = $nombreProducto;
$param2 = "%$nombreProducto%";
$param3 = "%".str_replace(' ', '', $nombreProducto)."%";
$param4 = "%".str_replace(['á', 'é', 'í', 'ó', 'ú'], ['a', 'e', 'i', 'o', 'u'], $nombreProducto)."%";

$stmt->bind_param("ssss", $param1, $param2, $param3, $param4);
$stmt->execute();
$resultado = $stmt->get_result();

if ($resultado->num_rows === 0) {
    echo json_encode([
        'error' => 'Producto no encontrado',
        'buscado' => $nombreProducto,
        'sugerencia' => 'Verifica el nombre exacto del producto'
    ]);
    exit;
}

$producto = $resultado->fetch_assoc();
$disponibles = (int)$producto['unidades_disponibles'];

echo json_encode([
    'disponibles' => $disponibles,
    'status' => 'success',
    'producto' => $nombreProducto
]);

$stmt->close();
$conexion->close();
?>