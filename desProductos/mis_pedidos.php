<?php
session_start();

// Verificar si el usuario está autenticado
if (!isset($_SESSION['id'])) {
    header('Location: https://panchielito.com/Login/');
    exit;
}

// Incluir el archivo de conexión
require_once '../controladoresPrincipales/conexion.php';

// Obtener información del usuario
$userId = $_SESSION['id'];
$stmt = $mysqli->prepare("SELECT nombre, apellido FROM usuarios WHERE id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
$usuario = $result->fetch_assoc();

if (!$usuario) {
    die("Error: No se pudo obtener la información del usuario.");
}

// Obtener los pedidos del usuario
$stmt = $mysqli->prepare("
    SELECT v.id, v.fecha_venta, v.total, v.metodo_pago, v.estado, v.referencia_pago, v.fecha_pago,
           DATE_ADD(COALESCE(v.fecha_pago, v.fecha_venta), INTERVAL 1 DAY) as fecha_entrega
    FROM ventas v
    WHERE v.usuario_id = ?
    ORDER BY v.fecha_venta DESC
");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
$pedidos = $result->fetch_all(MYSQLI_ASSOC);

// Función para obtener el estado de entrega de un pedido
function obtenerEstadoEntrega($pedido) {
    $ahora = new DateTime();
    $fechaEntrega = new DateTime($pedido['fecha_entrega']);
    
    if ($pedido['estado'] === 'pendiente') {
        return 'pendiente_pago';
    } else if ($pedido['estado'] === 'cancelada') {
        return 'cancelado';
    } else if ($ahora < $fechaEntrega) {
        return 'en_proceso';
    } else {
        return 'entregado';
    }
}

// Agrupar pedidos por estado
$pedidosPendientesPago = [];
$pedidosEnProceso = [];
$pedidosEntregados = [];
$pedidosCancelados = [];

foreach ($pedidos as $pedido) {
    $estadoEntrega = obtenerEstadoEntrega($pedido);
    
    switch ($estadoEntrega) {
        case 'pendiente_pago':
            $pedidosPendientesPago[] = $pedido;
            break;
        case 'en_proceso':
            $pedidosEnProceso[] = $pedido;
            break;
        case 'entregado':
            $pedidosEntregados[] = $pedido;
            break;
        case 'cancelado':
            $pedidosCancelados[] = $pedido;
            break;
    }
}

// Obtener detalles de un pedido específico si se solicita
$detallesPedido = null;
$pedidoSeleccionado = null;

if (isset($_GET['id'])) {
    $pedidoId = intval($_GET['id']);
    
    // Verificar que el pedido pertenezca al usuario
    $stmt = $mysqli->prepare("
        SELECT v.id, v.fecha_venta, v.total, v.metodo_pago, v.estado, v.referencia_pago, v.fecha_pago,
               DATE_ADD(COALESCE(v.fecha_pago, v.fecha_venta), INTERVAL 1 DAY) as fecha_entrega
        FROM ventas v
        WHERE v.id = ? AND v.usuario_id = ?
    ");
    $stmt->bind_param("ii", $pedidoId, $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $pedidoSeleccionado = $result->fetch_assoc();
    
    if ($pedidoSeleccionado) {
        // Obtener los detalles del pedido
        $stmt = $mysqli->prepare("
            SELECT dv.producto_id, dv.nombre_producto, dv.cantidad, dv.precio_unitario, dv.subtotal
            FROM detalles_venta dv
            WHERE dv.venta_id = ?
        ");
        $stmt->bind_param("i", $pedidoId);
        $stmt->execute();
        $result = $stmt->get_result();
        $detallesPedido = $result->fetch_all(MYSQLI_ASSOC);
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis Pedidos - Panchielito</title>
    <link rel="stylesheet" type="text/css" href="../css/estilospie.css">
    <link href="https://fonts.googleapis.com/css2?family=Dancing+Script:wght@400..700&display=swap" rel="stylesheet">
    <link rel="icon" type="image/png" href="../img/chielito-re.png">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
     <link href="https://fonts.googleapis.com/css2?family=Dancing+Script:wght@400..700&display=swap" rel="stylesheet">
      <link rel="icon" type="image/png" href="img/chielito-re.png">
      <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #6d0000;
            --secondary-color: #a93226;
            --accent-color: #e74c3c;
            --light-bg: #fff8f8;
            --border-color: #f0d0d0;
            --text-color: #333;
            --text-secondary: #666;
            --success-color: #2ecc71;
            --warning-color: #f39c12;
            --info-color: #3498db;
            --danger-color: #e74c3c;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        .welcome-message {
    font-family: 'Dancing Script', cursive;
    font-style: italic;
    color: #000000; /* Cambiado a negro */
    margin: 0;
    font-size: 1.5em; /* Aumentado el tamaño (antes era 1.2em) */
    font-weight: 500; /* Opcional: para darle un poco más de grosor */
}
        
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: var(--text-color);
            background-color: #f5f5f5;
        }
        
        .container {
            max-width: 1200px;
            margin: 30px auto;
            padding: 0 20px;
        }
        
        .page-title {
            color: var(--primary-color);
            font-family: 'Dancing Script', cursive;
            font-size: 2.5rem;
            margin-bottom: 20px;
            text-align: center;
        }
        
        .tabs {
            display: flex;
            margin-bottom: 30px;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }
        
        .tab {
            flex: 1;
            padding: 15px;
            text-align: center;
            background-color: #fff;
            cursor: pointer;
            transition: all 0.3s ease;
            font-weight: bold;
            color: var(--text-secondary);
            border-bottom: 3px solid transparent;
            position: relative;
        }
        
        .tab.active {
            background-color: var(--light-bg);
            color: var(--primary-color);
            border-bottom: 3px solid var(--secondary-color);
        }
        
        .tab:hover:not(.active) {
            background-color: #f9f9f9;
        }
        
        .tab-badge {
            position: absolute;
            top: 5px;
            right: 5px;
            background-color: var(--accent-color);
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            font-weight: bold;
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }
        
        .card {
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
            overflow: hidden;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }
        
        .card-header {
            background-color: var(--light-bg);
            padding: 15px 20px;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .card-title {
            font-size: 1.2rem;
            color: var(--primary-color);
            margin: 0;
            font-weight: bold;
        }
        
        .card-body {
            padding: 20px;
        }
        
        .card-footer {
            background-color: #f9f9f9;
            padding: 15px 20px;
            border-top: 1px solid var(--border-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .pedido-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 15px;
        }
        
        .info-item {
            margin-bottom: 10px;
        }
        
        .info-label {
            font-weight: bold;
            color: var(--text-secondary);
            margin-bottom: 5px;
            font-size: 0.9rem;
        }
        
        .info-value {
            font-size: 1.1rem;
        }
        
        .badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: bold;
            text-transform: uppercase;
        }
        
        .badge-success {
            background-color: #d4edda;
            color: #155724;
        }
        
        .badge-warning {
            background-color: #fff3cd;
            color: #856404;
        }
        
        .badge-info {
            background-color: #d1ecf1;
            color: #0c5460;
        }
        
        .badge-danger {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        .btn {
            display: inline-block;
            padding: 10px 15px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease;
            border: none;
            font-size: 0.9rem;
        }
        
        .btn-primary {
            background-color: var(--secondary-color);
            color: white;
        }
        
        .btn-secondary {
            background-color: #f8f8f8;
            color: var(--text-secondary);
            border: 1px solid #ddd;
        }
        
        .btn-warning {
            background-color: var(--warning-color);
            color: white;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        
        .empty-state {
            text-align: center;
            padding: 50px 20px;
            color: var(--text-secondary);
        }
        
        .empty-state i {
            font-size: 60px;
            margin-bottom: 20px;
            color: #ddd;
        }
        
        .empty-state h3 {
            font-size: 1.5rem;
            margin-bottom: 10px;
            color: var(--primary-color);
        }
        
        .empty-state p {
            margin-bottom: 20px;
        }
        
        /* Modal para detalles del pedido */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0, 0, 0, 0.5);
        }
        
        .modal-content {
            background-color: #fff;
            margin: 5% auto;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.2);
            width: 90%;
            max-width: 800px;
            max-height: 90vh;
            overflow-y: auto;
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-bottom: 15px;
            margin-bottom: 20px;
            border-bottom: 2px solid var(--border-color);
        }
        
        .modal-title {
            font-size: 1.5rem;
            color: var(--primary-color);
            margin: 0;
        }
        
        .close {
            color: var(--text-secondary);
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }
        
        .close:hover {
            color: var(--primary-color);
        }
        
        .modal-body {
            margin-bottom: 20px;
        }
        
        .modal-footer {
            padding-top: 15px;
            border-top: 1px solid var(--border-color);
            text-align: right;
        }
        
        /* Tabla de productos */
        .table-responsive {
            overflow-x: auto;
            margin-bottom: 20px;
        }
        
        .table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .table th, .table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        
        .table th {
            background-color: var(--light-bg);
            color: var(--primary-color);
            font-weight: bold;
        }
        
        .table tr:hover {
            background-color: #f9f9f9;
        }
        
        .table .total-row {
            font-weight: bold;
            background-color: #f9f9f9;
        }
        
        /* Estilos para la línea de tiempo */
        .timeline {
            position: relative;
            padding: 20px 0;
            margin-top: 20px;
        }
        
        .timeline::before {
            content: '';
            position: absolute;
            top: 0;
            bottom: 0;
            left: 20px;
            width: 4px;
            background-color: #eee;
        }
        
        .timeline-item {
            position: relative;
            margin-bottom: 25px;
            padding-left: 45px;
        }
        
        .timeline-item:last-child {
            margin-bottom: 0;
        }
        
        .timeline-icon {
            position: absolute;
            left: 0;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: #fff;
            border: 4px solid #eee;
            text-align: center;
            line-height: 32px;
            color: var(--text-secondary);
            z-index: 1;
        }
        
        .timeline-icon.active {
            background-color: var(--success-color);
            border-color: #d4edda;
            color: white;
        }
        
        .timeline-icon.pending {
            background-color: var(--warning-color);
            border-color: #fff3cd;
            color: white;
        }
        
        .timeline-content {
            padding: 15px;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        
        .timeline-title {
            margin: 0 0 10px 0;
            color: var(--primary-color);
            font-size: 1.1rem;
        }
        
        .timeline-date {
            color: var(--text-secondary);
            font-size: 0.9rem;
            margin-bottom: 10px;
        }
        
        .timeline-description {
            color: var(--text-color);
            font-size: 0.95rem;
        }
        
        /* Estilos responsivos */
        @media (max-width: 768px) {
            .pedido-info {
                grid-template-columns: 1fr;
            }
            
            .tabs {
                flex-direction: column;
                border-radius: 0;
            }
            
            .tab {
                border-bottom: 1px solid #eee;
            }
            
            .tab.active {
                border-bottom: 3px solid var(--secondary-color);
            }
            
            .modal-content {
                width: 95%;
                margin: 10% auto;
            }
            
            .timeline::before {
                left: 15px;
            }
            
            .timeline-item {
                padding-left: 40px;
            }
            
            .timeline-icon {
                width: 30px;
                height: 30px;
                line-height: 22px;
            }
        }
        
        /* Estilos para el botón de carrito en el header */
        .cart-button {
    background-color: transparent !important;
    color: var(--secondary-color) !important;
    border: none !important;
    width: auto !important;
    height: auto !important;
    padding: 5px !important;
    margin-left: 15px !important;
}

.cart-button:hover {
    background-color: transparent !important;
    color: var(--primary-color) !important;
    transform: scale(1.1) !important;
}
        
        .cart-count {
            position: absolute;
            top: -5px;
            right: -5px;
            background-color: var(--accent-color);
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            font-weight: bold;
        }
        * {
  margin: 0;
  padding: 0;
}

*, *::before, *::after {
  box-sizing: border-box;
}

body {
  margin: 0;
  padding: 0;
  overflow-x: hidden; /* Previene el desbordamiento horizontal */
}

header {
  background-color: rgba(255, 255, 255, 0.9);
  display: flex;
  justify-content: space-around;
  align-items: center;
  padding: 5px 15px;
  transition: 0.7s;
  z-index: 1000; /* Asegúrate de que el header tenga un alto z-index */
  position: sticky;
  top: 0;
  border-radius: 20px;
}

.parallax-section {
  height: calc(100vh - 80px);
  position: relative;
}

.parallax-image {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background-image: url('../img/fondo.png');
  background-attachment: fixed;
  background-position: center;
  background-repeat: no-repeat;
  background-size: cover;
  z-index: -1;
}

#LogoTipo {
  max-width: 80px;
  height: auto;
}

h1 {
  color: #000;
  text-align: left;
  font-size: 2em;
  margin: 0;
  font-family: 'Dancing Script', cursive;
}

.nav-menu {
  text-align: center;
  display: flex;
  justify-content: center;
  align-items: center;
  width: 100%;
  margin: 0;
}

.nav-menu ul {
  list-style: none;
  display: flex;
  gap: 20px;
  margin: 0;
  padding: 0;
}

.nav-menu ul li a {
  text-decoration: none;
  color: #6d0000;
  font-weight: bold;
  font-size: 20px;
  padding: 0 15px;
  border-right: 1px solid #ccc;
  border-left: 1px solid #ccc;
}

.nav-buttons {
  justify-content: flex-end;
  display: flex;
  gap: 10px;
}

.nav-buttons a {
  text-decoration: none;
  font-size: 2em;
  color: #6d0000;
  margin-left: 25px;
}

.menu-btn {
  display: none;
  font-size: 1.8em;
  background: none;
  border: none;
  color: #6d0000;
  cursor: pointer;
  margin-left: auto;
}

/* Icono de WhatsApp */
.icon-whatsapp {
  font-size: 20px; 
  color: #8B0000;
  transition: all 0.3s ease;
  margin-left: 0px;  
}

.icon-whatsapp:hover {
  color: #B22222;
  transform: scale(1.2);
}

@media (max-width: 480px) {
  .parallax-section {
    height: auto;
  }

  .parallax-image {
    background-size: cover;
    background-position: center top;
  }

  .nav-menu ul {
    visibility: hidden;
    opacity: 0;
    flex-direction: column;
    position: absolute;
    top: 95px; /* Ajusta según el tamaño de tu header */
    right: 0;
    width: 100%;
    background-color: rgba(255, 255, 255, 0.95);
    padding: 10px;
    border-radius: 0 0 20px 20px;
    box-shadow: 0 8px 16px rgba(0, 0, 0, 0.2);
    transition: opacity 0.3s ease, visibility 0s linear 0.3s;
    z-index: 9999; /* Asegúrate de que el menú tenga un alto z-index */
  }

  .nav-menu ul.active {
    visibility: visible;
    opacity: 1;
    transition: opacity 0.3s ease;
  }

  .nav-menu .menu-btn {
    display: block;
    font-size: 24px;
    background: none;
    border: none;
    cursor: pointer;
    z-index: 10000; 
  }

  .menu-btn {
    display: block;
  }

  .nav-menu .nav-buttons {
    justify-content: center;
    margin-top: 10px;
  }
}


    </style>
</head>
<body>
    <header>
        <div>
            <a href="https://panchielito.com/">
                <img src="../img/Chielito.png" id="LogoTipo" alt="Logo de la Panadería">
            </a>
        </div>
        <div>
    <h1>Panchielito</h1>
    <?php
        if (isset($_SESSION['nombre'])) {
            echo '<h2 class="welcome-message">¡Hola ' . htmlspecialchars($_SESSION['nombre']) . '!</h2>';
        } else {
            echo '<h1>¡Bienvenido!</h1>';
        }
    ?>
</div>
          <nav class="nav-menu">
    <button class="menu-btn" onclick="toggleMenu()">☰</button>
    <ul>
        <li><a href="../Galeria/index.php" data-categoria="">PRODUCTOS</a></li>
        <li><a href="../Galeria/biscocho.php" data-categoria="BIZCOCHO">BIZCOCHO</a></li>
        <li><a href="../Galeria/danes.php" data-categoria="DANÉS">DANÉS</a></li>
        <li><a href="../Galeria/hojaldre.php" data-categoria="HOJALDRE">HOJALDRE</a></li>
        
        
        <?php if (!isset($_SESSION['nombre'])): ?>
            <li><a href="https://panchielito.com/Login/" class="login-btn">Iniciar sesión</a></li>
        <?php else: ?>
            <li class="user-actions">
                <?php if ($_SESSION['usuario'] === 'admin@gmail.com'): ?>
                    <a href="../Admin/productos_admin.php">PANEL</a>
                <?php endif; ?>
                <a href="../Galeria/carrito.php" class="cart-button" title="Carrito de compras">
                    <i class="fas fa-shopping-cart"></i>
                    <span id="cartCount" class="cart-count" style="display: none;">0</span>
                </a>
            
            </li>
        <?php endif; ?>
    </ul>
</nav>
    </header>

    <div class="container">
        <h1 class="page-title">Mis Pedidos</h1>
        
        <?php if ($detallesPedido && $pedidoSeleccionado): ?>
            <!-- Detalles del pedido seleccionado -->
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">Detalles del Pedido #<?php echo $pedidoSeleccionado['id']; ?></h2>
                    <a href="mis_pedidos.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Volver
                    </a>
                </div>
                <div class="card-body">
                    <div class="pedido-info">
                        <div class="info-item">
                            <div class="info-label">Fecha del Pedido</div>
                            <div class="info-value"><?php echo date('d/m/Y H:i', strtotime($pedidoSeleccionado['fecha_venta'])); ?></div>
                        </div>
                        
                        <div class="info-item">
                            <div class="info-label">Método de Pago</div>
                            <div class="info-value">
                                <?php if ($pedidoSeleccionado['metodo_pago'] === 'online'): ?>
                                    <i class="fas fa-credit-card"></i> Pago en línea
                                <?php else: ?>
                                    <i class="fas fa-store"></i> Pago en OXXO
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="info-item">
                            <div class="info-label">Estado</div>
                            <div class="info-value">
                                <?php 
                                    $estadoEntrega = obtenerEstadoEntrega($pedidoSeleccionado);
                                    if ($estadoEntrega === 'pendiente_pago'): 
                                ?>
                                    <span class="badge badge-warning">Pendiente de pago</span>
                                <?php elseif ($estadoEntrega === 'en_proceso'): ?>
                                    <span class="badge badge-info">En proceso de entrega</span>
                                <?php elseif ($estadoEntrega === 'entregado'): ?>
                                    <span class="badge badge-success">Entregado</span>
                                <?php else: ?>
                                    <span class="badge badge-danger">Cancelado</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="info-item">
                            <div class="info-label">Total</div>
                            <div class="info-value" style="font-weight: bold; color: var(--secondary-color);">
                                $<?php echo number_format($pedidoSeleccionado['total'], 2); ?>
                            </div>
                        </div>
                    </div>
                    
                    <?php if ($pedidoSeleccionado['referencia_pago']): ?>
                        <div class="info-item" style="margin-bottom: 20px;">
                            <div class="info-label">Referencia de Pago</div>
                            <div class="info-value" style="font-family: monospace; background: #f5f5f5; padding: 5px 10px; border-radius: 4px;">
                                <?php echo $pedidoSeleccionado['referencia_pago']; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <h3 style="margin-bottom: 15px; color: var(--primary-color);">Productos</h3>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Producto</th>
                                    <th>Precio</th>
                                    <th>Cantidad</th>
                                    <th>Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($detallesPedido as $detalle): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($detalle['nombre_producto']); ?></td>
                                        <td>$<?php echo number_format($detalle['precio_unitario'], 2); ?></td>
                                        <td><?php echo $detalle['cantidad']; ?></td>
                                        <td>$<?php echo number_format($detalle['subtotal'], 2); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                <tr class="total-row">
                                    <td colspan="3" style="text-align: right;"><strong>Total:</strong></td>
                                    <td><strong>$<?php echo number_format($pedidoSeleccionado['total'], 2); ?></strong></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="timeline">
                        <div class="timeline-item">
                            <div class="timeline-icon active">
                                <i class="fas fa-check"></i>
                            </div>
                            <div class="timeline-content">
                                <h3 class="timeline-title">Pedido Realizado</h3>
                                <div class="timeline-date">
                                    <?php echo date('d/m/Y H:i', strtotime($pedidoSeleccionado['fecha_venta'])); ?>
                                </div>
                                <div class="timeline-description">
                                    Tu pedido ha sido registrado en nuestro sistema.
                                </div>
                            </div>
                        </div>
                        
                        <?php if ($pedidoSeleccionado['metodo_pago'] === 'OXXO'): ?>
                            <div class="timeline-item">
                                <div class="timeline-icon <?php echo $pedidoSeleccionado['estado'] === 'completada' ? 'active' : 'pending'; ?>">
                                    <?php if ($pedidoSeleccionado['estado'] === 'completada'): ?>
                                        <i class="fas fa-check"></i>
                                    <?php else: ?>
                                        <i class="fas fa-clock"></i>
                                    <?php endif; ?>
                                </div>
                                <div class="timeline-content">
                                    <h3 class="timeline-title">Pago Confirmado</h3>
                                    <div class="timeline-date">
                                        <?php 
                                            if ($pedidoSeleccionado['fecha_pago']) {
                                                echo date('d/m/Y H:i', strtotime($pedidoSeleccionado['fecha_pago']));
                                            } else {
                                                echo 'Pendiente';
                                            }
                                        ?>
                                    </div>
                                    <div class="timeline-description">
                                        <?php if ($pedidoSeleccionado['estado'] === 'completada'): ?>
                                            Tu pago en OXXO ha sido confirmado.
                                        <?php else: ?>
                                            Estamos esperando la confirmación de tu pago en OXXO.
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <div class="timeline-item">
                            <div class="timeline-icon <?php echo $estadoEntrega === 'entregado' ? 'active' : 'pending'; ?>">
                                <?php if ($estadoEntrega === 'entregado'): ?>
                                    <i class="fas fa-check"></i>
                                <?php else: ?>
                                    <i class="fas fa-clock"></i>
                                <?php endif; ?>
                            </div>
                            <div class="timeline-content">
                                <h3 class="timeline-title">Pedido Entregado</h3>
                                <div class="timeline-date">
                                    <?php 
                                        if ($estadoEntrega === 'entregado') {
                                            echo date('d/m/Y H:i', strtotime($pedidoSeleccionado['fecha_entrega']));
                                        } else if ($pedidoSeleccionado['estado'] === 'completada') {
                                            echo 'Estimado: ' . date('d/m/Y H:i', strtotime($pedidoSeleccionado['fecha_entrega']));
                                        } else {
                                            echo 'Pendiente';
                                        }
                                    ?>
                                </div>
                                <div class="timeline-description">
                                    <?php if ($estadoEntrega === 'entregado'): ?>
                                        Tu pedido ha sido entregado. ¡Gracias por tu compra!
                                    <?php elseif ($pedidoSeleccionado['estado'] === 'completada'): ?>
                                        Tu pedido será entregado en las próximas 24 horas.
                                    <?php else: ?>
                                        La entrega se realizará 24 horas después de confirmar el pago.
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-footer">
                    <a href="mis_pedidos.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Volver a Mis Pedidos
                    </a>
                    
                    <?php if ($pedidoSeleccionado['metodo_pago'] === 'OXXO' && $pedidoSeleccionado['estado'] === 'pendiente'): ?>
                        <a href="generar_comprobante.php?referencia=<?php echo $pedidoSeleccionado['referencia_pago']; ?>" class="btn btn-primary">
                            <i class="fas fa-print"></i> Reimprimir Comprobante
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        <?php else: ?>
            <!-- Pestañas para los diferentes estados de pedidos -->
            <div class="tabs">
                <div class="tab active" data-tab="en-proceso">
                    En Proceso
                    <?php if (count($pedidosEnProceso) > 0): ?>
                        <span class="tab-badge"><?php echo count($pedidosEnProceso); ?></span>
                    <?php endif; ?>
                </div>
                <div class="tab" data-tab="pendientes">
                    Pendientes de Pago
                    <?php if (count($pedidosPendientesPago) > 0): ?>
                        <span class="tab-badge"><?php echo count($pedidosPendientesPago); ?></span>
                    <?php endif; ?>
                </div>
                <div class="tab" data-tab="entregados">
                    Historial de Entregas
                </div>
                <div class="tab" data-tab="cancelados">
                    Cancelados
                </div>
            </div>
            
            <!-- Contenido de la pestaña "En Proceso" -->
            <div class="tab-content active" id="en-proceso">
                <?php if (empty($pedidosEnProceso)): ?>
                    <div class="empty-state">
                        <i class="fas fa-truck"></i>
                        <h3>No tienes pedidos en proceso</h3>
                        <p>Cuando realices una compra, podrás ver aquí el estado de tus pedidos en camino.</p>
                        <a href="/" class="btn btn-primary">Ir a la tienda</a>
                    </div>
                <?php else: ?>
                    <?php foreach ($pedidosEnProceso as $pedido): ?>
                        <div class="card">
                            <div class="card-header">
                                <h2 class="card-title">Pedido #<?php echo $pedido['id']; ?></h2>
                                <span class="badge badge-info">En proceso</span>
                            </div>
                            <div class="card-body">
                                <div class="pedido-info">
                                    <div class="info-item">
                                        <div class="info-label">Fecha del Pedido</div>
                                        <div class="info-value"><?php echo date('d/m/Y H:i', strtotime($pedido['fecha_venta'])); ?></div>
                                    </div>
                                    
                                    <div class="info-item">
                                        <div class="info-label">Método de Pago</div>
                                        <div class="info-value">
                                            <?php if ($pedido['metodo_pago'] === 'online'): ?>
                                                <i class="fas fa-credit-card"></i> Pago en línea
                                            <?php else: ?>
                                                <i class="fas fa-store"></i> Pago en OXXO
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    
                                    <div class="info-item">
                                        <div class="info-label">Total</div>
                                        <div class="info-value">$<?php echo number_format($pedido['total'], 2); ?></div>
                                    </div>
                                    
                                    <div class="info-item">
                                        <div class="info-label">Entrega Estimada</div>
                                        <div class="info-value"><?php echo date('d/m/Y H:i', strtotime($pedido['fecha_entrega'])); ?></div>
                                    </div>
                                </div>
                            </div>
                            <div class="card-footer">
                                <span>Tiempo restante: 
                                    <?php 
                                        $ahora = new DateTime();
                                        $fechaEntrega = new DateTime($pedido['fecha_entrega']);
                                        $intervalo = $ahora->diff($fechaEntrega);
                                        
                                        if ($intervalo->days > 0) {
                                            echo $intervalo->format('%d día(s), %h hora(s)');
                                        } else {
                                            echo $intervalo->format('%h hora(s), %i minuto(s)');
                                        }
                                    ?>
                                </span>
                                <a href="mis_pedidos.php?id=<?php echo $pedido['id']; ?>" class="btn btn-primary">
                                    <i class="fas fa-eye"></i> Ver Detalles
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            
            <!-- Contenido de la pestaña "Pendientes de Pago" -->
            <div class="tab-content" id="pendientes">
                <?php if (empty($pedidosPendientesPago)): ?>
                    <div class="empty-state">
                        <i class="fas fa-money-bill-wave"></i>
                        <h3>No tienes pagos pendientes</h3>
                        <p>Cuando realices una compra con pago en OXXO, podrás ver aquí tus pagos pendientes.</p>
                        <a href="/" class="btn btn-primary">Ir a la tienda</a>
                    </div>
                <?php else: ?>
                    <?php foreach ($pedidosPendientesPago as $pedido): ?>
                        <div class="card">
                            <div class="card-header">
                                <h2 class="card-title">Pedido #<?php echo $pedido['id']; ?></h2>
                                <span class="badge badge-warning">Pendiente de pago</span>
                            </div>
                            <div class="card-body">
                                <div class="pedido-info">
                                    <div class="info-item">
                                        <div class="info-label">Fecha del Pedido</div>
                                        <div class="info-value"><?php echo date('d/m/Y H:i', strtotime($pedido['fecha_venta'])); ?></div>
                                    </div>
                                    
                                    <div class="info-item">
                                        <div class="info-label">Método de Pago</div>
                                        <div class="info-value">
                                            <i class="fas fa-store"></i> Pago en OXXO
                                        </div>
                                    </div>
                                    
                                    <div class="info-item">
                                        <div class="info-label">Total</div>
                                        <div class="info-value">$<?php echo number_format($pedido['total'], 2); ?></div>
                                    </div>
                                    
                                    <div class="info-item">
                                        <div class="info-label">Referencia de Pago</div>
                                        <div class="info-value" style="font-family: monospace; background: #f5f5f5; padding: 5px 10px; border-radius: 4px;">
                                            <?php echo $pedido['referencia_pago']; ?>
                                        </div>
                                    </div>
                                </div>
                                
                                <div style="margin-top: 15px; padding: 10px; background-color: #fff3cd; border-radius: 6px; color: #856404;">
                                    <i class="fas fa-exclamation-triangle"></i> 
                                    <strong>Importante:</strong> Realiza el pago en OXXO antes de 48 horas para evitar que tu pedido sea cancelado.
                                </div>
                            </div>
                            <div class="card-footer">
                                <a href="generar_comprobante.php?referencia=<?php echo $pedido['referencia_pago']; ?>" class="btn btn-warning">
                                    <i class="fas fa-print"></i> Imprimir Comprobante
                                </a>
                                <a href="mis_pedidos.php?id=<?php echo $pedido['id']; ?>" class="btn btn-primary">
                                    <i class="fas fa-eye"></i> Ver Detalles
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            
            <!-- Contenido de la pestaña "Historial de Entregas" -->
            <div class="tab-content" id="entregados">
                <?php if (empty($pedidosEntregados)): ?>
                    <div class="empty-state">
                        <i class="fas fa-box-open"></i>
                        <h3>No tienes pedidos entregados</h3>
                        <p>Aquí podrás ver el historial de tus pedidos entregados.</p>
                        <a href="/" class="btn btn-primary">Ir a la tienda</a>
                    </div>
                <?php else: ?>
                    <?php foreach ($pedidosEntregados as $pedido): ?>
                        <div class="card">
                            <div class="card-header">
                                <h2 class="card-title">Pedido #<?php echo $pedido['id']; ?></h2>
                                <span class="badge badge-success">Entregado</span>
                            </div>
                            <div class="card-body">
                                <div class="pedido-info">
                                    <div class="info-item">
                                        <div class="info-label">Fecha del Pedido</div>
                                        <div class="info-value"><?php echo date('d/m/Y H:i', strtotime($pedido['fecha_venta'])); ?></div>
                                    </div>
                                    
                                    <div class="info-item">
                                        <div class="info-label">Método de Pago</div>
                                        <div class="info-value">
                                            <?php if ($pedido['metodo_pago'] === 'online'): ?>
                                                <i class="fas fa-credit-card"></i> Pago en línea
                                            <?php else: ?>
                                                <i class="fas fa-store"></i> Pago en OXXO
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    
                                    <div class="info-item">
                                        <div class="info-label">Total</div>
                                        <div class="info-value">$<?php echo number_format($pedido['total'], 2); ?></div>
                                    </div>
                                    
                                    <div class="info-item">
                                        <div class="info-label">Fecha de Entrega</div>
                                        <div class="info-value"><?php echo date('d/m/Y H:i', strtotime($pedido['fecha_entrega'])); ?></div>
                                    </div>
                                </div>
                            </div>
                            <div class="card-footer">
                                <a href="mis_pedidos.php?id=<?php echo $pedido['id']; ?>" class="btn btn-primary">
                                    <i class="fas fa-eye"></i> Ver Detalles
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            
            <!-- Contenido de la pestaña "Cancelados" -->
            <div class="tab-content" id="cancelados">
                <?php if (empty($pedidosCancelados)): ?>
                    <div class="empty-state">
                        <i class="fas fa-ban"></i>
                        <h3>No tienes pedidos cancelados</h3>
                        <p>Aquí podrás ver los pedidos que han sido cancelados.</p>
                        <a href="/" class="btn btn-primary">Ir a la tienda</a>
                    </div>
                <?php else: ?>
                    <?php foreach ($pedidosCancelados as $pedido): ?>
                        <div class="card">
                            <div class="card-header">
                                <h2 class="card-title">Pedido #<?php echo $pedido['id']; ?></h2>
                                <span class="badge badge-danger">Cancelado</span>
                            </div>
                            <div class="card-body">
                                <div class="pedido-info">
                                    <div class="info-item">
                                        <div class="info-label">Fecha del Pedido</div>
                                        <div class="info-value"><?php echo date('d/m/Y H:i', strtotime($pedido['fecha_venta'])); ?></div>
                                    </div>
                                    
                                    <div class="info-item">
                                        <div class="info-label">Método de Pago</div>
                                        <div class="info-value">
                                            <?php if ($pedido['metodo_pago'] === 'online'): ?>
                                                <i class="fas fa-credit-card"></i> Pago en línea
                                            <?php else: ?>
                                                <i class="fas fa-store"></i> Pago en OXXO
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    
                                    <div class="info-item">
                                        <div class="info-label">Total</div>
                                        <div class="info-value">$<?php echo number_format($pedido['total'], 2); ?></div>
                                    </div>
                                </div>
                                
                                <div style="margin-top: 15px; padding: 10px; background-color: #f8d7da; border-radius: 6px; color: #721c24;">
                                    <i class="fas fa-info-circle"></i> 
                                    <strong>Motivo de cancelación:</strong> 
                                    <?php if ($pedido['metodo_pago'] === 'OXXO'): ?>
                                        El pago no fue realizado dentro del tiempo límite.
                                    <?php else: ?>
                                        El pedido fue cancelado.
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="card-footer">
                                <a href="mis_pedidos.php?id=<?php echo $pedido['id']; ?>" class="btn btn-primary">
                                    <i class="fas fa-eye"></i> Ver Detalles
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <script>
        function toggleMenu() {
            const menu = document.querySelector('.nav-menu ul');
            menu.classList.toggle('active');
        }
        
        // Funcionalidad de pestañas
        document.addEventListener('DOMContentLoaded', function() {
            const tabs = document.querySelectorAll('.tab');
            const tabContents = document.querySelectorAll('.tab-content');
            
            tabs.forEach(tab => {
                tab.addEventListener('click', () => {
                    // Remover clase active de todas las pestañas
                    tabs.forEach(t => t.classList.remove('active'));
                    
                    // Agregar clase active a la pestaña clickeada
                    tab.classList.add('active');
                    
                    // Mostrar el contenido correspondiente
                    const tabId = tab.getAttribute('data-tab');
                    tabContents.forEach(content => {
                        content.classList.remove('active');
                        if (content.id === tabId) {
                            content.classList.add('active');
                        }
                    });
                });
            });
        });
    </script>

    <footer>
        <div class="lo">
            <img src="../img/chielito-re.png"" alt="Imagen de la panadería" width="150" height="150">
        </div>

        <nav>
            <p>Encuéntranos en redes sociales:</p>
            <ul>
                <li>
                    <a title="Facebook" href="#">
                        <img src="../img/facebook.png" alt="Facebook" width="30" height="30">
                    </a>
                </li>
                <li>
                    <a title="Instagram" href="#">
                        <img src="../img/insta.png" alt="Instagram" width="30" height="30">
                    </a>
                </li>
                <li>
                    <a title="Twitter" href="#">
                        <img src="../img/x.png" alt="Twitter" width="30" height="30">
                    </a>
                </li>
            </ul>
        </nav>

        <nav class="footer-nav">
            <p>Enlaces importantes:</p>
            <a href="#about">Sobre Nosotros</a>
            <a href="#contact">Contacto</a>
            <a href="#privacy">Política de Privacidad</a>
            <a href="#terms">Términos de Servicio</a>
        </nav>

        <div class="footer-content">
            <p>&copy; 2024 PanChielito. Todos los derechos reservados.</p>
            <p>Trabajo académico CETI Tonalá 2025</p>
        </div>
    </footer>
</body>
</html>
