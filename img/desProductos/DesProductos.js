// DesProductos.js

// Funciones y variables del carrito (sin cambios)
let carrito = [];
let stockDisponible = 15;

const botonCarrito = document.getElementById('botonCarrito');
const asideCarrito = document.getElementById('asideCarrito');
const contenedorProducto = document.querySelector('.contenedorProducto');
const contadorCarrito = document.getElementById('contadorCarrito');
const itemsCarrito = document.getElementById('itemsCarrito');
const carritoVacio = document.getElementById('carritoVacio');
const agregarAlCarritoBtn = document.getElementById('agregarAlCarrito');
const inputCantidad = document.getElementById('cantidad');

document.addEventListener('DOMContentLoaded', () => {
  actualizarCarrito();
  actualizarStockDisplay();
  validarStock();
  cargarComentarios(); // Cargamos los comentarios al iniciar la página
});

botonCarrito.addEventListener('click', toggleCarrito);
agregarAlCarritoBtn.addEventListener('click', agregarAlCarrito);

function toggleCarrito() {
  asideCarrito.classList.toggle('activo');
  contenedorProducto.classList.toggle('activo');
}

function agregarAlCarrito() {
  const cantidad = parseInt(inputCantidad.value);
  const producto = {
    nombre: document.querySelector('.tituloProducto').textContent,
    precio: parseFloat(document.querySelector('.precioProducto').textContent.replace('$', '')),
    cantidad: cantidad
  };
  const productoExistente = carrito.find(item => item.nombre === producto.nombre);
  if (productoExistente) {
    productoExistente.cantidad += cantidad;
  } else {
    carrito.push(producto);
  }
  stockDisponible -= cantidad;
  actualizarStockDisplay();
  validarStock();
  actualizarCarrito();
}

function eliminarDelCarrito(index) {
  const productoEliminado = carrito[index];
  if (productoEliminado) {
    stockDisponible += productoEliminado.cantidad;
    carrito.splice(index, 1);
    actualizarStockDisplay();
    validarStock();
    actualizarCarrito();
  }
}

function actualizarCarrito() {
  itemsCarrito.innerHTML = '';
  carritoVacio.style.display = carrito.length === 0 ? 'block' : 'none';
  carrito.forEach((item, index) => {
    const div = document.createElement('div');
    div.className = 'item-carrito';
    div.innerHTML = `
      <div class="item-superior">
        <h3>${item.nombre}</h3>
        <button onclick="eliminarDelCarrito(${index})">×</button>
      </div>
      <div class="item-inferior">
        <p>${item.cantidad} x $${item.precio.toFixed(2)}</p>
        <p>Total: $${(item.cantidad * item.precio).toFixed(2)}</p>
      </div>
    `;
    itemsCarrito.appendChild(div);
  });
  actualizarTotal();
  actualizarContador();
}

function actualizarTotal() {
  const total = carrito.reduce((acc, item) => acc + (item.precio * item.cantidad), 0);
  document.getElementById('totalCarrito').textContent = `$${total.toFixed(2)}`;
}

function actualizarContador() {
  const totalItems = carrito.reduce((acc, item) => acc + item.cantidad, 0);
  contadorCarrito.textContent = totalItems;
  contadorCarrito.style.display = totalItems > 0 ? 'block' : 'none';
}

function actualizarStockDisplay() {
  const stockDisplay = document.getElementById('stockDisplay');
  stockDisplay.textContent = stockDisponible > 0 
      ? `Disponibles: ${stockDisponible} unidades` 
      : 'AGOTADO';
  stockDisplay.style.color = stockDisponible > 0 ? '#f39c12' : '#e74c3c';
}

function validarStock() {
  let cantidad = parseInt(inputCantidad.value);
  if (cantidad > stockDisponible) {
    inputCantidad.value = stockDisponible;
    mostrarAdvertencia(`¡Solo quedan ${stockDisponible} unidades!`);
  } else if (cantidad < 1) {
    inputCantidad.value = 1;
  } else {
    limpiarAdvertencia();
  }
  agregarAlCarritoBtn.disabled = stockDisponible === 0;
}

function mostrarAdvertencia(mensaje) {
  const advertenciaStock = document.getElementById('advertenciaStock');
  advertenciaStock.textContent = mensaje;
}

function limpiarAdvertencia() {
  const advertenciaStock = document.getElementById('advertenciaStock');
  advertenciaStock.textContent = '';
}

function ajustarCantidad(cambio) {
  let nuevaCantidad = parseInt(inputCantidad.value) + cambio;
  inputCantidad.value = Math.max(1, Math.min(nuevaCantidad, stockDisponible));
  validarStock();
}

function cambiarImagen(miniatura) {
  document.getElementById('imagenPrincipal').src = miniatura.src;
}

document.querySelector('.boton-comprar').addEventListener('click', () => {
  if (carrito.length === 0) {
    alert('¡Tu carrito está vacío!');
    return;
  }
  if (confirm('¿Deseas finalizar tu compra?')) {
    alert('¡Compra realizada con éxito! Gracias por tu pedido.');
    carrito = [];
    actualizarCarrito();
    toggleCarrito();
  }
});

/* =========================
   Funcionalidad de Comentarios
========================= */
const btnMostrarFormulario = document.getElementById('mostrarFormularioComentario');
const formularioComentario = document.getElementById('formularioComentario');
const btnEnviarComentario = document.getElementById('enviarComentario');
const textoComentario = document.getElementById('textoComentario');
const estrellasContenedor = document.getElementById('estrellasCalificacion');
const inputValorCalificacion = document.getElementById('valorCalificacion');
const listaComentarios = document.getElementById('listaComentarios');

// Mostrar u ocultar el formulario de comentario
btnMostrarFormulario.addEventListener('click', () => {
  formularioComentario.style.display = (formularioComentario.style.display === 'none' || formularioComentario.style.display === '') 
      ? 'block' 
      : 'none';
});

// Selección de estrellas para la calificación
estrellasContenedor.querySelectorAll('.estrella').forEach(estrella => {
  estrella.addEventListener('click', function() {
    const valor = parseInt(this.getAttribute('data-valor'));
    inputValorCalificacion.value = valor;
    // Actualiza la apariencia de las estrellas
    estrellasContenedor.querySelectorAll('.estrella').forEach(el => {
      el.classList.remove('seleccionada');
      if (parseInt(el.getAttribute('data-valor')) <= valor) {
        el.classList.add('seleccionada');
      }
    });
  });
});

// Enviar comentario
btnEnviarComentario.addEventListener('click', async () => {
    const producto = document.querySelector('.tituloProducto').textContent;
    const usuario = document.getElementById('nombreUsuario').value.trim();
    const texto = textoComentario.value.trim();
    const calificacion = parseInt(inputValorCalificacion.value);
    
    if (!usuario || !texto || calificacion === 0) {
      alert('Por favor, completa todos los campos');
      return;
    }
    
    try {
      const respuesta = await fetch('comentarios.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({
          accion: 'agregar',
          producto: producto,
          usuario: usuario,
          texto: texto,
          calificacion: calificacion
        })
      });
      
      // Obtenemos la respuesta en XML y la parseamos
      const respuestaText = await respuesta.text();
      const parser = new DOMParser();
      const xmlResponse = parser.parseFromString(respuestaText, "application/xml");
      const estadoElem = xmlResponse.getElementsByTagName('estado')[0];
      if (estadoElem && estadoElem.textContent === 'exito') {
        cargarComentarios();
        document.getElementById('nombreUsuario').value = '';
        textoComentario.value = '';
        inputValorCalificacion.value = 0;
        estrellasContenedor.querySelectorAll('.estrella').forEach(el => el.classList.remove('seleccionada'));
        formularioComentario.style.display = 'none';
      } else {
        alert('Error al enviar comentario');
      }
    } catch (error) {
      console.error('Error al enviar comentario:', error);
    }
  });
  
  // Función para cargar comentarios usando XML
  async function cargarComentarios() {
    const producto = document.querySelector('.tituloProducto').textContent.trim();
    
    try {
      const respuesta = await fetch('comentarios.php?producto=' + encodeURIComponent(producto));
      if (!respuesta.ok) {
        console.error('No se pudo obtener los comentarios. Código:', respuesta.status);
        return;
      }
      
      const xmlText = await respuesta.text();
      console.log('XML recibido:', xmlText);
      
      const parser = new DOMParser();
      const xmlDoc = parser.parseFromString(xmlText, "application/xml");
      
      const comentarios = xmlDoc.getElementsByTagName('comentario');
      listaComentarios.innerHTML = '';
      
      if (comentarios.length === 0) {
        console.log('No hay comentarios');
        return;
      }
      
      // Recorremos cada elemento <comentario>
      for (let i = 0; i < comentarios.length; i++) {
        const comentarioElem = comentarios[i];
        const usuario = comentarioElem.getElementsByTagName('usuario')[0]?.textContent || 'Anónimo';
        const texto = comentarioElem.getElementsByTagName('texto')[0]?.textContent || '';
        const fecha = comentarioElem.getElementsByTagName('fecha')[0]?.textContent || '';
        const calificacion = parseInt(comentarioElem.getElementsByTagName('calificacion')[0]?.textContent || '0', 10);
        
        const fechaFormateada = fecha ? new Date(fecha).toLocaleDateString('es-ES', {
          day: 'numeric',
          month: 'long',
          year: 'numeric'
        }) : '(Fecha desconocida)';
        
        const divComentario = document.createElement('div');
        divComentario.classList.add('comentario');
        divComentario.innerHTML = `
          <div class="infoAutor">
            <div>
              <div class="autorComentario">
                ${usuario}
                <span class="fechaComentario">- ${fechaFormateada}</span>
              </div>
              <div class="calificacion">
                ${'★'.repeat(calificacion)}${'☆'.repeat(5 - calificacion)}
              </div>
            </div>
          </div>
          <p class="textoComentario">${texto}</p>
        `;
        listaComentarios.appendChild(divComentario);
      }
    } catch (error) {
      console.error('Error al cargar comentarios:', error);
    }
  }

        
        