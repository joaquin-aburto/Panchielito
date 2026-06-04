<!DOCTYPE html>
<html lang="es">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Panadería Minimal | Detalle de Producto</title>
        <link rel="stylesheet" type="text/css" href="DesProductos.css">
    </head>

    <body>
        <!-- Botón del carrito -->
        <button id="botonCarrito">
            🛒 Carrito <span id="contadorCarrito" style="display: none;">0</span>
        </button>

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
            <!-- Botón de regreso colocado fuera del gridProducto -->
            <a href="/" class="boton-regresar">← Volver al inicio</a>

            <div class="gridProducto">
                <!-- Sección de imágenes -->
                <div class="imagenesProducto">
                    <img src="img/pan-de-muerto.png" alt="Pan artesanal" class="imagenPrincipal" id="imagenPrincipal">
                    <div class="miniaturas">
                        <img src="img/pan-de-muerto.png" class="miniatura" onclick="cambiarImagen(this)">
                        <img src="img/pan-de-muerto.png" class="miniatura" onclick="cambiarImagen(this)">
                        <img src="img/pan-de-muerto.png" class="miniatura" onclick="cambiarImagen(this)">
                        <img src="img/pan-de-muerto.png" class="miniatura" onclick="cambiarImagen(this)">
                    </div>
                </div>

                <!-- Información del producto -->
                <div class="infoProducto">
                    <h1 class="tituloProducto">PanMuerto</h1>
                    <div class="precioProducto">$4.99</div>
                    <div class="stockProducto" id="stockDisplay">Disponibles: 15 unidades</div>
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
                        Pan artesanal horneado en horno de leña, elaborado con harina orgánica 
                        y fermentación natural. Peso aproximado 800g. Ideal para acompañar 
                        con aceite de oliva o tus dips favoritos.
                    </p>

                    <button class="agregarAlCarrito" id="agregarAlCarrito">🛒 Añadir al carrito</button>
                </div>
            </div>

            <!-- Sección de comentarios -->
            <div class="seccionComentarios">
                <h2>Opiniones de clientes</h2>
                
                <button id="mostrarFormularioComentario">Agregar comentario</button>
                
                <div id="formularioComentario">
                    <input type="text" id="nombreUsuario" placeholder="Tu nombre" required>
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
                
                <div id="listaComentarios"></div>
            </div>

        <script src="DesProductos.js"></script>
    </body>
</html>
