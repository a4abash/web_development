<?php
session_start();
if (!isset($_SESSION)) {
    session_start();
}

if (!isset($_SESSION['username'])) {
    $_SESSION['username'] = 'Guest';
}

if (isset($_GET['debug'])) {
    var_dump($_SESSION);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel</title>
    <link rel="stylesheet" href="../../assets/admin/css/style.css" />
</head>
<body>
    <div class="admin-container">
        <!-- Header -->
        <header class="admin-header">
            <div class="logo">
                <img src="../../assets/images/logo-2.png" alt="Company Logo">
                <span><?php echo htmlspecialchars($_SESSION['username']); ?></span>
            </div>
            <div class="profile-dropdown">
                <button onclick="toggleDropdown()">
                    <img src="../../assets/images/profile.jpg" alt="Profile">
                    <span><?php echo htmlspecialchars($_SESSION['username']); ?></span>
                </button>
                <div class="dropdown-content" id="profileDropdown">
                    <button onclick="location.href='#'">Settings</button>
                    <button onclick="logout()">Logout</button>
                </div>
            </div>
        </header>

        <!-- Sidebar -->
        <nav class="admin-sidebar">
            <ul class="sidebar-menu">
                <li><a href="#">Roles</a></li>
                <li><a href="#">Users</a></li>
                <li><a href="#">Products</a></li>
                <li><a href="#">Settings</a></li>
            </ul>
        </nav>

        <!-- Main Content -->
        <main class="admin-content">
            <div class="content-header">
                <h1>Welcome to Admin Panel <?php $_SESSION['username'] ?></h1>
                <p>This is your admin dashboard.</p>
            </div>
            <div class="content-body">
                <!-- Your content goes here -->
            </div>
        </main>
    </div>

    <script>
        function toggleDropdown() {
            const dropdown = document.getElementById('profileDropdown');
            dropdown.style.display = dropdown.style.display === 'block' ? 'none' : 'block';
        }

        function logout() {
            if (confirm('Are you sure you want to logout?')) {
                location.href = '../../logout.php';
            }
        }

        document.addEventListener('click', function(e) {
            const dropdown = document.getElementById('profileDropdown');
            if (e.target !== dropdown && !e.target.closest('.profile-dropdown')) {
                dropdown.style.display = 'none';
            }
        });
    </script>
</body>
</html>