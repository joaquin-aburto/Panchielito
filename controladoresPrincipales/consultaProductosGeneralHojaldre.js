// Función que redirige a la página de detalle del producto pasando id y nombre
function verProducto(id, nombre) {
    window.location.href = `/Panchielito/desProductos/vistaProductos.php?id=${id}&nombre=${encodeURIComponent(nombre)}`;
}

// Función para crear la tarjeta de producto
function crearTarjetaProducto(producto) {
    const divProducto = document.createElement('div');
    divProducto.classList.add('product');

    // Imagen del producto
    const img = document.createElement('img');
    img.src = (producto.imagenes && producto.imagenes.length > 0) 
        ? producto.imagenes[0] 
        : 'imgsProductos/null.jpg'; // Imagen por defecto si no hay imágenes
    img.alt = producto.nombre;
    divProducto.appendChild(img);

    // Nombre del producto
    const h3 = document.createElement('h3');
    h3.textContent = producto.nombre;
    divProducto.appendChild(h3);

    // Precio del producto
    const p = document.createElement('p');
    p.textContent = `$${parseFloat(producto.precio).toFixed(2)} MXN`;
    divProducto.appendChild(p);

    // Botón para ver producto
    const btn = document.createElement('button');
    btn.textContent = 'Ver producto';
    btn.addEventListener('click', () => verProducto(producto.id, producto.nombre));
    divProducto.appendChild(btn);

    return divProducto;
}

// Función para cargar los productos desde el servidor
async function cargarProductos(categoria = null) {
    try {
        let url = '../controladoresPrincipales/consultaProductos.php';
        
        if (categoria) {
            url += `?categoria=${encodeURIComponent(categoria)}`;
        }

        const response = await fetch(url);

        if (!response.ok) {
            throw new Error('Error en la respuesta del servidor');
        }

        const productos = await response.json();

        if (!Array.isArray(productos)) {
            throw new Error('La respuesta no es un array válido');
        }

        const container = document.getElementById('productos-container');
        container.innerHTML = ''; // Limpiar contenido previo

        if (productos.length === 0) {
            container.innerHTML = '<p>No hay productos disponibles en esta categoría.</p>';
            return;
        }

        productos.forEach(producto => {
            container.appendChild(crearTarjetaProducto(producto));
        });

    } catch (error) {
        console.error('Error al cargar productos:', error);
        document.getElementById('productos-container').innerHTML = 
            '<p>Error al cargar los productos. Por favor, inténtalo de nuevo más tarde.</p>';
    }
}

// Ejecutar las funciones al cargar la página
document.addEventListener('DOMContentLoaded', function() {
    // Cargar solo los productos de la categoría "bizcocho"
    cargarProductos('hojaldre');
});
