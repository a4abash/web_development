<?php
require 'config/auth.php';
include 'includes/header.php';

require_once 'config/db.php';

try {
    $stmt = $conn->prepare("
        SELECT id, name, description, price, stock, image
        FROM products
        ORDER BY id DESC
    ");
    $stmt->execute();
    $products = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
} catch (Exception $e) {
    error_log("Error fetching products: " . $e->getMessage());
    $products = [];
}

?>
<section class="services-section">
    <h1>Our Products</h1>
    <p>List of Products:</p><br>
    <div class="service-grid">
        <?php if (empty($products)): ?>
            <tr>
                <td colspan="7" class="text-center py-4">
                    <div class="text-muted">
                        <i class="fas fa-cart fa-3x mb-3"></i>
                        <h5>No Product Found</h5>
                    </div>
                </td>
            </tr>
        <?php else: ?>
            <div class="product-grid">
                <?php foreach ($products as $product): ?>
                    <div class="product-card" tabindex="0" role="region" aria-label="<?= htmlspecialchars($product['name']) ?> Product">
                        <div class="product-image-wrapper">
                            <img src="uploads/<?= htmlspecialchars($product['image']) ?>"
                                alt="<?= htmlspecialchars($product['name']) ?>"
                                class="product-image">
                        </div>
                        <div class="product-details">
                            <h3 class="product-title"><?= htmlspecialchars($product['name']) ?></h3>
                            <p class="product-description"><?= htmlspecialchars($product['description']) ?></p>
                            <div class="product-meta">
                                <span class="product-price">$<?= htmlspecialchars($product['price']) ?></span>
                                <span class="product-stock">
                                    <?= htmlspecialchars($product['stock']) ?> in stock
                                </span>
                            </div>
                            <form method="post" action="cart.php" class="cart-form">
                                <input type="hidden" name="product_id" value="<?= htmlspecialchars($product['id']) ?>">
                                <button type="submit" class="btn-cart">🛒 Add to Cart</button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

    </div>
</section>
<?php include 'includes/footer.php'; ?>