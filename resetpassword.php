<?php
include('admin/config.php');
$message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = $_POST['email'];

    $stmt = $pdo->prepare("SELECT id FROM userdata WHERE email = ?");
    $stmt->execute([$email]);

    if ($stmt->rowCount() > 0) {
        $token = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', time() + 3600); // 1 hour expiry

        $pdo->prepare("INSERT INTO password_resets (email, token, expires_at) VALUES (?, ?, ?)")
            ->execute([$email, $token, $expires]);

        $resetLink = "http://localhost/MMU-Talent-Showcase-Portal/newpassword.php?token=" . $token;


        // Simulated email output
        $message = "A password reset link has been generated: <a href='$resetLink'>$resetLink</a>";
    } else {
        $message = "No user found with that email.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Reset Password</title>
    <link rel="stylesheet" href="styles/register.css">
</head>
<body>
    <div class="container">
        <h2 class="reset-title">Request Password Reset</h2>

        <?php if (!empty($message)) : ?>
            <div class="message"><?= $message ?></div>
        <?php endif; ?>

        <form method="POST" class="form-control">
            <label for="email">Email Address</label>
            <input type="email" name="email" id="email" required>

            <button type="submit">Send Reset Link</button>
        </form>
    </div>
</body>
</html>

