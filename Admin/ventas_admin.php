<?php
session_start();
// Verificar admin
if (!isset($_SESSION['usuario']) || $_SESSION['usuario'] !== 'admin@gmail.com') {
    header('Location: https://panchielito.com/Login/');
    exit;
}
// Conexión
require_once '../controladoresPrincipales/conexion.php';

// Parámetros de filtrado
$filtroFechaInicio = isset($_GET['fecha_inicio']) ? $_GET['fecha_inicio'] : date('Y-m-01'); // Primer día del mes actual
$filtroFechaFin = isset($_GET['fecha_fin']) ? $_GET['fecha_fin'] : date('Y-m-d'); // Hoy
$filtroEstado = isset($_GET['estado']) ? $_GET['estado'] : '';
$filtroMetodoPago = isset($_GET['metodo_pago']) ? $_GET['metodo_pago'] : '';
$filtroUsuario = isset($_GET['usuario_id']) ? intval($_GET['usuario_id']) : 0;

// Construir la consulta SQL con filtros
$sql = "SELECT v.id, v.fecha_venta, v.total, v.metodo_pago, v.estado, v.referencia_pago, 
               u.nombre AS usuario_nombre, u.apellido AS usuario_apellido, u.email AS usuario_email
        FROM ventas v
        JOIN usuarios u ON v.usuario_id = u.id
        WHERE 1=1";

$params = [];
$types = "";

if ($filtroFechaInicio) {
    $sql .= " AND DATE(v.fecha_venta) >= ?";
    $params[] = $filtroFechaInicio;
    $types .= "s";
}

if ($filtroFechaFin) {
    $sql .= " AND DATE(v.fecha_venta) <= ?";
    $params[] = $filtroFechaFin;
    $types .= "s";
}

if ($filtroEstado) {
    $sql .= " AND v.estado = ?";
    $params[] = $filtroEstado;
    $types .= "s";
}

if ($filtroMetodoPago) {
    $sql .= " AND v.metodo_pago = ?";
    $params[] = $filtroMetodoPago;
    $types .= "s";
}

if ($filtroUsuario > 0) {
    $sql .= " AND v.usuario_id = ?";
    $params[] = $filtroUsuario;
    $types .= "i";
}

$sql .= " ORDER BY v.fecha_venta DESC";

// Preparar y ejecutar la consulta
$stmt = $mysqli->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
$ventas = $result->fetch_all(MYSQLI_ASSOC);

// Obtener estadísticas
$stmt = $mysqli->prepare("SELECT 
                            SUM(total) AS total_ventas,
                            COUNT(*) AS cantidad_ventas,
                            AVG(total) AS promedio_venta
                          FROM ventas 
                          WHERE estado = 'completada'
                          AND DATE(fecha_venta) BETWEEN ? AND ?");
$stmt->bind_param("ss", $filtroFechaInicio, $filtroFechaFin);
$stmt->execute();
$estadisticas = $stmt->get_result()->fetch_assoc();

// Obtener productos más vendidos
$stmt = $mysqli->prepare("SELECT 
                            dv.producto_id,
                            dv.nombre_producto,
                            SUM(dv.cantidad) AS total_vendido,
                            SUM(dv.subtotal) AS total_ingresos
                          FROM detalles_venta dv
                          JOIN ventas v ON dv.venta_id = v.id
                          WHERE v.estado = 'completada'
                          AND DATE(v.fecha_venta) BETWEEN ? AND ?
                          GROUP BY dv.producto_id, dv.nombre_producto
                          ORDER BY total_vendido DESC
                          LIMIT 5");
$stmt->bind_param("ss", $filtroFechaInicio, $filtroFechaFin);
$stmt->execute();
$productosPopulares = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Obtener mejores clientes
$stmt = $mysqli->prepare("SELECT 
                            v.usuario_id,
                            u.nombre,
                            u.apellido,
                            u.email,
                            COUNT(v.id) AS total_compras,
                            SUM(v.total) AS total_gastado
                          FROM ventas v
                          JOIN usuarios u ON v.usuario_id = u.id
                          WHERE v.estado = 'completada'
                          AND DATE(v.fecha_venta) BETWEEN ? AND ?
                          GROUP BY v.usuario_id, u.nombre, u.apellido, u.email
                          ORDER BY total_gastado DESC
                          LIMIT 5");
$stmt->bind_param("ss", $filtroFechaInicio, $filtroFechaFin);
$stmt->execute();
$mejoresClientes = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Obtener ventas por día para gráfica
$stmt = $mysqli->prepare("SELECT 
                            DATE(fecha_venta) AS fecha,
                            COUNT(*) AS cantidad_ventas,
                            SUM(total) AS total_ventas
                          FROM ventas
                          WHERE estado = 'completada'
                          AND DATE(fecha_venta) BETWEEN ? AND ?
                          GROUP BY DATE(fecha_venta)
                          ORDER BY DATE(fecha_venta)");
$stmt->bind_param("ss", $filtroFechaInicio, $filtroFechaFin);
$stmt->execute();
$ventasPorDia = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Obtener ventas por categoría para gráfica de pastel
$stmt = $mysqli->prepare("SELECT 
                            p.categoria,
                            SUM(dv.cantidad) AS total_vendido,
                            SUM(dv.subtotal) AS total_ingresos
                          FROM detalles_venta dv
                          JOIN ventas v ON dv.venta_id = v.id
                          JOIN productos p ON dv.producto_id = p.id
                          WHERE v.estado = 'completada'
                          AND DATE(v.fecha_venta) BETWEEN ? AND ?
                          GROUP BY p.categoria
                          ORDER BY total_ingresos DESC");
$stmt->bind_param("ss", $filtroFechaInicio, $filtroFechaFin);
$stmt->execute();
$ventasPorCategoria = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Obtener lista de usuarios para el filtro
$usuarios = $mysqli->query("SELECT id, nombre, apellido FROM usuarios ORDER BY nombre, apellido")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Panel Admin – Ventas</title>
    <link rel="stylesheet" href="admin_style.css">
    <link rel="stylesheet" href="ventas_style.css">
    <link href="https://fonts.googleapis.com/css2?family=Dancing+Script:wght@400..700&display=swap" rel="stylesheet">
    <link rel="icon" type="image/png" href="../img/chielito-re.png">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <h1>Panel de Administración – Ventas</h1>
    
    <!-- Pestañas de navegación -->
    <div class="nav-tabs">
        <a href="admin.php">Usuarios</a>
        <a href="productos_admin.php">Productos</a>
        <a href="ventas_admin.php" class="active">Ventas</a>
        <a href="https://panchielito.com/">Página Principal</a>
    </div>
    
    <!-- Resumen de estadísticas -->
    <div class="stats-container">
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-dollar-sign"></i>
            </div>
            <div class="stat-content">
                <h3>Total de Ventas</h3>
                <div class="stat-value">$<?php echo number_format($estadisticas['total_ventas'] ?? 0, 2); ?></div>
                <div class="stat-period">Periodo seleccionado</div>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-shopping-cart"></i>
            </div>
            <div class="stat-content">
                <h3>Cantidad de Ventas</h3>
                <div class="stat-value"><?php echo number_format($estadisticas['cantidad_ventas'] ?? 0); ?></div>
                <div class="stat-period">Transacciones completadas</div>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-chart-line"></i>
            </div>
            <div class="stat-content">
                <h3>Promedio por Venta</h3>
                <div class="stat-value">$<?php echo number_format($estadisticas['promedio_venta'] ?? 0, 2); ?></div>
                <div class="stat-period">Valor promedio por transacción</div>
            </div>
        </div>
    </div>
    
    <!-- Filtros -->
    <div class="filter-container">
        <h2><i class="fas fa-filter"></i> Filtros</h2>
        <form class="filter-form" method="get">
            <div class="form-row">
                <div class="form-group">
                    <label for="fecha_inicio">Fecha Inicio:</label>
                    <input type="date" id="fecha_inicio" name="fecha_inicio" value="<?php echo $filtroFechaInicio; ?>">
                </div>
                
                <div class="form-group">
                    <label for="fecha_fin">Fecha Fin:</label>
                    <input type="date" id="fecha_fin" name="fecha_fin" value="<?php echo $filtroFechaFin; ?>">
                </div>
                
                <div class="form-group">
                    <label for="estado">Estado:</label>
                    <select id="estado" name="estado">
                        <option value="">Todos</option>
                        <option value="completada" <?php echo $filtroEstado === 'completada' ? 'selected' : ''; ?>>Completada</option>
                        <option value="pendiente" <?php echo $filtroEstado === 'pendiente' ? 'selected' : ''; ?>>Pendiente</option>
                        <option value="cancelada" <?php echo $filtroEstado === 'cancelada' ? 'selected' : ''; ?>>Cancelada</option>
                    </select>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="metodo_pago">Método de Pago:</label>
                    <select id="metodo_pago" name="metodo_pago">
                        <option value="">Todos</option>
                        <option value="online" <?php echo $filtroMetodoPago === 'online' ? 'selected' : ''; ?>>Online</option>
                        <option value="OXXO" <?php echo $filtroMetodoPago === 'OXXO' ? 'selected' : ''; ?>>OXXO</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="usuario_id">Cliente:</label>
                    <select id="usuario_id" name="usuario_id">
                        <option value="0">Todos</option>
                        <?php foreach ($usuarios as $usuario): ?>
                            <option value="<?php echo $usuario['id']; ?>" <?php echo $filtroUsuario === $usuario['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($usuario['nombre'] . ' ' . $usuario['apellido']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group button-group">
                    <button type="submit" class="btn-apply">
                        <i class="fas fa-search"></i> Aplicar Filtros
                    </button>
                    <a href="ventas_admin.php" class="btn-reset">
                        <i class="fas fa-undo"></i> Limpiar
                    </a>
                </div>
            </div>
        </form>
    </div>
    
    <!-- Gráficos -->
    <div class="charts-container">
        <div class="chart-card">
            <h2><i class="fas fa-chart-line"></i> Ventas por Día</h2>
            <div class="chart-wrapper">
                <canvas id="ventasPorDiaChart"></canvas>
            </div>
        </div>
        
        <div class="chart-card">
            <h2><i class="fas fa-chart-pie"></i> Ventas por Categoría</h2>
            <div class="chart-wrapper">
                <canvas id="ventasPorCategoriaChart"></canvas>
            </div>
        </div>
    </div>
    
    <div class="charts-container">
        <div class="chart-card">
            <h2><i class="fas fa-trophy"></i> Productos Más Vendidos</h2>
            <div class="chart-wrapper">
                <canvas id="productosMasVendidosChart"></canvas>
            </div>
        </div>
        
        <div class="chart-card">
            <h2><i class="fas fa-users"></i> Mejores Clientes</h2>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Cliente</th>
                        <th>Email</th>
                        <th>Compras</th>
                        <th>Total Gastado</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($mejoresClientes as $cliente): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($cliente['nombre'] . ' ' . $cliente['apellido']); ?></td>
                            <td><?php echo htmlspecialchars($cliente['email']); ?></td>
                            <td><?php echo $cliente['total_compras']; ?></td>
                            <td>$<?php echo number_format($cliente['total_gastado'], 2); ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($mejoresClientes)): ?>
                        <tr>
                            <td colspan="4" class="no-data">No hay datos disponibles</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- Listado de ventas -->
    <div class="table-container">
        <h2><i class="fas fa-list"></i> Listado de Ventas</h2>
        <table class="data-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Fecha</th>
                    <th>Cliente</th>
                    <th>Total</th>
                    <th>Método</th>
                    <th>Estado</th>
                    <th>Referencia</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($ventas as $venta): ?>
                    <tr>
                        <td><?php echo $venta['id']; ?></td>
                        <td><?php echo date('d/m/Y H:i', strtotime($venta['fecha_venta'])); ?></td>
                        <td><?php echo htmlspecialchars($venta['usuario_nombre'] . ' ' . $venta['usuario_apellido']); ?></td>
                        <td>$<?php echo number_format($venta['total'], 2); ?></td>
                        <td><?php echo $venta['metodo_pago']; ?></td>
                        <td>
                            <?php if ($venta['estado'] === 'completada'): ?>
                                <span class="badge badge-success">Completada</span>
                            <?php elseif ($venta['estado'] === 'pendiente'): ?>
                                <span class="badge badge-warning">Pendiente</span>
                            <?php else: ?>
                                <span class="badge badge-danger">Cancelada</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo $venta['referencia_pago'] ?: '-'; ?></td>
                        <td>
                            <a href="detalle_venta.php?id=<?php echo $venta['id']; ?>" class="btn-action btn-view" title="Ver detalles">
                                <i class="fas fa-eye"></i>
                            </a>
                            <?php if ($venta['estado'] === 'pendiente'): ?>
                                <a href="confirmar_pago_oxxo.php?referencia=<?php echo $venta['referencia_pago']; ?>" class="btn-action btn-confirm" title="Confirmar pago">
                                    <i class="fas fa-check-circle"></i>
                                </a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($ventas)): ?>
                    <tr>
                        <td colspan="8" class="no-data">No hay ventas que coincidan con los filtros</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    
    <script>
        // Gráfica de ventas por día
        const ventasPorDiaCtx = document.getElementById('ventasPorDiaChart').getContext('2d');
        const ventasPorDiaChart = new Chart(ventasPorDiaCtx, {
            type: 'line',
            data: {
                labels: [
                    <?php foreach ($ventasPorDia as $venta): ?>
                        '<?php echo date('d/m/Y', strtotime($venta['fecha'])); ?>',
                    <?php endforeach; ?>
                ],
                datasets: [{
                    label: 'Total de Ventas ($)',
                    data: [
                        <?php foreach ($ventasPorDia as $venta): ?>
                            <?php echo $venta['total_ventas']; ?>,
                        <?php endforeach; ?>
                    ],
                    backgroundColor: 'rgba(169, 50, 38, 0.2)',
                    borderColor: 'rgba(169, 50, 38, 1)',
                    borderWidth: 2,
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return '$' + context.raw.toFixed(2);
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return '$' + value;
                            }
                        }
                    }
                }
            }
        });
        
        // Gráfica de productos más vendidos
        const productosMasVendidosCtx = document.getElementById('productosMasVendidosChart').getContext('2d');
        const productosMasVendidosChart = new Chart(productosMasVendidosCtx, {
            type: 'bar',
            data: {
                labels: [
                    <?php foreach ($productosPopulares as $producto): ?>
                        '<?php echo htmlspecialchars($producto['nombre_producto']); ?>',
                    <?php endforeach; ?>
                ],
                datasets: [{
                    label: 'Unidades Vendidas',
                    data: [
                        <?php foreach ($productosPopulares as $producto): ?>
                            <?php echo $producto['total_vendido']; ?>,
                        <?php endforeach; ?>
                    ],
                    backgroundColor: [
                        'rgba(169, 50, 38, 0.7)',
                        'rgba(142, 68, 173, 0.7)',
                        'rgba(41, 128, 185, 0.7)',
                        'rgba(39, 174, 96, 0.7)',
                        'rgba(243, 156, 18, 0.7)'
                    ],
                    borderColor: [
                        'rgba(169, 50, 38, 1)',
                        'rgba(142, 68, 173, 1)',
                        'rgba(41, 128, 185, 1)',
                        'rgba(39, 174, 96, 1)',
                        'rgba(243, 156, 18, 1)'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
        
        // Gráfica de ventas por categoría
        const ventasPorCategoriaCtx = document.getElementById('ventasPorCategoriaChart').getContext('2d');
        const ventasPorCategoriaChart = new Chart(ventasPorCategoriaCtx, {
            type: 'pie',
            data: {
                labels: [
                    <?php foreach ($ventasPorCategoria as $categoria): ?>
                        '<?php echo htmlspecialchars($categoria['categoria']); ?>',
                    <?php endforeach; ?>
                ],
                datasets: [{
                    data: [
                        <?php foreach ($ventasPorCategoria as $categoria): ?>
                            <?php echo $categoria['total_ingresos']; ?>,
                        <?php endforeach; ?>
                    ],
                    backgroundColor: [
                        'rgba(169, 50, 38, 0.7)',
                        'rgba(142, 68, 173, 0.7)',
                        'rgba(41, 128, 185, 0.7)',
                        'rgba(39, 174, 96, 0.7)',
                        'rgba(243, 156, 18, 0.7)',
                        'rgba(211, 84, 0, 0.7)',
                        'rgba(52, 152, 219, 0.7)'
                    ],
                    borderColor: [
                        'rgba(169, 50, 38, 1)',
                        'rgba(142, 68, 173, 1)',
                        'rgba(41, 128, 185, 1)',
                        'rgba(39, 174, 96, 1)',
                        'rgba(243, 156, 18, 1)',
                        'rgba(211, 84, 0, 1)',
                        'rgba(52, 152, 219, 1)'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'right',
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const label = context.label || '';
                                const value = context.raw;
                                const total = context.chart.data.datasets[0].data.reduce((a, b) => a + b, 0);
                                const percentage = Math.round((value / total) * 100);
                                return `${label}: $${value.toFixed(2)} (${percentage}%)`;
                            }
                        }
                    }
                }
            }
        });
    </script>
</body>
</html>