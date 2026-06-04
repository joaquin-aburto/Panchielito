<?php
session_start();

// Verificar si el usuario es administrador
if (!isset($_SESSION['id']) || !isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
    header('Location: https://panchielito.com/Login/');
    exit;
}

// Incluir el archivo de conexión
require_once '../controladoresPrincipales/conexion.php';

// Verificar si se recibió la referencia de pago
if (!isset($_POST['referencia'])) {
    die("Error: No se recibió la referencia de pago.");
}

$referencia = $_POST['referencia'];

// Buscar la venta con esa referencia
$stmt = $mysqli->prepare("SELECT id, usuario_id, total FROM ventas WHERE referencia_pago = ? AND estado = 'pendiente'");
$stmt->bind_param("s", $referencia);
$stmt->execute();
$result = $stmt->get_result();
$venta = $result->fetch_assoc();

if (!$venta) {
    die("Error: No se encontró una venta pendiente con esa referencia.");
}

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
    
    echo "Pago confirmado correctamente.";
    
} catch (Exception $e) {
    // Revertir transacción en caso de error
    $mysqli->rollback();
    die("Error al confirmar el pago: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirmar Pago OXXO - Panchielito</title>
    <link rel="stylesheet" type="text/css" href="../css/estilospie.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: #f5f5f5;
            margin: 0;
            padding: 20px;
        }
        
        .container {
            max-width: 800px;
            margin: 0 auto;
            background-color: #fff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.1);
        }
        
        h1 {
            color: #6d0000;
            text-align: center;
            margin-bottom: 30px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
        }
        
        input[type="text"] {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
        }
        
        button {
            background-color: #a93226;
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            font-weight: bold;
        }
        
        button:hover {
            background-color: #8B0000;
        }
        
        .message {
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
        }
        
        .success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Confirmar Pago OXXO</h1>
        
        <?php if (isset($_POST['referencia'])): ?>
            <?php if (isset($venta)): ?>
                <div class="message success">
                    El pago con referencia <?php echo htmlspecialchars($referencia); ?> ha sido confirmado correctamente.
                </div>
            <?php else: ?>
                <div class="message error">
                    No se encontró una venta pendiente con la referencia proporcionada.
                </div>
            <?php endif; ?>
        <?php endif; ?>
        
        <form method="post" action="">
            <div class="form-group">
                <label for="referencia">Referencia de Pago:</label>
                <input type="text" id="referencia" name="referencia" required placeholder="Ingrese la referencia de pago">
            </div>
            
            <button type="submit">Confirmar Pago</button>
        </form>
    </div>
</body>
</html>