<?php
session_start();
include 'header.php';
require_once 'admin/config.php';

// 1. Require login
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// 2. Get & validate profile ID
$profileId = $_GET['user_id'] ?? null;
if (!$profileId || !is_numeric($profileId)) {
    header('Location: catalogue.php?error=invalid_user');
    exit;
}

// 3. Fetch user
$stmt = $pdo->prepare("SELECT * FROM userdata WHERE id = ?");
$stmt->execute([$profileId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$user) {
    header('Location: catalogue.php?error=not_found');
    exit;
}

// 4. Profile picture or placeholder
if (!empty($user['profile_pic']) && file_exists(__DIR__ . '/' . $user['profile_pic'])) {
    $pic = $user['profile_pic'];
} else {
    $pic = 'assets/contributor/icon.jpg';
}

// 5. Fetch resources (newest first)
$resStmt = $pdo->prepare("
    SELECT * FROM resources 
    WHERE user_id = ? AND status = 'approved'
    ORDER BY uploaded_at DESC
");
$resStmt->execute([$profileId]);
$resources = $resStmt->fetchAll(PDO::FETCH_ASSOC);

// 6. Prepare category pills
$currentCats = [];
if (!empty($user['talent_category'])) {
    $currentCats = explode(',', $user['talent_category']);
}
$colors = ['#667eea','#764ba2','#6b8e23','#ff6b6b','#48dbfb','#feca57','#ffa502','#1e90ff'];
?>
<link rel="stylesheet" href="styles/userprofile.css">

<main class="userprofile-container">

  <!-- PROFILE CARD -->
  <div class="profile-card">
    <div class="profile-header">
      <img src="<?= htmlspecialchars($pic) ?>"
           alt="Profile of <?= htmlspecialchars($user['username']) ?>"
           class="profile-pic">
      <h2><?= htmlspecialchars($user['username']) ?></h2>
      <?php if (!empty($user['bio'])): ?>
        <p class="bio"><?= nl2br(htmlspecialchars($user['bio'])) ?></p>
      <?php endif; ?>

      <div class="category-pills">
        <?php foreach ($currentCats as $cat):
          $color = $colors[array_rand($colors)];
        ?>
          <span class="pill"
                style="background-color: <?= $color ?>;">
            <?= htmlspecialchars($cat) ?>
          </span>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

  <!-- RESOURCES CARD -->
  <div class="resources-card">
    <h3>Resources</h3>
    <?php if ($resources): ?>
      <div class="resources-grid">
        <?php foreach ($resources as $r): ?>
          <div class="resource-card">
            <?php if (strpos($r['mime_type'], 'image/') === 0): ?>
              <img src="<?= htmlspecialchars($r['file_path']) ?>"
                   alt="<?= htmlspecialchars($r['title']) ?>"
                   class="resource-media">
            <?php elseif (strpos($r['mime_type'], 'video/') === 0): ?>
              <video src="<?= htmlspecialchars($r['file_path']) ?>"
                     controls
                     class="resource-media"></video>
            <?php elseif (strpos($r['mime_type'], 'audio/') === 0): ?>
              <a href="<?= htmlspecialchars($r['file_path']) ?>" target="_blank">
                <img src="assets/misc/audioicon.png"
                     alt="Audio: <?= htmlspecialchars($r['title']) ?>"
                     class="resource-media icon">
              </a>
            <?php else: ?>
              <a href="<?= htmlspecialchars($r['file_path']) ?>" target="_blank">
                <img src="assets/misc/fileicon.png"
                     alt="File: <?= htmlspecialchars($r['title']) ?>"
                     class="resource-media icon">
              </a>
            <?php endif; ?>
            <h4><?= htmlspecialchars($r['title']) ?></h4>
          </div>
        <?php endforeach; ?>
      </div>
    <?php else: ?>
      <p class="no-resources">No resources available.</p>
    <?php endif; ?>
  </div>

</main>

<?php include 'footer.php'; ?>
