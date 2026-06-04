<?php
session_start();
if (!isset($_SESSION['usuario']) || $_SESSION['usuario'] !== 'admin@gmail.com') {
    header('Location: https://panchielito.com/Login/');
    exit;
}
require_once '../controladoresPrincipales/conexion.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id <= 0) die("Producto no encontrado.");

// Función para manejar la subida de imágenes
function procesarImagenes($imagenes_actuales, $files) {
    $imagenes_array = json_decode($imagenes_actuales, true);
    if (!is_array($imagenes_array)) {
        $imagenes_array = [];
    }
    
    // Directorio donde se guardarán las imágenes
    $directorio_destino = "../imgsProductos/";
    
    // Verificar si el directorio existe, si no, crearlo
    if (!file_exists($directorio_destino)) {
        mkdir($directorio_destino, 0777, true);
    }
    
    // Procesar cada imagen subida
    for ($i = 0; $i < count($files['name']); $i++) {
        if ($files['error'][$i] === 0) {
            $nombre_archivo = $files['name'][$i];
            $extension = pathinfo($nombre_archivo, PATHINFO_EXTENSION);
            
            // Generar un nombre único para evitar sobrescribir archivos
            $nombre_base = pathinfo($nombre_archivo, PATHINFO_FILENAME);
            $nombre_base = preg_replace("/[^a-zA-Z0-9]/", "_", $nombre_base); // Reemplazar caracteres no alfanuméricos
            $nombre_archivo_final = $nombre_base . "_" . uniqid() . "." . $extension;
            
            $ruta_destino = $directorio_destino . $nombre_archivo_final;
            
            // Mover el archivo al directorio de destino
            if (move_uploaded_file($files['tmp_name'][$i], $ruta_destino)) {
                // Agregar la ruta relativa al array de imágenes
                $imagenes_array[] = "../imgsProductos/" . $nombre_archivo_final;
            }
        }
    }
    
    return json_encode($imagenes_array);
}

// Si vino POST, procesar la actualización
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = $_POST['nombre'];
    $descripcion = $_POST['descripcion'];
    $precio = floatval($_POST['precio']);
    $unidades_disponibles = intval($_POST['unidades_disponibles']);
    $categoria = $_POST['categoria'];
    
    // Obtener las imágenes actuales
    $stmt = $mysqli->prepare("SELECT imagenes FROM productos WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $producto = $result->fetch_assoc();
    $stmt->close();
    
    $imagenes_actuales = $producto['imagenes'];
    
    // Procesar las imágenes si se han subido nuevas
    if (isset($_FILES['nuevas_imagenes']) && $_FILES['nuevas_imagenes']['name'][0] != '') {
        $imagenes_actualizadas = procesarImagenes($imagenes_actuales, $_FILES['nuevas_imagenes']);
    } else {
        $imagenes_actualizadas = $imagenes_actuales;
    }
    
    // Eliminar imágenes seleccionadas
    if (isset($_POST['eliminar_imagenes']) && !empty($_POST['eliminar_imagenes'])) {
        $imagenes_array = json_decode($imagenes_actualizadas, true);
        foreach ($_POST['eliminar_imagenes'] as $indice) {
            if (isset($imagenes_array[$indice])) {
                // Intentar eliminar el archivo físico (opcional)
                $ruta_archivo = $imagenes_array[$indice];
                if (file_exists($ruta_archivo)) {
                    unlink($ruta_archivo);
                }
                // Eliminar del array
                unset($imagenes_array[$indice]);
            }
        }
        // Reindexar el array
        $imagenes_array = array_values($imagenes_array);
        $imagenes_actualizadas = json_encode($imagenes_array);
    }
    
    // Procesar el valor de des_gak
    $des_gak = isset($_POST['des_gak']) ? (int)$_POST['des_gak'] : 1;
    
    // Actualizar el producto en la base de datos
    $stmt = $mysqli->prepare("
        UPDATE productos
        SET nombre=?, descripcion=?, precio=?, unidades_disponibles=?, categoria=?, imagenes=?, des_gak=?
        WHERE id=?
    ");
    $stmt->bind_param("ssdissii", $nombre, $descripcion, $precio, $unidades_disponibles, $categoria, $imagenes_actualizadas, $des_gak, $id);
    $stmt->execute();
    $stmt->close();
    
    header('Location: productos_admin.php');
    exit;
}

// Si no es POST, traer datos para el formulario
$stmt = $mysqli->prepare("
    SELECT nombre, descripcion, precio, unidades_disponibles, categoria, imagenes, des_gak
    FROM productos WHERE id=?
");
$stmt->bind_param("i", $id);
$stmt->execute();
$res = $stmt->get_result();
$producto = $res->fetch_assoc();
$stmt->close();
if (!$producto) die("Producto no encontrado.");

// Decodificar el JSON de imágenes
$imagenes = json_decode($producto['imagenes'], true);
if (!is_array($imagenes)) {
    $imagenes = [];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Editar Producto #<?=$id?></title>
    <link rel="stylesheet" href="style.css">
    <link href="https://fonts.googleapis.com/css2?family=Dancing+Script:wght@400..700&display=swap" rel="stylesheet">
    <link rel="icon" type="image/png" href="img/chielito-re.png">
    <style>
        .image-preview-container {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 10px;
        }
        .image-preview {
            position: relative;
            width: 100px;
            height: 100px;
        }
        .image-preview img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 4px;
        }
        .image-preview .delete-checkbox {
            position: absolute;
            top: 5px;
            right: 5px;
        }
        .file-input-container {
            margin-top: 10px;
        }
        textarea {
            width: 100%;
            min-height: 150px;
            padding: 8px;
            margin-top: 4px;
            box-sizing: border-box;
        }
        label {
    display: block;
    margin-bottom: 12px;
    color: #333;
    font-size: 14px;
    font-weight: 500;
}

select {
    min-width: 200px;
    padding: 8px 12px;
    border: 1px solid #ddd;
    border-radius: 4px;
    background-color: white;
    color: #333;
    font-size: 14px;
    cursor: pointer;
}

select:focus {
    border-color: #6d0000;
    outline: none;
}
    </style>
</head>
<body>
    <h1>Editar Producto #<?=$id?></h1>
    <form method="post" enctype="multipart/form-data">
        <label>Nombre:
            <input type="text" name="nombre" value="<?=htmlspecialchars($producto['nombre'])?>" required>
        </label>
        <label>Descripción:
            <textarea name="descripcion"><?=htmlspecialchars($producto['descripcion'])?></textarea>
        </label>
        <label>Precio:
            <input type="number" step="0.01" name="precio" value="<?=htmlspecialchars($producto['precio'])?>" required>
        </label>
        <label>Unidades disponibles:
            <input type="number" name="unidades_disponibles" value="<?=htmlspecialchars($producto['unidades_disponibles'])?>" required>
        </label>
        <label>Categoría:
            <input type="text" name="categoria" value="<?=htmlspecialchars($producto['categoria'])?>" required>
        </label>
        
        <label>Visible en galería:
            <select name="des_gak">
                <option value="1" <?=($producto['des_gak'] == 1 ? 'selected' : '')?>>Sí - Mostrar en galería</option>
                <option value="0" <?=($producto['des_gak'] == 0 ? 'selected' : '')?>>No - Ocultar de galería</option>
            </select>
        </label>
        
        <label>Imágenes actuales:</label>
        <?php if (count($imagenes) > 0): ?>
            <div class="image-preview-container">
                <?php foreach ($imagenes as $indice => $imagen): ?>
                    <div class="image-preview">
                        <img src="<?=htmlspecialchars($imagen)?>" alt="Imagen del producto">
                        <label class="delete-checkbox">
                            <input type="checkbox" name="eliminar_imagenes[]" value="<?=$indice?>">
                            Eliminar
                        </label>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p>No hay imágenes para este producto.</p>
        <?php endif; ?>
        
        <div class="file-input-container">
            <label>Agregar nuevas imágenes:
                <input type="file" name="nuevas_imagenes[]" multiple accept="image/*">
            </label>
            <p class="help-text">Puedes seleccionar múltiples imágenes. Formatos aceptados: JPG, PNG, GIF.</p>
        </div>
        
        <input type="submit" value="Guardar Cambios">
        <a href="productos_admin.php" style="margin-left: 10px; text-decoration: none;">Cancelar</a>
    </form>
</body>
</html>
