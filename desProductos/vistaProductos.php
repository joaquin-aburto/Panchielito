<?php
    session_start();
    
    // Incluir el archivo de conexión (modelo)
    // Ajusta la ruta según tu estructura de carpetas
    require_once '../controladoresPrincipales/conexion.php';
    
    // Obtener los parámetros de la URL
    $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    $nombre_param = isset($_GET['nombre']) ? $_GET['nombre'] : '';
    
    // Validar el ID
    if ($id === 0) {
        die("Producto no encontrado.");
    }
    
    // Preparar la consulta (usamos prepared statement para evitar inyecciones SQL)
    $stmt = $mysqli->prepare("SELECT id, nombre, descripcion, precio, unidades_disponibles, categoria, imagenes FROM productos WHERE id = ? AND nombre = ?");
    $stmt->bind_param("is", $id, $nombre_param);
    $stmt->execute();
    $result = $stmt->get_result();
    $product = $result->fetch_assoc();
    
    if (!$product) {
        die("Producto no encontrado.");
    }
    
    // Decodificar el campo de imágenes (suponemos que está almacenado como JSON)
    $imagenes = json_decode($product['imagenes'], true);
?>
<!DOCTYPE html>
<html lang="es">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?php echo htmlspecialchars($product['nombre']); ?></title>
        <link rel="stylesheet" type="text/css" href="../desProductos/Desproductos.css">
        <link rel="stylesheet" type="text/css" href="../desProductos/carrito.css">
         <link href="https://fonts.googleapis.com/css2?family=Dancing+Script:wght@400..700&display=swap" rel="stylesheet">
         <link rel="icon" type="image/png" href="../img/chielito-re.png">
         <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
         <link rel="stylesheet" type="text/css" href="../css/estilospie.css">
         
    <link rel="stylesheet" href="https://unpkg.com/swiper/swiper-bundle.min.css">

    </head>
     <header>
            <div>
                <a href="http://127.0.0.1/Panchielito/">
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
        <li><a href="../" class="boton-regresar">Inicio</a></li>
        <li><a href="../Galeria/index.php" data-categoria="">PRODUCTOS</a></li>
        <li><a href="../Galeria/biscocho.php" data-categoria="BIZCOCHO">BIZCOCHO</a></li>
        <li><a href="../Galeria/danes.php" data-categoria="DANÉS">DANÉS</a></li>
        <li><a href="../Galeria/hojaldre.php" data-categoria="HOJALDRE">HOJALDRE</a></li>
           <?php if (isset($_SESSION['usuario']) && $_SESSION['usuario'] === 'admin@gmail.com'): ?>
                    <li><a href="../Admin/productos_admin.php">PANEL</a></li>
                <?php endif; ?>
    </ul>
    
    <?php if (isset($_SESSION['usuario'])): ?>
        <!-- Botón del carrito independiente -->
        <button id="botonCarrito">
            <i class="fas fa-shopping-cart"></i>
            <span id="contadorCarrito">0</span> 
        </button>
    <?php endif; ?>
</nav>

       </header>

    <body>
        

        <!-- Aside del carrito -->
        <aside id="asideCarrito">
            <div class="contenido-carrito">
                <h2>Tu Carrito</h2>
                <div class="items-carrito" id="itemsCarrito"></div>
                <p id="carritoVacio">El carrito está vacío</p>
                
                <!-- Sección fija en la parte inferior -->
                <div class="resumen-compra">
                    <div class="total-carrito">
                        <span>Total:</span>
                        <span id="totalCarrito">$0.00</span>
                    </div>
                    <button class="boton-comprar">➔ Ir a comprar</button>
                </div>
            </div>
        </aside>

       <div class="contenedorProducto">
            <div class="gridProducto">
                <!-- Sección de imágenes -->
                <div class="imagenesProducto">
                    <img src="<?php echo htmlspecialchars($imagenes[0]); ?>" 
                        alt="<?php echo htmlspecialchars($product['nombre']); ?>" 
                        class="imagenPrincipal" id="imagenPrincipal">
        
                    <div class="miniaturas">
                        <?php if (is_array($imagenes)): ?>
                            <?php foreach ($imagenes as $img): ?>
                                <img src="<?php echo htmlspecialchars($img); ?>" 
                                    class="miniatura" 
                                    onmouseover="cambiarImagen(this)">
                            <?php endforeach; ?>
                        <?php else: ?>
                            <img src="<?php echo htmlspecialchars($imagenes); ?>" 
                                class="miniatura" 
                                onmouseover="cambiarImagen(this)">
                        <?php endif; ?>
                    </div>
                </div>
        
                <!-- Información del producto -->
                <div class="infoProducto">
                    <h1 class="tituloProducto"><?php echo htmlspecialchars($product['nombre']); ?></h1>
                    <div class="precioProducto">$<?php echo number_format($product['precio'], 2); ?></div>
                    <div class="stockProducto" id="stockDisplay">
                        Disponibles: <?php echo htmlspecialchars($product['unidades_disponibles']); ?> unidades
                    </div>
                    <div class="calificacion">
                        ★★★★☆ (4.2/5)
                        </div>
        
                    <div class="selectorCantidad">
                        <button class="botonCantidad" onclick="ajustarCantidad(-1)">-</button>
                        <input type="number" class="inputCantidad" value="1" min="1" id="cantidad" onchange="validarStock()">
                        <button class="botonCantidad" onclick="ajustarCantidad(1)">+</button>
                    </div>
                    <div class="advertenciaStock" id="advertenciaStock"></div>
        
                    <p class="descripcionProducto">
                        <?php echo nl2br(htmlspecialchars($product['descripcion'])); ?>
                    </p>
                         <?php if (isset($_SESSION['id'])): ?>
                        <button class="agregarAlCarrito" id="agregarAlCarrito">🛒 Añadir al carrito</button>
                    <?php else: ?>
                        <button class="agregarAlCarrito" onclick="window.location.href='http://127.0.0.1/Panchielito/Login/'">🔒 Iniciar sesión para comprar</button>
                    <?php endif; ?>
</div>
                </div>
            </div>
        </div>


            <!-- Sección de comentarios -->
            <div class="seccionComentarios">
                <h2>Opiniones de clientes</h2>
            
                <?php if (isset($_SESSION['id']) && isset($_SESSION['nombre'])): ?>
                <p>
                    <strong>Usuario:</strong>
                    <span id="nombreUsuario" data-id="<?php echo $_SESSION['id']; ?>">
                        <?php echo htmlspecialchars($_SESSION['nombre']); ?>
                    </span>
                </p>
                    
                    <button id="mostrarFormularioComentario">Agregar comentario</button>
                    
                    <div id="formularioComentario" style="display:none;">
                        <textarea id="textoComentario" placeholder="Escribe tu comentario aquí..." required></textarea>
                        <div style="margin-top: 0.5rem;">
                            <span>Calificación: </span>
                            <span id="estrellasCalificacion">
                                <span class="estrella" data-valor="1">☆</span>
                                <span class="estrella" data-valor="2">☆</span>
                                <span class="estrella" data-valor="3">☆</span>
                                <span class="estrella" data-valor="4">☆</span>
                                <span class="estrella" data-valor="5">☆</span>
                            </span>
                            <input type="hidden" id="valorCalificacion" value="0">
                        </div>
                        <button id="enviarComentario">Enviar comentario</button>
                    </div>
                <?php else: ?>
                    <button id="botonIniciarSesion" onclick="location.href='http://127.0.0.1/Panchielito/'">Iniciar sesión para comentar</button>
                <?php endif; ?>
                
                <div id="listaComentarios"></div>
            </div>
        </div>

        <script src="DesProductos.js"></script>
        <script>
              document.addEventListener("DOMContentLoaded", function () {
                cargarComentarios(); 
            });
        </script>
        <script>
            function toggleMenu() {
              const menu = document.querySelector('.nav-menu ul');
              menu.classList.toggle('active'); 
            }
        </script>
           <script src="https://unpkg.com/swiper/swiper-bundle.min.js"></script>
           <script>
               
           </script>
        
    </body>
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
</html>


