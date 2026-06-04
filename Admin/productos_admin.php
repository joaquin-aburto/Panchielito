<?php
session_start();
// 1) Verificar admin
if (!isset($_SESSION['usuario']) || $_SESSION['usuario'] !== 'admin@gmail.com') {
    header('Location: https://panchielito.com/Login/');
    exit;
}
// 2) Conexión
require_once '../controladoresPrincipales/conexion.php';

// Inicializar variables de búsqueda y filtrado
$busqueda = isset($_GET['busqueda']) ? $_GET['busqueda'] : '';
$campo = isset($_GET['campo']) ? $_GET['campo'] : 'nombre';
$orden = isset($_GET['orden']) ? $_GET['orden'] : '';

// Construir la consulta SQL base
$sql = "SELECT id, nombre, descripcion, precio, unidades_disponibles, categoria, imagenes, des_gak FROM productos";

// Agregar condiciones de búsqueda si se proporcionó un término
if (!empty($busqueda)) {
    if ($campo == 'id') {
        // Si el campo es ID, asegurarse de que sea un número
        if (is_numeric($busqueda)) {
            $sql .= " WHERE id = " . intval($busqueda);
        }
    } else {
        // Para otros campos, usar LIKE para búsqueda parcial
        $sql .= " WHERE $campo LIKE '%" . $mysqli->real_escape_string($busqueda) . "%'";
    }
}

// Agregar ordenamiento
if (!empty($orden)) {
    switch ($orden) {
        case 'stock_asc':
            $sql .= " ORDER BY unidades_disponibles ASC";
            break;
        case 'stock_desc':
            $sql .= " ORDER BY unidades_disponibles DESC";
            break;
        case 'nombre_asc':
            $sql .= " ORDER BY nombre ASC";
            break;
        case 'nombre_desc':
            $sql .= " ORDER BY nombre DESC";
            break;
        case 'precio_asc':
            $sql .= " ORDER BY precio ASC";
            break;
        case 'precio_desc':
            $sql .= " ORDER BY precio DESC";
            break;
        default:
            $sql .= " ORDER BY id DESC";
    }
} else {
    // Orden predeterminado
    $sql .= " ORDER BY id DESC";
}

// 3) Ejecutar la consulta
$result = $mysqli->query($sql);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Panel Admin – Productos</title>
    <link rel="stylesheet" href="productos_style.css">
    <link href="https://fonts.googleapis.com/css2?family=Dancing+Script:wght@400..700&display=swap" rel="stylesheet">
    <link rel="icon" type="image/png" href="img/chielito-re.png">
    <style>
       
    </style>
</head>

<body>
    <h1>Panel de Administración – Productos</h1>
    
    <div class="nav-tabs">
        <a href="admin.php">Usuarios</a>
        <a href="productos_admin.php" class="active">Productos</a>
        <a href="ventas_admin.php">Ventas</a>
         <a href="https://panchielito.com/" class="active">Pagina Principal</a>
        
    </div>
    
    <!-- Buscador y filtros -->
    <div class="search-container">
        <form class="search-form" method="GET" action="productos_admin.php">
            <div class="form-group">
                <label for="busqueda">Buscar:</label>
                <input type="text" id="busqueda" name="busqueda" value="<?= htmlspecialchars($busqueda) ?>" placeholder="Término de búsqueda">
                
            </div>
            
            <div class="form-group">
                <label for="campo">Buscar en:</label>
                <select id="campo" name="campo">
                    <option value="nombre" <?= $campo == 'nombre' ? 'selected' : '' ?>>Nombre</option>
                    <option value="id" <?= $campo == 'id' ? 'selected' : '' ?>>ID</option>
                    <option value="categoria" <?= $campo == 'categoria' ? 'selected' : '' ?>>Categoría</option>
                    <option value="descripcion" <?= $campo == 'descripcion' ? 'selected' : '' ?>>Descripción</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="orden">Ordenar por:</label>
                <select id="orden" name="orden">
                    <option value="" <?= $orden == '' ? 'selected' : '' ?>>Más recientes primero</option>
                    <option value="stock_asc" <?= $orden == 'stock_asc' ? 'selected' : '' ?>>Stock: Menor a Mayor</option>
                    <option value="stock_desc" <?= $orden == 'stock_desc' ? 'selected' : '' ?>>Stock: Mayor a Menor</option>
                    <option value="nombre_asc" <?= $orden == 'nombre_asc' ? 'selected' : '' ?>>Nombre: A-Z</option>
                    <option value="nombre_desc" <?= $orden == 'nombre_desc' ? 'selected' : '' ?>>Nombre: Z-A</option>
                    <option value="precio_asc" <?= $orden == 'precio_asc' ? 'selected' : '' ?>>Precio: Menor a Mayor</option>
                    <option value="precio_desc" <?= $orden == 'precio_desc' ? 'selected' : '' ?>>Precio: Mayor a Menor</option>
                </select>
            </div>
            
            <button type="submit">Buscar</button>
            <a href="productos_admin.php" class="reset-button">Limpiar filtros</a>
        </form>
    </div>
    
    <a href="add_product.php" class="add-button">+ Agregar Nuevo Producto</a>
    
    <!-- Información de resultados -->
    <div class="results-info">
        <?php 
        $num_resultados = $result->num_rows;
        echo "Mostrando $num_resultados producto" . ($num_resultados != 1 ? 's' : '');
        if (!empty($busqueda)) {
            echo " para la búsqueda: \"" . htmlspecialchars($busqueda) . "\"";
        }
        ?>
    </div>
    
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Imagen</th>
                <th>Nombre</th>
                <th>Descripción</th>
                <th>Precio</th>
                <th>Stock</th>
                <th>Categoría</th>
                <th>Estado</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
        <?php if ($result->num_rows > 0): ?>
            <?php while($p = $result->fetch_assoc()): 
                // Decodificar el JSON de imágenes
                $imagenes = json_decode($p['imagenes'], true);
                // Obtener la primera imagen (principal) si existe
                $imagen_principal = !empty($imagenes) && is_array($imagenes) ? $imagenes[0] : '../img/placeholder.jpg';
            ?>
                <tr>
                    <td><?=htmlspecialchars($p['id'])?></td>
                    <td><img src="<?=htmlspecialchars($imagen_principal)?>" class="product-image" alt="<?=htmlspecialchars($p['nombre'])?>"></td>
                    <td><?=htmlspecialchars($p['nombre'])?></td>
                    <td class="description-cell"><?=htmlspecialchars($p['descripcion'])?></td>
                    <td>$<?=number_format($p['precio'], 2)?></td>
                    <td><?=htmlspecialchars($p['unidades_disponibles'])?></td>
                    <td><?=htmlspecialchars($p['categoria'])?></td>
                    <td>
                        <?php if($p['des_gak'] == 1): ?>
                            <span class="status visible">Visible</span>
                        <?php else: ?>
                            <span class="status hidden">Oculto</span>
                        <?php endif; ?>
                        
                        <?php if($p['unidades_disponibles'] < 5): ?>
                            <span class="status low-stock">Stock Bajo</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <a class="button edit" href="edit_product.php?id=<?=$p['id']?>">Editar</a>
                        <a class="button delete" href="delete_product.php?id=<?=$p['id']?>"
                           onclick="return confirm('¿Eliminar producto <?=htmlspecialchars($p['nombre'])?>?');">
                           Eliminar
                        </a>
                    </td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr>
                <td colspan="9" style="text-align: center; padding: 20px;">No se encontraron productos que coincidan con la búsqueda.</td>
            </tr>
        <?php endif; ?>
        </tbody>
    </table>
</body>
</html>
