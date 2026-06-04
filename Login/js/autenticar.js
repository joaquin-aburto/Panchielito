function submitForm(event, formType) {
    event.preventDefault(); // Prevenir el recargado de la página

    const respuesta = document.getElementById('respuesta');

    if (formType === 'login') {
        const email = document.getElementById('login-email').value;
        const password = document.getElementById('login-password').value;

        console.log('submitForm llamado con tipo:', formType);
        console.log('Campos:', email, password);

        if (!email || !password) {
            respuesta.innerHTML = '<div class="alert alert-danger" role="alert">Por favor completa todos los campos.</div>';
            return;
        }

        const datos = new FormData();
        datos.append('email', email);
        datos.append('password', password);

        fetch('http://127.0.0.1/Panchielito/Login/validarUsr.php', {
            method: 'POST',
            body: datos,
        })
        .then((res) => res.json())
        .then((data) => {
            console.log('Respuesta del servidor:', data);
            if (data === 'Admin') {
                respuesta.innerHTML = '<div class="alert alert-primary" role="alert">¡Usuario aceptado... Bienvenido!</div>';
                setTimeout(() => window.location.href = "http://127.0.0.1/Panchielito/dmin/admin.php", 4000);
            } else if (data === 'Correcto') {
                respuesta.innerHTML = '<div class="alert alert-primary" role="alert">¡Usuario aceptado... Bienvenido!</div>';
                setTimeout(() => window.location.href = "http://127.0.0.1/Panchielito/", 4000);
            }
            else {
                respuesta.innerHTML = '<div class="alert alert-danger" role="alert">¡Usuario no reconocido... favor de verificar los datos!</div>';
            }
        })
        .catch((error) => {
            console.error('Error al realizar la petición:', error);
            respuesta.innerHTML = '<div class="alert alert-danger" role="alert">Error al conectarse con el servidor. Intente nuevamente. Login</div>';
        });
    }

    if (formType === 'register') {
        const nombre = document.getElementById('nombre').value.trim();
        const apellido = document.getElementById('apellido').value.trim();
        const telefono = document.getElementById('telefono').value.trim();
        const email = document.getElementById('email').value.trim();
        const direccion = document.getElementById('direccion').value.trim();
        const password = document.getElementById('password').value.trim();
        const pan = document.getElementById('pan').value.trim();
        const acceptTerms = document.getElementById('accept-terms').checked;

        const regexLetras = /^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]+$/;
        const regexTelefono = /^\d{10}$/;
        const regexEmail = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        const regexPan = /^[a-zA-Z0-9]+$/;

        if (!nombre || !apellido || !telefono || !email || !direccion || !password || !pan) {
            respuesta.innerHTML = '<div class="alert alert-danger" role="alert">Por favor completa todos los campos.</div>';
            return;
        }

        // Verificar si se aceptaron los términos y condiciones
        if (!acceptTerms) {
            respuesta.innerHTML = '<div class="alert alert-danger" role="alert">Debes aceptar los términos y condiciones para registrarte.</div>';
            return;
        }

        if (!regexLetras.test(nombre) || !regexLetras.test(apellido)) {
            respuesta.innerHTML = '<div class="alert alert-danger" role="alert">Nombre y apellido solo deben contener letras y espacios.</div>';
            return;
        }

        if (!regexTelefono.test(telefono)) {
            respuesta.innerHTML = '<div class="alert alert-danger" role="alert">El teléfono debe contener exactamente 10 dígitos.</div>';
            return;
        }

        if (!regexEmail.test(email)) {
            respuesta.innerHTML = '<div class="alert alert-danger" role="alert">Por favor ingresa un correo válido.</div>';
            return;
        }

        if (direccion.length < 5) {
            respuesta.innerHTML = '<div class="alert alert-danger" role="alert">La dirección debe tener al menos 5 caracteres.</div>';
            return;
        }

        if (password.length < 8) {
            respuesta.innerHTML = '<div class="alert alert-danger" role="alert">La contraseña debe tener al menos 8 caracteres.</div>';
            return;
        }

        if (!regexPan.test(pan)) {
            respuesta.innerHTML = '<div class="alert alert-danger" role="alert">El PAN solo puede contener caracteres alfanuméricos.</div>';
            return;
        }

        const datos = new FormData();
        datos.append('nombre', nombre);
        datos.append('apellido', apellido);
        datos.append('telefono', telefono);
        datos.append('email', email);
        datos.append('direccion', direccion);
        datos.append('password', password);
        datos.append('pan', pan);
        datos.append('termsAccepted', acceptTerms ? '1' : '0');

        fetch('http://127.0.0.1/Panchielito//Login/register.php', {
            method: 'POST',
            body: datos,
        })
        .then((res) => res.json())
        .then((data) => {
            console.log('Respuesta del servidor:', data);
            if (data === 'Registrado') {
                respuesta.innerHTML = '<div class="alert alert-primary" role="alert">¡Registro exitoso! Bienvenido.</div>';
                setTimeout(() => window.location.href = "http://127.0.0.1/Panchielito/", 4000);
            } else if (data === 'Correo') {
                respuesta.innerHTML = '<div class="alert alert-warning" role="alert">El correo ya está registrado, Intente Iniicar sesion. Redirigiendo al inicio de sesión...</div>';
                setTimeout(() => {
                    document.getElementById('login-form').style.display = 'block'; // Mostrar el formulario de inicio de sesión
                    document.getElementById('register-form').style.display = 'none'; // Ocultar el formulario de registro
                    showForm('login');
                }, 3000);
            }
            else {
                respuesta.innerHTML = `<div class="alert alert-danger" role="alert">${data.message || data}</div>`;
            }
        })
        .catch((error) => {
            console.error('Error al realizar la petición:', error);
            respuesta.innerHTML = '<div class="alert alert-danger" role="alert">Error al conectarse con el servidor. Intente nuevamente.</div>';
        });
    }
}

// Función para cargar los términos y condiciones desde la base de datos
function loadTermsAndConditions() {
    fetch('http://127.0.0.1/Panchielito/Login/obtener_terminos.php')
        .then(response => response.json())
        .then(data => {
            termsData = data;
            document.getElementById('termsTitle').textContent = data.nombre;
            
            // Formatear el contenido de los términos y condiciones
            const formattedContent = formatTermsAndConditions(data.descripcion);
            document.getElementById('termsContent').innerHTML = formattedContent;
        })
        .catch(error => {
            console.error('Error al cargar los términos y condiciones:', error);
            document.getElementById('termsContent').textContent = 'Error al cargar los términos y condiciones. Por favor, inténtelo de nuevo más tarde.';
        });
}

