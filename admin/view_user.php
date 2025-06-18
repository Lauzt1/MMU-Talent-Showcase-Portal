

<?php
session_start();
include('../admin/config.php');

// Check if user is logged in and is admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

// Get user ID from query
$userId = $_GET['id'] ?? 0;

// Fetch user
$stmt = $pdo->prepare("SELECT * FROM userdata WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();

if (!$user) {
    echo "User not found.";
    exit();
}

// Handle update form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $updatedUsername = $_POST['username'] ?? '';
    $updatedEmail = $_POST['email'] ?? '';
    $updatedStudentId = $_POST['student_id'] ?? '';
    $updatedRole = $_POST['role'] ?? '';
    $updatedBio = $_POST['bio'] ?? '';

    $updateStmt = $pdo->prepare("UPDATE userdata SET username = ?, email = ?, student_id = ?, role = ?, bio = ? WHERE id = ?");
    $updateStmt->execute([$updatedUsername, $updatedEmail, $updatedStudentId, $updatedRole, $updatedBio, $userId]);

    $_SESSION['success'] = true;
    header("Location: view_user.php?id=$userId");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>View Profile</title>
    <link rel="stylesheet" href="../styles/view_user.css">
</head>
<body>
<?php include 'header.php'; ?>

<?php if (isset($_SESSION['success'])): ?>
<script>alert('Changes saved successfully!');</script>
<?php unset($_SESSION['success']); ?>
<?php endif; ?>

<div class="profile-container">
    <div class="profile-card">
        <div class="profile-left">
            <div class="avatar">
                <?php
                $defaultPic = '../assets/contributor/icon.jpg';
                $profilePic = $defaultPic;
                if (!empty($user['profile_pic'])) {
                    $relativePath = '../' . ltrim($user['profile_pic'], '/');
                    if (file_exists($relativePath)) {
                        $profilePic = $relativePath;
                    }
                }
                ?>
                <img src="<?= htmlspecialchars($profilePic) ?>" alt="Profile Picture">
            </div>
            <h2><?= htmlspecialchars($user['username']) ?></h2>
        </div>

        <form method="POST" class="profile-right">
            <div class="profile-field">
                <label>Username</label>
                <input type="text" name="username" value="<?= htmlspecialchars($user['username']) ?>">
            </div>

            <div class="profile-field">
                <label>Email</label>
                <input type="email" name="email" value="<?= htmlspecialchars($user['email']) ?>">
            </div>

            <div class="profile-field">
                <label>Student ID</label>
                <input type="text" name="student_id" value="<?= htmlspecialchars($user['student_id']) ?>">
            </div>

            <div class="profile-field">
                <label>Role</label>
                <select name="role">
                    <option value="user" <?= $user['role'] === 'user' ? 'selected' : '' ?>>User</option>
                    <option value="admin" <?= $user['role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
                </select>
            </div>

            <div class="profile-field">
                <label>Bio</label>
                <textarea name="bio" rows="4"><?= htmlspecialchars($user['bio']) ?></textarea>
            </div>

            <div class="back-button">
                <button type="submit">Save Changes</button>
                <a href="manage_users.php">← Back to User List</a>
            </div>
        </form>
    </div>
</div>
</body>
</html>

