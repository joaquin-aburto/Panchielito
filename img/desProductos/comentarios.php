<?php
    header('Content-Type: application/xml; charset=UTF-8');

    // Ruta del directorio donde se guardarán los XML
    $directorio = __DIR__ . '/desProductos/xmlsComentarios';

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        // Para GET, obtenemos el nombre del producto de la URL
        $producto = isset($_GET['producto']) ? $_GET['producto'] : '';
        $productoLimpiado = preg_replace('/[^a-zA-Z0-9]/', '', $producto);
        $nombreArchivo = $directorio . '/comentarios-' . $productoLimpiado . '.xml';
        
        if (file_exists($nombreArchivo)) {
            echo file_get_contents($nombreArchivo);
        } else {
            echo '<?xml version="1.0" encoding="UTF-8"?><comentarios></comentarios>';
        }
        exit;
    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Para POST, leemos el JSON enviado en el body
        $datos = json_decode(file_get_contents('php://input'), true);
        
        if (!$datos) {
            echo '<?xml version="1.0" encoding="UTF-8"?><respuesta><estado>error</estado><mensaje>No se recibió JSON</mensaje></respuesta>';
            exit;
        }
        
        // Verificamos que la acción sea 'agregar'
        if (!isset($datos['accion']) || $datos['accion'] !== 'agregar') {
            echo '<?xml version="1.0" encoding="UTF-8"?><respuesta><estado>error</estado><mensaje>Acción inválida</mensaje></respuesta>';
            exit;
        }
        
        // Verificamos que lleguen los datos requeridos
        if (!isset($datos['producto'], $datos['usuario'], $datos['texto'], $datos['calificacion'])) {
            echo '<?xml version="1.0" encoding="UTF-8"?><respuesta><estado>error</estado><mensaje>Faltan datos</mensaje></respuesta>';
            exit;
        }
        
        // Usamos el producto del JSON para construir el nombre del archivo
        $producto = $datos['producto'];
        $productoLimpiado = preg_replace('/[^a-zA-Z0-9]/', '', $producto);
        $nombreArchivo = $directorio . '/comentarios-' . $productoLimpiado . '.xml';
        
        // Creamos el directorio si no existe
        if (!file_exists($directorio)) {
            mkdir($directorio, 0755, true);
        }
        
        // Cargamos el XML existente o creamos uno nuevo
        if (file_exists($nombreArchivo)) {
            $xml = simplexml_load_file($nombreArchivo);
        } else {
            $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><comentarios></comentarios>');
        }
        
        // Agregamos el nuevo comentario
        $nuevoComentario = $xml->addChild('comentario');
        $nuevoComentario->addChild('usuario', htmlspecialchars($datos['usuario']));
        $nuevoComentario->addChild('texto', htmlspecialchars($datos['texto']));
        $nuevoComentario->addChild('fecha', date('Y-m-d H:i:s'));
        $nuevoComentario->addChild('calificacion', (int)$datos['calificacion']);
        
        // Guardamos el archivo XML
        $xml->asXML($nombreArchivo);
        
        // Respuesta en XML indicando éxito
        echo '<?xml version="1.0" encoding="UTF-8"?><respuesta><estado>exito</estado></respuesta>';
        exit;
    }
?>


