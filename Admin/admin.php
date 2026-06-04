<?php
session_start();
// 1) Verificar admin
if (!isset($_SESSION['usuario']) || $_SESSION['usuario'] !== 'admin@gmail.com') {
    header('Location: https://panchielito.com/Login/');
    exit;
}
// 2) Conexión
require_once '../controladoresPrincipales/conexion.php';

// 3) Obtener todos los usuarios - Incluir el campo terCon y AseptacionterAsep
$result = $mysqli->query("
    SELECT id, nombre, apellido, telefono, email, direccion, pass, pan, dinero, terCon, AseptacionterAsep
    FROM usuarios
");
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Panel Admin – Usuarios</title>
    <link rel="stylesheet" href="admin_style.css">
    <link href="https://fonts.googleapis.com/css2?family=Dancing+Script:wght@400..700&display=swap" rel="stylesheet">
    <link rel="icon" type="image/png" href="../img/chielito-re.png">
    <style>
       
    </style>
</head>
<body>
    <h1>Panel de Administración – Usuarios</h1>
    
    <!-- Pestañas de navegación -->
    <div class="nav-tabs">
        <a href="admin.php" class="active">Usuarios</a>
        <a href="productos_admin.php">Productos</a>
        <a href="ventas_admin.php">Ventas</a>
        <a href="https://panchielito.com/">Página Principal</a>
    </div>
    
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Nombre</th>
                <th>Apellido</th>
                <th>Teléfono</th>
                <th>Email</th>
                <th>Dirección</th>
                <th>Pass</th>
                <th>PAN</th>
                <th>Dinero</th>
                <th>Términos y Condiciones</th>
                <th>Fecha de Aceptación</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
        <?php while($u = $result->fetch_assoc()): ?>
            <tr>
                <td><?=htmlspecialchars($u['id'])?></td>
                <td><?=htmlspecialchars($u['nombre'])?></td>
                <td><?=htmlspecialchars($u['apellido'])?></td>
                <td><?=htmlspecialchars($u['telefono'])?></td>
                <td><?=htmlspecialchars($u['email'])?></td>
                <td><?=htmlspecialchars($u['direccion'])?></td>
                <td><?=htmlspecialchars($u['pass'])?></td>
                <td><?=htmlspecialchars($u['pan'])?></td>
                <td><?=htmlspecialchars($u['dinero'])?></td>
                <td>
                    <?php if($u['terCon'] == 1): ?>
                        <span class="terms-accepted">Aceptados</span>
                    <?php else: ?>
                        <span class="terms-not-accepted">No Aceptados</span>
                    <?php endif; ?>
                </td>
                <td>
                    <?php if($u['AseptacionterAsep']): ?>
                        <span class="terms-date"><?=htmlspecialchars($u['AseptacionterAsep'])?></span>
                    <?php else: ?>
                        <span class="terms-not-accepted">No disponible</span>
                    <?php endif; ?>
                </td>
                <td>
                    <a class="button edit"   href="edit_user.php?id=<?=$u['id']?>">Editar</a>
                    <a class="button delete" href="delete_user.php?id=<?=$u['id']?>"
                       onclick="return confirm('¿Eliminar usuario <?=$u['nombre']?>?');">
                       Eliminar
                    </a>
                </td>
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
</body>
</html>
