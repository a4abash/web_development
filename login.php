<?php
session_start();
define('ABSPATH', __DIR__);
require_once 'config/toastr.php';
require_once 'config/db.php';

if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = 'Invalid request';
    } else {
        $username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_SPECIAL_CHARS);
        $password = $_POST['password'];

        if (!$username || !$password) {
            $error = 'Username and password are required';
        } else {
            try {
                $stmt = $conn->prepare("
                    SELECT u.id, u.password, r.role as role_name, r.id as role_id
                    FROM users u
                    JOIN roles r ON u.role_id = r.id
                    WHERE u.username = ?
                    LIMIT 1
                ");

                if (!$stmt || !$stmt->bind_param("s", $username)) {
                    throw new RuntimeException('Database preparation failed');
                }

                $stmt->execute();
                $result = $stmt->get_result();

                if (!$result) {
                    throw new RuntimeException('Database query failed');
                }

                $user = $result->fetch_assoc();
                $stmt->close();

                if (!$user) {
                    $error = 'Invalid username or password';
                } elseif (!password_verify($password, $user['password'])) {
                    $error = 'Invalid username or password';
                } else {
                    session_regenerate_id(true);

                    $_SESSION['loggedin'] = true;
                    $_SESSION['username'] = $username;
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['role_id'] = $user['role_id'];
                    $_SESSION['role_name'] = $user['role_name'];

                    $_SESSION['toastr'] = [
                        'type' => 'success',
                        'message' => 'Welcome back, ' . $username . '!'
                    ];

                    switch ($user['role_name']) {
                        case 'admin':
                            header("Location: pages/admin/dashboard.php");
                            break;
                        case 'moderator':
                            header("Location: pages/moderator/dashboard.php");
                            break;
                        case 'user':
                            header("Location: index.php");
                            break;
                        default:
                            header("Location: index.php");
                    }
                    exit;
                }
            } catch (Exception $e) {
                error_log("Login error: " . $e->getMessage());
                $error = 'Internal server error';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Ripper Teach & Solutions - Web & App Development, cloud Solutions, and Security Services.">
    <meta name="robots" content="noindex, nofollow">
    <link rel="icon" type="image/png" href="assets/images/logo-2.png">
    <title>Login</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="assets/css/css2.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
        integrity="sha512-..."
        crossorigin="anonymous"
        referrerpolicy="no-referrer">
    <link rel="stylesheet" href="assets/css/toastr.min.css">
</head>

<body>
    <div class="login-container">
        <div class="login-form">
            <h2>Login</h2>
            <?php if (!empty($error)) echo "<p style='color:red;'>$error</p>"; ?>
            <form method="POST" action="" novalidate>
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                <label>Username:</label>
                <input type="text"
                    placeholder="admin"
                    name="username"
                    required
                    value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>"
                    autocomplete="username">
                <label>Password:</label>
                <input type="password"
                    placeholder="12345"
                    name="password"
                    required
                    autocomplete="current-password">
                <button type="submit">Login</button>
            </form>
            <span>Goto </span><a class="goto-register" href="register.php">Register</a>
        </div>
    </div>
    <script src="assets/js/jquery.min.js"></script>
    <script src="assets/js/toastr.min.js"></script>
    <script src="assets/js/script.js" defer></script>
    <?php include 'config/toastr.php'; ?>
</body>

</html>