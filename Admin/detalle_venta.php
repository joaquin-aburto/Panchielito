<?php
session_start();
// Verificar admin
if (!isset($_SESSION['usuario']) || $_SESSION['usuario'] !== 'admin@gmail.com') {
    header('Location: https://panchielito.com/Login/');
    exit;
}
// Conexión
require_once '../controladoresPrincipales/conexion.php';

// Verificar si se recibió el ID de la venta
if (!isset($_GET['id'])) {
    header('Location: ventas_admin.php');
    exit;
}

$ventaId = intval($_GET['id']);

// Obtener información de la venta
$stmt = $mysqli->prepare("SELECT v.*, 
                            u.nombre AS usuario_nombre, 
                            u.apellido AS usuario_apellido, 
                            u.email AS usuario_email,
                            u.telefono AS usuario_telefono
                          FROM ventas v
                          JOIN usuarios u ON v.usuario_id = u.id
                          WHERE v.id = ?");
$stmt->bind_param("i", $ventaId);
$stmt->execute();
$result = $stmt->get_result();
$venta = $result->fetch_assoc();

if (!$venta) {
    header('Location: ventas_admin.php');
    exit;
}

// Obtener detalles de la venta
$stmt = $mysqli->prepare("SELECT * FROM detalles_venta WHERE venta_id = ?");
$stmt->bind_param("i", $ventaId);
$stmt->execute();
$result = $stmt->get_result();
$detalles = $result->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Detalle de Venta #<?php echo $ventaId; ?> - Panchielito</title>
    <link rel="stylesheet" href="admin_style.css">
    <link rel="stylesheet" href="ventas_style.css">
    <link href="https://fonts.googleapis.com/css2?family=Dancing+Script:wght@400..700&display=swap" rel="stylesheet">
    <link rel="icon" type="image/png" href="../img/chielito-re.png">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <style>
        .detail-container {
            max-width: 1000px;
            margin: 0 auto;
        }
        
        .detail-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .detail-actions {
            display: flex;
            gap: 10px;
        }
        
        .detail-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 15px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: bold;
            transition: all 0.3s ease;
        }
        
        .btn-back {
            background-color: #f8f8f8;
            color: #666;
            border: 1px solid #ddd;
        }
        
        .btn-print {
            background-color: #a83232;
            color: white;
            border: none;
        }
        
        .btn-confirm {
            background-color: #27ae60;
            color: white;
            border: none;
        }
        
        .detail-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
        }
        
        .info-section {
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .info-section h2 {
            color: #6d0000;
            margin-top: 0;
            margin-bottom: 15px;
            font-size: 1.3rem;
            border-bottom: 1px solid #f0e0e0;
            padding-bottom: 10px;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
        }
        
        .info-item {
            margin-bottom: 15px;
        }
        
        .info-label {
            font-weight: bold;
            color: #666;
            margin-bottom: 5px;
            font-size: 0.9rem;
        }
        
        .info-value {
            font-size: 1.1rem;
        }
        
        .total-row {
            font-weight: bold;
            background-color: #f8f5f5;
        }
        
        @media print {
            .detail-actions, .nav-tabs {
                display: none;
            }
            
            body {
                background-color: white;
                padding: 0;
                margin: 0;
            }
            
            .detail-container {
                width: 100%;
                max-width: none;
            }
            
            .info-section {
                box-shadow: none;
                border: 1px solid #ddd;
                break-inside: avoid;
            }
            
            h1 {
                font-size: 1.8rem;
                margin-bottom: 20px;
                text-align: center;
            }
        }
    </style>
</head>
<body>
    <div class="detail-container">
        <h1>Detalle de Venta #<?php echo $ventaId; ?></h1>
        
        <div class="nav-tabs">
            <a href="admin.php">Usuarios</a>
            <a href="productos_admin.php">Productos</a>
            <a href="ventas_admin.php" class="active">Ventas</a>
            <a href="https://panchielito.com/">Página Principal</a>
        </div>
        
        <div class="detail-header">
            <h2><i class="fas fa-receipt"></i> Información de la Venta</h2>
            <div class="detail-actions">
                <a href="ventas_admin.php" class="detail-btn btn-back">
                    <i class="fas fa-arrow-left"></i> Volver
                </a>
                <button onclick="window.print()" class="detail-btn btn-print">
                    <i class="fas fa-print"></i> Imprimir
                </button>
                <?php if ($venta['estado'] === 'pendiente' && $venta['metodo_pago'] === 'OXXO'): ?>
                    <a href="confirmar_pago_oxxo.php?referencia=<?php echo $venta['referencia_pago']; ?>" class="detail-btn btn-confirm">
                        <i class="fas fa-check-circle"></i> Confirmar Pago
                    </a>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="info-section">
            <h2><i class="fas fa-info-circle"></i> Información General</h2>
            <div class="info-grid">
                <div class="info-item">
                    <div class="info-label">ID de Venta</div>
                    <div class="info-value">#<?php echo $venta['id']; ?></div>
                </div>
                
                <div class="info-item">
                    <div class="info-label">Fecha</div>
                    <div class="info-value"><?php echo date('d/m/Y H:i', strtotime($venta['fecha_venta'])); ?></div>
                </div>
                
                <div class="info-item">
                    <div class="info-label">Estado</div>
                    <div class="info-value">
                        <?php if ($venta['estado'] === 'completada'): ?>
                            <span class="badge badge-success">Completada</span>
                        <?php elseif ($venta['estado'] === 'pendiente'): ?>
                            <span class="badge badge-warning">Pendiente</span>
                        <?php else: ?>
                            <span class="badge badge-danger">Cancelada</span>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="info-item">
                    <div class="info-label">Método de Pago</div>
                    <div class="info-value"><?php echo $venta['metodo_pago']; ?></div>
                </div>
                
                <?php if ($venta['referencia_pago']): ?>
                    <div class="info-item">
                        <div class="info-label">Referencia de Pago</div>
                        <div class="info-value"><?php echo $venta['referencia_pago']; ?></div>
                    </div>
                <?php endif; ?>
                
                <?php if ($venta['fecha_pago']): ?>
                    <div class="info-item">
                        <div class="info-label">Fecha de Pago</div>
                        <div class="info-value"><?php echo date('d/m/Y H:i', strtotime($venta['fecha_pago'])); ?></div>
                    </div>
                <?php endif; ?>
                
                <div class="info-item">
                    <div class="info-label">Total</div>
                    <div class="info-value" style="font-size: 1.5rem; font-weight: bold; color: #a93226;">
                        $<?php echo number_format($venta['total'], 2); ?>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="info-section">
            <h2><i class="fas fa-user"></i> Información del Cliente</h2>
            <div class="info-grid">
                <div class="info-item">
                    <div class="info-label">Nombre</div>
                    <div class="info-value"><?php echo htmlspecialchars($venta['usuario_nombre'] . ' ' . $venta['usuario_apellido']); ?></div>
                </div>
                
                <div class="info-item">
                    <div class="info-label">Email</div>
                    <div class="info-value"><?php echo htmlspecialchars($venta['usuario_email']); ?></div>
                </div>
                
                <div class="info-item">
                    <div class="info-label">Teléfono</div>
                    <div class="info-value"><?php echo htmlspecialchars($venta['usuario_telefono']); ?></div>
                </div>
                
                <div class="info-item">
                    <div class="info-label">ID de Usuario</div>
                    <div class="info-value"><?php echo $venta['usuario_id']; ?></div>
                </div>
            </div>
        </div>
        
        <div class="info-section">
            <h2><i class="fas fa-shopping-cart"></i> Productos</h2>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Producto</th>
                        <th>Precio Unitario</th>
                        <th>Cantidad</th>
                        <th>Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($detalles as $detalle): ?>
                        <tr>
                            <td><?php echo $detalle['producto_id']; ?></td>
                            <td><?php echo htmlspecialchars($detalle['nombre_producto']); ?></td>
                            <td>$<?php echo number_format($detalle['precio_unitario'], 2); ?></td>
                            <td><?php echo $detalle['cantidad']; ?></td>
                            <td>$<?php echo number_format($detalle['subtotal'], 2); ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <tr class="total-row">
                        <td colspan="4" style="text-align: right;">Total:</td>
                        <td>$<?php echo number_format($venta['total'], 2); ?></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>