# Events Staff Manager

Plugin de WordPress para gestionar las funcionalidades de los ejecutivos de ventas, permitiendo filtrar leads por ciudad y categoría.

## Características

- **Gestión de Usuarios**: Administra todos los usuarios con rol "ejecutivo_de_ventas"
- **Filtros por Ciudad**: Permite asignar ciudades específicas que cada ejecutivo puede ver
- **Filtros por Categoría**: Permite asignar categorías específicas que cada ejecutivo puede ver
- **Interfaz de Admin**: Panel de administración intuitivo para configurar restricciones
- **Filtrado Automático**: Los filtros se aplican automáticamente cuando los ejecutivos acceden a /leads/

## Instalación

1. Sube la carpeta del plugin a `/wp-content/plugins/`
2. Activa el plugin desde el panel de administración de WordPress
3. Ve a "Events Staff Manager" en el menú de administración

## Uso

### Panel de Administración

1. Ve a **Events Staff Manager** en el menú de WordPress admin
2. Verás una tabla con todos los usuarios que tienen el rol "ejecutivo_de_ventas"
3. Para cada usuario puedes seleccionar:
   - **Ciudades permitidas**: Selecciona una o más ciudades (o deja vacío para todas)
   - **Categorías permitidas**: Selecciona una o más categorías (o deja vacío para todas)
4. Haz clic en **Guardar** para aplicar las restricciones

### Funcionamiento

- Cuando un ejecutivo de ventas accede a `/leads/`, solo verá los leads que coincidan con sus restricciones
- Si no se asignan restricciones, el ejecutivo verá todos los leads
- Los filtros se aplican mediante hooks de WordPress sin modificar el plugin de Leads Management

## Requisitos

- WordPress 5.0+
- Plugin "Leads Management" instalado y activo
- Rol de usuario "ejecutivo_de_ventas" creado (mediante Capabilities plugin)

## Estructura del Plugin

```
events-staff-manager/
├── events-staff-manager.php    # Archivo principal del plugin
├── includes/
│   └── admin-page.php          # Página de administración
├── assets/
│   ├── admin.css              # Estilos del admin
│   ├── admin.js               # JavaScript del admin
│   └── frontend.js            # JavaScript del frontend
└── README.md                  # Este archivo
```

## Hooks y Filtros

El plugin utiliza los siguientes hooks de WordPress:

- `posts_where`: Para filtrar las consultas de leads
- `admin_menu`: Para agregar el menú de administración
- `wp_ajax_esm_save_user_restrictions`: Para guardar restricciones vía AJAX

## Desarrollo

### Base de Datos

El plugin utiliza las siguientes tablas del plugin Leads Management:

- `wp_jet_cct_leads`: Tabla principal de leads
- `wp_jet_cct_eventos`: Tabla de eventos (contiene ubicacion_evento y categoria_listing_post)

### Meta Fields

Las restricciones se guardan en user meta:

- `esm_allowed_cities`: Array de ciudades permitidas
- `esm_allowed_categories`: Array de categorías permitidas

## Compatibilidad

Este plugin está diseñado para trabajar junto con:

- Leads Management Plugin
- WordPress Capabilities Plugin (para el rol ejecutivo_de_ventas)

No modifica ningún archivo del plugin original de Leads Management.