
let carrito = []
let stockDisponible = 0 // Inicializamos en 0, se actualizará con el valor real

const botonCarrito = document.getElementById("botonCarrito")
const asideCarrito = document.getElementById("asideCarrito")
const contenedorProducto = document.querySelector(".contenedorProducto")
const contadorCarrito = document.getElementById("contadorCarrito")
const itemsCarrito = document.getElementById("itemsCarrito")
const carritoVacio = document.getElementById("carritoVacio")
const agregarAlCarritoBtn = document.getElementById("agregarAlCarrito")
const inputCantidad = document.getElementById("cantidad")

document.addEventListener("DOMContentLoaded", () => {
  // Obtener el stock disponible del elemento HTML
  obtenerStockInicial()
  cargarCarritoDesdeServidor()
})

// Función para obtener el stock inicial del elemento HTML
function obtenerStockInicial() {
  const stockDisplay = document.getElementById("stockDisplay")
  if (stockDisplay) {
    // Extraer el número de unidades del texto "Disponibles: X unidades"
    const textoStock = stockDisplay.textContent
    const match = textoStock.match(/\d+/)
    if (match) {
      stockDisponible = Number.parseInt(match[0])
      validarStock()
    }
  }
}

botonCarrito.addEventListener("click", toggleCarrito)
agregarAlCarritoBtn.addEventListener("click", agregarAlCarrito)

function toggleCarrito() {
  asideCarrito.classList.toggle("activo")
  contenedorProducto.classList.toggle("activo")
  document.querySelector(".seccionComentarios").classList.toggle("activo")
}

// Función para cargar el carrito desde el servidor
async function cargarCarritoDesdeServidor() {
  try {
    const respuesta = await fetch("carrito.php")
    const datos = await respuesta.json()

    if (datos.estado === "exito") {
      carrito = datos.carrito
      actualizarCarrito()

      // Actualizar el stock disponible basado en lo que ya está en el carrito
      const productoActual = document.querySelector(".tituloProducto").textContent
      const productoEnCarrito = carrito.find((item) => item.nombre === productoActual)

      if (productoEnCarrito) {
        stockDisponible -= productoEnCarrito.cantidad
      }

      actualizarStockDisplay()
      validarStock()
    } else {
      console.error("Error al cargar el carrito:", datos.mensaje)
    }
  } catch (error) {
    console.error("Error al cargar el carrito:", error)
  }
}

async function agregarAlCarrito() {
  const cantidad = Number.parseInt(inputCantidad.value)
  const producto = {
    nombre: document.querySelector(".tituloProducto").textContent,
    precio: Number.parseFloat(document.querySelector(".precioProducto").textContent.replace("$", "")),
    cantidad: cantidad,
  }

  try {
    const respuesta = await fetch("carrito.php", {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
      },
      body: JSON.stringify({
        accion: "agregar",
        producto: producto,
      }),
    })

    const datos = await respuesta.json()

    if (datos.estado === "exito") {
      carrito = datos.carrito
      stockDisponible -= cantidad
      actualizarStockDisplay()
      validarStock()
      actualizarCarrito()
    } else {
      console.error("Error al agregar al carrito:", datos.mensaje)
    }
  } catch (error) {
    console.error("Error al agregar al carrito:", error)
  }
}

function limpiarNombreProducto(nombre) {
  return nombre.replace(/[^a-zA-Z0-9]/g, "")
}

async function eliminarDelCarrito(index) {
  try {
    const respuesta = await fetch("carrito.php", {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
      },
      body: JSON.stringify({
        accion: "eliminar",
        indice: index,
      }),
    })

    const datos = await respuesta.json()

    if (datos.estado === "exito") {
      // Actualizar el stock si el producto eliminado es el mismo que se está viendo
      const productoActual = document.querySelector(".tituloProducto").textContent
      const productoEliminado = carrito[index]

      if (productoEliminado && productoEliminado.nombre === productoActual) {
        stockDisponible += productoEliminado.cantidad
        actualizarStockDisplay()
        validarStock()
      }

      carrito = datos.carrito
      actualizarCarrito()
    } else {
      console.error("Error al eliminar del carrito:", datos.mensaje)
    }
  } catch (error) {
    console.error("Error al eliminar del carrito:", error)
  }
}

async function actualizarCantidadCarrito(index, nuevaCantidad) {
  try {
    const respuesta = await fetch("carrito.php", {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
      },
      body: JSON.stringify({
        accion: "actualizar",
        indice: index,
        cantidad: nuevaCantidad,
      }),
    })

    const datos = await respuesta.json()

    if (datos.estado === "exito") {
      // Actualizar el stock si el producto actualizado es el mismo que se está viendo
      const productoActual = document.querySelector(".tituloProducto").textContent
      const productoAnterior = carrito[index]
      const productoNuevo = datos.carrito.find((item, i) => i === index)

      if (productoAnterior && productoAnterior.nombre === productoActual) {
        const diferencia = productoAnterior.cantidad - (productoNuevo ? productoNuevo.cantidad : 0)
        stockDisponible += diferencia
        actualizarStockDisplay()
        validarStock()
      }

      carrito = datos.carrito
      actualizarCarrito()
    } else {
      console.error("Error al actualizar el carrito:", datos.mensaje)
    }
  } catch (error) {
    console.error("Error al actualizar el carrito:", error)
  }
}

function actualizarCarrito() {
  itemsCarrito.innerHTML = ""
  carritoVacio.style.display = carrito.length === 0 ? "block" : "none"

  carrito.forEach((item, index) => {
    const div = document.createElement("div")
    div.className = "item-carrito"
    div.innerHTML = `
      <div class="item-superior">
        <h3>${item.nombre}</h3>
        <button onclick="eliminarDelCarrito(${index})">×</button>
      </div>
      <div class="item-inferior">
        <div class="cantidad-control">
          <button onclick="actualizarCantidadCarrito(${index}, ${item.cantidad - 1})">-</button>
          <span>${item.cantidad}</span>
          <button onclick="actualizarCantidadCarrito(${index}, ${item.cantidad + 1})">+</button>
        </div>
        <p>$${item.precio.toFixed(2)} c/u</p>
        <p>Total: $${(item.cantidad * item.precio).toFixed(2)}</p>
      </div>
    `
    itemsCarrito.appendChild(div)
  })

  actualizarTotal()
  actualizarContador()
}

function actualizarTotal() {
  const total = carrito.reduce((acc, item) => acc + item.precio * item.cantidad, 0)
  document.getElementById("totalCarrito").textContent = `$${total.toFixed(2)}`
}

function actualizarContador() {
  const totalItems = carrito.reduce((acc, item) => acc + item.cantidad, 0)
  contadorCarrito.textContent = totalItems
  contadorCarrito.style.display = totalItems > 0 ? "block" : "none"
}

function actualizarStockDisplay() {
  const stockDisplay = document.getElementById("stockDisplay")
  stockDisplay.textContent = stockDisponible > 0 ? `Disponibles: ${stockDisponible} unidades` : "AGOTADO"
  stockDisplay.style.color = stockDisponible > 0 ? "#f39c12" : "#e74c3c"
}

function validarStock() {
  const cantidad = Number.parseInt(inputCantidad.value)
  if (cantidad > stockDisponible) {
    inputCantidad.value = stockDisponible
    mostrarAdvertencia(`¡Solo quedan ${stockDisponible} unidades!`)
  } else if (cantidad < 1) {
    inputCantidad.value = 1
  } else {
    limpiarAdvertencia()
  }
  agregarAlCarritoBtn.disabled = stockDisponible === 0
}

function mostrarAdvertencia(mensaje) {
  const advertenciaStock = document.getElementById("advertenciaStock")
  advertenciaStock.textContent = mensaje
}

function limpiarAdvertencia() {
  const advertenciaStock = document.getElementById("advertenciaStock")
  advertenciaStock.textContent = ""
}

function ajustarCantidad(cambio) {
  const nuevaCantidad = Number.parseInt(inputCantidad.value) + cambio
  inputCantidad.value = Math.max(1, Math.min(nuevaCantidad, stockDisponible))
  validarStock()
}

function cambiarImagen(miniatura) {
  document.getElementById("imagenPrincipal").src = miniatura.src
}

document.querySelector(".boton-comprar").addEventListener("click", () => {
  if (carrito.length === 0) {
    alert("¡Tu carrito está vacío!")
    return
  }

  // Redirigir a la página de pago
  window.location.href = "cobrar.php"
})

/* =========================
   Funcionalidad de Comentarios
========================= */
const btnMostrarFormulario = document.getElementById("mostrarFormularioComentario")
const formularioComentario = document.getElementById("formularioComentario")
const btnEnviarComentario = document.getElementById("enviarComentario")
const textoComentario = document.getElementById("textoComentario")
const estrellasContenedor = document.getElementById("estrellasCalificacion")
const inputValorCalificacion = document.getElementById("valorCalificacion")
const listaComentarios = document.getElementById("listaComentarios")

// Mostrar u ocultar el formulario de comentario
btnMostrarFormulario.addEventListener("click", () => {
  formularioComentario.style.display =
    formularioComentario.style.display === "none" || formularioComentario.style.display === "" ? "block" : "none"
})

// Selección de estrellas para la calificación
estrellasContenedor.querySelectorAll(".estrella").forEach((estrella) => {
  estrella.addEventListener("click", function () {
    const valor = Number.parseInt(this.getAttribute("data-valor"))
    inputValorCalificacion.value = valor
    // Actualiza la apariencia de las estrellas
    estrellasContenedor.querySelectorAll(".estrella").forEach((el) => {
      el.classList.remove("seleccionada")
      if (Number.parseInt(el.getAttribute("data-valor")) <= valor) {
        el.classList.add("seleccionada")
      }
    })
  })
})

// Enviar comentario
btnEnviarComentario.addEventListener("click", async () => {
  const producto = document.querySelector(".tituloProducto").textContent
  const productoLimpiado = limpiarNombreProducto(producto)
  // Obtenemos el nombre y el id del usuario de la sesión (ya presente en el HTML)
  const usuarioElem = document.getElementById("nombreUsuario")
  const usuario = usuarioElem.textContent.trim()
  const usuarioId = usuarioElem.dataset.id
  const texto = textoComentario.value.trim()
  const calificacion = Number.parseInt(inputValorCalificacion.value)

  if (!usuario || !texto || calificacion === 0) {
    alert("Por favor, completa todos los campos")
    return
  }

  try {
    const respuesta = await fetch("comentarios.php?producto=" + encodeURIComponent(productoLimpiado), {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
      },
      body: JSON.stringify({
        accion: "agregar",
        producto: productoLimpiado, // Enviar el nombre LIMPIO
        usuario: usuario,
        id: usuarioId, // Se envía el identificador del usuario
        texto: texto,
        calificacion: calificacion,
      }),
    })

    // Obtenemos la respuesta en XML y la parseamos
    const respuestaText = await respuesta.text()
    const parser = new DOMParser()
    const xmlResponse = parser.parseFromString(respuestaText, "application/xml")
    const estadoElem = xmlResponse.getElementsByTagName("estado")[0]
    if (estadoElem && estadoElem.textContent === "exito") {
      cargarComentarios()
      // No reiniciamos el span del nombre, pues proviene de la sesión
      textoComentario.value = ""
      inputValorCalificacion.value = 0
      estrellasContenedor.querySelectorAll(".estrella").forEach((el) => el.classList.remove("seleccionada"))
      formularioComentario.style.display = "none"
    } else {
      alert("Error al enviar comentario")
    }
  } catch (error) {
    console.error("Error al enviar comentario:", error)
  }
})

// Función para cargar y ordenar los comentarios, además de agregar botones de edición y eliminación
async function cargarComentarios() {
  const producto = document.querySelector(".tituloProducto").textContent.trim()
  const productoLimpiado = limpiarNombreProducto(producto)
  const currentUserElem = document.getElementById("nombreUsuario")
  const currentUserId = currentUserElem ? currentUserElem.dataset.id : ""

  try {
    const respuesta = await fetch("comentarios.php?producto=" + encodeURIComponent(productoLimpiado))
    if (!respuesta.ok) {
      console.error("No se pudo obtener los comentarios. Código:", respuesta.status)
      return
    }

    const xmlText = await respuesta.text()
    console.log("XML recibido:", xmlText)

    const parser = new DOMParser()
    const xmlDoc = parser.parseFromString(xmlText, "application/xml")
    const comentariosNodes = Array.from(xmlDoc.getElementsByTagName("comentario"))
    listaComentarios.innerHTML = ""

    // Variables para promedio de calificaciones
    let totalCalificacion = 0
    let count = 0

    // Ordenamos: los comentarios del usuario conectado primero
    comentariosNodes.sort((a, b) => {
      const aId = a.getElementsByTagName("usuario_id")[0]?.textContent || ""
      const bId = b.getElementsByTagName("usuario_id")[0]?.textContent || ""
      if (aId === currentUserId && bId !== currentUserId) return -1
      if (aId !== currentUserId && bId === currentUserId) return 1
      return 0
    })

    // Recorremos cada comentario
    comentariosNodes.forEach((comentarioElem) => {
      const usuario = comentarioElem.getElementsByTagName("usuario")[0]?.textContent || "Anónimo"
      const usuarioId = comentarioElem.getElementsByTagName("usuario_id")[0]?.textContent || ""
      const texto = comentarioElem.getElementsByTagName("texto")[0]?.textContent || ""
      const fecha = comentarioElem.getElementsByTagName("fecha")[0]?.textContent || ""
      const calificacion = Number.parseInt(
        comentarioElem.getElementsByTagName("calificacion")[0]?.textContent || "0",
        10,
      )

      totalCalificacion += calificacion
      count++

      const fechaFormateada = fecha
        ? new Date(fecha).toLocaleDateString("es-ES", {
            day: "numeric",
            month: "long",
            year: "numeric",
          })
        : "(Fecha desconocida)"

      const divComentario = document.createElement("div")
      divComentario.classList.add("comentario")

      // Si el comentario pertenece al usuario conectado, se agregarán botones de editar y eliminar.
      let botones = ""
      if (usuarioId === currentUserId && currentUserId !== "") {
        botones = `
          <button class="btnEditarComentario" data-fecha="${fecha}">Editar</button>
          <button class="btnEliminarComentario" data-fecha="${fecha}">Eliminar</button>
        `
      }

      divComentario.innerHTML = `
        <div class="infoAutor">
          <div>
            <div class="autorComentario" data-id="${usuarioId}">
              ${usuario} <span class="fechaComentario">- ${fechaFormateada}</span>
            </div>
            ${botones}
            <div class="calificacion">
              ${"★".repeat(calificacion)}${"☆".repeat(5 - calificacion)}
            </div>
          </div>
        </div>
        <p class="textoComentario">${texto}</p>
      `

      listaComentarios.appendChild(divComentario)

      // Asignar eventos a los botones de editar y eliminar si existen
      if (usuarioId === currentUserId && currentUserId !== "") {
        const btnEditar = divComentario.querySelector(".btnEditarComentario")
        const btnEliminar = divComentario.querySelector(".btnEliminarComentario")
        if (btnEditar) {
          btnEditar.addEventListener("click", () => {
            editarComentario(fecha, divComentario)
          })
        }
        if (btnEliminar) {
          btnEliminar.addEventListener("click", () => {
            eliminarComentario(fecha)
          })
        }
      }
    })

    // Actualizar calificación promedio en la sección del producto
    const promedio = totalCalificacion / count
    const promedioRedondeado = Math.round(promedio)
    document.querySelector(".infoProducto .calificacion").innerHTML =
      `${"★".repeat(promedioRedondeado)}${"☆".repeat(5 - promedioRedondeado)} (${promedio.toFixed(1)}/5.0)`
  } catch (error) {
    console.error("Error al cargar comentarios:", error)
  }
}

// Función para editar un comentario (usando la fecha como identificador único)
function editarComentario(fechaIdentificador, divComentario) {
  // Obtiene el texto y calificación actuales
  const textoActual = divComentario.querySelector(".textoComentario").textContent
  const calificacionActual = divComentario.querySelector(".calificacion").textContent.replace(/[^★]/g, "").length

  // Muestra un formulario inline para editar
  const form = document.createElement("div")
  form.classList.add("formEditarComentario")
  form.innerHTML = `
    <textarea class="textoEditar" required>${textoActual}</textarea>
    <div>
      <span>Calificación: </span>
      <span class="estrellasEditar">
        <span class="estrellaEditar" data-valor="1">☆</span>
        <span class="estrellaEditar" data-valor="2">☆</span>
        <span class="estrellaEditar" data-valor="3">☆</span>
        <span class="estrellaEditar" data-valor="4">☆</span>
        <span class="estrellaEditar" data-valor="5">☆</span>
      </span>
    </div>
    <button class="btnGuardarEdicion">Guardar</button>
    <button class="btnCancelarEdicion">Cancelar</button>
  `

  const estrellasEditar = form.querySelectorAll(".estrellaEditar")

  // **Marcar las estrellas según la calificación actual**
  estrellasEditar.forEach((estrella) => {
    const valor = Number.parseInt(estrella.getAttribute("data-valor"))
    if (valor <= calificacionActual) {
      estrella.textContent = "★"
      estrella.classList.add("seleccionada") // Agregar clase CSS
    }
  })

  // **Manejo de clic en estrellas**
  estrellasEditar.forEach((estrella) => {
    estrella.addEventListener("click", function () {
      const nuevoValor = Number.parseInt(this.getAttribute("data-valor")) // Obtiene el valor de la estrella

      // Recorrer y actualizar todas las estrellas
      estrellasEditar.forEach((el) => {
        const valor = Number.parseInt(el.getAttribute("data-valor"))
        if (valor <= nuevoValor) {
          el.textContent = "★"
          el.classList.add("seleccionada")
        } else {
          el.textContent = "☆"
          el.classList.remove("seleccionada")
        }
      })
    })
  })

  // Insertar el formulario justo debajo del comentario
  divComentario.appendChild(form)

  // Botón cancelar
  form.querySelector(".btnCancelarEdicion").addEventListener("click", () => {
    form.remove()
  })

  // Botón guardar
  form.querySelector(".btnGuardarEdicion").addEventListener("click", async () => {
    const nuevoTexto = form.querySelector(".textoEditar").value.trim()
    let nuevoValor = 0
    estrellasEditar.forEach((el) => {
      if (el.textContent === "★") nuevoValor++
    })

    if (!nuevoTexto || nuevoValor === 0) {
      alert("Por favor, completa todos los campos para editar el comentario.")
      return
    }

    if (!confirm("¿Estás seguro de modificar el comentario?")) {
      return
    }

    // Preparar los datos para enviar la edición
    const producto = document.querySelector(".tituloProducto").textContent
    const productoLimpiado = limpiarNombreProducto(producto)
    const usuarioElem = document.getElementById("nombreUsuario")
    const usuario = usuarioElem.textContent.trim()
    const usuarioId = usuarioElem.dataset.id

    try {
      const respuesta = await fetch("comentarios.php?producto=" + encodeURIComponent(productoLimpiado), {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
          accion: "editar",
          producto: productoLimpiado,
          usuario: usuario,
          id: usuarioId,
          fecha: fechaIdentificador, // identificador único del comentario
          nuevoTexto: nuevoTexto,
          nuevaCalificacion: nuevoValor,
        }),
      })
      const respuestaText = await respuesta.text()
      const parser = new DOMParser()
      const xmlResponse = parser.parseFromString(respuestaText, "application/xml")
      const estadoElem = xmlResponse.getElementsByTagName("estado")[0]
      if (estadoElem && estadoElem.textContent === "exito") {
        cargarComentarios()
      } else {
        alert("Error al editar comentario.")
      }
    } catch (error) {
      console.error("Error al editar comentario:", error)
    }
  })
}

// Función para eliminar un comentario
async function eliminarComentario(fechaIdentificador) {
  if (!confirm("¿Estás seguro de eliminar el comentario?")) return

  const producto = document.querySelector(".tituloProducto").textContent
  const productoLimpiado = limpiarNombreProducto(producto)
  const usuarioElem = document.getElementById("nombreUsuario")
  const usuario = usuarioElem.textContent.trim()
  const usuarioId = usuarioElem.dataset.id

  try {
    const respuesta = await fetch("comentarios.php?producto=" + encodeURIComponent(productoLimpiado), {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({
        accion: "eliminar",
        producto: productoLimpiado,
        usuario: usuario,
        id: usuarioId,
        fecha: fechaIdentificador,
      }),
    })
    const respuestaText = await respuesta.text()
    const parser = new DOMParser()
    const xmlResponse = parser.parseFromString(respuestaText, "application/xml")
    const estadoElem = xmlResponse.getElementsByTagName("estado")[0]
    if (estadoElem && estadoElem.textContent === "exito") {
      cargarComentarios()
    } else {
      alert("Error al eliminar comentario.")
    }
  } catch (error) {
    console.error("Error al eliminar comentario:", error)
  }
}






        
        