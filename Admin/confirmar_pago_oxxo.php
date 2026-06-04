<?php
session_start();
// Verificar admin
if (!isset($_SESSION['usuario']) || $_SESSION['usuario'] !== 'admin@gmail.com') {
    header('Location: https://panchielito.com/Login/');
    exit;
}
// Conexión
require_once '../controladoresPrincipales/conexion.php';

$mensaje = '';
$tipo_mensaje = '';
$venta = null;

// Verificar si se recibió la referencia de pago
if (isset($_GET['referencia'])) {
    $referencia = $_GET['referencia'];
    
    // Buscar la venta con esa referencia
    $stmt = $mysqli->prepare("SELECT id, usuario_id, total FROM ventas WHERE referencia_pago = ? AND estado = 'pendiente'");
    $stmt->bind_param("s", $referencia);
    $stmt->execute();
    $result = $stmt->get_result();
    $venta = $result->fetch_assoc();
    
    if (!$venta) {
        $mensaje = "No se encontró una venta pendiente con la referencia proporcionada.";
        $tipo_mensaje = "error";
    }
}

// Si se envió el formulario para confirmar el pago
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirmar']) && isset($_POST['referencia'])) {
    $referencia = $_POST['referencia'];
    
    // Buscar la venta con esa referencia
    $stmt = $mysqli->prepare("SELECT id, usuario_id, total FROM ventas WHERE referencia_pago = ? AND estado = 'pendiente'");
    $stmt->bind_param("s", $referencia);
    $stmt->execute();
    $result = $stmt->get_result();
    $venta = $result->fetch_assoc();
    
    if (!$venta) {
        $mensaje = "No se encontró una venta pendiente con la referencia proporcionada.";
        $tipo_mensaje = "error";
    } else {
        // Iniciar transacción
        $mysqli->begin_transaction();
        
        try {
            // Actualizar el estado de la venta a completada
            $stmt = $mysqli->prepare("UPDATE ventas SET estado = 'completada', fecha_pago = CURRENT_TIMESTAMP WHERE id = ?");
            $stmt->bind_param("i", $venta['id']);
            $stmt->execute();
            
            // Actualizar estadísticas de ventas
            $fechaHoy = date('Y-m-d');
            
            // Intentar actualizar registro existente
            $stmt = $mysqli->prepare("INSERT INTO estadisticas_ventas (fecha, total_ventas, cantidad_ventas) 
                                     VALUES (?, ?, 1) 
                                     ON DUPLICATE KEY UPDATE 
                                     total_ventas = total_ventas + ?, 
                                     cantidad_ventas = cantidad_ventas + 1");
            $stmt->bind_param("sdd", $fechaHoy, $venta['total'], $venta['total']);
            $stmt->execute();
            
            // Confirmar transacción
            $mysqli->commit();
            
            $mensaje = "El pago ha sido confirmado correctamente.";
            $tipo_mensaje = "success";
            
        } catch (Exception $e) {
            // Revertir transacción en caso de error
            $mysqli->rollback();
            $mensaje = "Error al confirmar el pago: " . $e->getMessage();
            $tipo_mensaje = "error";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Confirmar Pago OXXO - Panchielito</title>
    <link rel="stylesheet" href="admin_style.css">
    <link rel="stylesheet" href="ventas_style.css">
    <link href="https://fonts.googleapis.com/css2?family=Dancing+Script:wght@400..700&display=swap" rel="stylesheet">
    <link rel="icon" type="image/png" href="../img/chielito-re.png">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <style>
        .confirm-container {
            max-width: 600px;
            margin: 0 auto;
        }
        
        .confirm-form {
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            padding: 30px;
            margin-top: 20px;
        }
        
        .form-title {
            color: #6d0000;
            margin-top: 0;
            margin-bottom: 20px;
            font-size: 1.5rem;
            text-align: center;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
            color: #6d0000;
        }
        
        .form-group input {
            width: 100%;
            padding: 12px;
            border: 1px solid #e0d0d0;
            border-radius: 6px;
            font-size: 1rem;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #a83232;
            box-shadow: 0 0 0 2px rgba(168, 50, 50, 0.2);
        }
        
        .form-actions {
            display: flex;
            justify-content: space-between;
            margin-top: 30px;
        }
        
        .btn {
            padding: 12px 20px;
            border-radius: 6px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
            text-align: center;
        }
        
        .btn-primary {
            background-color: #a83232;
            color: white;
            border: none;
        }
        
        .btn-secondary {
            background-color: #f8f8f8;
            color: #666;
            border: 1px solid #ddd;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
        }
        
        .message {
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
            font-weight: bold;
        }
        
        .message-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .message-error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .venta-info {
            background-color: #fff8f8;
            border: 1px solid #f0d0d0;
            border-radius: 6px;
            padding: 15px;
            margin-bottom: 20px;
        }
        
        .venta-info h3 {
            margin-top: 0;
            color: #6d0000;
            font-size: 1.2rem;
            margin-bottom: 15px;
            border-bottom: 1px solid #f0d0d0;
            padding-bottom: 10px;
        }
        
        .info-row {
            display: flex;
            margin-bottom: 10px;
        }
        
        .info-label {
            font-weight: bold;
            width: 150px;
            color: #666;
        }
        
        .info-value {
            flex: 1;
        }
    </style>
</head>
<body>
    <div class="confirm-container">
        <h1>Confirmar Pago OXXO</h1>
        
        <div class="nav-tabs">
            <a href="admin.php">Usuarios</a>
            <a href="productos_admin.php">Productos</a>
            <a href="ventas_admin.php" class="active">Ventas</a>
            <a href="https://panchielito.com/">Página Principal</a>
        </div>
        
        <?php if ($mensaje): ?>
            <div class="message message-<?php echo $tipo_mensaje; ?>">
                <?php echo $mensaje; ?>
            </div>
        <?php endif; ?>
        
        <?php if ($venta && $tipo_mensaje !== 'success'): ?>
            <div class="venta-info">
                <h3>Información de la Venta</h3>
                <div class="info-row">
                    <div class="info-label">ID de Venta:</div>
                    <div class="info-value">#<?php echo $venta['id']; ?></div>
                </div>
                <div class="info-row">
                    <div class="info-label">ID de Usuario:</div>
                    <div class="info-value"><?php echo $venta['usuario_id']; ?></div>
                </div>
                <div class="info-row">
                    <div class="info-label">Total:</div>
                    <div class="info-value">$<?php echo number_format($venta['total'], 2); ?></div>
                </div>
                <div class="info-row">
                    <div class="info-label">Referencia:</div>
                    <div class="info-value"><?php echo $referencia; ?></div>
                </div>
            </div>
            
            <form method="post" class="confirm-form">
                <h2 class="form-title">¿Confirmar este pago?</h2>
                <p>Al confirmar este pago, la venta pasará a estado "completada" y se actualizarán las estadísticas.</p>
                <input type="hidden" name="referencia" value="<?php echo $referencia; ?>">
                <div class="form-actions">
                    <a href="ventas_admin.php" class="btn btn-secondary">Cancelar</a>
                    <button type="submit" name="confirmar" class="btn btn-primary">Confirmar Pago</button>
                </div>
            </form>
        <?php elseif (!$venta && !$mensaje): ?>
            <form method="get" class="confirm-form">
                <h2 class="form-title">Buscar Venta por Referencia</h2>
                <div class="form-group">
                    <label for="referencia">Referencia de Pago:</label>
                    <input type="text" id="referencia" name="referencia" required placeholder="Ingrese la referencia de pago">
                </div>
                <div class="form-actions">
                    <a href="ventas_admin.php" class="btn btn-secondary">Cancelar</a>
                    <button type="submit" class="btn btn-primary">Buscar</button>
                </div>
            </form>
        <?php endif; ?>
        
        <?php if ($tipo_mensaje === 'success'): ?>
            <div class="form-actions" style="margin-top: 20px;">
                <a href="ventas_admin.php" class="btn btn-primary">Volver a Ventas</a>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>