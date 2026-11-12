# BrickStore · Tienda de LEGO con sistema de usuarios

Sistema web de registro, inicio de sesión y administración de usuarios para una tienda de sets de LEGO,
con catálogo, página de cada producto, carrito y compra.
Hecho con **PHP + PDO + MySQL**, HTML y CSS puro, para ejecutarse en **XAMPP** o **Laragon**.

## Requisitos

- XAMPP (Apache + MySQL) **o** Laragon
- PHP 7.4 o superior (XAMPP y Laragon actuales traen PHP 8)
- Un navegador web
- Visual Studio Code para abrir y editar el proyecto

## Instalación en XAMPP

1. Abrí el **XAMPP Control Panel** y presioná **Start** en **Apache** y en **MySQL**.
2. Copiá la carpeta `tienda_legos` dentro de `C:\xampp\htdocs\`
   (debe quedar `C:\xampp\htdocs\tienda_legos\index.php`).
3. Entrá a **http://localhost/phpmyadmin**.
4. Hacé clic en la pestaña **Importar** → **Seleccionar archivo** → elegí `tienda_legos.sql` → **Importar**.
   Se crea la base `tienda_legos` con las tablas `usuarios`, `pedidos` y `pedido_items`, y el administrador de prueba.
5. Abrí **http://localhost/tienda_legos/**

## Instalación en Laragon

1. Abrí Laragon y presioná **Start All**.
2. Copiá la carpeta `tienda_legos` dentro de `C:\laragon\www\`.
3. Hacé clic en **Database** (abre HeidiSQL) o entrá a **http://localhost/phpmyadmin** si lo tenés instalado,
   e importá `tienda_legos.sql`.
   - En HeidiSQL: **Archivo → Cargar archivo SQL** → elegí `tienda_legos.sql` → ejecutar (F9).
4. Abrí **http://localhost/tienda_legos/** (o **http://tienda_legos.test/** si Laragon creó el host virtual).

## Usuario administrador de prueba

| Email                  | Contraseña  |
|------------------------|-------------|
| admin@brickstore.com   | Admin1234   |

Los clientes se crean desde **Crear cuenta** (registro público) o desde el panel del administrador.

## Configuración de la conexión

Los datos están en `conexion.php`. Por defecto usa el usuario `root` **sin contraseña**, que es la configuración
estándar de XAMPP y Laragon. Si tu MySQL tiene contraseña, cambiala en la variable `$clave`.

## Estructura

```
tienda_legos/
├── index.php                 Página de inicio con el catálogo
├── producto.php              Página de cada producto (producto.php?id=75192)
├── carrito.php               Carrito y finalizar compra
├── conexion.php              Conexión PDO a MySQL
├── registro.php              Registro público de clientes
├── login.php                 Inicio de sesión
├── panel.php                 Panel personal con Mis pedidos (protegido)
├── admin_usuarios.php        Alta, listado y buscador (solo admin)
├── editar_usuario.php        Edición de usuarios (solo admin)
├── archivar_usuario.php      Archiva un usuario (solo admin, POST + CSRF)
├── usuarios_archivados.php   Listado de archivados (solo admin)
├── reactivar_usuario.php     Reactiva un usuario (solo admin, POST + CSRF)
├── cerrar_sesion.php         Cierre de sesión
├── includes/
│   ├── funciones.php         Sesión, seguridad (CSRF, escape HTML), validaciones y catálogo
│   └── plantilla.php         Encabezado, menú y pie de todas las páginas
├── css/styles.css            Estilos de la interfaz
├── img/                      Foto de portada y fotos de los sets
├── tienda_legos.sql          Script de la base de datos
├── README.md                 Este archivo
└── DOCUMENTACION.md          Explicación, seguridad, pruebas y guía para la defensa
```

## Catálogo y fotos

Los sets del catálogo se venden en la tienda oficial de LEGO de Estados Unidos (lego.com): de ahí se tomaron el
nombre, el número de set, el precio en dólares, la cantidad de piezas y la edad. Las descripciones están escritas
para este proyecto. Para cambiar o agregar productos, se edita el array `$productos` de `includes/funciones.php`.

Las fotos son de Wikimedia Commons y Unsplash, con licencias libres que piden nombrar al autor:

| Foto | Autor | Licencia |
|---|---|---|
| Portada (`ladrillos.jpg`) | Xavi Cabrera, Unsplash | Licencia de Unsplash |
| Halcón Milenario | Cappo80 | Dominio público |
| Máquina del tiempo | BullDawg2021 | CC BY 4.0 |
| Saturn V | Legoktm | CC BY-SA 4.0 |
| Titanic | Norsyazwan | CC BY 4.0 |
| Ladrillos clásicos | Priwo | Dominio público |
| DUPLO | Christian Ursilva | CC BY-SA 4.0 |

LEGO es una marca de The LEGO Group; este es un proyecto escolar sin relación con la empresa.

## Problemas frecuentes

- **"No se pudo conectar con la base de datos"**: MySQL está apagado o no importaste `tienda_legos.sql`.
- **MySQL no arranca en XAMPP**: el puerto 3306 está ocupado (por ejemplo por otro MySQL). Cerrá el otro servicio
  o cambiá el puerto en XAMPP.
- **"No se pudo registrar la compra"**: faltan las tablas `pedidos` y `pedido_items`. Volvé a importar
  `tienda_legos.sql` (si la base ya existía, las tablas se crean sin tocar los usuarios).
- **Página en blanco o error 404**: revisá que la carpeta esté dentro de `htdocs` (XAMPP) o `www` (Laragon)
  y que la URL sea `/tienda_legos/`.
