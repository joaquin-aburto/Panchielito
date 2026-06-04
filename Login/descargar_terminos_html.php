<?php
// Configuración de encabezados más robusta
header("Access-Control-Allow-Origin: http://127.0.0.1/Panchielito/");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: text/html; charset=UTF-8");

// Verificar si hay errores en la conexión a la base de datos
try {
    require_once 'conexion.php';
    
    // Verificar si la conexión se estableció correctamente
    if ($mysqli->connect_error) {
        throw new Exception("Error de conexión: " . $mysqli->connect_error);
    }
    
    // Configurar el manejo de errores de la base de datos
    $mysqli->report_mode = MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT;
    
    // Validar y sanitizar el ID de entrada
    $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    
    // Obtener los términos y condiciones de la base de datos
    $query = "SELECT nombre, descripcion FROM terminos_condiciones WHERE id = ?";
    $stmt = $mysqli->prepare($query);
    
    if (!$stmt) {
        throw new Exception("Error al preparar la consulta: " . $mysqli->error);
    }
    
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $terminos = $result->fetch_assoc();
    } else {
        // Si no se encuentra el registro, intentar obtener el más reciente
        $query = "SELECT nombre, descripcion FROM terminos_condiciones ORDER BY id DESC LIMIT 1";
        $result = $mysqli->query($query);
        
        if ($result && $result->num_rows > 0) {
            $terminos = $result->fetch_assoc();
        } else {
            $terminos = [
                'nombre' => 'Términos y Condiciones',
                'descripcion' => 'No se encontraron términos y condiciones en la base de datos.'
            ];
        }
    }
    
    $stmt->close();
    
} catch (Exception $e) {
    // Manejar errores de manera más robusta
    error_log("Error en términos y condiciones: " . $e->getMessage());
    
    // Mostrar un mensaje de error amigable
    die("<h1>Error al cargar los términos y condiciones</h1>
        <p>Por favor, recarga la página o intenta nuevamente más tarde.</p>
        <p>Detalle del error: " . htmlspecialchars($e->getMessage()) . "</p>
        <button onclick='window.location.reload()'>Recargar</button>");
}

// Fecha de descarga
$fecha_descarga = date("d/m/Y H:i:s");

// Procesar el texto para formatear mejor en HTML
function formatTerminosText($text) {
    $lineas = explode("\n", $text);
    $html_content = '';
    $in_list = false;

    foreach ($lineas as $linea) {
        $linea = trim($linea);
        
        // Si la línea está vacía, agregar un espacio
        if (empty($linea)) {
            if ($in_list) {
                $html_content .= '</ul>';
                $in_list = false;
            }
            $html_content .= '<br>';
            continue;
        }
        
        // Detectar encabezados (números seguidos de punto y espacio)
        if (preg_match('/^\d+\.\s+/', $linea)) {
            if ($in_list) {
                $html_content .= '</ul>';
                $in_list = false;
            }
            $html_content .= '<h3>' . htmlspecialchars($linea) . '</h3>';
            continue;
        }
        
        // Detectar elementos de lista (guiones o viñetas)
        if (preg_match('/^[-–•]\s+/', $linea)) {
            if (!$in_list) {
                $html_content .= '<ul>';
                $in_list = true;
            }
            $html_content .= '<li>' . htmlspecialchars(substr($linea, 2)) . '</li>';
            continue;
        }
        
        // Texto normal
        if ($in_list) {
            $html_content .= '</ul>';
            $in_list = false;
        }
        $html_content .= '<p>' . htmlspecialchars($linea) . '</p>';
    }
    
    // Cerrar lista si quedó abierta
    if ($in_list) {
        $html_content .= '</ul>';
    }
    
    return $html_content;
}

$html_content = formatTerminosText($terminos['descripcion']);

// Generar HTML completo
$html = <<<HTML
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{$terminos['nombre']} - Panchielito</title>
    <link href="https://fonts.googleapis.com/css2?family=Dancing+Script:wght@400..700&display=swap" rel="stylesheet">
    <link rel="icon" type="image/png" href="https://panchielito.com/img/chielito-re.png">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 0;
            color: #333;
            background-color: #f9f5f0;
        }

        header {
            background-color: rgba(255, 255, 255, 0.9);
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 20px;
            position: sticky;
            top: 0;
            z-index: 1000;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        #LogoTipo {
            max-width: 80px;
            height: auto;
        }

        .header-title {
            flex-grow: 1;
            text-align: center;
        }

        h1 {
            color: #6d0000;
            font-family: 'Dancing Script', cursive;
            font-size: 2em;
            margin: 0;
        }

        .nav-menu {
            display: flex;
            align-items: center;
        }

        .nav-menu ul {
            list-style: none;
            display: flex;
            gap: 15px;
            margin: 0;
            padding: 0;
        }

        .nav-menu ul li a {
            text-decoration: none;
            color: #6d0000;
            font-weight: bold;
            font-size: 16px;
            padding: 5px 10px;
            transition: color 0.3s;
        }

        .nav-menu ul li a:hover {
            color: #8a2e2e;
        }

        .menu-btn {
            display: none;
            background: none;
            border: none;
            color: #6d0000;
            font-size: 1.5em;
            cursor: pointer;
        }

        .container {
            max-width: 800px;
            margin: 20px auto;
            padding: 30px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
        }

        .terminos-title {
            color: #8a2e2e;
            text-align: center;
            padding-bottom: 15px;
            border-bottom: 2px solid #d4b483;
            margin-bottom: 20px;
        }

        .fecha {
            text-align: right;
            font-style: italic;
            color: #666;
            margin-bottom: 25px;
        }

        h3 {
            color: #8a2e2e;
            margin: 25px 0 15px;
        }

        p {
            margin-bottom: 15px;
            text-align: justify;
        }

        ul {
            margin: 15px 0;
            padding-left: 30px;
        }

        li {
            margin-bottom: 8px;
        }

        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 12px;
            color: #666;
            border-top: 1px solid #ddd;
            padding-top: 15px;
        }

        .action-buttons {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin-top: 30px;
        }

        .action-btn {
            padding: 10px 25px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
            transition: all 0.3s;
        }

        .print-btn {
            background-color: #8a2e2e;
            color: white;
        }

        .close-btn {
            background-color: #d4b483;
            color: #5c2e2e;
        }

        .action-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }

        @media (max-width: 768px) {
            header {
                flex-wrap: wrap;
                padding: 10px;
            }

            .header-title {
                order: 2;
                width: 100%;
                margin: 10px 0;
            }

            .nav-menu {
                order: 3;
                width: 100%;
            }

            .nav-menu ul {
                flex-direction: column;
                display: none;
                width: 100%;
            }

            .nav-menu ul.active {
                display: flex;
            }

            .menu-btn {
                display: block;
            }

            .container {
                padding: 20px;
                margin: 10px;
            }
        }

        @media print {
            .no-print {
                display: none;
            }
            header {
                display: none;
            }
            .container {
                border: none;
                box-shadow: none;
                padding: 0;
                margin: 0;
            }
        }
    </style>
</head>
<body>
    <header>
        <a href="https://panchielito.com/">
            <img src="https://panchielito.com/img/Chielito.png" id="LogoTipo" alt="Logo de Panchielito">
        </a>
        <div class="header-title">
            <h1>Panchielito</h1>
        </div>
        <nav class="nav-menu">
            <button class="menu-btn" onclick="toggleMenu()">☰</button>
            <ul id="navList">
                <li><a href="https://panchielito.com/Login/">Volver</a></li>
            </ul>
        </nav>
    </header>

    <div class="container">
        <h1 class="terminos-title">{$terminos['nombre']}</h1>
        <div class="fecha">Fecha de descarga: {$fecha_descarga}</div>
        
        {$html_content}
        
        <div class="footer">
            <p>Este documento es una copia de los términos y condiciones de Panchielito.</p>
        </div>
        
        <div class="action-buttons no-print">
            <button class="action-btn print-btn" onclick="window.print();">Imprimir / Guardar como PDF</button>
            <button class="action-btn close-btn" onclick="window.close();">Cerrar</button>
        </div>
    </div>

    <script>
        function toggleMenu() {
            const navList = document.getElementById('navList');
            navList.classList.toggle('active');
        }
    </script>
</body>
</html>
HTML;

echo $html;

$mysqli->close();
?>