<?php
    
    header("Access-Control-Allow-Origin: https://panchielito.com");
    header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
    header("Access-Control-Allow-Headers: Content-Type, Authorization");
    
    session_start();
    date_default_timezone_set("America/Mexico_City");
    
    $NombreUsr = $_POST['email'] ?? '';
    $PaswordUsr = $_POST['password'] ?? '';
    $Nombre = $_POST['nombre'] ?? '';
    $Apellido = $_POST['apellido'] ?? '';
    $Telefono = $_POST['telefono'] ?? '';
    $Direccion = $_POST['direccion'] ?? '';
    $Pan = $_POST['pan'] ?? '';
    $TermsAccepted = isset($_POST['termsAccepted']) && $_POST['termsAccepted'] === '1' ? 1 : 0;
    
    if (empty($NombreUsr) || empty($PaswordUsr) || empty($Nombre) || empty($Apellido) || empty($Telefono) || empty($Direccion) || empty($Pan)) {
        echo json_encode(['status' => 'error', 'message' => 'Todos los campos son obligatorios.']);
        exit;
    }
    
    // Verificar que se hayan aceptado los términos y condiciones
    if ($TermsAccepted !== 1) {
        echo json_encode(['status' => 'error', 'message' => 'Debes aceptar los términos y condiciones para registrarte.']);
        exit;
    }
    
    include("conexion.php");
    
    // Verificar si el correo ya está registrado
    $checkSql = "SELECT id FROM usuarios WHERE email = ?";
    $checkStmt = $mysqli->prepare($checkSql);
    
    if ($checkStmt === false) {
        echo json_encode(['status' => 'error', 'message' => 'Error al preparar la consulta: ' . $mysqli->error]);
        exit;
    }
    
    $checkStmt->bind_param("s", $NombreUsr);
    $checkStmt->execute();
    $checkStmt->store_result();
    
    if ($checkStmt->num_rows > 0) {
        echo json_encode('Correo');
        $checkStmt->close();
        $mysqli->close();
        exit;
    }
    
    $checkStmt->close();
    
    // Insertar los datos en la base de datos
    $sql = "INSERT INTO usuarios (nombre, apellido, telefono, email, direccion, pass, pan, terCon) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $mysqli->prepare($sql);
    
    if ($stmt === false) {
        echo json_encode(['status' => 'error', 'message' => 'Error al preparar la consulta: ' . $mysqli->error]);
        exit;
    }
    
    $stmt->bind_param("sssssssi", $Nombre, $Apellido, $Telefono, $NombreUsr, $Direccion, $PaswordUsr, $Pan, $TermsAccepted);
    
    if ($stmt->execute()) {
        // Obtener el ID del usuario insertado
        $userId = $mysqli->insert_id;
    
        // Guardar los datos del usuario en la sesión
        $_SESSION["id"] = $userId;
        $_SESSION["nombre"] = $Nombre;
        $_SESSION["usuario"] = $NombreUsr;
        $_SESSION["Enter"] = date("Y-m-d H:i:s");
    
        echo json_encode('Registrado');
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Error al registrar el usuario: ' . $stmt->error]);
    }
    
    $stmt->close();
    $mysqli->close();

?>
