<?php
$currentRoute = basename($_SERVER['PHP_SELF']);
?>
<nav class="admin-sidebar">
    <ul class="sidebar-menu">
        <li><a class="<?php echo $currentRoute === 'roles.php' ? 'active' : ''; ?>" href="roles.php">Roles</a></li>
        <li><a class="<?php echo $currentRoute === 'users.php' ? 'active' : ''; ?>" href="users.php">Users</a></li>
        <li><a class="<?php echo $currentRoute === 'products.php' ? 'active' : ''; ?>" href="products.php">Products</a></li>
        <li><a class="<?php echo $currentRoute === 'contacts.php' ? 'active' : ''; ?>" href="contacts.php">Contacts</a></li>
    </ul>
</nav>