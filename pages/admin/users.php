<?php
include 'includes/header.php';
include 'includes/sidebar.php';
require_once '../../config/auth.php';
require_once '../../config/db.php';

// Handle delete action
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    try {
        if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $id) {
            $error_msg = "You cannot delete your own account!";
        } else {
            $checkStmt = $conn->prepare("SELECT id FROM users WHERE id = ?");
            $checkStmt->bind_param("i", $id);
            $checkStmt->execute();
            $result = $checkStmt->get_result();

            if ($result->num_rows > 0) {
                $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
                $stmt->bind_param("i", $id);

                if ($stmt->execute()) {
                    $success_msg = "User deleted successfully!";
                } else {
                    $error_msg = "Error deleting user: " . $conn->error;
                }
            } else {
                $error_msg = "User not found.";
            }
        }
    } catch (Exception $e) {
        $error_msg = "Error deleting user: " . $e->getMessage();
    }
}

if (isset($_GET['action']) && $_GET['action'] === 'toggle_status' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    try {
        $stmt = $conn->prepare("SELECT status FROM users WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();

        if ($user) {
            $currentStatus = $user['status'];
            $newStatus = ($currentStatus === 'active') ? 'inactive' : 'active';

            $updateStmt = $conn->prepare("UPDATE users SET status = ? WHERE id = ?");
            $updateStmt->bind_param("si", $newStatus, $id);

            if ($updateStmt->execute()) {
                $success_msg = "User status updated to " . $newStatus . "!";
            } else {
                $error_msg = "Error updating user status: " . $conn->error;
            }
        } else {
            $error_msg = "User not found.";
        }
    } catch (Exception $e) {
        $error_msg = "Error updating user status: " . $e->getMessage();
    }
}

try {
    $stmt = $conn->prepare("
        SELECT u.id, u.name, u.email, u.role_id, u.status, u.created_at,
               COALESCE(r.role, 'user') as role
        FROM users u
        LEFT JOIN roles r ON u.role_id = r.id
        ORDER BY u.id DESC
    ");
    $stmt->execute();
    $users = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
} catch (Exception $e) {
    error_log("Error fetching users: " . $e->getMessage());
    $users = [];
    $error_msg = "Error loading users data.";
}

try {
    $rolesStmt = $conn->prepare("SELECT id, name FROM roles ORDER BY name");
    $rolesStmt->execute();
    $roles = $rolesStmt->get_result()->fetch_all(MYSQLI_ASSOC);
} catch (Exception $e) {
    $roles = [
        ['id' => 1, 'name' => 'Admin'],
        ['id' => 2, 'name' => 'Member'],
        ['id' => 3, 'name' => 'User']
    ];
}
?>

<main class="admin-content">
    <div class="content-header">
        <h1><i class="fas fa-users me-2"></i>Users</h1>
        <p>Manage User Accounts</p>
        <!-- <a href="user-create.php" class="btn btn-primary">
            <i class="fas fa-plus me-1"></i>New User
        </a> -->
    </div>

    <div class="content-body">
        <?php if (isset($success_msg)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($success_msg); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($error_msg)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i><?php echo htmlspecialchars($error_msg); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="row stats-cards">
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-number"><?php echo count($users); ?></div>
                    <div class="stat-label">Total Users</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-number">
                        <?php
                        $activeUsers = array_filter($users, function ($u) {
                            return ($u['status'] ?? 'active') === 'active';
                        });
                        echo count($activeUsers);
                        ?>
                    </div>
                    <div class="stat-label">Active Users</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-number">
                        <?php
                        $admins = array_filter($users, function ($u) {
                            return strtolower($u['role'] ?? 'user') === 'admin';
                        });
                        echo count($admins);
                        ?>
                    </div>
                    <div class="stat-label">Administrators</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-number">
                        <?php
                        $newUsers = array_filter($users, function ($u) {
                            return strtotime($u['created_at']) > strtotime('-30 days');
                        });
                        echo count($newUsers);
                        ?>
                    </div>
                    <div class="stat-label">New This Month</div>
                </div>
            </div>
        </div>

        <div class="search-filter-bar">
            <div class="row align-items-center">
                <div class="col-md-4">
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-search"></i></span>
                        <input type="text" class="form-control" id="searchInput"
                            placeholder="Search users...">
                    </div>
                </div>
                <div class="col-md-3">
                    <select class="form-select" id="roleFilter">
                        <option value="">All Roles</option>
                        <option value="admin">Administrator</option>
                        <option value="manager">Manager</option>
                        <option value="user">User</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <select class="form-select" id="statusFilter">
                        <option value="">All Status</option>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                        <option value="pending">Pending</option>
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
                        <th>User</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="usersTableBody">
                    <?php if (empty($users)): ?>
                        <tr>
                            <td colspan="6" class="text-center py-4">
                                <div class="text-muted">
                                    <i class="fas fa-users fa-3x mb-3"></i>
                                    <h5>No Users Found</h5>
                                    <p>Start by adding your first user.</p>
                                    <a href="user-create.php" class="btn btn-primary">
                                        <i class="fas fa-plus me-1"></i>Add User
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($users as $user): ?>
                            <tr data-user-name="<?php echo strtolower(htmlspecialchars($user['name'])); ?>"
                                data-email="<?php echo strtolower(htmlspecialchars($user['email'])); ?>"
                                data-role="<?php echo strtolower(htmlspecialchars($user['role'] ?? 'user')); ?>"
                                data-status="<?php echo strtolower(htmlspecialchars($user['status'] ?? 'active')); ?>">
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="user-avatar me-3">
                                            <?php echo strtoupper(substr($user['name'], 0, 2)); ?>
                                        </div>
                                        <div class="user-info">
                                            <h6 class="mb-0"><?php echo htmlspecialchars($user['name']); ?></h6>
                                            <small class="text-muted">ID: #<?php echo $user['id']; ?></small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="text-primary"><?php echo htmlspecialchars($user['email']); ?></span>
                                </td>
                                <td>
                                    <?php
                                    $role = $user['role'] ?? 'user';
                                    $roleClass = 'role-' . strtolower($role);
                                    ?>
                                    <span class="badge role-badge <?php echo $roleClass; ?>">
                                        <?php echo ucfirst($role); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php
                                    $status = $user['status'] ?? 'active';
                                    $statusClass = 'status-' . strtolower($status);
                                    ?>
                                    <span class="badge status-badge <?php echo $statusClass; ?>">
                                        <i class="fas fa-circle me-1" style="font-size: 0.5em;"></i>
                                        <?php echo ucfirst($status); ?>
                                    </span>
                                </td>
                                <td>
                                    <small class="text-muted">
                                        <?php echo date('M j, Y', strtotime($user['created_at'])); ?>
                                    </small>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <!-- <a href="user-edit.php?id=<?php echo $user['id']; ?>"
                                            class="btn btn-outline-primary btn-sm" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a> -->
                                        <?php if (!isset($_SESSION['user_id']) || $_SESSION['user_id'] != $user['id']): ?>
                                            <button type="button" class="btn btn-outline-warning btn-sm"
                                                onclick="toggleStatus(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars(addslashes($user['name'])); ?>', '<?php echo $user['status'] ?? 'active'; ?>')"
                                                title="Toggle Status">
                                                <i class="fas fa-toggle-<?php echo ($user['status'] ?? 'active') === 'active' ? 'on' : 'off'; ?>"></i>
                                            </button>
                                        <?php endif; ?>
                                        <?php if (!isset($_SESSION['user_id']) || $_SESSION['user_id'] != $user['id']): ?>
                                            <button type="button" class="btn btn-outline-danger btn-sm"
                                                onclick="confirmDelete(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars(addslashes($user['name'])); ?>')"
                                                title="Delete">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        <?php endif; ?>
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

<!-- Delete Confirmation Modal -->
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
                <p>Are you sure you want to delete the user "<strong id="userName"></strong>"?</p>
                <p class="text-muted">This action cannot be undone and will permanently remove the user account.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <a href="#" id="confirmDeleteBtn" class="btn btn-danger">
                    <i class="fas fa-trash me-1"></i>Delete User
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Status Toggle Modal -->
<div class="modal fade" id="statusModal" tabindex="-1" aria-labelledby="statusModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="statusModalLabel">
                    <i class="fas fa-toggle-on text-warning me-2"></i>Toggle User Status
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to <span id="statusAction"></span> the user "<strong id="statusUserName"></strong>"?</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <a href="#" id="confirmStatusBtn" class="btn btn-warning">
                    <i class="fas fa-toggle-on me-1"></i>Update Status
                </a>
            </div>
        </div>
    </div>
</div>

<script>
    // Search and filter functionality
    document.getElementById('searchInput').addEventListener('input', filterUsers);
    document.getElementById('roleFilter').addEventListener('change', filterUsers);
    document.getElementById('statusFilter').addEventListener('change', filterUsers);

    function filterUsers() {
        const searchTerm = document.getElementById('searchInput').value.toLowerCase();
        const roleFilter = document.getElementById('roleFilter').value.toLowerCase();
        const statusFilter = document.getElementById('statusFilter').value.toLowerCase();
        const rows = document.querySelectorAll('#usersTableBody tr[data-user-name]');

        rows.forEach(row => {
            const userName = row.getAttribute('data-user-name');
            const userEmail = row.getAttribute('data-email');
            const role = row.getAttribute('data-role');
            const status = row.getAttribute('data-status');

            let showRow = true;

            if (searchTerm && !userName.includes(searchTerm) && !userEmail.includes(searchTerm)) {
                showRow = false;
            }

            if (roleFilter && role !== roleFilter) {
                showRow = false;
            }

            if (statusFilter && status !== statusFilter) {
                showRow = false;
            }

            row.style.display = showRow ? '' : 'none';
        });
    }

    function clearFilters() {
        document.getElementById('searchInput').value = '';
        document.getElementById('roleFilter').value = '';
        document.getElementById('statusFilter').value = '';
        filterUsers();
    }

    // Delete user functionality
    function confirmDelete(id, name) {
        document.getElementById('userName').textContent = name;
        document.getElementById('confirmDeleteBtn').href = `?action=delete&id=${id}`;
        new bootstrap.Modal(document.getElementById('deleteModal')).show();
    }

    // Toggle status functionality
    function toggleStatus(id, name, currentStatus) {
        const action = currentStatus === 'active' ? 'deactivate' : 'activate';
        document.getElementById('statusUserName').textContent = name;
        document.getElementById('statusAction').textContent = action;
        document.getElementById('confirmStatusBtn').href = `?action=toggle_status&id=${id}`;
        new bootstrap.Modal(document.getElementById('statusModal')).show();
    }

    // Auto-hide alerts after 5 seconds
    setTimeout(() => {
        const alerts = document.querySelectorAll('.alert');
        alerts.forEach(alert => {
            if (alert.classList.contains('show')) {
                const bsAlert = bootstrap.Alert.getOrCreateInstance(alert);
                bsAlert.close();
            }
        });
    }, 5000);

    // Remove URL parameters after successful action (optional - prevents repeat actions on page refresh)
    if (window.location.search.includes('action=')) {
        const url = new URL(window.location);
        url.searchParams.delete('action');
        url.searchParams.delete('id');
        window.history.replaceState({}, document.title, url.toString());
    }
</script>

<?php include 'includes/footer.php'; ?>