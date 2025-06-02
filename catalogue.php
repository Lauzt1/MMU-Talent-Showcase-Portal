<?php
// catalogue.php
session_start();
include 'header.php';
require_once 'admin/config.php';

// 1. Only‐logged‐in check (unchanged)
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// 2. Handle incoming rating POST (unchanged)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['resource_id'], $_POST['rating'])) {
    $resId = (int) $_POST['resource_id'];
    $rating = (int) $_POST['rating'];
    $userId = $_SESSION['user_id'];
    if ($resId > 0 && $rating >= 1 && $rating <= 5) {
        $stmt = $pdo->prepare("
            INSERT INTO resource_ratings (resource_id, user_id, rating)
            VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE rating = VALUES(rating), created_at = CURRENT_TIMESTAMP
        ");
        $stmt->execute([$resId, $userId, $rating]);
    }
    // Redirect back with preserved GET params
    $redirectUrl = 'catalogue.php';
    $qs = [];
    if (!empty($_GET['category'])) {
        $qs[] = 'category=' . urlencode($_GET['category']);
    }
    if (!empty($_GET['sort'])) {
        $qs[] = 'sort=' . urlencode($_GET['sort']);
    }
    if ($qs) {
        $redirectUrl .= '?' . implode('&', $qs);
    }
    header("Location: $redirectUrl");
    exit;
}

// 3. Valid categories and sorts (unchanged)
$validCats = [
    'All',
    'Art & Design',
    'Music & Audio',
    'Video & Film',
    'Writing & Documents',
    'others'
];
$validSorts = ['recent', 'top'];

// 4. Selected filters (unchanged)
$selCategory = $_GET['category'] ?? 'All';
if (!in_array($selCategory, $validCats, true)) {
    $selCategory = 'All';
}
$selSort = $_GET['sort'] ?? 'recent';
if (!in_array($selSort, $validSorts, true)) {
    $selSort = 'recent';
}

// 5. Build SQL (unchanged)
$whereClause = '';
$params = [];
if ($selCategory !== 'All') {
    $whereClause = 'WHERE r.category = ?';
    $params[] = $selCategory;
}
if ($selSort === 'top') {
    $orderClause = 'ORDER BY avg_rating DESC, total_ratings DESC, r.uploaded_at DESC';
} else {
    $orderClause = 'ORDER BY r.uploaded_at DESC';
}

$sql = "
    SELECT
      r.*,
      IFNULL(ROUND(AVG(rr.rating),1), 0) AS avg_rating,
      COUNT(rr.id) AS total_ratings
    FROM resources r
    LEFT JOIN resource_ratings rr
      ON r.id = rr.resource_id
    $whereClause
    GROUP BY r.id
    $orderClause
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$allUploads = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<link rel="stylesheet" href="styles/catalogue.css">

<main class="catalogue-container">
  <!-- ───────────────────────────────────────────────────────────────────────────
       (A) CARDS WRAPPER (centered, width=80%)
       ─────────────────────────────────────────────────────────────────────────── -->
  <div class="cards-wrapper">
    <!-- 1. Top Pills + Sort dropdown -->
    <ul class="pill-container">
      <?php foreach ($validCats as $cat): ?>
        <?php
          $link = 'catalogue.php?category=' . urlencode($cat) . '&sort=' . urlencode($selSort);
          $isActive = $selCategory === $cat ? 'active' : '';
        ?>
        <li>
          <a href="<?= $link ?>" class="<?= $isActive ?>">
            <?= htmlspecialchars($cat) ?>
          </a>
        </li>
      <?php endforeach; ?>

      <!-- 2. Sort‐by dropdown, pushed to right via margin-left:auto -->
      <li class="sort-li">
        <select onchange="location.href = this.value;">
          <option
            value="catalogue.php?category=<?= urlencode($selCategory) ?>&sort=recent"
            <?= $selSort === 'recent' ? 'selected' : '' ?>
          >Sort by: Most Recent</option>
          <option
            value="catalogue.php?category=<?= urlencode($selCategory) ?>&sort=top"
            <?= $selSort === 'top' ? 'selected' : '' ?>
          >Sort by: Top Rated</option>
        </select>
      </li>
    </ul>

    <!-- 3. Grid of cards -->
    <div class="grid">
      <?php if (empty($allUploads)): ?>
        <p class="no-results">No uploads found.</p>
      <?php endif; ?>

      <?php foreach ($allUploads as $u): ?>
        <div class="card">
          <!-- Media (image / video / icon) -->
          <?php if (strpos($u['mime_type'], 'image/') === 0): ?>
            <img
              class="card-media"
              src="<?= htmlspecialchars($u['file_path']) ?>"
              alt="<?= htmlspecialchars($u['title']) ?>"
            >
          
          <?php elseif (strpos($u['mime_type'], 'video/') === 0): ?>
            <video class="card-media" controls>
              <source
                src="<?= htmlspecialchars($u['file_path']) ?>"
                type="<?= htmlspecialchars($u['mime_type']) ?>"
              >
              Your browser does not support the video tag.
            </video>
          
          <?php elseif (strpos($u['mime_type'], 'audio/') === 0): ?>
            <a href="<?= htmlspecialchars($u['file_path']) ?>" target="_blank">
              <img
                class="card-media icon"
                src="assets/misc/audioicon.png"
                alt="Audio: <?= htmlspecialchars($u['title']) ?>"
              >
            </a>
          
          <?php elseif ($u['mime_type'] === 'application/pdf' || $u['mime_type'] === 'text/plain'): ?>
            <a href="<?= htmlspecialchars($u['file_path']) ?>" target="_blank">
              <img
                class="card-media icon"
                src="assets/misc/fileicon.png"
                alt="File: <?= htmlspecialchars($u['title']) ?>"
              >
            </a>
          <?php endif; ?>

          <!-- Meta content -->
          <div class="meta">
            <h3><?= htmlspecialchars($u['title']) ?></h3>
            <p class="category-label"><?= htmlspecialchars($u['category']) ?></p>

            <?php if (!empty($u['description'])): ?>
              <?php
                $desc = htmlspecialchars($u['description']);
                if (strlen($u['description']) > 100) {
                  $desc = htmlspecialchars(substr($u['description'], 0, 100)) . '…';
                }
              ?>
              <p class="description"><?= nl2br($desc) ?></p>
            <?php endif; ?>

            <small class="upload-date">
              Uploaded: <?= date('Y-m-d H:i', strtotime($u['uploaded_at'])) ?>
            </small>

            <!-- Rating display -->
            <div class="rating-display">
              <?= number_format((float)$u['avg_rating'], 1) ?>★ (<?= $u['total_ratings'] ?>)
            </div>

            <!-- Rating form (only if not uploader) -->
            <?php if ($u['user_id'] !== $_SESSION['user_id']): ?>
              <form method="post" class="rating-form">
                <input type="hidden" name="resource_id" value="<?= $u['id'] ?>">
                <select name="rating" required>
                  <option value="" disabled selected>Rate…</option>
                  <option value="1">1★</option>
                  <option value="2">2★</option>
                  <option value="3">3★</option>
                  <option value="4">4★</option>
                  <option value="5">5★</option>
                </select>
                <button type="submit">Submit</button>
              </form>
            <?php endif; ?>

          </div> <!-- /.meta -->
        </div> <!-- /.card -->
      <?php endforeach; ?>
    </div> <!-- /.grid -->
  </div> <!-- /.cards-wrapper -->
</main>

<?php include 'footer.php'; ?>
