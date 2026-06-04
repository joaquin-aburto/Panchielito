<?php
session_start();
if (!isset($_SESSION['usuario']) || $_SESSION['usuario'] !== 'admin@gmail.com') {
    header('Location: https://panchielito.com/Login/');
    exit;
}
require_once '../controladoresPrincipales/conexion.php';

// Configuración de límites
$max_file_size = 5 * 1024 * 1024; // 5MB máximo por imagen
$allowed_types = ['image/jpeg', 'image/png'];

// Función para manejar la subida de imágenes
function procesarImagenes($files, $max_size, $allowed_types) {
    $imagenes_array = [];
    $errors = [];
    
    // Directorio donde se guardarán las imágenes
    $directorio_destino = "../imgsProductos/";
    
    // Verificar si el directorio existe, si no, crearlo
    if (!file_exists($directorio_destino)) {
        mkdir($directorio_destino, 0777, true);
    }
    
    // Procesar cada imagen subida
    for ($i = 0; $i < count($files['name']); $i++) {
        if ($files['error'][$i] === 0) {
            // Verificar tipo de archivo
            $file_type = mime_content_type($files['tmp_name'][$i]);
            if (!in_array($file_type, $allowed_types)) {
                $errors[] = "El archivo {$files['name'][$i]} no es un tipo de imagen válido (JPEG o PNG).";
                continue;
            }
            
            // Verificar tamaño del archivo
            if ($files['size'][$i] > $max_size) {
                $errors[] = "El archivo {$files['name'][$i]} excede el tamaño máximo permitido (5MB).";
                continue;
            }
            
            $nombre_archivo = $files['name'][$i];
            $extension = pathinfo($nombre_archivo, PATHINFO_EXTENSION);
            
            // Generar un nombre único
            $nombre_base = pathinfo($nombre_archivo, PATHINFO_FILENAME);
            $nombre_base = preg_replace("/[^a-zA-Z0-9]/", "_", $nombre_base);
            $nombre_archivo_final = $nombre_base . "_" . uniqid() . "." . $extension;
            
            $ruta_destino = $directorio_destino . $nombre_archivo_final;
            
            // Mover el archivo
            if (move_uploaded_file($files['tmp_name'][$i], $ruta_destino)) {
                $imagenes_array[] = "../imgsProductos/" . $nombre_archivo_final;
            }
        } elseif ($files['error'][$i] !== UPLOAD_ERR_NO_FILE) {
            $errors[] = "Error al subir el archivo {$files['name'][$i]}. Código de error: {$files['error'][$i]}";
        }
    }
    
    return [
        'imagenes' => json_encode($imagenes_array),
        'errors' => $errors
    ];
}

// Si vino POST, procesar la creación
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $errors = [];
    
    $nombre = $_POST['nombre'];
    $descripcion = $_POST['descripcion'];
    $precio = floatval($_POST['precio']);
    $unidades_disponibles = intval($_POST['unidades_disponibles']);
    $categoria = $_POST['categoria'];
    
    // Validar que se hayan subido imágenes
    if (empty($_FILES['imagenes']['name'][0])) {
        $errors[] = "Debes subir al menos una imagen del producto.";
    } else {
        $resultado_imagenes = procesarImagenes($_FILES['imagenes'], $max_file_size, $allowed_types);
        $imagenes = $resultado_imagenes['imagenes'];
        $errors = array_merge($errors, $resultado_imagenes['errors']);
        
        // Verificar que al menos una imagen se subió correctamente
        if (json_decode($imagenes, true) === []) {
            $errors[] = "No se pudo subir ninguna imagen válida.";
        }
    }
    
    $des_gak = isset($_POST['des_gak']) ? (int)$_POST['des_gak'] : 1;
    
    // Si no hay errores, insertar en la base de datos
    if (empty($errors)) {
        $stmt = $mysqli->prepare("
            INSERT INTO productos (nombre, descripcion, precio, unidades_disponibles, categoria, imagenes, des_gak)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param("ssdissi", $nombre, $descripcion, $precio, $unidades_disponibles, $categoria, $imagenes, $des_gak);
        $stmt->execute();
        $stmt->close();
        
        header('Location: productos_admin.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Agregar Nuevo Producto</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://fonts.googleapis.com/css2?family=Dancing+Script:wght@400..700&display=swap" rel="stylesheet">
    <link rel="icon" type="image/png" href="img/chielito-re.png">
    <style>
        h1 {
            font-family: 'Dancing Script', cursive;
        }
        textarea {
            width: 100%;
            min-height: 150px;
            padding: 8px;
            margin-top: 4px;
            box-sizing: border-box;
        }
        .help-text {
            font-size: 0.85em;
            color: #666;
            margin-top: 2px;
        }
        .error {
            color: #d32f2f;
            background-color: #ffebee;
            padding: 10px;
            margin: 10px 0;
            border-radius: 4px;
            border-left: 4px solid #d32f2f;
        }
        #file-warnings {
            color: #d32f2f;
            margin-top: 5px;
        }
        
        /* Estilos para el select de visibilidad */
        select[name="des_gak"] {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            background-color: #fff;
            font-size: 14px;
            color: #333;
            cursor: pointer;
            transition: all 0.3s ease;
            width: 100%;
            max-width: 300px;
            margin-top: 5px;
            appearance: none;
            -webkit-appearance: none;
            -moz-appearance: none;
            background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6 9 12 15 18 9'%3e%3c/polyline%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right 10px center;
            background-size: 15px;
        }
        
        select[name="des_gak"]:focus {
            outline: none;
            border-color: #4a90e2;
            box-shadow: 0 0 0 2px rgba(74, 144, 226, 0.2);
        }
        
        /* Estilos para las opciones */
        select[name="des_gak"] option {
            padding: 8px;
        }
        
        /* Estilo para la opción "Visible" */
        select[name="des_gak"] option[value="1"] {
            color: #2e7d32;
            font-weight: 500;
        }
        
        /* Estilo para la opción "No visible" */
        select[name="des_gak"] option[value="0"] {
            color: #c62828;
            font-weight: 500;
        }
        
        /* Contenedor del label para mejor espaciado */
        label:has(select[name="des_gak"]) {
            display: block;
            margin-bottom: 15px;
        }
    </style>
</head>
<body>
    <h1>Agregar Nuevo Producto</h1>
    
    <?php if (!empty($errors)): ?>
        <div class="error">
            <strong>Errores encontrados:</strong>
            <ul>
                <?php foreach ($errors as $error): ?>
                    <li><?= htmlspecialchars($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>
    
    <form method="post" enctype="multipart/form-data" id="product-form">
        <label>Nombre:
            <input type="text" name="nombre" required>
        </label>
        <label>Descripción:
            <textarea name="descripcion"></textarea>
        </label>
        <label>Precio:
            <input type="number" step="0.01" name="precio" required>
        </label>
        <label>Unidades disponibles:
            <input type="number" name="unidades_disponibles" value="0" required>
        </label>
        <label>Categoría:
            <input type="text" name="categoria" required>
        </label>
        
        <label>Visible en galería:
            <select name="des_gak">
                <option value="1" selected>Sí - Mostrar en galería</option>
                <option value="0">No - Ocultar de galería</option>
            </select>
        </label>
        
        <label>Imágenes del producto (máx. 5MB c/u):
            <input type="file" name="imagenes[]" id="file-input" multiple accept="image/jpeg, image/png" required>
            <div id="file-warnings"></div>
        </label>
        <p class="help-text">Puedes seleccionar múltiples imágenes. Formatos aceptados: JPG, PNG. Máximo 5MB por imagen.</p>
        
        <button type="submit">Crear Producto</button>
        <a href="productos_admin.php" style="margin-left: 10px; text-decoration: none;">Cancelar</a>
    </form>

    <script>
        document.getElementById('file-input').addEventListener('change', function(e) {
            const maxSize = 5 * 1024 * 1024; // 5MB
            const warningsDiv = document.getElementById('file-warnings');
            warningsDiv.innerHTML = '';
            
            let hasInvalidFiles = false;
            let validFiles = [];
            
            // Verificar cada archivo seleccionado
            for (let i = 0; i < this.files.length; i++) {
                const file = this.files[i];
                
                // Verificar tamaño
                if (file.size > maxSize) {
                    warningsDiv.innerHTML += `<div>El archivo "${file.name}" (${(file.size/1024/1024).toFixed(2)}MB) excede el límite de 5MB y será removido.</div>`;
                    hasInvalidFiles = true;
                } else {
                    validFiles.push(file);
                }
            }
            
            // Si hay archivos inválidos, actualizar el input de archivos
            if (hasInvalidFiles) {
                // Crear un nuevo DataTransfer para los archivos válidos
                const dataTransfer = new DataTransfer();
                validFiles.forEach(file => dataTransfer.items.add(file));
                
                // Reemplazar los archivos en el input
                this.files = dataTransfer.files;
                
                // Mostrar mensaje adicional
                if (validFiles.length === 0) {
                    warningsDiv.innerHTML += '<div>No hay archivos válidos seleccionados.</div>';
                } else {
                    warningsDiv.innerHTML += `<div>Archivos válidos: ${validFiles.length}/${validFiles.length + (this.files.length - validFiles.length)}</div>`;
                }
            }
        });

        function validateForm() {
            const fileInput = document.getElementById('file-input');
            const maxSize = 5 * 1024 * 1024; // 5MB
            
            // Verificar que al menos un archivo esté seleccionado
            if (fileInput.files.length === 0) {
                alert('Debes seleccionar al menos una imagen para el producto.');
                return false;
            }
            
            // Verificar que todos los archivos sean válidos
            for (let i = 0; i < fileInput.files.length; i++) {
                if (fileInput.files[i].size > maxSize) {
                    alert(`El archivo "${fileInput.files[i].name}" aún excede el tamaño máximo de 5MB. Por favor remuévelo.`);
                    return false;
                }
            }
            
            return true;
        }
    </script>
</body>
</html>