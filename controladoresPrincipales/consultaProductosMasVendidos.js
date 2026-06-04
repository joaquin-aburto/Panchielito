// Función que redirige a la página de detalle del producto pasando id y nombre
function verProducto(id, nombre) {
  // Codificamos el nombre para usarlo en la URL
  window.location.href = "/desProductos/vistaProductos.php?id=" + id + "&nombre=" + encodeURIComponent(nombre)
}

// Función para crear la tarjeta de producto
function crearTarjetaProducto(producto) {
  const divProducto = document.createElement("div")
  divProducto.classList.add("product")

  // Usamos la primera imagen del array (si existe)
  const img = document.createElement("img")
  if (producto.imagenes && producto.imagenes.length > 0) {
    img.src = producto.imagenes[0]
  } else {
    img.src = "imgsProductos/null.jpg" // Imagen por defecto si no hay imágenes
  }
  img.alt = producto.nombre
  divProducto.appendChild(img)

  const h3 = document.createElement("h3")
  h3.textContent = producto.nombre
  divProducto.appendChild(h3)

  const p = document.createElement("p")
  p.textContent = "$" + Number.parseFloat(producto.precio).toFixed(2) + " MXN"
  divProducto.appendChild(p)

  const btn = document.createElement("button")
  btn.textContent = "Ver producto"
  // Al hacer clic, se llamará a la función verProducto con id y nombre
  btn.addEventListener("click", () => {
    verProducto(producto.id, producto.nombre)
  })
  divProducto.appendChild(btn)

  return divProducto
}

// Función para cargar los productos más vendidos desde el endpoint
async function cargarProductosMasVendidos() {
  try {
    const response = await fetch("../controladoresPrincipales/consultaProductosMasVendidos.php")

    // Verifica que la respuesta sea válida
    if (!response.ok) {
      throw new Error("Error en la respuesta del servidor")
    }

    const productos = await response.json()

    // Verifica que los productos sean un array
    if (!Array.isArray(productos)) {
      throw new Error("La respuesta no es un array")
    }

    const container = document.getElementById("productos-container")
    container.innerHTML = "" // Limpiar contenido previo

    // Si no hay productos, mostrar mensaje
    if (productos.length === 0) {
      const mensaje = document.createElement("p")
      mensaje.textContent = "No hay productos disponibles en este momento."
      mensaje.style.textAlign = "center"
      mensaje.style.width = "100%"
      container.appendChild(mensaje)
      return
    }

    // Por cada producto, crea la tarjeta y la añade al contenedor
    productos.forEach((producto) => {
      container.appendChild(crearTarjetaProducto(producto))
    })
  } catch (error) {
    console.error("Error al cargar productos más vendidos:", error)

    // Mostrar mensaje de error en el contenedor
    const container = document.getElementById("productos-container")
    container.innerHTML =
      '<p style="text-align: center; width: 100%;">No se pudieron cargar los productos. Intente más tarde.</p>'
  }
}

// Ejecutar la función al cargar la página
document.addEventListener("DOMContentLoaded", cargarProductosMasVendidos)
