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
$stmt = $mysqli->prepare("SELECT nombre, apellido, dinero FROM usuarios WHERE id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
$usuario = $result->fetch_assoc();

if (!$usuario) {
    die("Error: No se pudo obtener la información del usuario.");
}

// Ruta al archivo JSON del carrito
$rutaArchivo = __DIR__ . 'carritos.json';

// Verificar si el archivo existe
if (!file_exists($rutaArchivo)) {
    die("Error: No se encontró el archivo del carrito.");
}

// Obtener los datos del carrito
$contenido = file_get_contents($rutaArchivo);
$carritos = json_decode($contenido, true) ?: [];
$carrito = isset($carritos[$userId]) ? $carritos[$userId] : [];

// Calcular el total del carrito
$total = 0;
foreach ($carrito as $item) {
    $total += $item['precio'] * $item['cantidad'];
}

// Verificar si hay productos no disponibles
$productosNoDisponibles = [];
foreach ($carrito as $index => $item) {
    $stmt = $mysqli->prepare("SELECT des_gak, unidades_disponibles FROM productos WHERE nombre = ?");
    $stmt->bind_param("s", $item['nombre']);
    $stmt->execute();
    $result = $stmt->get_result();
    $producto = $result->fetch_assoc();
    
    if (!$producto || $producto['des_gak'] != 1) {
        $productosNoDisponibles[] = [
            'index' => $index,
            'nombre' => $item['nombre'],
            'motivo' => !$producto ? 'no_existe' : 'no_visible'
        ];
    } else if ($producto['unidades_disponibles'] < $item['cantidad']) {
        $productosNoDisponibles[] = [
            'index' => $index,
            'nombre' => $item['nombre'],
            'solicitado' => $item['cantidad'],
            'disponible' => $producto['unidades_disponibles'],
            'motivo' => 'stock_insuficiente'
        ];
    }
}

// Verificar si se ha enviado el formulario de pago
$mensajeError = '';
$mensajeExito = '';
$stockInsuficiente = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($productosNoDisponibles)) {
    // Verificar stock de cada producto
    $stockOK = true;
    $productosInfo = []; // Para almacenar información completa de los productos
    
    foreach ($carrito as $item) {
        // Consultar stock actual e ID del producto
        $stmt = $mysqli->prepare("SELECT id, unidades_disponibles, des_gak FROM productos WHERE nombre = ?");
        $stmt->bind_param("s", $item['nombre']);
        $stmt->execute();
        $result = $stmt->get_result();
        $producto = $result->fetch_assoc();
        
        if (!$producto) {
            $mensajeError = "Error: Producto no encontrado: " . htmlspecialchars($item['nombre']);
            $stockOK = false;
            break;
        }
        
        if ($producto['des_gak'] != 1) {
            $mensajeError = "Error: El producto " . htmlspecialchars($item['nombre']) . " ya no está disponible para la venta.";
            $stockOK = false;
            break;
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
    
    // Si hay suficiente stock, procesar según el método de pago
    if ($stockOK) {
        $metodo_pago = isset($_POST['metodo_pago']) ? $_POST['metodo_pago'] : 'online';
        
        if ($metodo_pago === 'online') {
            // Verificar si el usuario tiene suficiente saldo
            if ($usuario['dinero'] < $total) {
                $mensajeError = "No tienes suficiente saldo para completar esta compra. Tu saldo actual es $" . number_format($usuario['dinero'], 2);
            } else {
                // Iniciar transacción
                $mysqli->begin_transaction();
                
                try {
                    // Actualizar saldo del usuario
                    $nuevoSaldo = $usuario['dinero'] - $total;
                    $stmt = $mysqli->prepare("UPDATE usuarios SET dinero = ? WHERE id = ?");
                    $stmt->bind_param("di", $nuevoSaldo, $userId);
                    $stmt->execute();
                    
                    // Registrar la venta en la tabla ventas
                    $stmt = $mysqli->prepare("INSERT INTO ventas (usuario_id, total, metodo_pago, estado) VALUES (?, ?, 'online', 'completada')");
                    $stmt->bind_param("id", $userId, $total);
                    $stmt->execute();
                    $ventaId = $mysqli->insert_id;
                    
                    // Registrar los detalles de la venta
                    foreach ($productosInfo as $producto) {
                        $stmt = $mysqli->prepare("INSERT INTO detalles_venta (venta_id, producto_id, nombre_producto, cantidad, precio_unitario, subtotal) VALUES (?, ?, ?, ?, ?, ?)");
                        $stmt->bind_param("iisidi", $ventaId, $producto['id'], $producto['nombre'], $producto['cantidad'], $producto['precio'], $producto['subtotal']);
                        $stmt->execute();
                        
                        // Actualizar stock de productos
                        $stmt = $mysqli->prepare("UPDATE productos SET unidades_disponibles = unidades_disponibles - ? WHERE id = ?");
                        $stmt->bind_param("ii", $producto['cantidad'], $producto['id']);
                        $stmt->execute();
                    }
                    
                    // Actualizar estadísticas de ventas
                    $fechaHoy = date('Y-m-d');
                    
                    // Intentar actualizar registro existente
                    $stmt = $mysqli->prepare("INSERT INTO estadisticas_ventas (fecha, total_ventas, cantidad_ventas) 
                                             VALUES (?, ?, 1) 
                                             ON DUPLICATE KEY UPDATE 
                                             total_ventas = total_ventas + ?, 
                                             cantidad_ventas = cantidad_ventas + 1");
                    $stmt->bind_param("sdd", $fechaHoy, $total, $total);
                    $stmt->execute();
                    
                    // Confirmar transacción
                    $mysqli->commit();
                    
                    // Vaciar el carrito
                    unset($carritos[$userId]);
                    file_put_contents($rutaArchivo, json_encode($carritos, JSON_PRETTY_PRINT));
                    
                    $mensajeExito = "¡Pago realizado con éxito! Tu nuevo saldo es $" . number_format($nuevoSaldo, 2);
                    $carrito = []; // Vaciar carrito en la vista
                    $total = 0;
                    
                } catch (Exception $e) {
                    // Revertir transacción en caso de error
                    $mysqli->rollback();
                    $mensajeError = "Error al procesar el pago: " . $e->getMessage();
                }
            }
        } else if ($metodo_pago === 'oxxo') {
            // No se procesa el pago aquí, solo se prepara para generar el PDF
            // El procesamiento real se hace en generar_comprobante.php
        }
    }
}

// Resto del código HTML permanece igual...
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Finalizar Compra - Panchielito</title>
    <link rel="stylesheet" type="text/css" href="../desProductos/Desproductos.css">
    <link rel="stylesheet" type="text/css" href="../desProductos/carrito.css">
    <link rel="stylesheet" type="text/css" href="../css/estilospie.css">
    <link href="https://fonts.googleapis.com/css2?family=Dancing+Script:wght@400..700&display=swap" rel="stylesheet">
    <link rel="icon" type="image/png" href="../img/chielito-re.png">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <style>
        /* =========================
    Estilos para Finalizar Compra
========================== */

.contenedor-pago {
    max-width: 1000px;
    margin: 30px auto;
    padding: 25px;
    background-color: #fff8f2;
    border-radius: 12px;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
    border: 1px solid #e6b8af;
}

.encabezado-pago {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 25px;
    padding-bottom: 15px;
    border-bottom: 2px solid #e6b8af;
}

.encabezado-pago h2 {
    color: #6d0000;
    font-family: 'Dancing Script', cursive;
    font-size: 2.2rem;
    margin: 0;
}

.info-usuario {
    background-color: #fef5f4;
    padding: 20px;
    border-radius: 8px;
    margin-bottom: 25px;
    border-left: 4px solid #a93226;
}

.info-usuario h3 {
    margin-top: 0;
    color: #6d0000;
    font-size: 1.3rem;
    margin-bottom: 15px;
}

.info-usuario p {
    margin: 8px 0;
    font-size: 1.1rem;
    color: #333;
}

.saldo {
    font-size: 1.3em;
    font-weight: bold;
    color: #27ae60;
    padding: 5px 10px;
    background-color: #e7f9e7;
    border-radius: 5px;
    display: inline-block;
}

.saldo.insuficiente {
    color: #e74c3c;
    background-color: #ffecec;
}

.tabla-carrito {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 25px;
    background-color: white;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.tabla-carrito th {
    background-color: #a93226;
    color: white;
    font-weight: bold;
    padding: 15px;
    text-align: left;
}

.tabla-carrito td {
    padding: 12px 15px;
    border-bottom: 1px solid #f0e6e6;
    color: #333;
}

.tabla-carrito tr:last-child td {
    border-bottom: none;
}

.tabla-carrito tr:hover {
    background-color: #fef5f4;
}

.total-pago {
    text-align: right;
    font-size: 1.5em;
    margin: 25px 0;
    padding: 20px;
    background-color: #fef5f4;
    border-radius: 8px;
    border-left: 4px solid #a93226;
    color: #6d0000;
    font-weight: bold;
}

.botones-accion {
    display: flex;
    justify-content: space-between;
    margin-top: 30px;
}

.btn-volver, .btn-pagar {
    padding: 14px 28px;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    font-size: 1.1rem;
    font-weight: bold;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    gap: 10px;
}

.btn-volver {
    background-color: #95a5a6;
    color: white;
    text-decoration: none;
}

.btn-volver:hover {
    background-color: #7f8c8d;
    transform: translateY(-2px);
}

.btn-pagar {
    background-color: #a93226;
    color: white;
}

.btn-pagar:hover:not(:disabled) {
    background-color: #8B0000;
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(139, 0, 0, 0.2);
}

.btn-pagar:disabled {
    background-color: #bdc3c7;
    cursor: not-allowed;
}

.mensaje {
    padding: 18px;
    margin: 25px 0;
    border-radius: 8px;
    display: flex;
    align-items: center;
    gap: 15px;
    font-size: 1.1rem;
}

.mensaje i {
    font-size: 1.5rem;
}

.mensaje-error {
    background-color: #ffecec;
    color: #e74c3c;
    border-left: 5px solid #e74c3c;
}

.mensaje-exito {
    background-color: #e7f9e7;
    color: #27ae60;
    border-left: 5px solid #27ae60;
}

.stock-warning {
    background-color: #fff9e6;
    padding: 20px;
    margin: 25px 0;
    border-radius: 8px;
    border-left: 5px solid #f39c12;
}

.stock-warning h3 {
    color: #d35400;
    margin-top: 0;
    font-size: 1.3rem;
    display: flex;
    align-items: center;
    gap: 10px;
}

.stock-warning ul {
    margin: 15px 0 0 0;
    padding-left: 25px;
}

.stock-warning li {
    margin-bottom: 8px;
    font-size: 1.1rem;
    color: #7f8c8d;
}

.carrito-vacio {
    text-align: center;
    padding: 50px 0;
    color: #7f8c8d;
}

.carrito-vacio i {
    font-size: 60px;
    margin-bottom: 20px;
    color: #bdc3c7;
}

.carrito-vacio h3 {
    font-size: 1.8rem;
    margin-bottom: 15px;
    color: #6d0000;
}

.carrito-vacio p {
    font-size: 1.2rem;
    margin-bottom: 25px;
}

/* Estilos para los métodos de pago */
.metodos-pago {
    margin: 30px 0;
    border: 1px solid #e6b8af;
    border-radius: 12px;
    overflow: hidden;
    background-color: white;
}

.metodo-pago {
    padding: 20px;
    border-bottom: 1px solid #e6b8af;
    display: flex;
    align-items: center;
    transition: background-color 0.3s ease;
}

.metodo-pago:last-child {
    border-bottom: none;
}

.metodo-pago:hover {
    background-color: #fef5f4;
}

.metodo-pago input[type="radio"] {
    margin-right: 20px;
    transform: scale(1.3);
    accent-color: #a93226;
}

.metodo-pago-info {
    flex: 1;
}

.metodo-pago-titulo {
    font-weight: bold;
    margin-bottom: 8px;
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 1.2rem;
    color: #6d0000;
}

.metodo-pago-titulo i {
    font-size: 1.5rem;
}

.metodo-pago-descripcion {
    color: #7f8c8d;
    font-size: 1rem;
    line-height: 1.5;
}

.metodo-pago-logo {
    margin-left: 20px;
    height: 40px;
}

/* Formularios de pago */
#form-online, #form-oxxo {
    padding: 20px 0;
}

/* Productos no disponibles */
.producto-no-disponible {
    background-color: #ffecec;
    border-left: 4px solid #e74c3c;
    padding: 10px;
    margin-bottom: 10px;
    border-radius: 4px;
}

.advertencia-productos {
    background-color: #ffecec;
    border-left: 5px solid #e74c3c;
    padding: 20px;
    margin: 25px 0;
    border-radius: 8px;
    color: #e74c3c;
}

.advertencia-productos h3 {
    margin-top: 0;
    font-size: 1.3rem;
    display: flex;
    align-items: center;
    gap: 10px;
}

.advertencia-productos ul {
    margin: 15px 0 0 0;
    padding-left: 25px;
}

.advertencia-productos li {
    margin-bottom: 8px;
    font-size: 1.1rem;
}

.btn-actualizar-carrito {
    background-color: #e74c3c;
    color: white;
    border: none;
    padding: 10px 20px;
    border-radius: 6px;
    cursor: pointer;
    font-weight: bold;
    margin-top: 15px;
    display: inline-block;
    text-decoration: none;
}

.btn-actualizar-carrito:hover {
    background-color: #c0392b;
}

/* Responsive */
@media (max-width: 768px) {
    .contenedor-pago {
        margin: 15px;
        padding: 15px;
    }
    
    .encabezado-pago {
        flex-direction: column;
        align-items: flex-start;
        gap: 15px;
    }
    
    .botones-accion {
        flex-direction: column;
        gap: 15px;
    }
    
    .btn-volver, .btn-pagar {
        width: 100%;
        justify-content: center;
    }
    
    .metodo-pago {
        flex-direction: column;
        align-items: flex-start;
        gap: 15px;
    }
    
    .metodo-pago-logo {
        margin-left: 0;
        margin-top: 10px;
        align-self: flex-end;
    }
    
    .tabla-carrito {
        font-size: 0.9rem;
    }
    
    .tabla-carrito th, .tabla-carrito td {
        padding: 10px;
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
                    echo '<h2>¡Hola ' . htmlspecialchars($_SESSION['nombre']) . '!</h2>';
                } else {
                    echo '<h1>¡Bienvenido!</h1>';
                }
            ?>
        </div>
        <nav class="nav-menu">
            <button class="menu-btn" onclick="toggleMenu()">☰</button>
            <ul>
                <li><a href="/" class="boton-regresar">Inicio</a></li>
                     <li><a href="../Galeria/biscocho.php" "BIZCOCHO">BIZCOCHO</a></li>
                    <li><a href="../Galeria/danes.php" >DANÉS</a></li>
                    <li><a href="../Galeria/hojaldre.php" >HOJALDRE</a></li>
            </ul>
        </nav>
    </header>

    <div class="contenedor-pago">
        <div class="encabezado-pago">
            <h2>Finalizar Compra</h2>
            <a href="javascript:history.back()" class="btn-volver">
                <i class="fas fa-arrow-left"></i> Volver
            </a>
        </div>
        
        <?php if ($mensajeError): ?>
            <div class="mensaje mensaje-error">
                <i class="fas fa-exclamation-circle"></i> <?php echo $mensajeError; ?>
            </div>
        <?php endif; ?>
        
        <?php if ($mensajeExito): ?>
            <div class="mensaje mensaje-exito">
                <i class="fas fa-check-circle"></i> <?php echo $mensajeExito; ?>
            </div>
        <?php endif; ?>
        
        <div class="info-usuario">
            <h3>Información del Cliente</h3>
            <p><strong>Nombre:</strong> <?php echo htmlspecialchars($usuario['nombre'] . ' ' . $usuario['apellido']); ?></p>
            <p class="saldo <?php echo ($usuario['dinero'] < $total) ? 'insuficiente' : ''; ?>">
                <strong>Saldo disponible:</strong> $<?php echo number_format($usuario['dinero'], 2); ?>
            </p>
        </div>
        
        <?php if (!empty($productosNoDisponibles)): ?>
            <div class="advertencia-productos">
                <h3><i class="fas fa-exclamation-triangle"></i> Productos no disponibles</h3>
                <p>Los siguientes productos en tu carrito ya no están disponibles para la venta:</p>
                <ul>
                    <?php foreach ($productosNoDisponibles as $item): ?>
                        <li>
                            <strong><?php echo htmlspecialchars($item['nombre']); ?></strong>: 
                            <?php if ($item['motivo'] === 'no_existe'): ?>
                                Este producto ya no existe en nuestro catálogo.
                            <?php elseif ($item['motivo'] === 'no_visible'): ?>
                                Este producto ya no está disponible para la venta.
                            <?php elseif ($item['motivo'] === 'stock_insuficiente'): ?>
                                Solicitaste <?php echo $item['solicitado']; ?> unidades, 
                                pero solo hay <?php echo $item['disponible']; ?> disponibles.
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
                <p>Por favor, actualiza tu carrito antes de continuar con la compra.</p>
                <a href="../Galeria/carrito.php" class="btn-actualizar-carrito">
                    <i class="fas fa-shopping-cart"></i> Actualizar carrito
                </a>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($stockInsuficiente)): ?>
            <div class="stock-warning">
                <h3><i class="fas fa-exclamation-triangle"></i> Stock insuficiente</h3>
                <p>Los siguientes productos no tienen suficiente stock:</p>
                <ul>
                    <?php foreach ($stockInsuficiente as $item): ?>
                        <li>
                            <strong><?php echo htmlspecialchars($item['nombre']); ?></strong>: 
                            Solicitaste <?php echo $item['solicitado']; ?> unidades, 
                            pero solo hay <?php echo $item['disponible']; ?> disponibles.
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <?php if (empty($carrito)): ?>
            <div class="carrito-vacio">
                <i class="fas fa-shopping-cart"></i>
                <h3>Tu carrito está vacío</h3>
                <p>Regresa a la tienda para agregar productos a tu carrito.</p>
                <a href="/" class="btn-volver" style="margin-top: 15px;">Ir a la tienda</a>
            </div>
        <?php else: ?>
            <h3>Resumen de tu pedido</h3>
            <table class="tabla-carrito">
                <thead>
                    <tr>
                        <th>Producto</th>
                        <th>Precio unitario</th>
                        <th>Cantidad</th>
                        <th>Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($carrito as $item): ?>
                        <tr <?php 
                            $esNoDisponible = false;
                            foreach ($productosNoDisponibles as $noDisp) {
                                if ($noDisp['nombre'] === $item['nombre']) {
                                    $esNoDisponible = true;
                                    break;
                                }
                            }
                            echo $esNoDisponible ? 'class="producto-no-disponible"' : '';
                        ?>>
                            <td><?php echo htmlspecialchars($item['nombre']); ?></td>
                            <td>$<?php echo number_format($item['precio'], 2); ?></td>
                            <td><?php echo $item['cantidad']; ?></td>
                            <td>$<?php echo number_format($item['precio'] * $item['cantidad'], 2); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <div class="total-pago">
                <strong>Total a pagar:</strong> $<?php echo number_format($total, 2); ?>
            </div>
            
            <?php if (empty($productosNoDisponibles)): ?>
                <h3>Selecciona tu método de pago</h3>
                <div class="metodos-pago">
                    <div class="metodo-pago">
                        <input type="radio" id="pago-online" name="metodo_pago" value="online" checked onchange="mostrarFormulario('online')">
                        <div class="metodo-pago-info">
                            <label for="pago-online" class="metodo-pago-titulo">
                                <i class="fas fa-credit-card"></i> Pago con saldo en línea
                            </label>
                            <p class="metodo-pago-descripcion">Paga directamente con tu saldo disponible en la plataforma.</p>
                        </div>
                    </div>
                    
                    <div class="metodo-pago">
                        <input type="radio" id="pago-oxxo" name="metodo_pago" value="oxxo" onchange="mostrarFormulario('oxxo')">
                        <div class="metodo-pago-info">
                            <label for="pago-oxxo" class="metodo-pago-titulo">
                                <i class="fas fa-store"></i> Pago en OXXO
                            </label>
                            <p class="metodo-pago-descripcion">Genera un comprobante y paga en cualquier tienda OXXO.</p>
                        </div>
                        <img src="../img/oxxo.png" alt="OXXO" class="metodo-pago-logo">
                    </div>
                </div>
                
                <!-- Formulario para pago en línea -->
                <form id="form-online" method="post" action="">
                    <input type="hidden" name="metodo_pago" value="online">
                    <div class="botones-accion">
                        <a href="javascript:history.back()" class="btn-volver">Cancelar</a>
                        <button type="submit" name="confirmar_pago" class="btn-pagar" <?php echo ($usuario['dinero'] < $total || !empty($stockInsuficiente)) ? 'disabled' : ''; ?>>
                            Confirmar Pago
                        </button>
                    </div>
                </form>
                <!-- Formulario para pago en OXXO -->
                <form id="form-oxxo" method="post" action="generar_comprobante.php" style="display: none;">
                <input type="hidden" name="metodo_pago" value="oxxo">
                <input type="hidden" name="productos" value='<?php echo json_encode($carrito); ?>'>
                <input type="hidden" name="total" value="<?php echo $total; ?>">
                <div class="botones-accion">
                    <a href="javascript:history.back()" class="btn-volver">Cancelar</a>
                    <button type="submit" class="btn-pagar" <?php echo !empty($stockInsuficiente) ? 'disabled' : ''; ?>>
                        Generar Comprobante de Pago
                    </button>
                </div>
            </form>
            <?php else: ?>
                <div class="botones-accion">
                    <a href="../Galeria/carrito.php" class="btn-pagar">
                        <i class="fas fa-shopping-cart"></i> Actualizar carrito
                    </a>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <script>
            function toggleMenu() {
                const menu = document.querySelector('.nav-menu ul');
                menu.classList.toggle('active');
            }
            
            function mostrarFormulario(tipo) {
        if (tipo === 'online') {
            document.getElementById('form-online').style.display = 'block';
            document.getElementById('form-oxxo').style.display = 'none';
        } else if (tipo === 'oxxo') {
            document.getElementById('form-online').style.display = 'none';
            document.getElementById('form-oxxo').style.display = 'block';
        }
    }
    </script>
    <script src="../menu.js"></script>

    <footer>
        <div class="lo">
            <img src="../img/Chielito.png" alt="Imagen de la panadería" width="150" height="150">
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
