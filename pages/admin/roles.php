<?php
include 'includes/header.php';
include 'includes/sidebar.php';
require_once '../../config/auth.php';
require_once '../../config/db.php';

try {
    $stmt = $conn->prepare("
        SELECT r.id, r.role, r.description,
               COUNT(u.id) as user_count
        FROM roles r
        LEFT JOIN users u ON r.id = u.role_id
        GROUP BY r.id, r.role, r.description
        ORDER BY r.id ASC
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
        <h1><i class="fas fa-user-shield me-2"></i>Roles</h1>
        <p>Manage User Roles & Permissions</p>
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

        <div class="row stats-cards mb-4">
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-number"><?php echo count($roles); ?></div>
                    <div class="stat-label">Total Roles</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-number">
                        <?php 
                        $activeRoles = array_filter($roles, function($r) { 
                            return $r['user_count'] > 0; 
                        });
                        echo count($activeRoles);
                        ?>
                    </div>
                    <div class="stat-label">Active Roles</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-number">
                        <?php 
                        $totalUsers = array_sum(array_column($roles, 'user_count'));
                        echo $totalUsers;
                        ?>
                    </div>
                    <div class="stat-label">Total Users</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-number">
                        <?php 
                        $unusedRoles = array_filter($roles, function($r) { 
                            return $r['user_count'] == 0; 
                        });
                        echo count($unusedRoles);
                        ?>
                    </div>
                    <div class="stat-label">Unused Roles</div>
                </div>
            </div>
        </div>

        <div class="search-filter-bar">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-search"></i></span>
                        <input type="text" class="form-control" id="searchInput" 
                               placeholder="Search roles...">
                    </div>
                </div>
                <div class="col-md-3">
                    <select class="form-select" id="usageFilter">
                        <option value="">All Roles</option>
                        <option value="active">In Use</option>
                        <option value="unused">Unused</option>
                    </select>
                </div>
                <div class="col-md-3 text-end">
                    <button type="button" class="btn btn-outline-secondary" onclick="clearFilters()">
                        <i class="fas fa-times me-1"></i>Clear
                    </button>
                </div>
            </div>
        </div>

        <!-- Roles Table -->
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Role</th>
                        <th>Description</th>
                        <th>Users Assigned</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody id="rolesTableBody">
                    <?php if (empty($roles)): ?>
                    <tr>
                        <td colspan="5" class="text-center py-4">
                            <div class="text-muted">
                                <i class="fas fa-user-shield fa-3x mb-3"></i>
                                <h5>No Roles Found</h5>
                                <p>Start by adding your first role.</p>
                                <a href="role-create.php" class="btn btn-primary">
                                    <i class="fas fa-plus me-1"></i>Add Role
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($roles as $role): ?>
                    <tr data-role-name="<?php echo strtolower(htmlspecialchars($role['role'])); ?>" 
                        data-description="<?php echo strtolower(htmlspecialchars($role['description'])); ?>"
                        data-user-count="<?php echo $role['user_count']; ?>">
                        <td>
                            <div class="d-flex align-items-center">
                                <div class="role-icon me-3">
                                    <?php
                                    $roleName = strtolower($role['role']);
                                    if (strpos($roleName, 'admin') !== false) {
                                        $icon = 'fas fa-crown';
                                        $iconColor = 'text-warning';
                                    } elseif (strpos($roleName, 'manager') !== false) {
                                        $icon = 'fas fa-users-cog';
                                        $iconColor = 'text-info';
                                    } elseif (strpos($roleName, 'editor') !== false) {
                                        $icon = 'fas fa-edit';
                                        $iconColor = 'text-success';
                                    } else {
                                        $icon = 'fas fa-user';
                                        $iconColor = 'text-secondary';
                                    }
                                    ?>
                                    <i class="<?php echo $icon; ?> <?php echo $iconColor; ?>" style="font-size: 1.5rem;"></i>
                                </div>
                                <div class="role-info">
                                    <h6 class="mb-0 fw-bold"><?php echo htmlspecialchars($role['role']); ?></h6>
                                    <small class="text-muted">ID: #<?php echo $role['id']; ?></small>
                                </div>
                            </div>
                        </td>
                        <td>
                            <?php if ($role['description']): ?>
                                <div class="description-text" title="<?php echo htmlspecialchars($role['description']); ?>">
                                    <?php echo htmlspecialchars($role['description']); ?>
                                </div>
                            <?php else: ?>
                                <span class="text-muted fst-italic">No description provided</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="d-flex align-items-center">
                                <?php if ($role['user_count'] > 0): ?>
                                    <span class="badge bg-primary me-2"><?php echo $role['user_count']; ?></span>
                                    <a href="users.php?role=<?php echo urlencode($role['role']); ?>" 
                                       class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-users me-1"></i>View Users
                                    </a>
                                <?php else: ?>
                                    <span class="text-muted">No users assigned</span>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td>
                            <?php if ($role['user_count'] > 0): ?>
                                <span class="badge bg-success">
                                    <i class="fas fa-check-circle me-1"></i>Active
                                </span>
                            <?php else: ?>
                                <span class="badge bg-secondary">
                                    <i class="fas fa-pause-circle me-1"></i>Unused
                                </span>
                            <?php endif; ?>
                        </td>
                        <!-- <td>
                            <div class="action-buttons">
                                <a href="role-view.php?id=<?php echo $role['id']; ?>" 
                                   class="btn btn-outline-info" title="View Details">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="role-edit.php?id=<?php echo $role['id']; ?>" 
                                   class="btn btn-outline-primary" title="Edit Role">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <a href="role-permissions.php?id=<?php echo $role['id']; ?>" 
                                   class="btn btn-outline-success" title="Manage Permissions">
                                    <i class="fas fa-key"></i>
                                </a>
                                <?php if ($role['user_count'] == 0): ?>
                                <button type="button" class="btn btn-outline-danger" 
                                        onclick="confirmDelete(<?php echo $role['id']; ?>, '<?php echo addslashes($role['role']); ?>')" 
                                        title="Delete Role">
                                    <i class="fas fa-trash"></i>
                                </button>
                                <?php else: ?>
                                <button type="button" class="btn btn-outline-danger disabled" 
                                        title="Cannot delete - role is in use" disabled>
                                    <i class="fas fa-lock"></i>
                                </button>
                                <?php endif; ?>
                            </div>
                        </td> -->
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
                <p>Are you sure you want to delete the role "<strong id="roleName"></strong>"?</p>
                <p class="text-muted">This action cannot be undone. Only unused roles can be deleted.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <a href="#" id="confirmDeleteBtn" class="btn btn-danger">
                    <i class="fas fa-trash me-1"></i>Delete Role
                </a>
            </div>
        </div>
    </div>
</div>

<script>
// Search and Filter Functions
document.getElementById('searchInput').addEventListener('input', filterRoles);
document.getElementById('usageFilter').addEventListener('change', filterRoles);

function filterRoles() {
    const searchTerm = document.getElementById('searchInput').value.toLowerCase();
    const usageFilter = document.getElementById('usageFilter').value;
    const rows = document.querySelectorAll('#rolesTableBody tr[data-role-name]');
    
    rows.forEach(row => {
        const roleName = row.getAttribute('data-role-name');
        const description = row.getAttribute('data-description');
        const userCount = parseInt(row.getAttribute('data-user-count'));
        
        let showRow = true;
        
        // Search filter
        if (searchTerm && !roleName.includes(searchTerm) && !description.includes(searchTerm)) {
            showRow = false;
        }
        
        // Usage filter
        if (usageFilter === 'active' && userCount === 0) {
            showRow = false;
        } else if (usageFilter === 'unused' && userCount > 0) {
            showRow = false;
        }
        
        row.style.display = showRow ? '' : 'none';
    });
}

function clearFilters() {
    document.getElementById('searchInput').value = '';
    document.getElementById('usageFilter').value = '';
    filterRoles();
}

// Delete confirmation
function confirmDelete(id, name) {
    document.getElementById('roleName').textContent = name;
    document.getElementById('confirmDeleteBtn').href = `?action=delete&id=${id}`;
    new bootstrap.Modal(document.getElementById('deleteModal')).show();
}

// Auto-hide alerts after 5 seconds
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