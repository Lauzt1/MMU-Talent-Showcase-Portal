<?php
include('admin/config.php');
$token = $_GET['token'] ?? '';
$message = "";

// Check token exists
$stmt = $pdo->prepare("SELECT * FROM password_resets WHERE token = ?");
$stmt->execute([$token]);
$resetData = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$resetData) {
    die("Invalid or expired token.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_pass = $_POST['password'] ?? '';
    $confirm_pass = $_POST['confirm_password'] ?? '';

    if ($new_pass !== $confirm_pass) {
        $message = "Passwords do not match.";
    } elseif (strlen($new_pass) < 6) {
        $message = "Password must be at least 6 characters.";
    } else {
        // Update password in userdata
        $pdo->prepare("UPDATE userdata SET password = ? WHERE email = ?")
            ->execute([$new_pass, $resetData['email']]);

        // Delete the token
        $pdo->prepare("DELETE FROM password_resets WHERE token = ?")->execute([$token]);

        $message = "Password updated successfully. <a href='login.php'>Login here</a>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Set New Password</title>
    <link rel="stylesheet" href="styles/resetpassword.css">
</head>
<body>
    <div class="container">
        <h2 class="reset-title">Set Your New Password</h2>

        <?php if (!empty($message)) : ?>
            <div class="message"><?php echo $message; ?></div>
        <?php endif; ?>

        <form method="POST" class="form-control">
            <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">

            <label for="password">New Password:</label>
            <input type="password" id="password" name="password" required>

            <label for="confirm_password">Confirm Password:</label>
            <input type="password" id="confirm_password" name="confirm_password" required>

            <input type="submit" value="Reset Password">
        </form>
    </div>
</body>
</html>



