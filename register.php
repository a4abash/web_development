<?php
define('ABSPATH', __DIR__);
session_start();
require_once 'config/db.php';

$sql = "SELECT id, role FROM roles WHERE role != 'admin'";
$result = $conn->query($sql);
$roles = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $roles[] = $row;
    }
}
$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = trim($_POST['name']);
    $username = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $_POST['name']));
    $email = trim($_POST['email']);
    $role_id = intval($_POST['role_id']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $check = $conn->prepare("SELECT id FROM users WHERE username=?");
    $check->bind_param("s", $username);
    $check->execute();
    $check->store_result();

    if ($check->num_rows > 0) {
        $error = "Username already taken!";
    } else {
        $stmt = $conn->prepare("INSERT INTO users (username, name, email,role_id, password) VALUES (?, ?,?, ?, ?)");
        $stmt->bind_param("sssss", $username, $name, $email, $role_id,  $password);
        if ($stmt->execute()) {
            $_SESSION['loggedin'] = true;
            $_SESSION['username'] = $username;

            $_SESSION['toastr'] = [
                'type' => 'success',
                'message' => 'Registered successful! Welcome, ' . $username . '!'
            ];

            header("Location: index.php");
            exit;
        } else {
            $error = "Something went wrong. Try again!";
        }
    }
}
$conn->close();
?>

<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="description" content="Ripper Teach & Solutions - Web & App Development, cloud Solutions, and Security Services.">
    <link rel="icon" type="image/png" href="assets/images/logo-2.png">
    <title>Register</title>
    <link rel="stylesheet" href="assets/css/style.css" />
    <link href="assets/css/css2.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" integrity="sha512-..." crossorigin="anonymous" referrerpolicy="no-referrer" />

</head>

<body>
    <div class="login-container">
        <div class="login-form">
            <h2>Register</h2>
            <?php if (!empty($error)) echo "<p style='color:red;'>$error</p>"; ?>
            <form method="POST" action="">
                <!-- <label>Username:</label>
                <input type="text" placeholder="admin" name="username" required value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>"> -->
                <label>Name:</label>
                <input type="text" placeholder="Admin Lal" name="name" required value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>">
                <label>Email:</label>
                <input type="email" placeholder="admin@gmail.com" name="email" required value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                <label for="role">Role</label>
                <select name="role_id" id="role" required>
                    <option value="" disabled selected>Select Role</option>
                    <?php foreach ($roles as $role): ?>
                        <option value="<?= $role['id']; ?>"><?= ucfirst($role['role']); ?></option>
                    <?php endforeach; ?>
                </select>
                <label>Password:</label>
                <input type="password" placeholder="12345" name="password" required>
                <button type="submit">Register</button>
            </form>
            <span>Goto </span><a style="text-decoration:none" class="goto-login" href="login.php">Login</a>
        </div>
    </div>
</body>

</html>