<?php
include 'includes/header.php';
include 'includes/sidebar.php';
require_once '../../config/auth.php';
require_once '../../config/db.php';

$success_msg = '';
$error_msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create') {
    $name = trim($_POST['name'] ?? '');
    $price = (float)($_POST['price'] ?? 0);
    $stock = (int)($_POST['stock'] ?? 0);
    $description = trim($_POST['description'] ?? '');
    $category = trim($_POST['category'] ?? '');

    if (empty($name)) {
        $error_msg = "Product name is required.";
    } elseif (strlen($name) > 150) {
        $error_msg = "Product name cannot exceed 150 characters.";
    } elseif ($price <= 0) {
        $error_msg = "Price must be greater than 0.";
    } elseif ($stock < 0) {
        $error_msg = "Stock cannot be negative.";
    } else {
        try {
            $image_filename = '';
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $upload_dir = '../../uploads/';

                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }

                $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                $file_type = $_FILES['image']['type'];
                $max_size = 10 * 1024 * 1024; 

                if (!in_array($file_type, $allowed_types)) {
                    $error_msg = "Invalid image type. Please upload JPEG, PNG, GIF, or WebP images only.";
                } elseif ($_FILES['image']['size'] > $max_size) {
                    $error_msg = "Image size must be less than 10MB.";
                } else {
                    $file_extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
                    $image_filename = 'product_' . time() . '_' . uniqid() . '.' . $file_extension;
                    $upload_path = $upload_dir . $image_filename;

                    if (!move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
                        $error_msg = "Failed to upload image.";
                        $image_filename = '';
                    }
                }
            }

            if (empty($error_msg)) {
                $created_by = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 1;

                $sql = "INSERT INTO products (name, description, price, stock, category, image, created_by) VALUES (?, ?, ?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);

                if (!$stmt) {
                    throw new Exception("Prepare failed: " . $conn->error);
                }

                $name = (string)$name;
                $description = (string)$description;
                $price = (float)$price;
                $stock = (int)$stock;
                $category = (string)$category;
                $image_filename = (string)$image_filename;
                $created_by = (int)$created_by;

                $result = $stmt->bind_param("ssdissi", $name, $description, $price, $stock, $category, $image_filename, $created_by);

                if (!$result) {
                    throw new Exception("Binding parameters failed: " . $stmt->error);
                }

                if ($stmt->execute()) {
                    $success_msg = "Product created successfully! Product ID: " . $conn->insert_id;
                    $_POST = [];
                } else {
                    throw new Exception("Execute failed: " . $stmt->error);
                }

                $stmt->close();
            }
        } catch (Exception $e) {
            $error_msg = "Error: " . $e->getMessage();
            error_log("Product creation error: " . $e->getMessage());

            if (!empty($image_filename) && file_exists($upload_dir . $image_filename)) {
                unlink($upload_dir . $image_filename);
            }
        }
    }
}
?>
<main class="admin-content">
    <div class="content-header">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h1><i class="fas fa-plus-circle me-2"></i>Add New Product</h1>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item">
                            <a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
                        </li>
                        <li class="breadcrumb-item">
                            <a href="products.php">Products</a>
                        </li>
                        <li class="breadcrumb-item active">Add Product</li>
                    </ol>
                </nav>
            </div>
            <a href="products.php" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-1"></i>Back to Products
            </a>
        </div>
    </div>

    <div class="content-body">
        <?php if ($success_msg): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($success_msg); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if ($error_msg): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i><?php echo htmlspecialchars($error_msg); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="row">
            <div class="col-lg-12 mx-auto">
                <div class="form-container">
                    <h2 class="mb-0">New Product</h2>
                    <small>Fill the form to create new Product</small>
                    <hr>
                    <form id="productForm" method="POST" enctype="multipart/form-data" novalidate>
                        <input type="hidden" name="action" value="create">

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="name" class="form-label">
                                    Product Name <span class="required-field">*</span>
                                </label>
                                <input type="text" class="form-control" id="name" name="name"
                                    value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>"
                                    placeholder="Enter product name" required maxlength="150">
                                <div class="form-text">Maximum 150 characters</div>
                                <div class="invalid-feedback">
                                    Please provide a valid product name.
                                </div>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="category" class="form-label">Category</label>
                                <select class="form-select" id="category" name="category">
                                    <option value="">Select Category</option>
                                    <option value="Electronics" <?php echo (($_POST['category'] ?? '') === 'Electronics') ? 'selected' : ''; ?>>Electronics</option>
                                    <option value="Clothing" <?php echo (($_POST['category'] ?? '') === 'Clothing') ? 'selected' : ''; ?>>Clothing</option>
                                    <option value="Books" <?php echo (($_POST['category'] ?? '') === 'Books') ? 'selected' : ''; ?>>Books</option>
                                    <option value="Home & Garden" <?php echo (($_POST['category'] ?? '') === 'Home & Garden') ? 'selected' : ''; ?>>Home & Garden</option>
                                    <option value="Sports" <?php echo (($_POST['category'] ?? '') === 'Sports') ? 'selected' : ''; ?>>Sports</option>
                                    <option value="Toys" <?php echo (($_POST['category'] ?? '') === 'Toys') ? 'selected' : ''; ?>>Toys</option>
                                    <option value="Beauty" <?php echo (($_POST['category'] ?? '') === 'Beauty') ? 'selected' : ''; ?>>Beauty</option>
                                    <option value="Food" <?php echo (($_POST['category'] ?? '') === 'Food') ? 'selected' : ''; ?>>Food</option>
                                    <option value="Automotive" <?php echo (($_POST['category'] ?? '') === 'Automotive') ? 'selected' : ''; ?>>Automotive</option>
                                    <option value="Health" <?php echo (($_POST['category'] ?? '') === 'Health') ? 'selected' : ''; ?>>Health</option>
                                    <option value="Other" <?php echo (($_POST['category'] ?? '') === 'Other') ? 'selected' : ''; ?>>Other</option>
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
                                    <input type="number" class="form-control" id="price" name="price"
                                        value="<?php echo htmlspecialchars($_POST['price'] ?? ''); ?>"
                                        placeholder="0.00" step="0.01" min="0.01" max="99999999.99" required>
                                </div>
                                <div class="form-text">Enter the selling price in USD</div>
                                <div class="invalid-feedback">
                                    Please provide a valid price greater than $0.
                                </div>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="stock" class="form-label">Stock Quantity</label>
                                <input type="number" class="form-control" id="stock" name="stock"
                                    value="<?php echo htmlspecialchars($_POST['stock'] ?? '0'); ?>"
                                    placeholder="0" min="0" max="999999">
                                <div class="form-text">Number of units in inventory</div>
                                <div class="invalid-feedback">
                                    Please provide a valid stock quantity.
                                </div>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label for="description" class="form-label">Product Description</label>
                            <textarea class="form-control" id="description" name="description"
                                rows="4" placeholder="Describe your product..."><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
                            <div class="form-text">Optional - provide detailed information about the product</div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label">Product Image</label>
                            <div class="file-input-wrapper">
                                <input type="file" id="image" name="image" accept="image/*">
                                <label for="image" class="file-input-label" id="imageLabel">
                                    <i class="fas fa-cloud-upload-alt fa-2x text-muted mb-2"></i>
                                    <div class="text-muted">
                                        <strong>Click to upload</strong> or drag and drop
                                    </div>
                                    <small class="text-muted">PNG, JPG, GIF, WebP up to 10MB</small>
                                </label>
                            </div>
                            <img id="imagePreview" class="image-preview" alt="Preview">
                            <div class="form-text">Optional - upload a product image to make it more appealing</div>
                        </div>

                        <div class="d-flex justify-content-between align-items-center">
                            <a href="products.php" class="btn btn-secondary">
                                <i class="fas fa-times me-1"></i>Cancel
                            </a>
                            <div>
                                <button type="reset" class="btn btn-outline-secondary me-2">
                                    <i class="fas fa-undo me-1"></i>Reset Form
                                </button>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-1"></i>Create Product
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
            const reader = new FileReader();
            reader.onload = function(e) {
                preview.src = e.target.result;
                preview.style.display = 'block';
                label.innerHTML = `
                <i class="fas fa-check-circle fa-2x text-success mb-2"></i>
                <div class="text-success">
                    <strong>${file.name}</strong>
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
                <strong>Click to upload</strong> or drag and drop
            </div>
            <small class="text-muted">PNG, JPG, GIF, WebP up to 10MB</small>
        `;
        }
    });

    setTimeout(() => {
        const alerts = document.querySelectorAll('.alert');
        alerts.forEach(alert => {
            if (alert.classList.contains('alert-success') || alert.classList.contains('alert-danger')) {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            }
        });
    }, 5000);

    document.querySelector('button[type="reset"]').addEventListener('click', function(e) {
        if (!confirm('Are you sure you want to reset the form? All entered data will be lost.')) {
            e.preventDefault();
        } else {
            document.getElementById('imagePreview').style.display = 'none';
            document.getElementById('imageLabel').innerHTML = `
            <i class="fas fa-cloud-upload-alt fa-2x text-muted mb-2"></i>
            <div class="text-muted">
                <strong>Click to upload</strong> or drag and drop
            </div>
            <small class="text-muted">PNG, JPG, GIF, WebP up to 10MB</small>
        `;
        }
    });
</script>

</div>
<?php include 'includes/footer.php'; ?>