<?php 
    header('Content-Type: application/xml; charset=UTF-8');
    $directorio = __DIR__ . '/xmlsComentarios';
    
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $producto = isset($_GET['producto']) ? $_GET['producto'] : '';
        $productoLimpiado = preg_replace('/[^a-zA-Z0-9]/', '', $producto);
        $nombreArchivo = $directorio . '/comentarios-' . $productoLimpiado . '.xml';
        
        if (file_exists($nombreArchivo)) {
            echo file_get_contents($nombreArchivo);
        } else {
            // Si no existe, se devuelve un XML vacío
            echo '<?xml version="1.0" encoding="UTF-8"?><comentarios></comentarios>';
        }
        exit;
    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $datos = json_decode(file_get_contents('php://input'), true);
        if (!$datos) {
            echo '<?xml version="1.0" encoding="UTF-8"?><respuesta><estado>error</estado><mensaje>No se recibió JSON</mensaje></respuesta>';
            exit;
        }
        if (!isset($datos['accion'])) {
            echo '<?xml version="1.0" encoding="UTF-8"?><respuesta><estado>error</estado><mensaje>Acción no especificada</mensaje></respuesta>';
            exit;
        }
        
        $accion = $datos['accion'];
        // Para todas las acciones, requerimos al menos estos campos:
        if (!isset($datos['producto'], $datos['usuario'], $datos['id'])) {
            echo '<?xml version="1.0" encoding="UTF-8"?><respuesta><estado>error</estado><mensaje>Faltan datos básicos</mensaje></respuesta>';
            exit;
        }
        
        $producto = $datos['producto'];
        $productoLimpiado = preg_replace('/[^a-zA-Z0-9]/', '', $producto);
        $nombreArchivo = $directorio . '/comentarios-' . $productoLimpiado . '.xml';
        
        if (!file_exists($directorio)) {
            mkdir($directorio, 0755, true);
        }
        
        // Si el archivo no existe, se crea automáticamente con la estructura base
        if (!file_exists($nombreArchivo)) {
            $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><comentarios></comentarios>');
        } else {
            $xml = simplexml_load_file($nombreArchivo);
        }
        
        // Acción agregar
        if ($accion === 'agregar') {
            if (!isset($datos['texto'], $datos['calificacion'])) {
                echo '<?xml version="1.0" encoding="UTF-8"?><respuesta><estado>error</estado><mensaje>Faltan datos para agregar</mensaje></respuesta>';
                exit;
            }
            $nuevoComentario = $xml->addChild('comentario');
            $nuevoComentario->addChild('usuario', htmlspecialchars($datos['usuario']));
            $nuevoComentario->addChild('usuario_id', htmlspecialchars($datos['id']));
            $nuevoComentario->addChild('texto', htmlspecialchars($datos['texto']));
            $nuevoComentario->addChild('fecha', date('Y-m-d H:i:s'));
            $nuevoComentario->addChild('calificacion', (int)$datos['calificacion']);
            $xml->asXML($nombreArchivo);
            echo '<?xml version="1.0" encoding="UTF-8"?><respuesta><estado>exito</estado></respuesta>';
            exit;
        }
        // Acción editar
        elseif ($accion === 'editar') {
            if (!isset($datos['fecha'], $datos['nuevoTexto'], $datos['nuevaCalificacion'])) {
                echo '<?xml version="1.0" encoding="UTF-8"?><respuesta><estado>error</estado><mensaje>Faltan datos para editar</mensaje></respuesta>';
                exit;
            }
            $fechaBuscada = $datos['fecha'];
            $encontrado = false;
            foreach ($xml->comentario as $comentario) {
                // Verificamos que coincida el usuario_id y la fecha (clave única)
                if ($comentario->usuario_id == htmlspecialchars($datos['id']) && $comentario->fecha == $fechaBuscada) {
                    $comentario->texto = htmlspecialchars($datos['nuevoTexto']);
                    $comentario->calificacion = (int)$datos['nuevaCalificacion'];
                    $encontrado = true;
                    break;
                }
            }
            if ($encontrado) {
                $xml->asXML($nombreArchivo);
                echo '<?xml version="1.0" encoding="UTF-8"?><respuesta><estado>exito</estado></respuesta>';
            } else {
                echo '<?xml version="1.0" encoding="UTF-8"?><respuesta><estado>error</estado><mensaje>Comentario no encontrado</mensaje></respuesta>';
            }
            exit;
        }
        // Acción eliminar
        elseif ($accion === 'eliminar') {
            if (!isset($datos['fecha'])) {
                echo '<?xml version="1.0" encoding="UTF-8"?><respuesta><estado>error</estado><mensaje>Faltan datos para eliminar</mensaje></respuesta>';
                exit;
            }
            $fechaBuscada = $datos['fecha'];
            $encontrado = false;
            foreach ($xml->comentario as $comentario) {
                if ($comentario->usuario_id == htmlspecialchars($datos['id']) && $comentario->fecha == $fechaBuscada) {
                    // Elimina el comentario mediante DOM
                    $dom = dom_import_simplexml($comentario);
                    $dom->parentNode->removeChild($dom);
                    $encontrado = true;
                    break;
                }
            }
            if ($encontrado) {
                $xml->asXML($nombreArchivo);
                echo '<?xml version="1.0" encoding="UTF-8"?><respuesta><estado>exito</estado></respuesta>';
            } else {
                echo '<?xml version="1.0" encoding="UTF-8"?><respuesta><estado>error</estado><mensaje>Comentario no encontrado</mensaje></respuesta>';
            }
            exit;
        } else {
            echo '<?xml version="1.0" encoding="UTF-8"?><respuesta><estado>error</estado><mensaje>Acción desconocida</mensaje></respuesta>';
            exit;
        }
    }
?>





