<?php
include('admin/config.php');

$message = "";
$success = false;

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = $_POST['email'];
    $student_id = $_POST['student_id'];
    $new_password_plain = substr($_POST['new_password'], 0, 45); // Enforce 45-char max
    $reason = $_POST['reason'];

    // Optional: Hash for future use (not stored here)
    $hashed_password = password_hash($new_password_plain, PASSWORD_DEFAULT);

    // Check if user exists
    $stmt = $pdo->prepare("SELECT id FROM userdata WHERE email = ? AND student_id = ?");
    $stmt->execute([$email, $student_id]);

    if ($stmt->rowCount() > 0) {
        // Store request in password_resets_request table
        $insert = $pdo->prepare("INSERT INTO password_resets_request (email, student_id, new_password, reason) VALUES (?, ?, ?, ?)");
        $insert->execute([$email, $student_id, $new_password_plain, $reason]);

        $message = "✅ Request submitted successfully. Please wait for admin approval.";
        $success = true;
    } else {
        $message = "❌ No user found with the provided email and student ID.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Forgot Password</title>
    <link rel="stylesheet" href="styles/password.css">
</head>
<body>
    <div class="container">
        <h2 class="reset-title">Forgot Password</h2>

        <?php if (!empty($message)): ?>
            <p class="message <?= $success ? 'success' : 'error' ?>"><?= $message ?></p>
        <?php endif; ?>

        <form class="form-control" method="POST">
            <label for="email">Email:</label>
            <input type="email" name="email" id="email" required>

            <label for="student_id">Student ID:</label>
            <input type="text" name="student_id" id="student_id" required>

            <label for="new_password">New Password:</label>
            <input type="text" name="new_password" id="new_password" maxlength="45" required>

            <label for="reason">Reason:</label>
            <textarea name="reason" id="reason" rows="4" cols="50" required></textarea>

            <button type="submit">Submit Request</button>
        </form>

        <p class="message"><a href="login.php">🔙 Return to Login Page</a></p>
    </div>
</body>
</html>




