<?php
include 'includes/header.php';
include 'includes/sidebar.php';
require_once '../../config/auth.php';
require_once '../../config/db.php';

try {
    $stmt = $conn->prepare("SELECT name, email, message, created_at FROM contacts ORDER BY created_at DESC");
    $stmt->execute();
    $contacts = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
} catch (Exception $e) {
    error_log("Error fetching contacts: " . $e->getMessage());
    $contacts = [];
}
?>

<main class="admin-content">
    <div class="content-header">
        <h1><i class="fas fa-envelope me-2"></i>Contacts</h1>
        <p>All messages submitted via the contact form.</p>
    </div>

    <div class="content-body">
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-number"><?php echo count($contacts); ?></div>
                    <div class="stat-label">Total Contacts</div>
                </div>
            </div>
        </div>

        <div class="search-filter-bar mb-3">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-search"></i></span>
                        <input type="text" class="form-control" id="searchInput" placeholder="Search contacts...">
                    </div>
                </div>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover table-striped">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Message</th>
                        <th>Submitted At</th>
                    </tr>
                </thead>
                <tbody id="contactsTableBody">
                    <?php if (empty($contacts)): ?>
                        <tr>
                            <td colspan="4" class="text-center py-4 text-muted">
                                <i class="fas fa-inbox fa-2x mb-2"></i>
                                <p>No contact messages found.</p>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($contacts as $contact): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($contact['name']); ?></td>
                                <td><?php echo htmlspecialchars($contact['email']); ?></td>
                                <td><?php echo nl2br(htmlspecialchars($contact['message'])); ?></td>
                                <td><?php echo date('Y-m-d H:i A', strtotime($contact['created_at'])); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>

<script>
document.getElementById('searchInput').addEventListener('input', function () {
    const value = this.value.toLowerCase();
    const rows = document.querySelectorAll('#contactsTableBody tr');

    rows.forEach(row => {
        const text = row.innerText.toLowerCase();
        row.style.display = text.includes(value) ? '' : 'none';
    });
});
</script>

<?php include 'includes/footer.php'; ?>
