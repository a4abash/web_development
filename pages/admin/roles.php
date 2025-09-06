<?php
include 'includes/header.php';
include 'includes/sidebar.php';
require_once '../../config/auth.php';
require_once '../../config/db.php';
try {
    $stmt = $conn->prepare("
        SELECT id, role, description
        FROM roles
        ORDER BY id DESC
    ");
    $stmt->execute();
    $roles = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
} catch (Exception $e) {
    error_log("Error fetching roles: " . $e->getMessage());
    $roles = [];
}
?>

<main class="admin-content">
    <div class="content-header">
        <h1>Roles</h1>
        <p>Manage system roles and their permissions</p>
        <a href="create-role.php" class="btn btn-primary">Create New Role</a>
    </div>
    <div class="content-body">
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Description</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($roles as $role): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($role['id']); ?></td>
                            <td><?php echo htmlspecialchars($role['role']); ?></td>
                            <td><?php echo htmlspecialchars($role['description']); ?></td>
                            <td>
                                <div class="btn-group">
                                    <a href="edit-role.php?id=<?php echo htmlspecialchars($role['id']); ?>"
                                        class="btn btn-sm btn-primary me-2">
                                        <i class="fas fa-edit"></i> Edit
                                    </a>
                                    <form action="delete-role.php" method="POST"
                                        style="display: inline-block"
                                        onsubmit="return confirm('Are you sure you want to delete this role?')">
                                        <input type="hidden" name="id" value="<?php echo htmlspecialchars($role['id']); ?>">
                                        <button type="submit" class="btn btn-sm btn-danger">
                                            <i class="fas fa-trash"></i> Delete
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>
</div>
<?php include 'includes/footer.php'; ?>