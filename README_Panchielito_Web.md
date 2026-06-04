#  Panchielito — Sitio Web de Panadería

Aplicación web para una panadería artesanal que permite a los clientes explorar el catálogo de productos, gestionar su carrito de compras y realizar pedidos en línea.

##  Funcionalidades

- Catálogo de productos organizado por categorías (Bizcocho, Danés, Hojaldre)
- Sistema de autenticación de usuarios con sesiones PHP
- Carrito de compras con contador en tiempo real
- Historial de pedidos por usuario
- Panel de administración para gestión de productos
- Diseño responsive con parallax y carousel de productos más vendidos

##  Tecnologías

| Capa | Tecnología |
|------|-----------|
| Frontend | HTML5, CSS3, JavaScript (Vanilla) |
| Backend | PHP 8 |
| Estilos | CSS modular, Google Fonts, Font Awesome |
| Datos | JSON, MySQL |

##  Estructura del proyecto

```
Panchielito/
├── index.php               # Página principal
├── logout.php              # Cierre de sesión
├── menu.js                 # Navegación responsive
├── css/                    # Hojas de estilo modulares
├── Galeria/                # Vistas de productos y carrito
├── Admin/                  # Panel de administración
├── desProductos/           # Módulo de pedidos
├── controladoresPrincipales/  # Lógica JS del frontend
└── img/                    # Recursos gráficos
```

##  Instalación local

1. Clona el repositorio:
   ```bash
   git clone https://github.com/tu-usuario/Panchielito.git
   ```
2. Coloca la carpeta dentro de tu servidor local (XAMPP, WAMP, Laragon, etc.)
3. Importa la base de datos desde `/database/panchielito.sql`
4. Accede desde `http://localhost/Panchielito`

##  Capturas

> *Próximamente*

##  Autor

Joaquin Aburto Sanchez
Maximiliano Ruiz Romero

Desarrollado como proyecto académico en **CETI Tonalá**, 2025.
