<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Content-Type: application/json');

// Configuración de la base de datos
define('DB_HOST', 'localhost');
define('DB_USER', 'u914164434_Joaquin');
define('DB_PASS', 'Thejoaquin20/');
define('DB_NAME', 'u914164434_Panchielito');

// Conexión a la base de datos
$conexion = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conexion->connect_error) {
    error_log("Error de conexión a la base de datos: " . $conexion->connect_error);
    echo json_encode([
        "estado" => "error",
        "mensaje" => "Error de conexión a la base de datos"
    ]);
    exit;
}

// Validar que se hayan enviado datos
if (!isset($_POST['accion'], $_POST['usuarioId'], $_POST['idProducto'])) {
    error_log("Faltan datos requeridos en la solicitud");
    echo json_encode([
        "estado" => "error",
        "mensaje" => "Faltan datos requeridos"
    ]);
    exit;
}

// Recibir los datos
$accion = $_POST['accion'];
$usuarioId = $_POST['usuarioId'];
$idProducto = intval($_POST['idProducto']);
$cambio = isset($_POST['cambio']) ? intval($_POST['cambio']) : 0;
$nombreProducto = isset($_POST['nombreProducto']) ? trim($_POST['nombreProducto']) : '';

// Ruta del archivo donde se guarda el carrito
$archivo = '../desProductoscarritos.json';

// Verificar permisos del archivo
if (!file_exists($archivo)) {
    // Intentar crear el archivo si no existe
    if (!file_put_contents($archivo, json_encode([]))) {
        error_log("No se pudo crear el archivo $archivo");
        echo json_encode([
            "estado" => "error",
            "mensaje" => "Error en el sistema de carrito"
        ]);
        exit;
    }
} elseif (!is_writable($archivo)) {
    error_log("El archivo $archivo no tiene permisos de escritura");
    echo json_encode([
        "estado" => "error",
        "mensaje" => "Error en el sistema de carrito"
    ]);
    exit;
}

// Cargar carrito actual
$contenido = file_get_contents($archivo);
if ($contenido === false) {
    error_log("No se pudo leer el archivo $archivo");
    echo json_encode([
        "estado" => "error",
        "mensaje" => "Error al cargar el carrito"
    ]);
    exit;
}

$carrito = json_decode($contenido, true) ?? [];
if (json_last_error() !== JSON_ERROR_NONE) {
    error_log("Error al decodificar JSON: " . json_last_error_msg());
    $carrito = [];
}

// Verificar si existe el usuario
if (!isset($carrito[$usuarioId])) {
    $carrito[$usuarioId] = [];
}

// Verificar disponibilidad si es un aumento
if ($accion === 'cambiar' && $cambio > 0 && !empty($nombreProducto)) {
    $stmt = $conexion->prepare("SELECT unidades_disponibles FROM productos WHERE nombre LIKE ?");
    if (!$stmt) {
        error_log("Error en preparación de consulta: " . $conexion->error);
    } else {
        $nombreBusqueda = "%" . $nombreProducto . "%";
        $stmt->bind_param("s", $nombreBusqueda);
        $stmt->execute();
        $resultado = $stmt->get_result();
        
        if ($resultado->num_rows > 0) {
            $producto = $resultado->fetch_assoc();
            $disponibles = (int)$producto['unidades_disponibles'];
            
            // Obtener cantidad actual en carrito
            $cantidadEnCarrito = isset($carrito[$usuarioId][$idProducto]['cantidad']) ? 
                                 $carrito[$usuarioId][$idProducto]['cantidad'] : 0;
            
            if (($cantidadEnCarrito + $cambio) > $disponibles) {
                echo json_encode([
                    "estado" => "error",
                    "mensaje" => "Solo quedan {$disponibles} unidades disponibles",
                    "disponibles" => $disponibles
                ]);
                exit;
            }
        }
    }
}

// Modificar según la acción
switch ($accion) {
    case 'eliminar':
        if (isset($carrito[$usuarioId][$idProducto])) {
            unset($carrito[$usuarioId][$idProducto]);
            // Reindexar el array para evitar huecos
            $carrito[$usuarioId] = array_values($carrito[$usuarioId]);
        }
        break;

    case 'cambiar':
        if (!isset($carrito[$usuarioId][$idProducto])) {
            echo json_encode([
                "estado" => "error",
                "mensaje" => "Producto no encontrado en carrito"
            ]);
            exit;
        }
        
        $nuevaCantidad = $carrito[$usuarioId][$idProducto]['cantidad'] + $cambio;
        
        if ($nuevaCantidad <= 0) {
            unset($carrito[$usuarioId][$idProducto]);
            $carrito[$usuarioId] = array_values($carrito[$usuarioId]);
        } else {
            $carrito[$usuarioId][$idProducto]['cantidad'] = $nuevaCantidad;
        }
        break;

    default:
        echo json_encode([
            "estado" => "error",
            "mensaje" => "Acción no reconocida"
        ]);
        exit;
}

// Guardar el carrito actualizado
$resultado = file_put_contents($archivo, json_encode($carrito, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

if ($resultado !== false) {
    echo json_encode([
        "estado" => "exito",
        "mensaje" => "Carrito actualizado correctamente",
        "carrito" => $carrito[$usuarioId] ?? []
    ]);
} else {
    error_log("Error al escribir en el archivo $archivo");
    echo json_encode([
        "estado" => "error",
        "mensaje" => "No se pudo guardar el carrito"
    ]);
}

$conexion->close();
?>