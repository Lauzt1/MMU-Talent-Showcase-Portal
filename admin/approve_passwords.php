<?php
include 'header.php';
include('config.php');
session_start();

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Only admin access
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

$message = "";

// Approve
if (isset($_GET['approve_id'])) {
    $id = $_GET['approve_id'];

    $stmt = $pdo->prepare("SELECT email, new_password FROM password_resets_request WHERE id = ?");
    $stmt->execute([$id]);
    $request = $stmt->fetch();

    if ($request) {
        $plain_password = substr($request['new_password'], 0, 45); // Max length

        $update = $pdo->prepare("UPDATE userdata SET password = ? WHERE email = ?");
        $passwordUpdated = $update->execute([$plain_password, $request['email']]);

        $mark = $pdo->prepare("UPDATE password_resets_request SET status = 'approved' WHERE id = ?");
        $statusUpdated = $mark->execute([$id]);

        if ($passwordUpdated && $statusUpdated) {
            $message = "✅ Password approved and updated.";
        } else {
            $message = "❌ Approval failed.";
        }
    } else {
        $message = "❌ Request not found.";
    }
}

// Reject
if (isset($_GET['reject_id'])) {
    $id = $_GET['reject_id'];

    $reject = $pdo->prepare("UPDATE password_resets_request SET status = 'rejected' WHERE id = ?");
    if ($reject->execute([$id])) {
        $message = "🚫 Request has been rejected.";
    } else {
        $message = "❌ Rejection failed.";
    }
}

// Get all pending requests
$requests = $pdo->query("SELECT * FROM password_resets_request WHERE status = 'pending' ORDER BY requested_at DESC")->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Approve Password Requests</title>
    <link rel="stylesheet" href="../styles/password2.css">
</head>
<body>
    <div class="admin-main">
        <div class="container">
            <h2>Pending Password Reset Requests</h2>

            <?php if ($message): ?>
                <p class="message" style="color: green;"><?= htmlspecialchars($message) ?></p>
            <?php endif; ?>

            <a class="btn-secondary" href="index.php">← Back to Dashboard</a>

            <table class="table table-striped table-bordered">
                <thead>
                    <tr>
                        <th>Email</th>
                        <th>Student ID</th>
                        <th>Reason</th>
                        <th>Requested At</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (count($requests) > 0): ?>
                    <?php foreach ($requests as $row): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['email']) ?></td>
                            <td><?= htmlspecialchars($row['student_id']) ?></td>
                            <td><?= nl2br(htmlspecialchars($row['reason'])) ?></td>
                            <td><?= htmlspecialchars($row['requested_at']) ?></td>
                            <td>
                                <a href="?approve_id=<?= $row['id'] ?>" class="btn-sm btn-info" onclick="return confirm('Approve this request?');">✅ Approve</a>
                                <a href="?reject_id=<?= $row['id'] ?>" class="btn-sm btn-danger" onclick="return confirm('Reject this request?');">❌ Reject</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="5">No pending requests at the moment.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>



