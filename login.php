<?php
session_start();
define('ABSPATH', __DIR__);
include 'config/toastr.php';
include 'config/db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $stmt = $conn->prepare("SELECT id, password FROM users WHERE username=?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $stmt->bind_result($id, $hashed_password);
        $stmt->fetch();

        if (password_verify($password, $hashed_password)) {
            $_SESSION['loggedin'] = true;
            $_SESSION['username'] = $username;

            $_SESSION['toastr'] = [
                'type' => 'success',
                'message' => 'Welcome back, ' . $username . '!'
            ];

            header("Location: index.php");
            exit;
        } else {
            $error = "Invalid password!";
        }
    } else {
        $error = "Something went wrong!";
    }
}
?>

<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="description" content="Ripper Teach & Solutions - Web & App Development, cloud Solutions, and Security Services.">
    <link rel="icon" type="image/png" href="assets/images/logo-2.png">
    <title>Login</title>
    <link rel="stylesheet" href="assets/css/style.css" />
    <link href="assets/css/css2.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" integrity="sha512-..." crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="assets/css/toastr.min.css" />

</head>

<body>
    <div class="login-container">
        <div class="login-form">
            <h2>Login</h2>
            <?php if (!empty($error)) echo "<p style='color:red;'>$error</p>"; ?>
            <form method="POST" action="">
                <label>Username:</label>
                <input type="text" placeholder="admin" name="username" required value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
                <label>Password:</label>
                <input type="password" placeholder="12345" name="password" required>
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