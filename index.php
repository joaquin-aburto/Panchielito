<?php
  session_start();
?>

<!DOCTYPE html>
<html lang="es">
  <head>
      <meta name="viewport" content="width=device-width, initial-scale=1.0">
      <meta charset="utf-8" />
      <title>PanChielito</title>
      <link rel="stylesheet" type="text/css" href="css/estilos.css">
      <link rel="stylesheet" type="text/css" href="css/style.css">
      <link rel="stylesheet" type="text/css" href="css/estilosBanner.css">
      <link rel="stylesheet" type="text/css" href="css/estilosCate.css">
      <link rel="stylesheet" type="text/css" href="css/estilosVen.css">
      <link rel="stylesheet" type="text/css" href="css/estilosUbi.css">
      <link rel="stylesheet" type="text/css" href="css/estilospie.css">
      <link rel="stylesheet" type="text/css" href="css/estiloseve.css">
      <link href="https://fonts.googleapis.com/css2?family=Dancing+Script:wght@400..700&display=swap" rel="stylesheet">
      <link rel="icon" type="image/png" href="img/chielito-re.png">
      <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
      <style>
       .cart-button {
    position: relative;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background-color: transparent; /* Elimina el fondo */
    color: #a93226; /* Cambia el color del icono */
    border: none;
    width: 40px;
    height: 40px;
    cursor: pointer;
    transition: all 0.3s ease;
    margin-left: 15px;
    text-decoration: none;
    font-size: 20px; /* Ajusta el tamaño del icono */
}

.cart-button:hover {
    color: #6d0000; /* Cambia el color al hacer hover */
    transform: scale(1.1);
    background-color: transparent; /* Mantén el fondo transparente */
}

.cart-count {
    position: absolute;
    top: -5px;
    right: -5px;
    background-color: #e74c3c;
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

.orders-button {
    position: relative;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background-color: transparent; /* Elimina el fondo */
    color: #27ae60; /* Cambia el color del icono */
    border: none;
    width: 40px;
    height: 40px;
    cursor: pointer;
    transition: all 0.3s ease;
    margin-left: 10px;
    text-decoration: none;
    font-size: 20px; /* Ajusta el tamaño del icono */
}

.orders-button:hover {
    color: #219653; /* Cambia el color al hacer hover */
    transform: scale(1.1);
    background-color: transparent; /* Mantén el fondo transparente */
}

.user-actions {
    display: flex;
    align-items: center;
}
      </style>
  </head>

  <body>
      <div class="parallax-section">
          <div class="parallax-image"></div>
      </div>

      <header>
          <div>
              <a href="https://panchielito.com/">
                    <img src="img/Chielito.png" id="LogoTipo" alt="Logo de la Panadería">
                </a>
          </div>
          <div>
              <h1>Panchielito</h1>
              <?php
                if (isset($_SESSION['nombre'])) 
                {
                    echo '<h2>¡Hola ' . htmlspecialchars($_SESSION['nombre']) . '!</h2>';
                }
                else 
                {
                    echo '<h1>¡Bienvenido!</h1>';
                }
              ?>
          </div>
         <nav class="nav-menu">
    <button class="menu-btn" onclick="toggleMenu()">☰</button>
    <ul>
        <li><a href="Galeria/index.php" data-categoria="">PRODUCTOS</a></li>
        <li><a href="Galeria/biscocho.php" data-categoria="BIZCOCHO">BIZCOCHO</a></li>
        <li><a href="Galeria/danes.php" data-categoria="DANÉS">DANÉS</a></li>
        <li><a href="Galeria/hojaldre.php" data-categoria="HOJALDRE">HOJALDRE</a></li>
        
        <?php if (!isset($_SESSION['nombre'])): ?>
            <li><a href="http://127.0.0.1/Panchielito/Login" class="login-btn">Iniciar sesión</a></li>
        <?php else: ?>
            <li class="user-actions">
                <?php if ($_SESSION['usuario'] === 'admin@gmail.com'): ?>
                    <a href="Admin/productos_admin.php">PANEL</a>
                <?php endif; ?>
                <a href="Galeria/carrito.php" class="cart-button" title="Carrito de compras">
                    <i class="fas fa-shopping-cart"></i>
                    <span id="cartCount" class="cart-count" style="display: none;">0</span>
                </a>
                <a href="desProductos/mis_pedidos.php" class="orders-button" title="Mis pedidos">
                    <i class="fas fa-box"></i>
                </a>
                <a href="logout.php" class="logout-btn">Cerrar sesión</a>
            </li>
        <?php endif; ?>
    </ul>
</nav>

      </header>

      <section class="promocional">
          <div class="hero-content">
             <p>Pan elaborado con pasión, único cada día</p> 
             <h2>Una obra maestra artesanal</h2>
              <a href="Galeria/index.php">Ordena Ahora</a>
          </div>
      </section>

      <section class="Categorias">
          <h1 class="encabezado">Categorías</h1>
          <div class="Categoria">
              <div class="Carta">
                  <p>Panes</p>
                  <span class="boton">Ver más</span>
              </div>
              <div class="Carta">
                  <p>Cafe</p>
                  <span class="boton">Ver más</span>
              </div>
              <div class="Carta">
                  <p>Utencilios</p>
                  <span class="boton">Ver más</span>
              </div>
              <div class="Carta">
                  <p>Recetas</p>
                  <span class="boton">Ver más</span>
              </div>
          </div>
      </section>

        <section class="mas-Vendidos">
            <h2>Los Más Vendidos</h2>
            <div class="carousel-container">
               
                <!-- Contenedor donde se inyectarán los productos -->
                <div class="products" id="productos-container">
                    <!-- Aquí se cargarán los productos dinámicamente -->
                </div>
              
            </div>
        </section>


      <section class="parallax">
          <div class="parallax-content">
              <h2>Encuentra nuestra panadería</h2>
              <p>Palo Alto, California</p>
          </div>
      </section>

      <section id="eventos">
          <h1>Próximos eventos</h1>
          <div class="eventos-container">
              <div class="evento">
                  <h3>Feria del Pan 2024</h3>
                  <p>Ven y disfruta de nuestros productos en la Feria del Pan el próximo 15 de noviembre.</p>
                  <button>Ver Más</button>
              </div>
              <div class="evento">
                  <h3>No se trabaja :)</h3>
                  <p>Con el placer de informarles que Panchielito no tendrá servicio el próximo 31 de octubre. Gracias por su comprensión.</p>
                  <button>Ver Más</button>
              </div>
              <div class="evento">
                  <h3>No fio</h3>
                  <p>Si en walmart no pides fiado, menos aqui rey</p>
                  <button>Ver Más</button>
              </div>
          </div>
      </section>

      <footer>
          <div class="lo">
              <img src="img/chielito-re.png" alt="Imagen de la panadería" width="150" height="150">
          </div>

          <nav>
              <p>Encuéntranos en redes sociales:</p>
              <ul>
                  <li>
                      <a title="Facebook" href="#">
                          <img src="img/facebook.png" alt="Facebook" width="30" height="30">
                      </a>
                  </li>
                  <li>
                      <a title="Instagram" href="#">
                          <img src="img/insta.png" alt="Instagram" width="30" height="30">
                      </a>
                  </li>
                  <li>
                      <a title="Twitter" href="#">
                          <img src="img/x.png" alt="Twitter" width="30" height="30">
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

      <script>
          document.addEventListener("DOMContentLoaded", function () {
           setTimeout(() => {
           const products = document.querySelectorAll('.product');
                if (products.length === 0) return; // Evita errores si no hay productos
        
                let currentIndex = 0;
                products[currentIndex].classList.add('active');
        
                const leftBtn = document.querySelector('.scroll-btn.left');
                const rightBtn = document.querySelector('.scroll-btn.right');
        
                leftBtn.addEventListener('click', () => {
                    products[currentIndex].classList.remove('active');
                    currentIndex = (currentIndex - 1 + products.length) % products.length;
                    products[currentIndex].classList.add('active');
                });
        
                rightBtn.addEventListener('click', () => {
                    products[currentIndex].classList.remove('active');
                    currentIndex = (currentIndex + 1) % products.length;
                    products[currentIndex].classList.add('active');
                });
            }, 500); 
            
            // Actualizar contador del carrito
            actualizarContadorCarrito();
        });
        
        // Función para actualizar el contador del carrito
        async function actualizarContadorCarrito() {
            if (!document.getElementById('cartCount')) return;
            
            try {
                const response = await fetch('desProductoscarritos.json?t=' + Date.now());
                if (!response.ok) return;
                
                const carritos = await response.json();
                const usuarioId = "<?php echo isset($_SESSION['id']) ? $_SESSION['id'] : ''; ?>";
                
                if (carritos && carritos[usuarioId]) {
                    const totalItems = carritos[usuarioId].reduce((acc, item) => acc + item.cantidad, 0);
                    const cartCount = document.getElementById('cartCount');
                    
                    if (totalItems > 0) {
                        cartCount.textContent = totalItems;
                        cartCount.style.display = 'flex';
                    } else {
                        cartCount.style.display = 'none';
                    }
                }
            } catch (error) {
                console.error("Error al cargar el carrito:", error);
            }
        }
        
        // Actualizar el contador cada 5 segundos
        setInterval(actualizarContadorCarrito, 5000);
      </script>

      <script src="menu.js"></script>
      <script src="controladoresPrincipales/consultaProductosMasVendidos.js"></script>
      
  </body>
</html>

