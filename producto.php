<?php
/*
 * producto.php
 * Página de un producto: producto.php?id=75192
 * Muestra la foto grande, los datos y la descripción, y permite
 * agregarlo al carrito eligiendo la cantidad.
 */
require_once 'includes/funciones.php';

// El id llega por la URL. Solo se acepta si existe en el catálogo.
$id = is_string($_GET['id'] ?? null) ? $_GET['id'] : '';

if (!isset($productos[$id])) {
    setFlash('error', 'El producto que buscás no existe.');
    redirigir('index.php#catalogo');
}

$p = $productos[$id];

encabezado($p['nombre']);
?>

<p class="miga"><a href="index.php#catalogo">Catálogo</a> / <?= e($p['nombre']) ?></p>

<section class="detalle">
    <img src="img/<?= e($p['foto']) ?>" alt="Set <?= e($p['nombre']) ?>" class="detalle-foto">

    <div>
        <p class="producto-linea"><?= e($p['linea']) ?></p>
        <h1><?= e($p['nombre']) ?></h1>
        <p class="detalle-precio"><?= precio($p['precio']) ?></p>

        <p><?= e($p['descripcion']) ?></p>

        <dl class="ficha ficha-chica">
            <dt>Número de set</dt> <dd><?= e($id) ?></dd>
            <dt>Piezas</dt>        <dd><?= number_format($p['piezas'], 0, ',', '.') ?></dd>
            <dt>Edad</dt>          <dd><?= e($p['edad']) ?></dd>
        </dl>

        <form method="post" action="carrito.php" class="form-comprar">
            <?= campoCsrf() ?>
            <input type="hidden" name="accion" value="agregar">
            <input type="hidden" name="id" value="<?= e($id) ?>">
            <label>Cantidad
                <input type="number" name="cantidad" value="1" min="1" max="10" required>
            </label>
            <button type="submit" class="btn btn-amarillo btn-grande">Agregar al carrito</button>
        </form>
    </div>
</section>

<?php pie(); ?>
