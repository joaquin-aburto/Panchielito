<?php
session_start();
if (!isset($_SESSION['usuario']) || $_SESSION['usuario'] !== 'admin@gmail.com') {
    header('Location: https://panchielito.com/Login/');
    exit;
}
require_once '../controladoresPrincipales/conexion.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id > 0) {
    // Primero obtenemos las imágenes para intentar eliminarlas del servidor
    $stmt = $mysqli->prepare("SELECT imagenes FROM productos WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $producto = $result->fetch_assoc();
    $stmt->close();
    
    if ($producto) {
        // Decodificar el JSON de imágenes
        $imagenes = json_decode($producto['imagenes'], true);
        if (is_array($imagenes)) {
            // Intentar eliminar cada imagen del servidor
            foreach ($imagenes as $imagen) {
                if (file_exists($imagen)) {
                    unlink($imagen);
                }
            }
        }
    }
    
    // Ahora eliminamos el producto de la base de datos
    $stmt = $mysqli->prepare("DELETE FROM productos WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
}
header('Location: productos_admin.php');
exit;