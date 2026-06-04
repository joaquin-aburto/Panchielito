<?php
session_start();

// Verificar si el usuario está autenticado
if (!isset($_SESSION['id'])) {
    header('Location: https://panchielito.com/Login/');
    exit;
}

// Incluir el archivo de conexión
require_once '../controladoresPrincipales/conexion.php';

// Verificar si se recibió la referencia de un pedido existente
if (isset($_GET['referencia'])) {
    $referencia = $_GET['referencia'];
    
    // Buscar la venta con esa referencia
    $stmt = $mysqli->prepare("
        SELECT v.*, u.nombre, u.apellido, u.email, u.telefono
        FROM ventas v
        JOIN usuarios u ON v.usuario_id = u.id
        WHERE v.referencia_pago = ? AND v.usuario_id = ?
    ");
    $stmt->bind_param("si", $referencia, $_SESSION['id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $venta = $result->fetch_assoc();
    
    if (!$venta) {
        die("Error: No se encontró el pedido con la referencia proporcionada.");
    }
    
    // Obtener los productos del pedido
    $stmt = $mysqli->prepare("
        SELECT dv.nombre_producto as nombre, dv.precio_unitario as precio, dv.cantidad
        FROM detalles_venta dv
        WHERE dv.venta_id = ?
    ");
    $stmt->bind_param("i", $venta['id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $productos = $result->fetch_all(MYSQLI_ASSOC);
    
    $total = $venta['total'];
    $fechaVencimiento = $venta['fecha_pago'];
    $fechaVencimientoFormateada = date('d/m/Y H:i', strtotime($fechaVencimiento));
    $comprobante_generado = true;
    $usuario = [
        'nombre' => $venta['nombre'],
        'apellido' => $venta['apellido'],
        'email' => $venta['email'],
        'telefono' => $venta['telefono']
    ];
    
} else if (isset($_POST['productos']) && isset($_POST['total'])) {
    // Procesar un nuevo pedido
    $productos = json_decode($_POST['productos'], true);
    $total = floatval($_POST['total']);
    $userId = $_SESSION['id'];

    // Obtener información del usuario
    $stmt = $mysqli->prepare("SELECT nombre, apellido, email, telefono FROM usuarios WHERE id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $usuario = $result->fetch_assoc();

    if (!$usuario) {
        die("Error: No se pudo obtener la información del usuario.");
    }

    // Generar un código de referencia único para el pago
    $referencia = strtoupper(substr(md5(uniqid(rand(), true)), 0, 10));

    // Fecha de vencimiento (48 horas después)
    $fechaVencimiento = date('Y-m-d H:i:s', strtotime('+48 hours'));
    $fechaVencimientoFormateada = date('d/m/Y H:i', strtotime('+48 hours'));

    // Verificar stock de cada producto
    $stockOK = true;
    $stockInsuficiente = [];
    $productosInfo = []; // Para almacenar información completa de los productos

    foreach ($productos as $item) {
        // Consultar stock actual e ID del producto
        $stmt = $mysqli->prepare("SELECT id, unidades_disponibles, des_gak FROM productos WHERE nombre = ?");
        $stmt->bind_param("s", $item['nombre']);
        $stmt->execute();
        $result = $stmt->get_result();
        $producto = $result->fetch_assoc();
        
        if (!$producto) {
            die("Error: Producto no encontrado: " . htmlspecialchars($item['nombre']));
        }
        
        if ($producto['des_gak'] != 1) {
            die("Error: El producto " . htmlspecialchars($item['nombre']) . " ya no está disponible para la venta.");
        }
        
        if ($producto['unidades_disponibles'] < $item['cantidad']) {
            $stockInsuficiente[] = [
                'nombre' => $item['nombre'],
                'solicitado' => $item['cantidad'],
                'disponible' => $producto['unidades_disponibles']
            ];
            $stockOK = false;
        }
        
        // Guardar información completa del producto
        $productosInfo[] = [
            'id' => $producto['id'],
            'nombre' => $item['nombre'],
            'precio' => $item['precio'],
            'cantidad' => $item['cantidad'],
            'subtotal' => $item['precio'] * $item['cantidad']
        ];
    }

    // Si hay suficiente stock, procesar el pedido
    if ($stockOK) {
        // Iniciar transacción
        $mysqli->begin_transaction();
        
        try {
            // Registrar la venta en la tabla ventas como pendiente
            $stmt = $mysqli->prepare("INSERT INTO ventas (usuario_id, total, metodo_pago, estado, referencia_pago, fecha_pago) VALUES (?, ?, 'OXXO', 'pendiente', ?, ?)");
            $stmt->bind_param("idss", $userId, $total, $referencia, $fechaVencimiento);
            $stmt->execute();
            $ventaId = $mysqli->insert_id;
            
            // Registrar los detalles de la venta
            foreach ($productosInfo as $producto) {
                $stmt = $mysqli->prepare("INSERT INTO detalles_venta (venta_id, producto_id, nombre_producto, cantidad, precio_unitario, subtotal) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("iisidi", $ventaId, $producto['id'], $producto['nombre'], $producto['cantidad'], $producto['precio'], $producto['subtotal']);
                $stmt->execute();
            }
            
            // Actualizar stock de productos (reservar el stock)
            foreach ($productosInfo as $producto) {
                $stmt = $mysqli->prepare("UPDATE productos SET unidades_disponibles = unidades_disponibles - ? WHERE id = ?");
                $stmt->bind_param("ii", $producto['cantidad'], $producto['id']);
                $stmt->execute();
            }
            
            // Confirmar transacción
            $mysqli->commit();
            
            // Vaciar el carrito
            $rutaArchivo = __DIR__ . 'carritos.json';
            if (file_exists($rutaArchivo)) {
                $contenido = file_get_contents($rutaArchivo);
                $carritos = json_decode($contenido, true) ?: [];
                unset($carritos[$userId]);
                file_put_contents($rutaArchivo, json_encode($carritos, JSON_PRETTY_PRINT));
            }
            
            // Mostrar el comprobante (no se hace redirección)
            $comprobante_generado = true;
            
        } catch (Exception $e) {
            // Revertir transacción en caso de error
            $mysqli->rollback();
            die("Error al procesar el pedido: " . $e->getMessage());
        }
    } else {
        // Mostrar error de stock insuficiente
        die("Error: No hay suficiente stock para algunos productos.");
    }
} else {
    die("Error: Datos del pedido incompletos.");
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Comprobante de Pago - Panchielito</title>
    <link href="https://fonts.googleapis.com/css2?family=Dancing+Script:wght@400..700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
     <link rel="icon" type="image/png" href="../img/chielito-re.png">
    <style>
        @media print {
            body {
                width: 21cm;
                height: 29.7cm;
                margin: 0;
                padding: 0;
            }
            .no-print {
                display: none !important;
            }
            .container {
                box-shadow: none !important;
                margin: 0 !important;
                padding: 1cm !important;
            }
        }
        
        :root {
            --primary-color: #6d0000;
            --secondary-color: #a83232;
            --accent-color: #e74c3c;
            --light-bg: #fff8f8;
            --border-color: #f0d0d0;
            --text-color: #333;
            --text-secondary: #666;
            --success-color: #2ecc71;
            --info-color: #3498db;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: var(--text-color);
            background-color: #f5f5f5;
            margin: 0;
            padding: 10px;
        }
        
        .container {
            max-width: 800px;
            margin: 0 auto 30px auto;
            background: linear-gradient(to bottom, #fff, var(--light-bg));
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(168, 50, 50, 0.1);
            border: 1px solid var(--border-color);
            width: 100%;
        }
        
        .header {
            text-align: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid var(--border-color);
            position: relative;
        }
        
        .header h1 {
            font-family: 'Dancing Script', cursive;
            color: var(--primary-color);
            margin: 0 0 10px 0;
            font-size: 28px;
            font-weight: 700;
            text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.1);
        }
        
        .header p {
            color: var(--text-secondary);
            margin: 0;
            font-size: 16px;
            font-weight: 500;
        }
        
        .section {
            margin-bottom: 20px;
            background-color: #fff;
            border-radius: 10px;
            padding: 15px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            border: 1px solid var(--border-color);
        }
        
        .section-title {
            font-size: 18px;
            color: var(--primary-color);
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid var(--border-color);
            font-weight: 600;
        }
        
        .customer-info {
            display: grid;
            grid-template-columns: 1fr;
            gap: 10px;
        }
        
        .customer-info strong {
            color: var(--secondary-color);
            font-size: 15px;
            display: block;
            margin-top: 5px;
        }
        
        .customer-info span {
            font-size: 15px;
            display: block;
            padding-left: 10px;
            border-left: 3px solid var(--border-color);
        }
        
        /* Tabla responsiva */
        .table-responsive {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            margin-bottom: 15px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 300px;
        }
        
        table th, table td {
            padding: 12px 10px;
            text-align: left;
            border-bottom: 1px solid var(--border-color);
            font-size: 14px;
            white-space: nowrap;
        }
        
        table th {
            background-color: var(--primary-color);
            font-weight: bold;
            color: white;
            text-transform: uppercase;
            font-size: 13px;
            letter-spacing: 0.5px;
        }
        
        table tr:nth-child(even) {
            background-color: #fafafa;
        }
        
        table tr:hover {
            background-color: var(--light-bg);
        }
        
        .total-row {
            font-weight: bold;
            background-color: #f9f9f9 !important;
            font-size: 16px;
        }
        
        .total-row td {
            padding: 15px 10px;
            border-top: 2px solid var(--border-color);
        }
        
        .reference {
            text-align: center;
            margin: 20px 0;
            padding: 15px;
            background: linear-gradient(135deg, var(--light-bg), #fff);
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(168, 50, 50, 0.1);
            border: 2px dashed var(--accent-color);
        }
        
        .reference h2 {
            margin: 0 0 10px 0;
            color: var(--primary-color);
            font-family: 'Dancing Script', cursive;
            font-size: 22px;
        }
        
        .reference-code {
            font-family: 'Courier New', monospace;
            font-size: 18px;
            font-weight: bold;
            letter-spacing: 2px;
            color: var(--accent-color);
            background-color: #fff;
            padding: 10px;
            border-radius: 8px;
            display: inline-block;
            width: 100%;
            max-width: 300px;
            border: 1px solid var(--border-color);
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.05);
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .barcode {
            text-align: center;
            margin: 20px 0;
        }
        
        .barcode h3 {
            color: var(--primary-color);
            margin-bottom: 10px;
            font-size: 18px;
        }
        
        .barcode-img {
            height: 60px;
            background: repeating-linear-gradient(
                90deg,
                #000,
                #000 3px,
                #fff 3px,
                #fff 6px
            );
            width: 90%;
            max-width: 300px;
            margin: 0 auto;
            border-radius: 8px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }
        
        .instructions {
            background-color: #fff;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            border-left: 5px solid var(--primary-color);
        }
        
        .instructions ol {
            margin: 0;
            padding-left: 20px;
        }
        
        .instructions li {
            margin-bottom: 8px;
            position: relative;
            padding-left: 5px;
            font-size: 14px;
        }
        
        .instructions li::marker {
            color: var(--primary-color);
            font-weight: bold;
        }
        
        .expiration {
            text-align: center;
            margin: 20px 0;
            padding: 12px;
            background-color: #fff9e6;
            border-radius: 10px;
            color: #f39c12;
            border: 1px solid #ffeeba;
            box-shadow: 0 4px 10px rgba(243, 156, 18, 0.1);
            font-size: 14px;
        }
        
        .expiration strong {
            font-size: 16px;
            display: block;
            margin-bottom: 5px;
        }
        
        .footer {
            text-align: center;
            margin-top: 25px;
            padding-top: 15px;
            border-top: 2px solid var(--border-color);
            color: var(--text-secondary);
            font-size: 13px;
        }
        
        .footer p {
            margin-bottom: 5px;
        }
        
        .actions {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-top: 25px;
            flex-wrap: wrap;
        }
        
        .btn {
            padding: 12px 20px;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            text-align: center;
            transition: all 0.3s ease;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            position: relative;
            overflow: hidden;
            flex: 1;
            min-width: 120px;
            max-width: 200px;
        }
        
        .btn::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 5px;
            height: 5px;
            background: rgba(255, 255, 255, 0.5);
            opacity: 0;
            border-radius: 100%;
            transform: scale(1, 1) translate(-50%);
            transform-origin: 50% 50%;
        }
        
        .btn:hover::after {
            animation: ripple 1s ease-out;
        }
        
        @keyframes ripple {
            0% {
                transform: scale(0, 0);
                opacity: 0.5;
            }
            20% {
                transform: scale(25, 25);
                opacity: 0.5;
            }
            100% {
                opacity: 0;
                transform: scale(40, 40);
            }
        }
        
        .btn-print {
            background: linear-gradient(135deg, #3498db, #2980b9);
            color: white;
        }
        
        .btn-save {
            background: linear-gradient(135deg, #2ecc71, #27ae60);
            color: white;
        }
        
        .btn-back {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
        }
        
        .btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.2);
        }
        
        .btn:active {
            transform: translateY(1px);
        }
        
        /* Estilos responsivos mejorados */
        @media (min-width: 768px) {
            body {
                padding: 20px;
            }
            
            .container {
                padding: 40px;
            }
            
            .header h1 {
                font-size: 36px;
            }
            
            .header p {
                font-size: 18px;
            }
            
            .section {
                padding: 20px;
                margin-bottom: 30px;
            }
            
            .section-title {
                font-size: 20px;
            }
            
            .customer-info {
                grid-template-columns: 120px 1fr;
            }
            
            .customer-info strong {
                display: inline-block;
                margin-top: 0;
            }
            
            .customer-info span {
                display: inline-block;
                border-left: none;
                padding-left: 0;
            }
            
            table th, table td {
                padding: 15px;
                font-size: 16px;
            }
            
            .reference-code {
                font-size: 28px;
                padding: 15px;
            }
            
            .instructions li {
                font-size: 16px;
                margin-bottom: 12px;
            }
            
            .expiration {
                font-size: 16px;
                padding: 15px;
            }
            
            .expiration strong {
                font-size: 18px;
            }
            
            .btn {
                padding: 14px 28px;
                font-size: 16px;
            }
            
            .actions {
                gap: 20px;
            }
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
<header>
        <a href="https://panchielito.com/">
            <img src="https://panchielito.com/img/Chielito.png" id="LogoTipo" alt="Logo de Panchielito">
        </a>
        <div class="header-title">
            <h1>Panchielito</h1>
        </div>
        <nav class="nav-menu">
            <button class="menu-btn" onclick="toggleMenu()">☰</button>
            <ul id="navList">
                <li><a href="https://panchielito.com/desProductos/cobrar.php">Volver</a></li>
                
            </ul>
        </nav>
    </header>
<body>
    <div class="container">
        <div class="header">
            <h1>PANCHIELITO - ORDEN DE PAGO</h1>
            <p>Comprobante para pago en OXXO</p>
        </div>
        
        <div class="section">
            <h2 class="section-title">Información del Cliente</h2>
            <div class="customer-info">
                <strong>Nombre:</strong>
                <span><?php echo htmlspecialchars($usuario['nombre'] . ' ' . $usuario['apellido']); ?></span>
                
                <strong>Email:</strong>
                <span><?php echo htmlspecialchars($usuario['email']); ?></span>
                
                <strong>Teléfono:</strong>
                <span><?php echo htmlspecialchars($usuario['telefono']); ?></span>
            </div>
        </div>
        
        <div class="section">
            <h2 class="section-title">Detalle del Pedido</h2>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Producto</th>
                            <th>Precio</th>
                            <th>Cantidad</th>
                            <th>Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($productos as $producto): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($producto['nombre']); ?></td>
                            <td>$<?php echo number_format($producto['precio'], 2); ?></td>
                            <td><?php echo $producto['cantidad']; ?></td>
                            <td>$<?php echo number_format($producto['precio'] * $producto['cantidad'], 2); ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <tr class="total-row">
                            <td colspan="3" style="text-align: right;">Total a pagar:</td>
                            <td>$<?php echo number_format($total, 2); ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        
        <div class="reference">
            <h2>Referencia de Pago</h2>
            <div class="reference-code"><?php echo $referencia; ?></div>
        </div>
        
        <div class="barcode">
            <h3>Código de Barras</h3>
            <div class="barcode-img"></div>
        </div>
        
        <div class="instructions">
            <h2 class="section-title">Instrucciones de Pago</h2>
            <ol>
                <li>Acude a tu tienda OXXO más cercana.</li>
                <li>Indica al cajero que deseas realizar un pago de servicio.</li>
                <li>Proporciona la referencia de pago mostrada en este comprobante.</li>
                <li>Realiza el pago por la cantidad total indicada.</li>
                <li>Conserva tu recibo como comprobante de pago.</li>
            </ol>
        </div>
        
        <div class="expiration">
            <strong>Fecha límite de pago:</strong> <?php echo $fechaVencimientoFormateada; ?><br>
            <small>Este comprobante tiene una vigencia de 48 horas.</small>
        </div>
        
        <div class="footer">
            <p>Panchielito - Todos los derechos reservados <?php echo date('Y'); ?></p>
            <p>Este documento no es un comprobante fiscal.</p>
        </div>
        
        <div class="actions no-print">
            <button class="btn btn-print" onclick="window.print()">Imprimir</button>
            <button class="btn btn-save" onclick="guardarComoPDF()">Guardar PDF</button>
            <a href="mis_pedidos.php" class="btn btn-back">Mis Pedidos</a>
        </div>
    </div>
    
    <script>
        // Función para guardar como PDF usando la funcionalidad de impresión del navegador
        function guardarComoPDF() {
            window.print();
        }
        
        // Imprimir automáticamente al cargar la página (opcional)
        window.onload = function() {
            // Descomenta la siguiente línea si quieres que se abra el diálogo de impresión automáticamente
            // window.print();
        }
    </script>
    <script src="../menu.js"></script>
</body>
</html>
