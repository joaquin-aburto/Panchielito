<?php
session_start();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lista de Productos</title>
    <link rel="stylesheet" href="style.css">
     <link href="https://fonts.googleapis.com/css2?family=Dancing+Script:wght@400..700&display=swap" rel="stylesheet">
         <link rel="icon" type="image/png" href="../img/chielito-re.png">
         <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
          <link rel="stylesheet" type="text/css" href="../css/estilospie.css">
</head>
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
                     <li><a href="../" class="boton-regresar">Inicio</a></li>
                    <li><a href="../Galeria/index.php" data-categoria="">PRODUCTOS</a></li>
                <li><a href="../Galeria/danes.php" data-categoria="BIZCOCHO">DANES</a></li>
                
                <li><a href="../Galeria/hojaldre.php" data-categoria="HOJALDRE">HOJALDRE</a></li>
                  <?php if (isset($_SESSION['usuario']) && $_SESSION['usuario'] === 'admin@gmail.com'): ?>
                    <li><a href="../Admin/productos_admin.php">PANEL</a></li>
                <?php endif; ?>
                </ul>
              <?php if (isset($_SESSION['usuario'])): ?>
    <?php
    $totalProductos = 0;
    $jsonFile = '../desProductoscarritos.json';
    
    // Verificar si existe el archivo y el usuario tiene sesión
    if (file_exists($jsonFile) && isset($_SESSION['id'])) {
        $jsonData = file_get_contents($jsonFile);
        $carrito = json_decode($jsonData, true);
        
        // Obtener el ID del usuario de la sesión
        $idUsuario = $_SESSION['id'];
        
        // Solo sumar productos del usuario actual
        if (isset($carrito[$idUsuario])) {
            foreach ($carrito[$idUsuario] as $producto) {
                $totalProductos += $producto['cantidad'];
            }
        }
    }
    ?>
    
    <button id="botonCarrito" onclick="window.location.href='carrito.php'">
        <i class="fas fa-shopping-cart"></i>
        <span id="contadorCarrito"><?php echo $totalProductos; ?></span> 
    </button>
<?php endif; ?>
            </nav>

       </header>

<body>
   <h1 class="productos-disponibles-title">Productos Disponibles</h1>
    
     <!-- Aside del carrito -->
       
    <!-- Contenedor donde se cargarán las tarjetas de los productos -->
    <div id="productos-container"></div>
    
   
    <script src="../controladoresPrincipales/consultaProductosGeneralBiscocho.js"></script>
</body>

       <script src="DesProductos.js"></script>
       
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

        <script>
            function toggleMenu() {
              const menu = document.querySelector('.nav-menu ul');
              menu.classList.toggle('active'); 
            }
        </script>
        <!-- Script para actualizar el contador del carrito -->
        <script>
    function actualizarContadorCarrito() {
        const idUsuario = <?php echo isset($_SESSION['id']) ? $_SESSION['id'] : 'null'; ?>;
        if (!idUsuario) return;

        fetch('../desProductoscarritos.json?' + new Date().getTime()) // Agrega timestamp para evitar caché
            .then(response => response.json())
            .then(carrito => {
                let total = 0;
                const contador = document.getElementById('contadorCarrito');
                const boton = document.getElementById('botonCarrito');

                if (carrito[idUsuario]) {
                    carrito[idUsuario].forEach(producto => {
                        total += producto.cantidad;
                    });
                }

                if (total > 0) {
                    if (!contador) {
                        const nuevoContador = document.createElement('span');
                        nuevoContador.id = 'contadorCarrito';
                        nuevoContador.textContent = total;
                        boton.appendChild(nuevoContador);
                    } else {
                        contador.textContent = total;
                    }
                } else if (contador) {
                    contador.remove();
                }
            })
            .catch(error => console.error('Error:', error));
    }

    // Actualizar cada segundo (puedes ajustar este valor)
    setInterval(actualizarContadorCarrito, 1000);
    document.addEventListener("DOMContentLoaded", actualizarContadorCarrito);
</script>
<footer>
          <div class="lo">
              <img src="../img/chielito-re.png" alt="Imagen de la panadería" width="150" height="150">
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