-- =====================================================================
--  BrickStore - Tienda de Legos
--  Script de creación de la base de datos y la tabla de usuarios.
--  Importar desde phpMyAdmin (pestaña "Importar") o pegar en "SQL".
-- =====================================================================

-- 1) Crear la base de datos (si ya existe, no la vuelve a crear)
CREATE DATABASE IF NOT EXISTS tienda_legos
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

-- 2) Seleccionar la base de datos para trabajar sobre ella
USE tienda_legos;

-- 3) Crear la tabla de usuarios
CREATE TABLE IF NOT EXISTS usuarios (
    id              INT UNSIGNED NOT NULL AUTO_INCREMENT,           -- identificador único
    nombre          VARCHAR(50)  NOT NULL,                          -- nombre de la persona
    apellido        VARCHAR(50)  NOT NULL,                          -- apellido de la persona
    dni             VARCHAR(10)  NOT NULL,                          -- DNI (texto para no perder ceros)
    email           VARCHAR(100) NOT NULL,                          -- correo electrónico
    password        VARCHAR(255) NOT NULL,                          -- SOLO el hash de password_hash()
    rol             ENUM('cliente','admin') NOT NULL DEFAULT 'cliente', -- tipo de usuario
    fecha_creacion  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP, -- se completa sola al insertar
    fecha_archivado DATETIME     NULL     DEFAULT NULL,             -- NULL = activo / con fecha = archivado
    PRIMARY KEY (id),
    UNIQUE KEY uq_usuarios_dni   (dni),                             -- el DNI no se puede repetir
    UNIQUE KEY uq_usuarios_email (email)                            -- el email no se puede repetir
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4) Administrador de prueba
--    Email:      admin@brickstore.com
--    Contraseña: Admin1234
--    El valor guardado es el hash generado con password_hash('Admin1234', PASSWORD_DEFAULT).
--    INSERT IGNORE: si el admin ya existe (por ejemplo, al volver a importar), se saltea sin error.
INSERT IGNORE INTO usuarios (nombre, apellido, dni, email, password, rol)
VALUES ('Admin', 'BrickStore', '10000000', 'admin@brickstore.com',
        '$2y$10$Y3aWK6if3dpV9iT2OYIxcupOtM2bh/s272EPikyg5NRYFaMESjj4e', 'admin');

-- =====================================================================
--  Tienda: pedidos (se completan al tocar "Finalizar compra")
-- =====================================================================

-- 5) Un pedido por cada compra finalizada
CREATE TABLE IF NOT EXISTS pedidos (
    id         INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    usuario_id INT UNSIGNED  NOT NULL,                          -- quién compró
    total      DECIMAL(10,2) NOT NULL,                          -- total en dólares
    fecha      DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id)            -- debe ser un usuario existente
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6) Los productos de cada pedido. Se guardan nombre y precio del momento
--    de la compra, así el pedido no cambia si después cambia el catálogo.
CREATE TABLE IF NOT EXISTS pedido_items (
    id         INT UNSIGNED     NOT NULL AUTO_INCREMENT,
    pedido_id  INT UNSIGNED     NOT NULL,
    set_numero VARCHAR(10)      NOT NULL,                       -- número de set de LEGO
    nombre     VARCHAR(100)     NOT NULL,
    precio     DECIMAL(10,2)    NOT NULL,                       -- precio unitario
    cantidad   TINYINT UNSIGNED NOT NULL,
    PRIMARY KEY (id),
    FOREIGN KEY (pedido_id) REFERENCES pedidos(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
