<?php
  session_start();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style_carrito.css">
     <link rel="stylesheet" type="text/css" href="../css/estilospie.css">
     <link href="https://fonts.googleapis.com/css2?family=Dancing+Script:wght@400..700&display=swap" rel="stylesheet">
     <link rel="icon" type="image/png" href="../img/chielito-re.png">
     <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <title>Carrito de Compras</title>
    <style>
        .producto-no-disponible {
            background-color: #ffecec;
            border-left: 4px solid #e74c3c;
            padding: 10px;
            margin-bottom: 10px;
            border-radius: 4px;
        }
        
        .advertencia-carrito {
            background-color: #fff3e0;
            border-left: 4px solid #e65100;
            padding: 15px;
            margin: 15px 0;
            border-radius: 4px;
            font-weight: bold;
            color: #e65100;
            display: none;
        }
        
        .btn-eliminar-no-disponible {
            background-color: #e74c3c;
            color: white;
            border: none;
            padding: 5px 10px;
            border-radius: 4px;
            cursor: pointer;
            margin-top: 5px;
        }
        
        .btn-eliminar-no-disponible:hover {
            background-color: #c0392b;
        }
        
        .btn-limpiar-no-disponibles {
            background-color: #e74c3c;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 4px;
            cursor: pointer;
            margin-top: 10px;
            font-weight: bold;
        }
        
        .producto-no-visible {
            background-color: #ffecec;
            border-left: 4px solid #e74c3c;
            padding: 10px;
            margin-bottom: 10px;
            border-radius: 4px;
        }

        .mensaje-no-disponible {
            color: #e74c3c;
            font-weight: bold;
            margin-top: 5px;
            font-size: 0.9rem;
        }
        
        /* Estilo para botones deshabilitados */
        .aumentar-cantidad:disabled,
        .disminuir-cantidad:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            background-color: #ccc;
        }
    </style>
</head>
<header>
      <div>
              <a href="../">
                    <img src="../img/Chielito.png" id="LogoTipo" alt="Logo de la Panadería">
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
                    <li><a href="/" class="boton-regresar">Inicio</a></li>
                 
                     <li><a href="../Galeria/biscocho.php" data-categoria="BIZCOCHO">BIZCOCHO</a></li>
                    <li><a href="../Galeria/danes.php" data-categoria="DANÉS">DANÉS</a></li>
                    <li><a href="../Galeria/hojaldre.php" data-categoria="HOJALDRE">HOJALDRE</a></li>
                       <?php if ($_SESSION['usuario'] === 'admin@gmail.com'): ?>
                    <li><a href="../Admin/productos_admin.php">PANEL</a></li>
                <?php endif; ?>
                </ul>
           
            </nav>
</header>
<body>
    <main class="contenedor-ver-carrito">
        <h2>Tu Carrito de Compras</h2>
        
        <div id="advertenciaProductos" class="advertencia-carrito">
            <p>Algunos productos en tu carrito ya no están disponibles o han sido retirados de la publicación. Por favor, elimínalos antes de continuar.</p>
            <button id="btnLimpiarNoDisponibles" class="btn-limpiar-no-disponibles">Eliminar todos los productos no disponibles</button>
        </div>
        
        <div class="contenido-carrito">
            <div class="items-carrito" id="itemsCarrito">
                <!-- Los items se cargarán con JS -->
            </div>
            <p id="carritoVacio" style="display:none;">El carrito está vacío</p>
            
            <div class="resumen-compra">
                <div class="total-carrito">
                    <span>Total:</span>
                    <span id="totalCarrito">$0.00</span>
                </div>
               <button class="boton-comprar" id="botonPagar" onclick="verificarYProceder()">
                    Proceder al pago
                </button>
            </div>
        </div>
    </main>
    <script>
    const usuarioActivoId = "<?php echo $_SESSION['id']; ?>"; // Si tu sesión guarda el 'id' así de directo
</script>
 <script>
            function toggleMenu() {
              const menu = document.querySelector('.nav-menu ul');
              menu.classList.toggle('active'); 
            }
        </script>

<script>
document.addEventListener('DOMContentLoaded', async () => {
    // Elementos del DOM
    const itemsCarrito = document.getElementById("itemsCarrito");
    const totalCarrito = document.getElementById("totalCarrito");
    const carritoVacio = document.getElementById("carritoVacio");
    const botonPagar = document.getElementById("botonPagar");
    const advertenciaProductos = document.getElementById("advertenciaProductos");
    const btnLimpiarNoDisponibles = document.getElementById("btnLimpiarNoDisponibles");

    // Datos del usuario
    const usuarioActivoId = "<?php echo isset($_SESSION['id']) ? $_SESSION['id'] : ''; ?>";
    if (!usuarioActivoId) {
        carritoVacio.style.display = "block";
        carritoVacio.textContent = "Debes iniciar sesión para ver tu carrito";
        return;
    }

    let carrito = {};
    let ultimaActualizacion = 0;
    let productosNoDisponibles = [];
    
    // Almacenar el estado de visibilidad de los productos
    let estadoProductos = {};

    // Función para cargar el carrito
    const cargarCarrito = async (forzar = false) => {
        try {
            const ahora = Date.now();
            if (!forzar && ahora - ultimaActualizacion < 1000) return;
            ultimaActualizacion = ahora;

            const response = await fetch(`../desProductoscarritos.json?t=${ahora}`);
            if (!response.ok) throw new Error('Error al cargar el carrito');
            
            const nuevoCarrito = await response.json();
            if (JSON.stringify(nuevoCarrito) !== JSON.stringify(carrito)) {
                carrito = nuevoCarrito;
                await mostrarCarrito();
                await verificarProductosDisponibles();
            }
        } catch (error) {
            console.error("Error:", error);
            carritoVacio.style.display = "block";
            carritoVacio.textContent = "Error al cargar el carrito";
        }
    };

    // Función para verificar si los productos están disponibles
    const verificarProductosDisponibles = async () => {
        try {
            const response = await fetch(`../controladoresPrincipales/verificarCarrito.php`);
            if (!response.ok) throw new Error('Error al verificar disponibilidad');
            
            const data = await response.json();
            productosNoDisponibles = data.productosNoDisponibles || [];
            
            // Mostrar advertencia si hay productos no disponibles
            if (productosNoDisponibles.length > 0) {
                advertenciaProductos.style.display = "block";
                
                // Marcar visualmente los productos no disponibles
                productosNoDisponibles.forEach(producto => {
                    const itemElement = document.querySelector(`[data-id="${usuarioActivoId}-${producto.index}"]`);
                    if (itemElement) {
                        // Agregar clase según el motivo
                        if (producto.motivo === 'no_visible') {
                            itemElement.classList.add('producto-no-visible');
                            // Guardar el estado del producto
                            estadoProductos[`${usuarioActivoId}-${producto.index}`] = 'no_visible';
                        } else {
                            itemElement.classList.add('producto-no-disponible');
                            // Guardar el estado del producto
                            estadoProductos[`${usuarioActivoId}-${producto.index}`] = producto.motivo;
                        }
                        
                        // Deshabilitar botones de aumentar/disminuir cantidad
                        const aumentarBtn = itemElement.querySelector('.aumentar-cantidad');
                        const disminuirBtn = itemElement.querySelector('.disminuir-cantidad');
                        if (aumentarBtn) aumentarBtn.disabled = true;
                        if (disminuirBtn) disminuirBtn.disabled = true;
                        
                        // Agregar mensaje de advertencia
                        let mensajeAdvertencia = '';
                        if (producto.motivo === 'no_existe') {
                            mensajeAdvertencia = 'Este producto ya no existe en el catálogo.';
                        } else if (producto.motivo === 'no_visible') {
                            mensajeAdvertencia = 'Este producto ya no está disponible para la venta. Se ha retirado de la publicación.';
                        } else if (producto.motivo === 'stock_insuficiente') {
                            mensajeAdvertencia = `Stock insuficiente. Disponible: ${producto.disponible} unidades.`;
                        }
                        
                        // Verificar si ya existe un mensaje de advertencia
                        let advertencia = itemElement.querySelector('.advertencia-producto, .mensaje-no-disponible');
                        if (!advertencia) {
                            advertencia = document.createElement('p');
                            advertencia.className = producto.motivo === 'no_visible' ? 'mensaje-no-disponible' : 'advertencia-producto';
                            itemElement.querySelector('.item-info').appendChild(advertencia);
                        }
                        
                        advertencia.innerHTML = `<i class="fas fa-exclamation-circle"></i> ${mensajeAdvertencia}`;
                    }
                });
            } else {
                advertenciaProductos.style.display = "none";
                
                // Quitar marcas de productos no disponibles
                document.querySelectorAll('.producto-no-disponible, .producto-no-visible').forEach(item => {
                    item.classList.remove('producto-no-disponible', 'producto-no-visible');
                    const advertencia = item.querySelector('.advertencia-producto, .mensaje-no-disponible');
                    if (advertencia) advertencia.remove();
                });
                
                // Limpiar el estado de los productos
                estadoProductos = {};
            }
            
            // Habilitar o deshabilitar el botón de pago
            botonPagar.disabled = productosNoDisponibles.length > 0;
            
        } catch (error) {
            console.error("Error al verificar disponibilidad:", error);
        }
    };

    // Función para mostrar disponibilidad
    const cargarDisponibilidad = async (elemento, nombreProducto, itemId) => {
        try {
            const response = await fetch(`../controladoresPrincipales/verificarVisibilidad.php?producto=${encodeURIComponent(nombreProducto)}`);
            const data = await response.json();
            
            if (data.estado === 'error') {
                elemento.textContent = "Error";
                return;
            }
            
            elemento.textContent = data.disponibles;
            
            // Obtener el elemento del item del carrito
            const itemCarrito = elemento.closest('.item-carrito');
            
            // Deshabilitar botón si no hay disponibilidad o el producto no es visible
            const aumentarBtn = itemCarrito.querySelector('.aumentar-cantidad');
            const disminuirBtn = itemCarrito.querySelector('.disminuir-cantidad');
            
            // Verificar si el producto es visible (des_gak = 1)
            if (!data.visible) {
                // Guardar el estado del producto
                estadoProductos[itemId] = 'no_visible';
                
                // Deshabilitar botones
                if (aumentarBtn) aumentarBtn.disabled = true;
                if (disminuirBtn) disminuirBtn.disabled = true;
                
                itemCarrito.classList.add('producto-no-visible');
                
                // Agregar mensaje de no disponible si no existe
                if (!itemCarrito.querySelector('.mensaje-no-disponible')) {
                    const mensajeNoDisponible = document.createElement('p');
                    mensajeNoDisponible.className = 'mensaje-no-disponible';
                    mensajeNoDisponible.innerHTML = '<i class="fas fa-exclamation-circle"></i> Este producto ya no está disponible. Se ha retirado de la publicación.';
                    itemCarrito.querySelector('.item-info').appendChild(mensajeNoDisponible);
                }
            } else if (data.disponibles <= 0) {
                // Guardar el estado del producto
                estadoProductos[itemId] = 'stock_insuficiente';
                
                aumentarBtn.disabled = true;
                aumentarBtn.title = "Producto agotado";
            } else {
                // Producto disponible
                if (!estadoProductos[itemId]) {
                    aumentarBtn.disabled = false;
                    disminuirBtn.disabled = false;
                    aumentarBtn.title = "";
                }
            }
        } catch (error) {
            console.error("Error al cargar disponibilidad:", error);
            elemento.textContent = "?";
        }
    };

    // Función para mostrar los items del carrito
    const mostrarCarrito = async () => {
        const scrollPosition = window.scrollY;
        const itemsActuales = Array.from(itemsCarrito.children).map(el => el.dataset.id);

        let todosItems = [];
        if (carrito[usuarioActivoId]) {
            todosItems = carrito[usuarioActivoId].map((item, index) => ({
                ...item,
                usuarioId: usuarioActivoId,
                idProducto: index,
                itemId: `${usuarioActivoId}-${index}`
            }));
        }

        if (todosItems.length === 0) {
            itemsCarrito.innerHTML = '';
            carritoVacio.style.display = "block";
            totalCarrito.textContent = "$0.00";
            botonPagar.disabled = true;
            advertenciaProductos.style.display = "none";
            window.scrollTo(0, scrollPosition);
            return;
        }

        carritoVacio.style.display = "none";
        botonPagar.disabled = false;

        // Procesar cada item
        todosItems.forEach(item => {
            const itemId = item.itemId;
            const existe = itemsActuales.includes(itemId);
            
            if (!existe) {
                const itemElement = document.createElement("div");
                itemElement.className = "item-carrito";
                itemElement.dataset.id = itemId;
                itemElement.innerHTML = `
                    <div class="imagen-producto">
                        <img src="../imgsProductos/producto-default.jpg" alt="${item.nombre}" loading="lazy">
                    </div>
                    <div class="item-info">
                        <h4>${item.nombre}</h4>
                        <div class="contador-cantidad">
                            <button class="disminuir-cantidad">-</button>
                            <span class="cantidad">${item.cantidad}</span>
                            <button class="aumentar-cantidad">+</button>
                        </div>
                        <p>Precio unitario: $${item.precio.toFixed(2)}</p>
                        <p>Disponibles: <span class="disponibles">Verificando...</span></p>
                    </div>
                    <div class="item-subtotal">
                        <p>Subtotal: $${(item.precio * item.cantidad).toFixed(2)}</p>
                        <button class="eliminar-item">Eliminar</button>
                    </div>
                `;
                itemsCarrito.appendChild(itemElement);

                // Buscar la imagen específica para este producto
                buscarImagenProducto(itemElement.querySelector('img'), item.nombre);
                // Cargar disponibilidad
                cargarDisponibilidad(itemElement.querySelector('.disponibles'), item.nombre, itemId);
                
                // Si el producto ya estaba marcado como no disponible, aplicar estilos
                if (estadoProductos[itemId]) {
                    const aumentarBtn = itemElement.querySelector('.aumentar-cantidad');
                    const disminuirBtn = itemElement.querySelector('.disminuir-cantidad');
                    
                    if (aumentarBtn) aumentarBtn.disabled = true;
                    if (disminuirBtn) disminuirBtn.disabled = true;
                    
                    if (estadoProductos[itemId] === 'no_visible') {
                        itemElement.classList.add('producto-no-visible');
                    } else {
                        itemElement.classList.add('producto-no-disponible');
                    }
                }
            } else {
                const itemElement = document.querySelector(`[data-id="${itemId}"]`);
                if (itemElement) {
                    itemElement.querySelector('.cantidad').textContent = item.cantidad;
                    itemElement.querySelector('.item-subtotal p').textContent = 
                        `Subtotal: $${(item.precio * item.cantidad).toFixed(2)}`;
                    // Actualizar disponibilidad
                    cargarDisponibilidad(itemElement.querySelector('.disponibles'), item.nombre, itemId);
                }
            }
        });

        // Eliminar items que ya no están
        itemsActuales.forEach(id => {
            if (!todosItems.some(item => item.itemId === id)) {
                const itemToRemove = document.querySelector(`[data-id="${id}"]`);
                if (itemToRemove) {
                    itemToRemove.style.transition = 'opacity 0.3s, height 0.3s, margin 0.3s, padding 0.3s';
                    itemToRemove.style.opacity = '0';
                    itemToRemove.style.height = '0';
                    itemToRemove.style.padding = '0';
                    itemToRemove.style.margin = '0';
                    itemToRemove.style.overflow = 'hidden';
                    setTimeout(() => itemToRemove.remove(), 300);
                }
            }
        });

        actualizarTotal();
        window.scrollTo(0, scrollPosition);
    };

    // Función para actualizar el total
    const actualizarTotal = () => {
        let total = 0;
        document.querySelectorAll('.item-carrito').forEach(item => {
            const subtotal = item.querySelector('.item-subtotal p').textContent.replace(/[^\d.]/g, '');
            total += parseFloat(subtotal);
        });
        totalCarrito.textContent = `$${total.toFixed(2)}`;
    };

    // Función para buscar imágenes de productos
    const buscarImagenProducto = async (imgElement, nombreProducto) => {
        const extensiones = ['.jpeg', '.jpg', '.png', '.webp'];
        const versionCache = `?v=${Date.now()}`;
        
        // Normalizar el nombre del producto
        const nombreLimpio = nombreProducto.toLowerCase()
            .normalize("NFD").replace(/[\u0300-\u036f]/g, "")
            .replace(/[^\w\s]/gi, '')
            .replace(/\s+/g, '_')
            .replace(/_+/g, '_');

        // Posibles nombres de archivo
        const posiblesNombres = [
            `${nombreLimpio}_principal`,
            `${nombreLimpio}`,
            nombreLimpio.replace(/_de_/g, '_'),
            nombreLimpio.replace(/_/g, '')
        ];

        // Probar todas las combinaciones
        for (const nombre of posiblesNombres) {
            for (const extension of extensiones) {
                const url = `../imgsProductos/${nombre}${extension}${versionCache}`;
                try {
                    const img = new Image();
                    img.src = url;
                    
                    await new Promise((resolve, reject) => {
                        img.onload = () => {
                            imgElement.src = url;
                            resolve(true);
                        };
                        img.onerror = () => reject(new Error(`No se pudo cargar ${url}`));
                    });
                    return;
                } catch (error) {
                    continue;
                }
            }
        }
        
        console.warn(`No se encontró imagen para: ${nombreProducto}`);
    };

    
    const modificarCarrito = async (accion, usuarioId, idProducto, cambio = 0, nombreProducto = '') => {
        try {
            const formData = new FormData();
            formData.append('accion', accion);
            formData.append('usuarioId', usuarioId);
            formData.append('idProducto', idProducto);
            formData.append('nombreProducto', nombreProducto);
            if (cambio) formData.append('cambio', cambio);

            const response = await fetch('guardarCarrito.php', {
                method: 'POST',
                body: formData
            });

            if (!response.ok) throw new Error('Error al modificar el carrito');
            
            const data = await response.json();
            if (data.estado === 'error') throw new Error(data.mensaje);
            
            return data;
        } catch (error) {
            console.error("Error:", error);
            throw error;
        }
    };

    // Función para eliminar todos los productos no disponibles
    const eliminarProductosNoDisponibles = async () => {
        if (!confirm('¿Estás seguro de eliminar todos los productos no disponibles del carrito?')) {
            return;
        }
        
        try {
            // Ordenar por índice en orden descendente para evitar problemas al eliminar
            const indices = [...productosNoDisponibles]
                .sort((a, b) => b.index - a.index)
                .map(p => p.index);
                
            for (const indice of indices) {
                await modificarCarrito('eliminar', usuarioActivoId, indice);
            }
            
            // Recargar el carrito
            await cargarCarrito(true);
            
        } catch (error) {
            console.error("Error al eliminar productos no disponibles:", error);
            alert("Ocurrió un error al eliminar los productos no disponibles");
        }
    };

    // Eventos para los botones del carrito
    itemsCarrito.addEventListener('click', async (e) => {
        const itemElement = e.target.closest('.item-carrito');
        if (!itemElement) return;

        const itemId = itemElement.dataset.id;
        const [usuarioId, idProducto] = itemId.split('-');
        const cantidadElement = itemElement.querySelector('.cantidad');
        let cantidadActual = parseInt(cantidadElement.textContent);
        const precioUnitario = parseFloat(itemElement.querySelector('.item-info p').textContent.replace(/[^\d.]/g, ''));
        const nombreProducto = itemElement.querySelector('h4').textContent;

        // Verificar si el producto está marcado como no disponible
        const esNoDisponible = estadoProductos[itemId] === 'no_visible';

        if (e.target.classList.contains('eliminar-item')) {
            itemElement.style.transition = 'opacity 0.3s, height 0.3s, margin 0.3s, padding 0.3s';
            itemElement.style.opacity = '0';
            itemElement.style.height = '0';
            itemElement.style.padding = '0';
            itemElement.style.margin = '0';
            itemElement.style.overflow = 'hidden';
            setTimeout(() => itemElement.remove(), 300);

            try {
                await modificarCarrito('eliminar', usuarioId, idProducto);
                actualizarTotal();
                await verificarProductosDisponibles();
                
                // Eliminar el estado del producto
                delete estadoProductos[itemId];
            } catch (error) {
                alert(error.message);
            }

        } else if (e.target.classList.contains('aumentar-cantidad') && !esNoDisponible && !e.target.disabled) {
            cantidadActual += 1;
            cantidadElement.textContent = cantidadActual;
            itemElement.querySelector('.item-subtotal p').textContent = `Subtotal: $${(precioUnitario * cantidadActual).toFixed(2)}`;

            try {
                await modificarCarrito('cambiar', usuarioId, idProducto, 1, nombreProducto);
                actualizarTotal();
                // Actualizar disponibilidad después de modificar
                cargarDisponibilidad(itemElement.querySelector('.disponibles'), nombreProducto, itemId);
                await verificarProductosDisponibles();
            } catch (error) {
                // Revertir el cambio si hay error
                cantidadActual -= 1;
                cantidadElement.textContent = cantidadActual;
                itemElement.querySelector('.item-subtotal p').textContent = `Subtotal: $${(precioUnitario * cantidadActual).toFixed(2)}`;
                alert(error.message);
            }

        } else if (e.target.classList.contains('disminuir-cantidad') && !esNoDisponible && !e.target.disabled) {
            if (cantidadActual <= 1) {
                console.log("No puedes disminuir más, debes eliminar el producto.");
                return;
            }

            cantidadActual -= 1;
            cantidadElement.textContent = cantidadActual;
            itemElement.querySelector('.item-subtotal p').textContent = `Subtotal: $${(precioUnitario * cantidadActual).toFixed(2)}`;

            try {
                await modificarCarrito('cambiar', usuarioId, idProducto, -1, nombreProducto);
                actualizarTotal();
                // Actualizar disponibilidad después de modificar
                cargarDisponibilidad(itemElement.querySelector('.disponibles'), nombreProducto, itemId);
                await verificarProductosDisponibles();
            } catch (error) {
                // Revertir el cambio si hay error
                cantidadActual += 1;
                cantidadElement.textContent = cantidadActual;
                itemElement.querySelector('.item-subtotal p').textContent = `Subtotal: $${(precioUnitario * cantidadActual).toFixed(2)}`;
                alert(error.message);
            }
        }
    });

    // Evento para eliminar todos los productos no disponibles
    btnLimpiarNoDisponibles.addEventListener('click', eliminarProductosNoDisponibles);

    // Función para verificar disponibilidad antes de proceder al pago
    window.verificarYProceder = async () => {
        await verificarProductosDisponibles();
        
        if (productosNoDisponibles.length > 0) {
            alert('No puedes proceder al pago porque hay productos no disponibles en tu carrito. Por favor, elimínalos antes de continuar.');
            return;
        }
        
        window.location.href = 'https://panchielito.com/desProductos/cobrar.php';
    };

    // Inicialización
    setInterval(() => cargarCarrito(), 2000);
    await cargarCarrito(true);
});
</script>


</body>

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



