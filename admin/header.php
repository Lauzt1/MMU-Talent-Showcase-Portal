<?php
// header.php - Global header include
// Start session for user authentication
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>MMU Talent Showcase Portal</title>
  <link rel="stylesheet" href="../styles/header.css">
</head>
<body>
<header>
  <nav class="main-nav">
    <ul>
      <li><a href="index.php">Home</a></li>
      <li><a href="contributor.php">Contributor</a></li>
    </ul>
    <ul class="user-menu">
      <?php if (isset($_SESSION['user_id'])): ?>
    <?php
          // Dynamically fetch profile_pic for the logged-in user
          require_once 'config.php';
          $stmt = $pdo->prepare("SELECT profile_pic FROM userdata WHERE id = ?");
          $stmt->execute([$_SESSION['user_id']]);
          $row = $stmt->fetch(PDO::FETCH_ASSOC);

          if (
            $row &&
            !empty($row['profile_pic']) &&
            file_exists(__DIR__ . '/' . $row['profile_pic'])
          ) {
            $imgSrc = $row['profile_pic'];
          } else {
            $imgSrc = '../assets/contributor/icon.jpg';
          }
        ?>
        <li>
          <a href="profile.php" class="icon-link">
            <img src="<?= htmlspecialchars($imgSrc) ?>" alt="Profile" class="icon">
          </a>
        </li>
        <a href="logout.php">Logout</a></li>
      <?php else: ?>
        <li><a href="login.php">Login</a></li>
        <li><a href="register.php">Register</a></li>
      <?php endif; ?>
    </ul>
  </nav>
</header>
