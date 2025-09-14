<?php
include 'includes/header.php';
include 'includes/sidebar.php';
require_once '../../config/auth.php';
require_once '../../config/db.php';

class ProductErrorHandler {
    private $errors = [];
    private $warnings = [];
    
    public function addError($field, $message, $code = null) {
        $this->errors[$field] = [
            'message' => $message,
            'code' => $code,
            'timestamp' => date('Y-m-d H:i:s')
        ];
        error_log("Product Edit Error [{$field}]: {$message}" . ($code ? " (Code: {$code})" : ""));
    }
    
    public function addWarning($field, $message) {
        $this->warnings[$field] = $message;
    }
    
    public function hasErrors() {
        return !empty($this->errors);
    }
    
    public function getError($field) {
        return $this->errors[$field]['message'] ?? null;
    }
    
    public function getAllErrors() {
        return array_map(function($error) {
            return $error['message'];
        }, $this->errors);
    }
    
    public function getWarnings() {
        return $this->warnings;
    }
    
    public function clear() {
        $this->errors = [];
        $this->warnings = [];
    }
}

$errorHandler = new ProductErrorHandler();
$success_msg = '';

$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($product_id <= 0) {
    $errorHandler->addError('general', 'Invalid product ID provided.', 'INVALID_ID');
    header("Location: products.php");
    exit();
}

$product = null;
try {
    $sql = "SELECT * FROM products WHERE id = ? LIMIT 1";
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        throw new Exception("Database prepare failed: " . $conn->error, 500);
    }
    
    if (!$stmt->bind_param("i", $product_id)) {
        throw new Exception("Parameter binding failed: " . $stmt->error, 500);
    }
    
    if (!$stmt->execute()) {
        throw new Exception("Query execution failed: " . $stmt->error, 500);
    }
    
    $result = $stmt->get_result();
    $product = $result->fetch_assoc();
    $stmt->close();
    
    if (!$product) {
        $errorHandler->addError('general', 'Product not found.', 'NOT_FOUND');
        header("Location: products.php?error=not_found");
        exit();
    }
    
} catch (Exception $e) {
    $errorHandler->addError('database', 'Failed to load product: ' . $e->getMessage(), $e->getCode());
    error_log("Product fetch error: " . $e->getMessage());
    header("Location: products.php?error=database");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update') {
    
    $name = trim($_POST['name'] ?? '');
    $price = $_POST['price'] ?? '';
    $stock = $_POST['stock'] ?? '';
    $description = trim($_POST['description'] ?? '');
    $category = trim($_POST['category'] ?? '');
    
    if (empty($name)) {
        $errorHandler->addError('name', 'Product name is required.');
    } elseif (strlen($name) < 2) {
        $errorHandler->addError('name', 'Product name must be at least 2 characters long.');
    } elseif (strlen($name) > 150) {
        $errorHandler->addError('name', 'Product name cannot exceed 150 characters.');
    } elseif (!preg_match('/^[a-zA-Z0-9\s\-\._&()]+$/', $name)) {
        $errorHandler->addError('name', 'Product name contains invalid characters.');
    }
    
    if (empty($price)) {
        $errorHandler->addError('price', 'Price is required.');
    } elseif (!is_numeric($price)) {
        $errorHandler->addError('price', 'Price must be a valid number.');
    } else {
        $price = (float)$price;
        if ($price <= 0) {
            $errorHandler->addError('price', 'Price must be greater than 0.');
        } elseif ($price > 99999999.99) {
            $errorHandler->addError('price', 'Price cannot exceed $99,999,999.99.');
        }
    }
    
    if (!is_numeric($stock)) {
        $errorHandler->addError('stock', 'Stock must be a valid number.');
    } else {
        $stock = (int)$stock;
        if ($stock < 0) {
            $errorHandler->addError('stock', 'Stock cannot be negative.');
        } elseif ($stock > 999999) {
            $errorHandler->addError('stock', 'Stock cannot exceed 999,999 units.');
        }
    }
    
    if (strlen($description) > 2000) {
        $errorHandler->addError('description', 'Description cannot exceed 2000 characters.');
    }
    
    $old_image = $product['image'];
    $image_filename = $old_image;
    $image_updated = false;
    
    if (isset($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
        try {
            $upload_dir = '../../uploads/';
            
            switch ($_FILES['image']['error']) {
                case UPLOAD_ERR_OK:
                    break;
                case UPLOAD_ERR_INI_SIZE:
                case UPLOAD_ERR_FORM_SIZE:
                    throw new Exception('Image file is too large (max 10MB allowed).');
                case UPLOAD_ERR_PARTIAL:
                    throw new Exception('Image upload was interrupted. Please try again.');
                case UPLOAD_ERR_NO_TMP_DIR:
                    throw new Exception('Server configuration error: no temporary directory.');
                case UPLOAD_ERR_CANT_WRITE:
                    throw new Exception('Server error: cannot write uploaded file.');
                case UPLOAD_ERR_EXTENSION:
                    throw new Exception('Upload blocked by server extension.');
                default:
                    throw new Exception('Unknown upload error occurred.');
            }
            
            $file_info = finfo_open(FILEINFO_MIME_TYPE);
            $detected_type = finfo_file($file_info, $_FILES['image']['tmp_name']);
            finfo_close($file_info);
            
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            $max_size = 10 * 1024 * 1024; 
            
            if (!in_array($detected_type, $allowed_types)) {
                throw new Exception('Invalid image type. Only JPEG, PNG, GIF, and WebP images are allowed.');
            }
            
            if ($_FILES['image']['size'] > $max_size) {
                throw new Exception('Image size must be less than 10MB.');
            }
            
            $image_info = getimagesize($_FILES['image']['tmp_name']);
            if ($image_info === false) {
                throw new Exception('Invalid or corrupted image file.');
            }
            
            if ($image_info[0] > 4000 || $image_info[1] > 4000) {
                $errorHandler->addWarning('image', 'Image dimensions are very large. Consider resizing for better performance.');
            }
            
            if (!is_dir($upload_dir)) {
                if (!mkdir($upload_dir, 0755, true)) {
                    throw new Exception('Failed to create upload directory.');
                }
            }
            
            $file_extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
            $image_filename = 'product_' . $product_id . '_' . time() . '_' . uniqid() . '.' . $file_extension;
            $upload_path = $upload_dir . $image_filename;
            
            if (!move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
                throw new Exception('Failed to save uploaded image.');
            }
            
            $image_updated = true;
            
        } catch (Exception $e) {
            $errorHandler->addError('image', $e->getMessage());
            $image_filename = $old_image; 
        }
    }
    
    if (!$errorHandler->hasErrors()) {
        try {
            $conn->begin_transaction();
            
            $sql = "UPDATE products SET name = ?, description = ?, price = ?, stock = ?, category = ?, image = ?, updated_at = NOW() WHERE id = ?";
            $stmt = $conn->prepare($sql);
            
            if (!$stmt) {
                throw new Exception("Database prepare failed: " . $conn->error);
            }
            
            $name = (string)$name;
            $description = (string)$description;
            $price = (float)$price;
            $stock = (int)$stock;
            $category = (string)$category;
            $image_filename = (string)$image_filename;
            $product_id_int = (int)$product_id;
            
            if (!$stmt->bind_param("ssdissi", $name, $description, $price, $stock, $category, $image_filename, $product_id_int)) {
                throw new Exception("Parameter binding failed: " . $stmt->error);
            }
            
            if (!$stmt->execute()) {
                throw new Exception("Update execution failed: " . $stmt->error);
            }
            
            if ($stmt->affected_rows === 0) {
                $errorHandler->addWarning('general', 'No changes were made to the product.');
            }
            
            $stmt->close();
            
            if ($image_updated && !empty($old_image) && $old_image !== $image_filename) {
                $old_image_path = $upload_dir . $old_image;
                if (file_exists($old_image_path)) {
                    if (!unlink($old_image_path)) {
                        error_log("Failed to delete old image: " . $old_image_path);
                    }
                }
            }
            
            $conn->commit();
            
            $product['name'] = $name;
            $product['description'] = $description;
            $product['price'] = $price;
            $product['stock'] = $stock;
            $product['category'] = $category;
            $product['image'] = $image_filename;
            
            $success_msg = "Product updated successfully!";
            
            unset($_POST);
            header("Location: products.php?success=updated");
            
        } catch (Exception $e) {
            $conn->rollback();
            
            $errorHandler->addError('database', 'Failed to update product: ' . $e->getMessage());
            error_log("Product update error: " . $e->getMessage());
            
            if ($image_updated && $image_filename !== $old_image) {
                $failed_upload_path = $upload_dir . $image_filename;
                if (file_exists($failed_upload_path)) {
                    unlink($failed_upload_path);
                }
                $image_filename = $old_image;
            }
        }
    }
}
?>

<main class="admin-content">
    <div class="content-header">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h1><i class="fas fa-edit me-2"></i>Edit Product</h1>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item">
                            <a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
                        </li>
                        <li class="breadcrumb-item">
                            <a href="products.php">Products</a>
                        </li>
                        <li class="breadcrumb-item active">Edit Product #<?php echo $product_id; ?></li>
                    </ol>
                </nav>
            </div>
            <div>
                <a href="products.php" class="btn btn-outline-secondary me-2">
                    <i class="fas fa-arrow-left me-1"></i>Back to Products
                </a>
                <a href="view_product.php?id=<?php echo $product_id; ?>" class="btn btn-outline-info">
                    <i class="fas fa-eye me-1"></i>View Product
                </a>
            </div>
        </div>
    </div>

    <div class="content-body">
        <?php if ($success_msg): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($success_msg); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if ($errorHandler->hasErrors()): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i>
                <strong>Please fix the following errors:</strong>
                <ul class="mb-0 mt-2">
                    <?php foreach ($errorHandler->getAllErrors() as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (!empty($errorHandler->getWarnings())): ?>
            <div class="alert alert-warning alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <strong>Warnings:</strong>
                <ul class="mb-0 mt-2">
                    <?php foreach ($errorHandler->getWarnings() as $warning): ?>
                        <li><?php echo htmlspecialchars($warning); ?></li>
                    <?php endforeach; ?>
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="row">
            <div class="col-lg-12 mx-auto">
                <div class="form-container">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div>
                            <h2 class="mb-0">Edit Product: <?php echo htmlspecialchars($product['name']); ?></h2>
                            <small class="text-muted">Product ID: #<?php echo $product_id; ?> | Last updated: <?php echo $product['updated_at'] ?? $product['created_at']; ?></small>
                        </div>
                        <div class="badge bg-<?php echo $product['stock'] > 0 ? 'success' : 'danger'; ?>">
                            <?php echo $product['stock'] > 0 ? 'In Stock' : 'Out of Stock'; ?>
                        </div>
                    </div>
                    <hr>
                    
                    <form id="productForm" method="POST" enctype="multipart/form-data" novalidate>
                        <input type="hidden" name="action" value="update">

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="name" class="form-label">
                                    Product Name <span class="required-field">*</span>
                                </label>
                                <input type="text" class="form-control <?php echo $errorHandler->getError('name') ? 'is-invalid' : ''; ?>" 
                                    id="name" name="name"
                                    value="<?php echo htmlspecialchars($_POST['name'] ?? $product['name']); ?>"
                                    placeholder="Enter product name" required maxlength="150">
                                <div class="form-text">Maximum 150 characters</div>
                                <?php if ($errorHandler->getError('name')): ?>
                                    <div class="invalid-feedback">
                                        <?php echo htmlspecialchars($errorHandler->getError('name')); ?>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="category" class="form-label">Category</label>
                                <select class="form-select" id="category" name="category">
                                    <option value="">Select Category</option>
                                    <?php 
                                    $categories = ['Electronics', 'Clothing', 'Books', 'Home & Garden', 'Sports', 'Toys', 'Beauty', 'Food', 'Automotive', 'Health', 'Other'];
                                    $selected_category = $_POST['category'] ?? $product['category'];
                                    foreach ($categories as $cat): 
                                    ?>
                                        <option value="<?php echo $cat; ?>" <?php echo ($selected_category === $cat) ? 'selected' : ''; ?>>
                                            <?php echo $cat; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="form-text">Optional - helps organize your products</div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="price" class="form-label">
                                    Price ($) <span class="required-field">*</span>
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input type="number" class="form-control <?php echo $errorHandler->getError('price') ? 'is-invalid' : ''; ?>" 
                                        id="price" name="price"
                                        value="<?php echo htmlspecialchars($_POST['price'] ?? $product['price']); ?>"
                                        placeholder="0.00" step="0.01" min="0.01" max="99999999.99" required>
                                </div>
                                <div class="form-text">Enter the selling price in USD</div>
                                <?php if ($errorHandler->getError('price')): ?>
                                    <div class="invalid-feedback">
                                        <?php echo htmlspecialchars($errorHandler->getError('price')); ?>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="stock" class="form-label">Stock Quantity</label>
                                <input type="number" class="form-control <?php echo $errorHandler->getError('stock') ? 'is-invalid' : ''; ?>" 
                                    id="stock" name="stock"
                                    value="<?php echo htmlspecialchars($_POST['stock'] ?? $product['stock']); ?>"
                                    placeholder="0" min="0" max="999999">
                                <div class="form-text">Number of units in inventory</div>
                                <?php if ($errorHandler->getError('stock')): ?>
                                    <div class="invalid-feedback">
                                        <?php echo htmlspecialchars($errorHandler->getError('stock')); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label for="description" class="form-label">Product Description</label>
                            <textarea class="form-control <?php echo $errorHandler->getError('description') ? 'is-invalid' : ''; ?>" 
                                id="description" name="description"
                                rows="4" maxlength="2000"
                                placeholder="Describe your product..."><?php echo htmlspecialchars($_POST['description'] ?? $product['description']); ?></textarea>
                            <div class="form-text">Optional - provide detailed information about the product (max 2000 characters)</div>
                            <?php if ($errorHandler->getError('description')): ?>
                                <div class="invalid-feedback">
                                    <?php echo htmlspecialchars($errorHandler->getError('description')); ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="mb-4">
                            <label class="form-label">Product Image</label>
                            
                            <?php if (!empty($product['image'])): ?>
                                <div class="current-image mb-3">
                                    <label class="form-label text-muted">Current Image:</label>
                                    <div class="position-relative d-inline-block">
                                        <img src="../../uploads/<?php echo htmlspecialchars($product['image']); ?>" 
                                             alt="Current product image" class="current-product-image">
                                    </div>
                                </div>
                            <?php endif; ?>
                            
                            <div class="file-input-wrapper <?php echo $errorHandler->getError('image') ? 'is-invalid' : ''; ?>">
                                <input type="file" id="image" name="image" accept="image/*">
                                <label for="image" class="file-input-label" id="imageLabel">
                                    <i class="fas fa-cloud-upload-alt fa-2x text-muted mb-2"></i>
                                    <div class="text-muted">
                                        <strong>Click to upload new image</strong> or drag and drop
                                    </div>
                                    <small class="text-muted">PNG, JPG, GIF, WebP up to 10MB</small>
                                </label>
                            </div>
                            <img id="imagePreview" class="image-preview" alt="Preview" style="display: none;">
                            <div class="form-text">Optional - upload a new image to replace the current one</div>
                            <?php if ($errorHandler->getError('image')): ?>
                                <div class="invalid-feedback">
                                    <?php echo htmlspecialchars($errorHandler->getError('image')); ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="d-flex justify-content-between align-items-center">
                            <a href="products.php" class="btn btn-secondary">
                                <i class="fas fa-times me-1"></i>Cancel
                            </a>
                            <div>
                                <button type="button" class="btn btn-outline-warning me-2" onclick="resetForm()">
                                    <i class="fas fa-undo me-1"></i>Reset Changes
                                </button>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-1"></i>Update Product
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</main>

<script>
document.getElementById('image').addEventListener('change', function(e) {
    const file = e.target.files[0];
    const preview = document.getElementById('imagePreview');
    const label = document.getElementById('imageLabel');

    if (file) {
        const maxSize = 10 * 1024 * 1024; // 10MB
        if (file.size > maxSize) {
            alert('File size must be less than 10MB');
            this.value = '';
            return;
        }

        const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
        if (!allowedTypes.includes(file.type)) {
            alert('Please select a valid image file (JPEG, PNG, GIF, or WebP)');
            this.value = '';
            return;
        }

        const reader = new FileReader();
        reader.onload = function(e) {
            preview.src = e.target.result;
            preview.style.display = 'block';
            label.innerHTML = `
                <i class="fas fa-check-circle fa-2x text-success mb-2"></i>
                <div class="text-success">
                    <strong>New image: ${file.name}</strong>
                </div>
                <small class="text-muted">Click to change image</small>
            `;
        };
        reader.readAsDataURL(file);
    } else {
        preview.style.display = 'none';
        label.innerHTML = `
            <i class="fas fa-cloud-upload-alt fa-2x text-muted mb-2"></i>
            <div class="text-muted">
                <strong>Click to upload new image</strong> or drag and drop
            </div>
            <small class="text-muted">PNG, JPG, GIF, WebP up to 10MB</small>
        `;
    }
});

setTimeout(() => {
    const alerts = document.querySelectorAll('.alert-dismissible');
    alerts.forEach(alert => {
        const bsAlert = new bootstrap.Alert(alert);
        setTimeout(() => {
            bsAlert.close();
        }, 5000);
    });
}, 100);

function resetForm() {
    if (confirm('Are you sure you want to reset all changes? This will restore the original product data.')) {
        document.getElementById('name').value = '<?php echo addslashes($product['name']); ?>';
        document.getElementById('price').value = '<?php echo $product['price']; ?>';
        document.getElementById('stock').value = '<?php echo $product['stock']; ?>';
        document.getElementById('description').value = '<?php echo addslashes($product['description']); ?>';
        document.getElementById('category').value = '<?php echo $product['category']; ?>';
        
        document.getElementById('image').value = '';
        document.getElementById('imagePreview').style.display = 'none';
        document.getElementById('imageLabel').innerHTML = `
            <i class="fas fa-cloud-upload-alt fa-2x text-muted mb-2"></i>
            <div class="text-muted">
                <strong>Click to upload new image</strong> or drag and drop
            </div>
            <small class="text-muted">PNG, JPG, GIF, WebP up to 10MB</small>
        `;
        
        document.querySelectorAll('.is-invalid').forEach(el => {
            el.classList.remove('is-invalid');
        });
    }
}

document.getElementById('name').addEventListener('input', function() {
    const value = this.value.trim();
    if (value.length === 0) {
        this.setCustomValidity('Product name is required');
    } else if (value.length < 2) {
        this.setCustomValidity('Product name must be at least 2 characters long');
    } else if (value.length > 150) {
        this.setCustomValidity('Product name cannot exceed 150 characters');
    } else {
        this.setCustomValidity('');
    }
});

document.getElementById('price').addEventListener('input', function() {
    const value = parseFloat(this.value);
    if (isNaN(value) || value <= 0) {
        this.setCustomValidity('Price must be greater than 0');
    } else if (value > 99999999.99) {
        this.setCustomValidity('Price cannot exceed $99,999,999.99');
    } else {
        this.setCustomValidity('');
    }
});

document.getElementById('stock').addEventListener('input', function() {
    const value = parseInt(this.value);
    if (isNaN(value) || value < 0) {
        this.setCustomValidity('Stock cannot be negative');
    } else if (value > 999999) {
        this.setCustomValidity('Stock cannot exceed 999,999 units');
    } else {
        this.setCustomValidity('');
    }
});

document.getElementById('description').addEventListener('input', function() {
    const maxLength = 2000;
    const currentLength = this.value.length;
    const remaining = maxLength - currentLength;
    
    let helpText = this.parentElement.querySelector('.form-text');
    if (remaining < 100) {
        helpText.innerHTML = `Optional - provide detailed information about the product (${remaining} characters remaining)`;
        helpText.style.color = remaining < 20 ? '#dc3545' : '#ffc107';
    } else {
        helpText.innerHTML = 'Optional - provide detailed information about the product (max 2000 characters)';
        helpText.style.color = '';
    }
    
    if (currentLength > maxLength) {
        this.setCustomValidity('Description cannot exceed 2000 characters');
    } else {
        this.setCustomValidity('');
    }
});

document.getElementById('productForm').addEventListener('submit', function(e) {
    let isValid = true;
    
    const name = document.getElementById('name').value.trim();
    const price = parseFloat(document.getElementById('price').value);
    
    if (!name) {
        document.getElementById('name').classList.add('is-invalid');
        isValid = false;
    } else {
        document.getElementById('name').classList.remove('is-invalid');
    }
    
    if (isNaN(price) || price <= 0) {
        document.getElementById('price').classList.add('is-invalid');
        isValid = false;
    } else {
        document.getElementById('price').classList.remove('is-invalid');
    }
    
    if (!isValid) {
        e.preventDefault();
        alert('Please fix the validation errors before submitting.');
    }
});

let formChanged = false;
const originalFormData = new FormData(document.getElementById('productForm'));

document.getElementById('productForm').addEventListener('input', function() {
    formChanged = true;
});

window.addEventListener('beforeunload', function(e) {
    if (formChanged) {
        const confirmationMessage = 'You have unsaved changes. Are you sure you want to leave?';
        e.returnValue = confirmationMessage;
        return confirmationMessage;
    }
});

document.getElementById('productForm').addEventListener('submit', function() {
    formChanged = false;
});
</script>

<?php include 'includes/footer.php'; ?>