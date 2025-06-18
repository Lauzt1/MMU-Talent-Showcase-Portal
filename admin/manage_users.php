<?php
session_start();
include('../admin/config.php');
include 'header.php';

// Check if admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

// Fetch all users
$stmt = $pdo->query("SELECT id, username, student_id, email, role, profile_pic FROM userdata WHERE role != 'admin' ORDER BY id ASC");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Users</title>
    <link rel="stylesheet" href="../styles/manage_users.css">
</head>
<body>
<div class="admin-main">
    <div class="container mt-5">
        <h2 class="text-center mb-4">User's Profile Management</h2>
        <a href="index.php" class="btn btn-secondary mb-3">← Back to Dashboard</a>
        <table class="table table-bordered table-striped">
            <thead class="table-dark">
                <tr>
                    <th>ID</th>
                    <th>Username</th>
                    <th>Student ID</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Profile</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($users as $user): ?>
                <tr>
                    <td><?= $user['id'] ?></td>
                    <td><?= htmlspecialchars($user['username']) ?></td>
                    <td><?= htmlspecialchars($user['student_id']) ?></td>
                    <td><?= htmlspecialchars($user['email']) ?></td>
                    <td><?= htmlspecialchars($user['role']) ?></td>
                    <td>
                        <a href="view_user.php?id=<?= $user['id'] ?>" class="btn btn-sm btn-info">View</a>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (count($users) == 0): ?>
                <tr><td colspan="6" class="text-center">No users found.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
</body>
</html>
