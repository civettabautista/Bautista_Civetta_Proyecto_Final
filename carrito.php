<?php
/*
 * carrito.php
 * Muestra el carrito y procesa sus acciones (siempre por POST con token CSRF):
 *   - agregar:   suma un producto (desde el catálogo o la página del producto)
 *   - actualizar: cambia las cantidades
 *   - quitar:    saca un producto
 *   - finalizar: guarda el pedido en la base de datos (requiere sesión)
 *
 * El carrito vive en la sesión: $_SESSION['carrito'] = [ 'numero de set' => cantidad ]
 * Así no hace falta estar registrado para ir armándolo.
 */
require_once 'includes/funciones.php';

const MAX_POR_PRODUCTO = 10;

if (!isset($_SESSION['carrito'])) {
    $_SESSION['carrito'] = [];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verificarCsrf('carrito.php');

    $accion = $_POST['accion'] ?? '';
    $id     = is_string($_POST['id'] ?? null) ? $_POST['id'] : '';

    // ---------- Agregar ----------
    if ($accion === 'agregar') {
        if (!isset($productos[$id])) {
            setFlash('error', 'Ese producto no existe.');
            redirigir('index.php#catalogo');
        }
        $cantidad = filter_var($_POST['cantidad'] ?? 1, FILTER_VALIDATE_INT,
                               ['options' => ['min_range' => 1, 'max_range' => MAX_POR_PRODUCTO]]);
        if ($cantidad === false) {
            setFlash('error', 'La cantidad tiene que ser un número entre 1 y ' . MAX_POR_PRODUCTO . '.');
            redirigir('producto.php?id=' . urlencode($id));
        }
        // Se suma a lo que ya había, sin pasar del máximo
        $actual = $_SESSION['carrito'][$id] ?? 0;
        $_SESSION['carrito'][$id] = min($actual + $cantidad, MAX_POR_PRODUCTO);

        setFlash('exito', 'Agregaste ' . $productos[$id]['nombre'] . ' al carrito.');
        redirigir('carrito.php');
    }

    // ---------- Actualizar cantidades ----------
    if ($accion === 'actualizar') {
        $cantidades = is_array($_POST['cantidades'] ?? null) ? $_POST['cantidades'] : [];
        foreach ($cantidades as $idProducto => $valor) {
            $idProducto = (string) $idProducto;
            if (!isset($_SESSION['carrito'][$idProducto])) {
                continue; // ignoramos productos que no estaban en el carrito
            }
            $n = filter_var($valor, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => MAX_POR_PRODUCTO]]);
            if ($n === false) {
                setFlash('error', 'Las cantidades tienen que ser números entre 0 y ' . MAX_POR_PRODUCTO . '.');
                redirigir('carrito.php');
            }
            if ($n === 0) {
                unset($_SESSION['carrito'][$idProducto]); // 0 = quitar
            } else {
                $_SESSION['carrito'][$idProducto] = $n;
            }
        }
        setFlash('exito', 'Carrito actualizado.');
        redirigir('carrito.php');
    }

    // ---------- Quitar ----------
    if ($accion === 'quitar') {
        unset($_SESSION['carrito'][$id]);
        setFlash('exito', 'Quitaste el producto del carrito.');
        redirigir('carrito.php');
    }

    // ---------- Finalizar compra ----------
    if ($accion === 'finalizar') {
        // Para comprar hay que tener cuenta. El carrito queda guardado en la sesión.
        if (!estaLogueado()) {
            setFlash('error', 'Iniciá sesión para finalizar la compra. Tu carrito queda guardado.');
            redirigir('login.php');
        }
        requerirLogin(); // además confirma que la cuenta siga activa

        if (!$_SESSION['carrito']) {
            setFlash('error', 'Tu carrito está vacío.');
            redirigir('carrito.php');
        }

        // Los precios se toman del catálogo del servidor, nunca de lo que mande el navegador
        $total = 0;
        foreach ($_SESSION['carrito'] as $idProducto => $cantidad) {
            $total += $productos[$idProducto]['precio'] * $cantidad;
        }

        // Transacción: o se guardan el pedido y todos sus productos, o no se guarda nada
        try {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare('INSERT INTO pedidos (usuario_id, total) VALUES (?, ?)');
            $stmt->execute([$_SESSION['usuario_id'], $total]);
            $pedidoId = (int) $pdo->lastInsertId();

            $stmt = $pdo->prepare(
                'INSERT INTO pedido_items (pedido_id, set_numero, nombre, precio, cantidad) VALUES (?, ?, ?, ?, ?)'
            );
            foreach ($_SESSION['carrito'] as $idProducto => $cantidad) {
                $p = $productos[$idProducto];
                $stmt->execute([$pedidoId, $idProducto, $p['nombre'], $p['precio'], $cantidad]);
            }

            $pdo->commit();
        } catch (PDOException $e) {
            $pdo->rollBack();
            error_log($e->getMessage());
            setFlash('error', 'No se pudo registrar la compra. Intentá de nuevo.');
            redirigir('carrito.php');
        }

        $_SESSION['carrito'] = []; // se vacía el carrito
        setFlash('exito', '¡Compra finalizada! Tu número de pedido es ' . $pedidoId . '.');
        redirigir('panel.php');
    }

    setFlash('error', 'Acción no válida.');
    redirigir('carrito.php');
}

// ---------- Mostrar el carrito ----------
// Se arma la lista con los datos del catálogo. Si algún id ya no existe, se descarta.
$items = [];
$total = 0;
foreach ($_SESSION['carrito'] as $idProducto => $cantidad) {
    if (!isset($productos[$idProducto])) {
        unset($_SESSION['carrito'][$idProducto]);
        continue;
    }
    $p = $productos[$idProducto];
    $subtotal = $p['precio'] * $cantidad;
    $total += $subtotal;
    $items[] = ['id' => $idProducto, 'p' => $p, 'cantidad' => $cantidad, 'subtotal' => $subtotal];
}

encabezado('Carrito');
?>

<h1>Tu carrito</h1>

<?php if (!$items): ?>
    <div class="alerta alerta-info">Tu carrito está vacío.</div>
    <a href="index.php#catalogo" class="btn btn-amarillo">Ver el catálogo</a>
<?php else: ?>

    <!-- Formulario para cambiar cantidades -->
    <form method="post" action="carrito.php" id="form-cantidades">
        <?= campoCsrf() ?>
        <input type="hidden" name="accion" value="actualizar">
    </form>

    <div class="tabla-scroll">
        <table class="tabla tabla-carrito">
            <thead>
                <tr><th>Producto</th><th>Precio</th><th>Cantidad</th><th>Subtotal</th><th></th></tr>
            </thead>
            <tbody>
            <?php foreach ($items as $item): ?>
                <tr>
                    <td>
                        <a href="producto.php?id=<?= e($item['id']) ?>" class="carrito-producto">
                            <img src="img/<?= e($item['p']['foto']) ?>" alt="">
                            <span><?= e($item['p']['nombre']) ?><br><small>Set <?= e($item['id']) ?></small></span>
                        </a>
                    </td>
                    <td><?= precio($item['p']['precio']) ?></td>
                    <td>
                        <!-- form="..." conecta este campo con el formulario de cantidades -->
                        <input type="number" name="cantidades[<?= e($item['id']) ?>]" form="form-cantidades"
                               value="<?= (int) $item['cantidad'] ?>" min="0" max="<?= MAX_POR_PRODUCTO ?>" class="input-cantidad">
                    </td>
                    <td><?= precio($item['subtotal']) ?></td>
                    <td>
                        <form method="post" action="carrito.php">
                            <?= campoCsrf() ?>
                            <input type="hidden" name="accion" value="quitar">
                            <input type="hidden" name="id" value="<?= e($item['id']) ?>">
                            <button type="submit" class="btn btn-gris btn-chico">Quitar</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="carrito-pie">
        <button type="submit" form="form-cantidades" class="btn btn-gris">Actualizar cantidades</button>

        <div class="carrito-total">
            <p>Total: <strong><?= precio($total) ?></strong></p>
            <form method="post" action="carrito.php">
                <?= campoCsrf() ?>
                <input type="hidden" name="accion" value="finalizar">
                <button type="submit" class="btn btn-amarillo btn-grande">Finalizar compra</button>
            </form>
            <?php if (!estaLogueado()): ?>
                <p class="info">Para finalizar la compra vas a tener que iniciar sesión.</p>
            <?php endif; ?>
        </div>
    </div>

<?php endif; ?>

<?php pie(); ?>
