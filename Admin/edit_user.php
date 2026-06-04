<?php
session_start();
if (!isset($_SESSION['usuario']) || $_SESSION['usuario'] !== 'admin@gmail.com') {
    header('Location: https://panchielito.com/Login/');
    exit;
}
require_once '../controladoresPrincipales/conexion.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id <= 0) die("Usuario no encontrado.");

// Si vino POST, procesar la actualización
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre   = $_POST['nombre'];
    $apellido = $_POST['apellido'];
    $telefono = $_POST['telefono'];
    $email    = $_POST['email'];
    $direccion= $_POST['direccion'];
    $pass     = $_POST['pass'];
    $pan      = $_POST['pan'];
    $dinero   = $_POST['dinero'];

    // No modificamos el campo terCon ni AseptacionterAsep, los obtenemos de la base de datos
    $stmt = $mysqli->prepare("SELECT terCon, AseptacionterAsep FROM usuarios WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user_data = $result->fetch_assoc();
    $terCon = $user_data['terCon'];
    $aseptacionterAsep = $user_data['AseptacionterAsep'];
    $stmt->close();

    $stmt = $mysqli->prepare("
        UPDATE usuarios
        SET nombre=?, apellido=?, telefono=?, email=?, direccion=?, pass=?, pan=?, dinero=?
        WHERE id=?
    ");
    // 7 strings (s), 1 double (d), 1 int (i)
    $stmt->bind_param("sssssssdi",
        $nombre, $apellido, $telefono, $email,
        $direccion, $pass, $pan, $dinero, $id
    );
    $stmt->execute();
    $stmt->close();

    header('Location: https://panchielito.com/Admin/admin.php');
    exit;
}

// Si no es POST, traer datos para el formulario
$stmt = $mysqli->prepare("
    SELECT nombre, apellido, telefono, email, direccion, pass, pan, dinero, terCon, AseptacionterAsep
    FROM usuarios WHERE id=?
");
$stmt->bind_param("i", $id);
$stmt->execute();
$res = $stmt->get_result();
$user = $res->fetch_assoc();
$stmt->close();
if (!$user) die("Usuario no encontrado.");
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Editar Usuario #<?=$id?></title>
    <link rel="stylesheet" href="style.css">
    <link href="https://fonts.googleapis.com/css2?family=Dancing+Script:wght@400..700&display=swap" rel="stylesheet">
    <link rel="icon" type="image/png" href="img/chielito-re.png">
    <style>
        .readonly-field {
            background-color: #f5f5f5;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            margin-top: 4px;
            display: block;
            width: 100%;
            box-sizing: border-box;
        }
        .terms-accepted {
            background-color: #dff0d8;
            color: #3c763d;
            padding: 3px 8px;
            border-radius: 4px;
            font-weight: bold;
        }
        .terms-not-accepted {
            background-color: #f2dede;
            color: #a94442;
            padding: 3px 8px;
            border-radius: 4px;
            font-weight: bold;
        }
        .terms-date {
            background-color: #d9edf7;
            color: #31708f;
            padding: 3px 8px;
            border-radius: 4px;
            font-weight: bold;
        }
        .form-actions {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }
        .cancel-btn {
            background-color: #f44336;
            color: white;
            border: none;
            padding: 10px 20px;
            text-align: center;
            text-decoration: none;
            display: inline-block;
            font-size: 16px;
            margin: 4px 2px;
            cursor: pointer;
            border-radius: 4px;
        }
        .cancel-btn:hover {
            background-color: #d32f2f;
        }
    </style>
</head>
<body>
    <h1>Editar Usuario #<?=$id?></h1>
    <form method="post">
        <label>Nombre:
            <input type="text" name="nombre"   value="<?=htmlspecialchars($user['nombre'])?>" required>
        </label>
        <label>Apellido:
            <input type="text" name="apellido" value="<?=htmlspecialchars($user['apellido'])?>" required>
        </label>
        <label>Teléfono:
            <input type="text" name="telefono" value="<?=htmlspecialchars($user['telefono'])?>">
        </label>
        <label>Email:
            <input type="email" name="email"   value="<?=htmlspecialchars($user['email'])?>" required>
        </label>
        <label>Dirección:
            <input type="text" name="direccion" value="<?=htmlspecialchars($user['direccion'])?>">
        </label>
        <label>Contraseña:
            <input type="text" name="pass"     value="<?=htmlspecialchars($user['pass'])?>" required>
        </label>
        <label>PAN:
            <input type="text" name="pan"      value="<?=htmlspecialchars($user['pan'])?>">
        </label>
        <label>Dinero:
            <input type="number" step="0.01" name="dinero"
                   value="<?=htmlspecialchars($user['dinero'])?>" required>
        </label>
        
        <!-- Campo de Términos y Condiciones (solo lectura) -->
        <label>Términos y Condiciones:
            <div class="readonly-field">
                <?php if($user['terCon'] == 1): ?>
                    <span class="terms-accepted">Aceptados</span>
                <?php else: ?>
                    <span class="terms-not-accepted">No Aceptados</span>
                <?php endif; ?>
            </div>
        </label>
        
        <!-- Campo de Fecha de Aceptación (solo lectura) -->
        <label>Fecha de Aceptación:
            <div class="readonly-field">
                <?php if($user['AseptacionterAsep']): ?>
                    <span class="terms-date"><?=htmlspecialchars($user['AseptacionterAsep'])?></span>
                <?php else: ?>
                    <span class="terms-not-accepted">No disponible</span>
                <?php endif; ?>
            </div>
        </label>
        
        <div class="form-actions">
            <input type="submit" value="Guardar Cambios">
            <a href="javascript:history.back()" class="cancel-btn">Cancelar</a>
        </div>
    </form>
</body>
</html>