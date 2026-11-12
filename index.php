<?php
/*
 * index.php
 * Página de inicio pública de BrickStore: presentación y catálogo de sets.
 * El catálogo está en includes/funciones.php y se recorre con foreach.
 */
require_once 'includes/funciones.php';

encabezado('Inicio');
?>

<section class="hero">
    <div>
        <h1>Sets de LEGO para armar en casa</h1>
        <p>Desde ladrillos DUPLO para los más chicos hasta el Titanic y el Halcón Milenario.
           Elegí tus sets, sumalos al carrito y finalizá la compra con tu cuenta.</p>

        <div class="acciones">
            <a href="#catalogo" class="btn btn-amarillo btn-grande">Ver el catálogo</a>
            <?php if (!estaLogueado()): ?>
                <a href="registro.php" class="btn btn-gris btn-grande">Crear cuenta</a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Foto de portada (se carga desde el CSS) -->
    <div class="hero-foto" role="img" aria-label="Montón de ladrillos LEGO de colores"></div>
</section>

<section id="catalogo" class="catalogo">
    <h2>Catálogo</h2>

    <div class="productos">
        <?php foreach ($productos as $id => $p): ?>
            <article class="producto">
                <!-- La foto y el nombre llevan a la página del producto -->
                <a href="producto.php?id=<?= e($id) ?>" class="producto-enlace">
                    <img src="img/<?= e($p['foto']) ?>" alt="Set <?= e($p['nombre']) ?>" loading="lazy">
                    <p class="producto-linea"><?= e($p['linea']) ?>, set <?= e($id) ?></p>
                    <h3><?= e($p['nombre']) ?></h3>
                </a>
                <p class="producto-precio"><?= precio($p['precio']) ?></p>

                <!-- Agregar al carrito: formulario POST con token CSRF -->
                <form method="post" action="carrito.php">
                    <?= campoCsrf() ?>
                    <input type="hidden" name="accion" value="agregar">
                    <input type="hidden" name="id" value="<?= e($id) ?>">
                    <button type="submit" class="btn btn-amarillo btn-bloque">Agregar al carrito</button>
                </form>
            </article>
        <?php endforeach; ?>
    </div>
    <p class="info nota-precios">Precios en dólares de la tienda oficial de LEGO de Estados Unidos.</p>
</section>


<?php pie(); ?>
