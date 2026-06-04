// Función que redirige a la página de detalle del producto pasando id y nombre
function verProducto(id, nombre) {
    window.location.href = '../desProductos/vistaProductos.php?id=' + id + '&nombre=' + encodeURIComponent(nombre);
}

// Función para crear la tarjeta de producto
function crearTarjetaProducto(producto) {
    const divProducto = document.createElement('div');
    divProducto.classList.add('product');

    // Usamos la primera imagen del array (si existe)
    const img = document.createElement('img');
    if (producto.imagenes && producto.imagenes.length > 0) {
        img.src = producto.imagenes[0];
    } else {
        img.src = 'imgsProductos/null.jpg'; // Imagen por defecto si no hay imágenes
    }
    img.alt = producto.nombre;
    divProducto.appendChild(img);

    const h3 = document.createElement('h3');
    h3.textContent = producto.nombre;
    divProducto.appendChild(h3);

    const p = document.createElement('p');
    p.textContent = '$' + parseFloat(producto.precio).toFixed(2) + ' MXN';
    divProducto.appendChild(p);

    const btn = document.createElement('button');
    btn.textContent = 'Ver producto';
    btn.addEventListener('click', function() {
        verProducto(producto.id, producto.nombre);
    });
    divProducto.appendChild(btn);

    return divProducto;
}

// Función para cargar los productos desde el endpoint
async function cargarProductos(categoria = null) {
    try {
        let url = '../controladoresPrincipales/consultaProductos.php';
        
        // Si hay una categoría seleccionada, añadirla como parámetro
        if (categoria) {
            url += `?categoria=${encodeURIComponent(categoria)}`;
        }

        const response = await fetch(url);
        
        // Verifica que la respuesta sea válida
        if (!response.ok) {
            throw new Error('Error en la respuesta del servidor');
        }

        const productos = await response.json();
        
        // Verifica que los productos sean un array
        if (!Array.isArray(productos)) {
            throw new Error('La respuesta no es un array');
        }

        const container = document.getElementById('productos-container');
        container.innerHTML = ''; // Limpiar contenido previo

        // Mostrar mensaje si no hay productos
        if (productos.length === 0) {
            container.innerHTML = '<p>No hay productos disponibles en esta categoría.</p>';
            return;
        }

        // Por cada producto, crea la tarjeta y la añade al contenedor
        productos.forEach(producto => {
            container.appendChild(crearTarjetaProducto(producto));
        });

    } catch (error) {
        console.error('Error al cargar productos:', error);
        document.getElementById('productos-container').innerHTML = 
            '<p>Error al cargar los productos. Por favor, inténtalo de nuevo más tarde.</p>';
    }
}

// Manejar clics en las categorías
function configurarFiltrosCategorias() {
    document.querySelectorAll('[data-categoria]').forEach(enlace => {
        enlace.addEventListener('click', function(e) {
            e.preventDefault();
            const categoria = this.getAttribute('data-categoria');
            cargarProductos(categoria);
            
            // Opcional: Resaltar la categoría seleccionada
            document.querySelectorAll('[data-categoria]').forEach(item => {
                item.classList.remove('active');
            });
            this.classList.add('active');
        });
    });
}

// Ejecutar las funciones al cargar la página
document.addEventListener('DOMContentLoaded', function() {
    // Cargar todos los productos inicialmente
    cargarProductos();
    
    // Configurar los filtros por categoría
    configurarFiltrosCategorias();
});