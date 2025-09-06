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
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin Panel</title>
    <link rel="stylesheet" href="../../assets/admin/css/style.css" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">

</head>

<body>
    <div class="admin-container">
        <!-- Header -->
        <header class="admin-header">
            <div class="logo">
                <a href="dashboard.php">
                    <img src="../../assets/images/logo-2.png" alt="Company Logo">
                    <span class="title">Web Development</span>
                </a>
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