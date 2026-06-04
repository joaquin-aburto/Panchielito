<?php
header("Content-Type: application/json");

require_once 'conexion.php';

$categoria = isset($_GET['categoria']) ? $_GET['categoria'] : null;

$query = "SELECT id, nombre, precio, imagenes, categoria 
          FROM productos 
          WHERE des_gak = 1";

$productos = [];

if ($categoria) {
    $query .= " AND categoria = ?";
    $stmt = $mysqli->prepare($query);

    if (!$stmt) {
        echo json_encode([]);
        exit;
    }

    $stmt->bind_param("s", $categoria);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $result = $mysqli->query($query);
}

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $row['imagenes'] = json_decode($row['imagenes'], true);

        if (!is_array($row['imagenes'])) {
            $row['imagenes'] = [];
        }

        $productos[] = $row;
    }
}

echo json_encode($productos);

if (isset($stmt)) {
    $stmt->close();
}

$mysqli->close();
?>