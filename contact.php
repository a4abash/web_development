<?php
session_start();
require 'config/auth.php';
require_once 'config/db.php';
include 'includes/header.php';

$name = '';
$email = '';
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $message = trim($_POST['message'] ?? '');

    if (strlen($name) < 3 || !filter_var($email, FILTER_VALIDATE_EMAIL) || empty($message)) {
        $_SESSION['toastr'] = [
            'type' => 'error',
            'message' => 'Please fill all fields correctly.'
        ];
    } else {
        $stmt = $conn->prepare("INSERT INTO contacts (name, email, message) VALUES (?, ?, ?)");
        if (!$stmt) {
            error_log("Prepare error: " . $conn->error);
            $_SESSION['toastr'] = [
                'type' => 'error',
                'message' => 'Server error. Try again later.'
            ];
        } else {
            $stmt->bind_param("sss", $name, $email, $message);
            if ($stmt->execute()) {
                $_SESSION['toastr'] = [
                    'type' => 'success',
                    'message' => 'Thank you! Your message has been sent.'
                ];
                $stmt->close();
                
                $name = '';
                $email = '';
                $message = '';
                
                header("Location: " . $_SERVER['REQUEST_URI']);
                exit;
            } else {
                error_log("Execute error: " . $stmt->error);
                $_SESSION['toastr'] = [
                    'type' => 'error',
                    'message' => 'Something went wrong. Please try again.'
                ];
            }
        }
    }
}
?>

<section class="contact-section">
    <h1 style="text-align:center;">Contact Us</h1>

    <form method="POST" action="" aria-label="Contact Form">
        <label for="name">Name:</label>
        <input type="text" id="name" name="name" 
            placeholder="Your Full Name"
            value="<?= htmlspecialchars($name) ?>">

        <label for="email">Email:</label>
        <input type="email" id="email" name="email"
            placeholder="you@example.com"
            value="<?= htmlspecialchars($email) ?>">

        <label for="message">Message:</label>
        <textarea id="message" name="message" rows="5"
            placeholder="Your Message"><?= htmlspecialchars($message) ?></textarea>

        <button type="submit">Send Message</button>
    </form>

    <div class="contact-info">
        <a href="mailto:a4abash@gmail.com"><i class="fas fa-envelope"></i> Email Us</a><br>
        <a href="https://www.facebook.com/a4abash" target="_blank" aria-label="Facebook">
            <i class="fab fa-facebook"></i> a4abash</a><br>
        <p><i class="fa fa-phone"></i> <a href="tel:0456598567">Contact us</a></p>
    </div>
</section>

<?php include 'includes/footer.php'; ?>