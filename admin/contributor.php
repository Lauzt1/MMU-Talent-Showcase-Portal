<?php
// admin/contributor.php
// Start session and include header
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include 'header.php';

// Predefined contributors (all images moved into assets/contributor)
$contributors = [
    ['img' => '../assets/contributor/enxin.png', 'name' => 'Hoo Enn Xin', 'id' => '1231302621'],
    ['img' => '../assets/contributor/zithao.png', 'name' => 'Lau Zi Thao', 'id' => '1211102370'],
    ['img' => '../assets/contributor/weijoe.png', 'name' => 'Teng Wei Joe', 'id' => '1211102797'],
    ['img' => '../assets/contributor/xypp.jpg', 'name' => 'Lim Xin Yuen', 'id' => '1211108007'],
];
?>
<link rel="stylesheet" href="../styles/contributor.css">
<main class="contributors-container">
  <h1>Contributors</h1>
  <div class="contributors-grid">
    <?php foreach ($contributors as $person): ?>
      <div class="contributor-card">
        <img src="<?= htmlspecialchars($person['img']) ?>" alt="<?= htmlspecialchars($person['name']) ?>">
        <p class="contributor-name"><?= htmlspecialchars($person['name']) ?></p>
        <p class="student-id"><?= htmlspecialchars($person['id']) ?></p>
      </div>
    <?php endforeach; ?>
  </div>
</main>

<?php include 'footer.php'; ?>
