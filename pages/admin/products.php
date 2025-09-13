<?php
include 'includes/header.php';
include 'includes/sidebar.php';
require_once '../../config/auth.php';
require_once '../../config/db.php';

if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    try {
        $stmt = $conn->prepare("SELECT image FROM products WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $product = $result->fetch_assoc();
        
        $stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        
        if ($product && $product['image'] && file_exists('../../uploads/' . $product['image'])) {
            unlink('../../uploads/' . $product['image']);
        }
        
        $success_msg = "Product deleted successfully!";
    } catch (Exception $e) {
        $error_msg = "Error deleting product: " . $e->getMessage();
    }
}

try {
    $stmt = $conn->prepare("
        SELECT id, name, description, price, stock, category, image, created_at
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


<main class="admin-content">
    <div class="content-header">
        <h1><i class="fas fa-box me-2"></i>Products</h1>
        <p>Manage Products Inventory</p>
        <a href="product-create.php" class="btn btn-primary">
            <i class="fas fa-plus me-1"></i>New Product
        </a>
    </div>

    <div class="content-body">
        <?php if (isset($success_msg)): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i><?php echo $success_msg; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <?php if (isset($error_msg)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i><?php echo $error_msg; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <div class="row stats-cards">
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-number"><?php echo count($products); ?></div>
                    <div class="stat-label">Total Products</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-number">
                        <?php 
                        $lowStock = array_filter($products, function($p) { return $p['stock'] < 10; });
                        echo count($lowStock);
                        ?>
                    </div>
                    <div class="stat-label">Low Stock</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-number">
                        <?php 
                        $categories = array_unique(array_column($products, 'category'));
                        echo count(array_filter($categories));
                        ?>
                    </div>
                    <div class="stat-label">Categories</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-number">
                        $<?php 
                        $totalValue = array_sum(array_map(function($p) { 
                            return $p['price'] * $p['stock']; 
                        }, $products));
                        echo number_format($totalValue, 0);
                        ?>
                    </div>
                    <div class="stat-label">Inventory Value</div>
                </div>
            </div>
        </div>

        <div class="search-filter-bar">
            <div class="row align-items-center">
                <div class="col-md-4">
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-search"></i></span>
                        <input type="text" class="form-control" id="searchInput" 
                               placeholder="Search products...">
                    </div>
                </div>
                <div class="col-md-3">
                    <select class="form-select" id="categoryFilter">
                        <option value="">All Categories</option>
                        <?php 
                        $categories = array_unique(array_filter(array_column($products, 'category')));
                        foreach ($categories as $category): 
                        ?>
                        <option value="<?php echo htmlspecialchars($category); ?>">
                            <?php echo htmlspecialchars($category); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <select class="form-select" id="stockFilter">
                        <option value="">All Stock Levels</option>
                        <option value="low">Low Stock (< 10)</option>
                        <option value="medium">Medium Stock (10-50)</option>
                        <option value="high">High Stock (> 50)</option>
                    </select>
                </div>
                <div class="col-md-2 text-end">
                    <button type="button" class="btn btn-outline-secondary" onclick="clearFilters()">
                        <i class="fas fa-times me-1"></i>Clear
                    </button>
                </div>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Image</th>
                        <th>Product</th>
                        <th>Category</th>
                        <th>Price</th>
                        <th>Stock</th>
                        <th>Description</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="productsTableBody">
                    <?php if (empty($products)): ?>
                    <tr>
                        <td colspan="8" class="text-center py-4">
                            <div class="text-muted">
                                <i class="fas fa-box-open fa-3x mb-3"></i>
                                <h5>No Products Found</h5>
                                <p>Start by adding your first product.</p>
                                <a href="product-create.php" class="btn btn-primary">
                                    <i class="fas fa-plus me-1"></i>Add Product
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($products as $product): ?>
                    <tr data-product-name="<?php echo strtolower(htmlspecialchars($product['name'])); ?>" 
                        data-category="<?php echo strtolower(htmlspecialchars($product['category'])); ?>"
                        data-stock="<?php echo $product['stock']; ?>">
                        <td>
                            <?php if ($product['image'] && file_exists('../../uploads/' . $product['image'])): ?>
                                <img src="../../uploads/<?php echo htmlspecialchars($product['image']); ?>" 
                                     alt="Product Image" class="product-image">
                            <?php else: ?>
                                <div class="no-image">
                                    <i class="fas fa-image"></i>
                                </div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="fw-bold"><?php echo htmlspecialchars($product['name']); ?></div>
                            <small class="text-muted">ID: #<?php echo $product['id']; ?></small>
                        </td>
                        <td>
                            <?php if ($product['category']): ?>
                                <span class="badge bg-light text-dark">
                                    <?php echo htmlspecialchars($product['category']); ?>
                                </span>
                            <?php else: ?>
                                <span class="text-muted">-</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="price-text">$<?php echo number_format($product['price'], 2); ?></span>
                        </td>
                        <td>
                            <?php
                            $stockClass = 'stock-high';
                            if ($product['stock'] < 10) $stockClass = 'stock-low';
                            elseif ($product['stock'] <= 50) $stockClass = 'stock-medium';
                            ?>
                            <span class="badge stock-badge <?php echo $stockClass; ?>">
                                <?php echo $product['stock']; ?> units
                            </span>
                        </td>
                        <td>
                            <?php if ($product['description']): ?>
                                <div class="description-text" title="<?php echo htmlspecialchars($product['description']); ?>">
                                    <?php echo htmlspecialchars($product['description']); ?>
                                </div>
                            <?php else: ?>
                                <span class="text-muted">-</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <small class="text-muted">
                                <?php echo date('M j, Y', strtotime($product['created_at'])); ?>
                            </small>
                        </td>
                        <td>
                            <div class="action-buttons">
                                <a href="product-view.php?id=<?php echo $product['id']; ?>" 
                                   class="btn btn-outline-info" title="View">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="product-edit.php?id=<?php echo $product['id']; ?>" 
                                   class="btn btn-outline-primary" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <button type="button" class="btn btn-outline-danger" 
                                        onclick="confirmDelete(<?php echo $product['id']; ?>, '<?php echo addslashes($product['name']); ?>')" 
                                        title="Delete">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>

<div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteModalLabel">
                    <i class="fas fa-exclamation-triangle text-warning me-2"></i>Confirm Delete
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete the product "<strong id="productName"></strong>"?</p>
                <p class="text-muted">This action cannot be undone.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <a href="#" id="confirmDeleteBtn" class="btn btn-danger">
                    <i class="fas fa-trash me-1"></i>Delete Product
                </a>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('searchInput').addEventListener('input', filterProducts);
document.getElementById('categoryFilter').addEventListener('change', filterProducts);
document.getElementById('stockFilter').addEventListener('change', filterProducts);

function filterProducts() {
    const searchTerm = document.getElementById('searchInput').value.toLowerCase();
    const categoryFilter = document.getElementById('categoryFilter').value.toLowerCase();
    const stockFilter = document.getElementById('stockFilter').value;
    const rows = document.querySelectorAll('#productsTableBody tr[data-product-name]');
    
    rows.forEach(row => {
        const productName = row.getAttribute('data-product-name');
        const category = row.getAttribute('data-category');
        const stock = parseInt(row.getAttribute('data-stock'));
        
        let showRow = true;
        
        if (searchTerm && !productName.includes(searchTerm)) {
            showRow = false;
        }
        
        if (categoryFilter && category !== categoryFilter) {
            showRow = false;
        }
        
        if (stockFilter) {
            if (stockFilter === 'low' && stock >= 10) showRow = false;
            else if (stockFilter === 'medium' && (stock < 10 || stock > 50)) showRow = false;
            else if (stockFilter === 'high' && stock <= 50) showRow = false;
        }
        
        row.style.display = showRow ? '' : 'none';
    });
}

function clearFilters() {
    document.getElementById('searchInput').value = '';
    document.getElementById('categoryFilter').value = '';
    document.getElementById('stockFilter').value = '';
    filterProducts();
}

function confirmDelete(id, name) {
    document.getElementById('productName').textContent = name;
    document.getElementById('confirmDeleteBtn').href = `?action=delete&id=${id}`;
    new bootstrap.Modal(document.getElementById('deleteModal')).show();
}

setTimeout(() => {
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        const bsAlert = new bootstrap.Alert(alert);
        bsAlert.close();
    });
}, 5000);
</script>

</div>
<?php include 'includes/footer.php'; ?>