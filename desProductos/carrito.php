<?php
    session_start();
    
    // Verificar si el usuario está autenticado
    if (!isset($_SESSION['id'])) {
        echo json_encode(['estado' => 'error', 'mensaje' => 'Usuario no autenticado']);
        exit;
    }
    
    // Ruta al archivo JSON del carrito
    $rutaArchivo = __DIR__ . '/Panchielito/desProductoscarritos.json';
    
    // Crear el archivo si no existe
    if (!file_exists($rutaArchivo)) {
        file_put_contents($rutaArchivo, json_encode([]));
    }
    
    // Obtener los datos del carrito
    function obtenerCarritos() {
        global $rutaArchivo;
        $contenido = file_get_contents($rutaArchivo);
        return json_decode($contenido, true) ?: [];
    }
    
    // Guardar los datos del carrito
    function guardarCarritos($carritos) {
        global $rutaArchivo;
        file_put_contents($rutaArchivo, json_encode($carritos, JSON_PRETTY_PRINT));
    }
    
    // Obtener el carrito del usuario actual
    function obtenerCarritoUsuario() {
        $carritos = obtenerCarritos();
        $idUsuario = $_SESSION['id'];
        
        return isset($carritos[$idUsuario]) ? $carritos[$idUsuario] : [];
    }
    
    // Guardar el carrito del usuario actual
    function guardarCarritoUsuario($items) {
        $carritos = obtenerCarritos();
        $idUsuario = $_SESSION['id'];
        
        $carritos[$idUsuario] = $items;
        guardarCarritos($carritos);
    }
    
    // Procesar la solicitud según el método HTTP
    $metodo = $_SERVER['REQUEST_METHOD'];
    
    if ($metodo === 'GET') {
        // Devolver el carrito del usuario
        echo json_encode([
            'estado' => 'exito',
            'carrito' => obtenerCarritoUsuario()
        ]);
    } elseif ($metodo === 'POST') {
        // Recibir los datos enviados
        $datos = json_decode(file_get_contents('php://input'), true);
        
        if (!$datos) {
            echo json_encode(['estado' => 'error', 'mensaje' => 'Datos inválidos']);
            exit;
        }
        
        $accion = isset($datos['accion']) ? $datos['accion'] : '';
        
        switch ($accion) {
            case 'agregar':
                // Verificar datos necesarios
                if (!isset($datos['producto'])) {
                    echo json_encode(['estado' => 'error', 'mensaje' => 'Datos del producto incompletos']);
                    exit;
                }
                
                $carrito = obtenerCarritoUsuario();
                $producto = $datos['producto'];
                $encontrado = false;
                
                // Verificar si el producto ya existe en el carrito
                foreach ($carrito as &$item) {
                    if ($item['nombre'] === $producto['nombre']) {
                        $item['cantidad'] += $producto['cantidad'];
                        $encontrado = true;
                        break;
                    }
                }
                
                // Si no existe, agregarlo
                if (!$encontrado) {
                    $carrito[] = $producto;
                }
                
                guardarCarritoUsuario($carrito);
                
                echo json_encode([
                    'estado' => 'exito',
                    'mensaje' => 'Producto agregado al carrito',
                    'carrito' => $carrito
                ]);
                break;
                
            case 'actualizar':
                // Verificar datos necesarios
                if (!isset($datos['indice']) || !isset($datos['cantidad'])) {
                    echo json_encode(['estado' => 'error', 'mensaje' => 'Datos incompletos']);
                    exit;
                }
                
                $carrito = obtenerCarritoUsuario();
                $indice = $datos['indice'];
                $cantidad = $datos['cantidad'];
                
                // Verificar que el índice exista
                if (!isset($carrito[$indice])) {
                    echo json_encode(['estado' => 'error', 'mensaje' => 'Producto no encontrado en el carrito']);
                    exit;
                }
                
                // Actualizar la cantidad
                if ($cantidad > 0) {
                    $carrito[$indice]['cantidad'] = $cantidad;
                } else {
                    // Si la cantidad es 0 o negativa, eliminar el producto
                    array_splice($carrito, $indice, 1);
                }
                
                guardarCarritoUsuario($carrito);
                
                echo json_encode([
                    'estado' => 'exito',
                    'mensaje' => 'Carrito actualizado',
                    'carrito' => $carrito
                ]);
                break;
                
            case 'eliminar':
                // Verificar datos necesarios
                if (!isset($datos['indice'])) {
                    echo json_encode(['estado' => 'error', 'mensaje' => 'Índice no especificado']);
                    exit;
                }
                
                $carrito = obtenerCarritoUsuario();
                $indice = $datos['indice'];
                
                // Verificar que el índice exista
                if (!isset($carrito[$indice])) {
                    echo json_encode(['estado' => 'error', 'mensaje' => 'Producto no encontrado en el carrito']);
                    exit;
                }
                
                // Eliminar el producto
                array_splice($carrito, $indice, 1);
                guardarCarritoUsuario($carrito);
                
                echo json_encode([
                    'estado' => 'exito',
                    'mensaje' => 'Producto eliminado del carrito',
                    'carrito' => $carrito
                ]);
                break;
                
            case 'vaciar':
                // Vaciar el carrito
                guardarCarritoUsuario([]);
                
                echo json_encode([
                    'estado' => 'exito',
                    'mensaje' => 'Carrito vaciado',
                    'carrito' => []
                ]);
                break;
                
            default:
                echo json_encode(['estado' => 'error', 'mensaje' => 'Acción no reconocida']);
                break;
        }
    } else {
        echo json_encode(['estado' => 'error', 'mensaje' => 'Método no permitido']);
    }
?>