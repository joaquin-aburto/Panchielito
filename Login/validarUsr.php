<?php
    header("Access-Control-Allow-Origin: http://127.0.0.1/Panchielito/");
    header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
    header("Access-Control-Allow-Headers: Content-Type, Authorization");
    session_start();
    date_default_timezone_set("America/Mexico_City");

    $NombreUsr = $_POST['email'] ?? '';
    $PaswordUsr = $_POST['password'] ?? '';

    if (empty($NombreUsr) || empty($PaswordUsr)) {
        echo json_encode('Error');
        exit;
    }

    include("conexion.php");

    $sql = "SELECT id, nombre, email FROM usuarios WHERE email = ? AND pass = ?";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param("ss", $NombreUsr, $PaswordUsr);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();

        $_SESSION["usuario"] = $user['email'];
        $_SESSION["nombre"]  = $user['nombre'];
        $_SESSION["id"]      = $user['id'];
        $_SESSION["Enter"]   = date("Y-m-d H:i:s");

        // Detectar si es el admin
        if ($user['email'] === 'admin@gmail.com') {
            echo json_encode('Admin');
        } else {
            echo json_encode('Correcto');
        }
    } else {
        echo json_encode('Error');
    }

    $stmt->close();
    $mysqli->close();
?>

