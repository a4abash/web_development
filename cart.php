<?php
// cart.php - Main cart handling file
require 'config/auth.php';
require_once 'config/db.php';

// Enhanced Cart Error Handler
class CartErrorHandler {
    private $errors = [];
    private $success_messages = [];
    
    public function addError($message, $code = null) {
        $this->errors[] = [
            'message' => $message,
            'code' => $code,
            'timestamp' => date('Y-m-d H:i:s')
        ];
        error_log("Cart Error: {$message}" . ($code ? " (Code: {$code})" : ""));
    }
    
    public function addSuccess($message) {
        $this->success_messages[] = $message;
    }
    
    public function hasErrors() {
        return !empty($this->errors);
    }
    
    public function getErrors() {
        return array_map(function($error) {
            return $error['message'];
        }, $this->errors);
    }
    
    public function getSuccessMessages() {
        return $this->success_messages;
    }
    
    public function clear() {
        $this->errors = [];
        $this->success_messages = [];
    }
}

// Cart Management Class
class ShoppingCart {
    private $conn;
    private $user_id;
    private $session_id;
    
    public function __construct($database_connection, $user_id = null) {
        $this->conn = $database_connection;
        $this->user_id = $user_id;
        $this->session_id = session_id();
        
        // Create cart table if it doesn't exist
        $this->createCartTable();
    }
    
    private function createCartTable() {
        $sql = "CREATE TABLE IF NOT EXISTS cart_items (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NULL,
            session_id VARCHAR(128) NULL,
            product_id INT NOT NULL,
            quantity INT NOT NULL DEFAULT 1,
            added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_user_id (user_id),
            INDEX idx_session_id (session_id),
            INDEX idx_product_id (product_id),
            FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
        ) ENGINE=InnoDB";
        
        try {
            $this->conn->query($sql);
        } catch (Exception $e) {
            error_log("Failed to create cart table: " . $e->getMessage());
        }
    }
    
    public function addToCart($product_id, $quantity = 1) {
        $errorHandler = new CartErrorHandler();
        
        try {
            // Validate inputs
            $product_id = (int)$product_id;
            $quantity = (int)$quantity;
            
            if ($product_id <= 0) {
                $errorHandler->addError('Invalid product ID.', 'INVALID_PRODUCT_ID');
                return $errorHandler;
            }
            
            if ($quantity <= 0) {
                $errorHandler->addError('Quantity must be greater than 0.', 'INVALID_QUANTITY');
                return $errorHandler;
            }
            
            if ($quantity > 99) {
                $errorHandler->addError('Maximum quantity per item is 99.', 'QUANTITY_LIMIT');
                return $errorHandler;
            }
            
            // Check if product exists and get stock
            $stmt = $this->conn->prepare("SELECT id, name, stock, price FROM products WHERE id = ?");
            if (!$stmt) {
                $errorHandler->addError('Database error occurred.', 'DB_PREPARE_FAILED');
                return $errorHandler;
            }
            
            $stmt->bind_param("i", $product_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $product = $result->fetch_assoc();
            $stmt->close();
            
            if (!$product) {
                $errorHandler->addError('Product not found.', 'PRODUCT_NOT_FOUND');
                return $errorHandler;
            }
            
            // Check current cart quantity for this product
            $current_cart_qty = $this->getCartQuantity($product_id);
            $total_requested = $current_cart_qty + $quantity;
            
            if ($total_requested > $product['stock']) {
                $available = $product['stock'] - $current_cart_qty;
                if ($available <= 0) {
                    $errorHandler->addError('This product is already at maximum available quantity in your cart.', 'STOCK_EXCEEDED');
                } else {
                    $errorHandler->addError("Only {$available} units available to add (you already have {$current_cart_qty} in cart).", 'INSUFFICIENT_STOCK');
                }
                return $errorHandler;
            }
            
            // Begin transaction
            $this->conn->begin_transaction();
            
            // Check if item already exists in cart
            $check_sql = "SELECT id, quantity FROM cart_items WHERE product_id = ? AND " . 
                        ($this->user_id ? "user_id = ?" : "session_id = ?");
            $check_stmt = $this->conn->prepare($check_sql);
            
            if ($this->user_id) {
                $check_stmt->bind_param("ii", $product_id, $this->user_id);
            } else {
                $check_stmt->bind_param("is", $product_id, $this->session_id);
            }
            
            $check_stmt->execute();
            $existing_item = $check_stmt->get_result()->fetch_assoc();
            $check_stmt->close();
            
            if ($existing_item) {
                // Update existing item
                $new_quantity = $existing_item['quantity'] + $quantity;
                $update_sql = "UPDATE cart_items SET quantity = ?, updated_at = NOW() WHERE id = ?";
                $update_stmt = $this->conn->prepare($update_sql);
                $update_stmt->bind_param("ii", $new_quantity, $existing_item['id']);
                
                if (!$update_stmt->execute()) {
                    throw new Exception("Failed to update cart item: " . $update_stmt->error);
                }
                $update_stmt->close();
                
                $errorHandler->addSuccess("Updated {$product['name']} quantity to {$new_quantity} in cart.");
            } else {
                // Insert new item
                $insert_sql = "INSERT INTO cart_items (user_id, session_id, product_id, quantity) VALUES (?, ?, ?, ?)";
                $insert_stmt = $this->conn->prepare($insert_sql);
                $insert_stmt->bind_param("isii", $this->user_id, $this->session_id, $product_id, $quantity);
                
                if (!$insert_stmt->execute()) {
                    throw new Exception("Failed to add item to cart: " . $insert_stmt->error);
                }
                $insert_stmt->close();
                
                $errorHandler->addSuccess("Added {$product['name']} to cart.");
            }
            
            // Commit transaction
            $this->conn->commit();
            
        } catch (Exception $e) {
            // Rollback transaction
            $this->conn->rollback();
            $errorHandler->addError('Failed to add item to cart: ' . $e->getMessage(), 'DB_ERROR');
        }
        
        return $errorHandler;
    }
    
    private function getCartQuantity($product_id) {
        $sql = "SELECT SUM(quantity) as total FROM cart_items WHERE product_id = ? AND " . 
               ($this->user_id ? "user_id = ?" : "session_id = ?");
        $stmt = $this->conn->prepare($sql);
        
        if ($this->user_id) {
            $stmt->bind_param("ii", $product_id, $this->user_id);
        } else {
            $stmt->bind_param("is", $product_id, $this->session_id);
        }
        
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        return (int)($result['total'] ?? 0);
    }
    
    public function getCartItems() {
        $sql = "SELECT c.id as cart_id, c.quantity, c.added_at, c.updated_at,
                       p.id, p.name, p.description, p.price, p.image, p.stock
                FROM cart_items c
                JOIN products p ON c.product_id = p.id
                WHERE " . ($this->user_id ? "c.user_id = ?" : "c.session_id = ?") . "
                ORDER BY c.updated_at DESC";
        
        $stmt = $this->conn->prepare($sql);
        
        if ($this->user_id) {
            $stmt->bind_param("i", $this->user_id);
        } else {
            $stmt->bind_param("s", $this->session_id);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        $items = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        
        return $items;
    }
    
    public function updateQuantity($cart_id, $quantity) {
        $errorHandler = new CartErrorHandler();
        
        try {
            $cart_id = (int)$cart_id;
            $quantity = (int)$quantity;
            
            if ($quantity <= 0) {
                return $this->removeItem($cart_id);
            }
            
            if ($quantity > 99) {
                $errorHandler->addError('Maximum quantity per item is 99.', 'QUANTITY_LIMIT');
                return $errorHandler;
            }
            
            // Get cart item and product info
            $sql = "SELECT c.*, p.name, p.stock 
                    FROM cart_items c 
                    JOIN products p ON c.product_id = p.id 
                    WHERE c.id = ? AND " . ($this->user_id ? "c.user_id = ?" : "c.session_id = ?");
            
            $stmt = $this->conn->prepare($sql);
            
            if ($this->user_id) {
                $stmt->bind_param("ii", $cart_id, $this->user_id);
            } else {
                $stmt->bind_param("is", $cart_id, $this->session_id);
            }
            
            $stmt->execute();
            $cart_item = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            
            if (!$cart_item) {
                $errorHandler->addError('Cart item not found.', 'CART_ITEM_NOT_FOUND');
                return $errorHandler;
            }
            
            if ($quantity > $cart_item['stock']) {
                $errorHandler->addError("Only {$cart_item['stock']} units available.", 'INSUFFICIENT_STOCK');
                return $errorHandler;
            }
            
            // Update quantity
            $update_sql = "UPDATE cart_items SET quantity = ?, updated_at = NOW() WHERE id = ?";
            $update_stmt = $this->conn->prepare($update_sql);
            $update_stmt->bind_param("ii", $quantity, $cart_id);
            
            if (!$update_stmt->execute()) {
                throw new Exception("Failed to update quantity: " . $update_stmt->error);
            }
            $update_stmt->close();
            
            $errorHandler->addSuccess("Updated {$cart_item['name']} quantity to {$quantity}.");
            
        } catch (Exception $e) {
            $errorHandler->addError('Failed to update cart: ' . $e->getMessage(), 'DB_ERROR');
        }
        
        return $errorHandler;
    }
    
    public function removeItem($cart_id) {
        $errorHandler = new CartErrorHandler();
        
        try {
            $cart_id = (int)$cart_id;
            
            // Get item name before deletion
            $sql = "SELECT p.name FROM cart_items c 
                    JOIN products p ON c.product_id = p.id 
                    WHERE c.id = ? AND " . ($this->user_id ? "c.user_id = ?" : "c.session_id = ?");
            
            $stmt = $this->conn->prepare($sql);
            
            if ($this->user_id) {
                $stmt->bind_param("ii", $cart_id, $this->user_id);
            } else {
                $stmt->bind_param("is", $cart_id, $this->session_id);
            }
            
            $stmt->execute();
            $result = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            
            if (!$result) {
                $errorHandler->addError('Cart item not found.', 'CART_ITEM_NOT_FOUND');
                return $errorHandler;
            }
            
            // Delete the item
            $delete_sql = "DELETE FROM cart_items WHERE id = ? AND " . 
                         ($this->user_id ? "user_id = ?" : "session_id = ?");
            $delete_stmt = $this->conn->prepare($delete_sql);
            
            if ($this->user_id) {
                $delete_stmt->bind_param("ii", $cart_id, $this->user_id);
            } else {
                $delete_stmt->bind_param("is", $cart_id, $this->session_id);
            }
            
            if (!$delete_stmt->execute()) {
                throw new Exception("Failed to remove item: " . $delete_stmt->error);
            }
            $delete_stmt->close();
            
            $errorHandler->addSuccess("Removed {$result['name']} from cart.");
            
        } catch (Exception $e) {
            $errorHandler->addError('Failed to remove item from cart: ' . $e->getMessage(), 'DB_ERROR');
        }
        
        return $errorHandler;
    }
    
    public function getCartCount() {
        $sql = "SELECT SUM(quantity) as total FROM cart_items WHERE " . 
               ($this->user_id ? "user_id = ?" : "session_id = ?");
        $stmt = $this->conn->prepare($sql);
        
        if ($this->user_id) {
            $stmt->bind_param("i", $this->user_id);
        } else {
            $stmt->bind_param("s", $this->session_id);
        }
        
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        return (int)($result['total'] ?? 0);
    }
    
    public function getCartTotal() {
        $sql = "SELECT SUM(c.quantity * p.price) as total 
                FROM cart_items c 
                JOIN products p ON c.product_id = p.id 
                WHERE " . ($this->user_id ? "c.user_id = ?" : "c.session_id = ?");
        
        $stmt = $this->conn->prepare($sql);
        
        if ($this->user_id) {
            $stmt->bind_param("i", $this->user_id);
        } else {
            $stmt->bind_param("s", $this->session_id);
        }
        
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        return (float)($result['total'] ?? 0);
    }
    
    public function clearCart() {
        $errorHandler = new CartErrorHandler();
        
        try {
            $sql = "DELETE FROM cart_items WHERE " . 
                   ($this->user_id ? "user_id = ?" : "session_id = ?");
            $stmt = $this->conn->prepare($sql);
            
            if ($this->user_id) {
                $stmt->bind_param("i", $this->user_id);
            } else {
                $stmt->bind_param("s", $this->session_id);
            }
            
            if (!$stmt->execute()) {
                throw new Exception("Failed to clear cart: " . $stmt->error);
            }
            $stmt->close();
            
            $errorHandler->addSuccess("Cart cleared successfully.");
            
        } catch (Exception $e) {
            $errorHandler->addError('Failed to clear cart: ' . $e->getMessage(), 'DB_ERROR');
        }
        
        return $errorHandler;
    }
}

// Initialize cart system
$user_id = $_SESSION['user_id'] ?? null;
$cart = new ShoppingCart($conn, $user_id);
$errorHandler = new CartErrorHandler();

// Handle different cart actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'add';
    
    switch ($action) {
        case 'add':
            $product_id = $_POST['product_id'] ?? 0;
            $quantity = $_POST['quantity'] ?? 1;
            $result = $cart->addToCart($product_id, $quantity);
            
            if ($result->hasErrors()) {
                foreach ($result->getErrors() as $error) {
                    $errorHandler->addError($error);
                }
            } else {
                foreach ($result->getSuccessMessages() as $success) {
                    $errorHandler->addSuccess($success);
                }
            }
            break;
            
        case 'update':
            $cart_id = $_POST['cart_id'] ?? 0;
            $quantity = $_POST['quantity'] ?? 1;
            $result = $cart->updateQuantity($cart_id, $quantity);
            
            if ($result->hasErrors()) {
                foreach ($result->getErrors() as $error) {
                    $errorHandler->addError($error);
                }
            } else {
                foreach ($result->getSuccessMessages() as $success) {
                    $errorHandler->addSuccess($success);
                }
            }
            break;
            
        case 'remove':
            $cart_id = $_POST['cart_id'] ?? 0;
            $result = $cart->removeItem($cart_id);
            
            if ($result->hasErrors()) {
                foreach ($result->getErrors() as $error) {
                    $errorHandler->addError($error);
                }
            } else {
                foreach ($result->getSuccessMessages() as $success) {
                    $errorHandler->addSuccess($success);
                }
            }
            break;
            
        case 'clear':
            $result = $cart->clearCart();
            
            if ($result->hasErrors()) {
                foreach ($result->getErrors() as $error) {
                    $errorHandler->addError($error);
                }
            } else {
                foreach ($result->getSuccessMessages() as $success) {
                    $errorHandler->addSuccess($success);
                }
            }
            break;
    }
    
    // Redirect to prevent form resubmission
    header("Location: " . $_SERVER['PHP_SELF'] . "?updated=1");
    exit();
}

// Get cart items for display
$cart_items = $cart->getCartItems();
$cart_count = $cart->getCartCount();
$cart_total = $cart->getCartTotal();

include 'includes/header.php';
?>

<main class="container mt-4">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1><i class="fas fa-shopping-cart me-2"></i>Shopping Cart</h1>
                <div class="cart-summary">
                    <span class="badge bg-primary fs-6"><?php echo $cart_count; ?> items</span>
                    <span class="text-muted ms-2">Total: $<?php echo number_format($cart_total, 2); ?></span>
                </div>
            </div>
            
            <!-- Success/Error Messages -->
            <?php if (isset($_GET['updated']) && !empty($errorHandler->getSuccessMessages())): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle me-2"></i>
                    <?php foreach ($errorHandler->getSuccessMessages() as $message): ?>
                        <div><?php echo htmlspecialchars($message); ?></div>
                    <?php endforeach; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_GET['updated']) && $errorHandler->hasErrors()): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    <?php foreach ($errorHandler->getErrors() as $error): ?>
                        <div><?php echo htmlspecialchars($error); ?></div>
                    <?php endforeach; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <?php if (empty($cart_items)): ?>
                <!-- Empty Cart -->
                <div class="card">
                    <div class="card-body text-center py-5">
                        <i class="fas fa-shopping-cart fa-4x text-muted mb-3"></i>
                        <h3 class="text-muted">Your cart is empty</h3>
                        <p class="text-muted mb-4">Add some products to get started!</p>
                        <a href="products.php" class="btn btn-primary">
                            <i class="fas fa-arrow-left me-2"></i>Continue Shopping
                        </a>
                    </div>
                </div>
            <?php else: ?>
                <!-- Cart Items -->
                <div class="card">
                    <div class="card-header">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">Cart Items</h5>
                            <form method="post" style="display: inline;">
                                <input type="hidden" name="action" value="clear">
                                <button type="submit" class="btn btn-outline-danger btn-sm" 
                                        onclick="return confirm('Are you sure you want to clear your entire cart?')">
                                    <i class="fas fa-trash me-1"></i>Clear Cart
                                </button>
                            </form>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <?php foreach ($cart_items as $item): ?>
                            <div class="cart-item border-bottom p-3">
                                <div class="row align-items-center">
                                    <div class="col-md-2">
                                        <div class="product-image-wrapper">
                                            <?php if (!empty($item['image'])): ?>
                                                <img src="uploads/<?php echo htmlspecialchars($item['image']); ?>" 
                                                     alt="<?php echo htmlspecialchars($item['name']); ?>"
                                                     class="cart-product-image">
                                            <?php else: ?>
                                                <div class="cart-product-placeholder">
                                                    <i class="fas fa-image text-muted"></i>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <h6 class="mb-1"><?php echo htmlspecialchars($item['name']); ?></h6>
                                        <p class="text-muted mb-1 small">
                                            <?php echo htmlspecialchars(substr($item['description'], 0, 100)); ?>
                                            <?php if (strlen($item['description']) > 100): ?>...<?php endif; ?>
                                        </p>
                                        <small class="text-muted">
                                            Stock: <?php echo $item['stock']; ?> available
                                        </small>
                                    </div>
                                    <div class="col-md-2 text-center">
                                        <strong>$<?php echo number_format($item['price'], 2); ?></strong>
                                    </div>
                                    <div class="col-md-2">
                                        <form method="post" class="quantity-form">
                                            <input type="hidden" name="action" value="update">
                                            <input type="hidden" name="cart_id" value="<?php echo $item['cart_id']; ?>">
                                            <div class="input-group input-group-sm">
                                                <button type="button" class="btn btn-outline-secondary qty-btn" data-action="decrease">-</button>
                                                <input type="number" name="quantity" class="form-control text-center quantity-input" 
                                                       value="<?php echo $item['quantity']; ?>" min="0" max="<?php echo $item['stock']; ?>">
                                                <button type="button" class="btn btn-outline-secondary qty-btn" data-action="increase">+</button>
                                            </div>
                                        </form>
                                    </div>
                                    <div class="col-md-2 text-center">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <strong>$<?php echo number_format($item['price'] * $item['quantity'], 2); ?></strong>
                                            <form method="post" style="display: inline;">
                                                <input type="hidden" name="action" value="remove">
                                                <input type="hidden" name="cart_id" value="<?php echo $item['cart_id']; ?>">
                                                <button type="submit" class="btn btn-outline-danger btn-sm" 
                                                        onclick="return confirm('Remove this item from cart?')"
                                                        title="Remove item">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <!-- Cart Summary -->
                <div class="row mt-4">
                    <div class="col-md-8">
                        <a href="products.php" class="btn btn-outline-primary">
                            <i class="fas fa-arrow-left me-2"></i>Continue Shopping
                        </a>
                    </div>
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">Order Summary</h5>
                            </div>
                            <div class="card-body">
                                <div class="d-flex justify-content-between mb-2">
                                    <span>Subtotal (<?php echo $cart_count; ?> items):</span>
                                    <strong>$<?php echo number_format($cart_total, 2); ?></strong>
                                </div>
                                <div class="d-flex justify-content-between mb-2">
                                    <span>Shipping:</span>
                                    <span class="text-success">Free</span>
                                </div>
                                <hr>
                                <div class="d-flex justify-content-between mb-3">
                                    <strong>Total:</strong>
                                    <strong class="text-primary">$<?php echo number_format($cart_total, 2); ?></strong>
                                </div>
                                <button type="button" class="btn btn-success w-100" onclick="proceedToCheckout()">
                                    <i class="fas fa-credit-card me-2"></i>Proceed to Checkout
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</main>

<script>
// Quantity button handlers
document.addEventListener('DOMContentLoaded', function() {
    const qtyBtns = document.querySelectorAll('.qty-btn');
    
    qtyBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const form = this.closest('.quantity-form');
            const input = form.querySelector('.quantity-input');
            const action = this.dataset.action;
            const max = parseInt(input.max);
            let currentValue = parseInt(input.value);
            
            if (action === 'increase' && currentValue < max) {
                input.value = currentValue + 1;
                form.submit();
            } else if (action === 'decrease' && currentValue > 0) {
                input.value = currentValue - 1;
                form.submit();
            }
        });
    });
    
    // Auto-submit quantity changes after delay
    const quantityInputs = document.querySelectorAll('.quantity-input');
    quantityInputs.forEach(input => {
        let timeout;
        input.addEventListener('input', function() {
            clearTimeout(timeout);
            timeout = setTimeout(() => {
                if (this.value !== this.defaultValue) {
                    this.closest('form').submit();
                }
            }, 1000);
        });
    });
});

// Auto-dismiss alerts
setTimeout(() => {
    const alerts = document.querySelectorAll('.alert-dismissible');
    alerts.forEach(alert => {
        const bsAlert = new bootstrap.Alert(alert);
        setTimeout(() => {
            bsAlert.close();
        }, 5000);
    });
}, 100);

function proceedToCheckout() {
    // Implement checkout functionality
    alert('Checkout functionality would be implemented here!');
    // window.location.href = 'checkout.php';
}
</script>

<?php include 'includes/footer.php'; ?>

<!-- Additional JavaScript for enhanced cart functionality -->
<script>
// Add to cart with AJAX (for better UX)
function addToCartAjax(productId, quantity = 1) {
    const formData = new FormData();
    formData.append('action', 'add');
    formData.append('product_id', productId);
    formData.append('quantity', quantity);
    
    fetch('cart.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.text())
    .then(data => {
        // Show success notification
        showNotification('Product added to cart!', 'success');
        updateCartBadge();
    })
    .catch(error => {
        showNotification('Error adding product to cart', 'error');
        console.error('Error:', error);
    });
}

// Update cart badge count
function updateCartBadge() {
    fetch('get_cart_count.php')
    .then(response => response.json())
    .then(data => {
        const badge = document.querySelector('.cart-badge');
        if (badge) {
            badge.textContent = data.count;
            badge.style.display = data.count > 0 ? 'inline' : 'none';
        }
    });
}

// Show notification
function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `alert alert-${type === 'error' ? 'danger' : 'success'} alert-dismissible fade show position-fixed`;
    notification.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
    notification.innerHTML = `
        <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'} me-2"></i>
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        if (notification.parentNode) {
            notification.remove();
        }
    }, 5000);
}

// Initialize cart badge on page load
document.addEventListener('DOMContentLoaded', function() {
    updateCartBadge();
});
</script>