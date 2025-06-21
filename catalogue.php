<?php
// catalogue.php — updated with category pills and page padding
session_start();
include 'header.php';
require_once 'admin/config.php';

// 1. Logged-in check (unchanged)
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// 2. Valid categories & sorts (unchanged except we now filter by userdata.talent_category)
$validCats = [
    'All',
    'Art & Design',
    'Music & Audio',
    'Video & Film',
    'Writing & Documents',
    'others'
];
$validSorts = ['recent', 'top'];

// 3. Selected filters
$selCategory = $_GET['category'] ?? 'All';
if (!in_array($selCategory, $validCats, true)) {
    $selCategory = 'All';
}
$selSort = $_GET['sort'] ?? 'recent';
if (!in_array($selSort, $validSorts, true)) {
    $selSort = 'recent';
}

// 4. Build WHERE clause (filter by user’s talent_category set field)
$whereClause = '';
$params = [];
if ($selCategory !== 'All') {
    $whereClause = "WHERE FIND_IN_SET(?, u.talent_category)";
    $params[] = $selCategory;
}

// 5. Build ORDER BY clause
if ($selSort === 'top') {
    $orderClause = "
      ORDER BY 
        ur.avg_rating DESC,
        ur.total_ratings DESC,
        COALESCE(MAX(r.uploaded_at), '1970-01-01 00:00:00') DESC
    ";
} else {
    $orderClause = "
      ORDER BY
        COALESCE(MAX(r.uploaded_at), '1970-01-01 00:00:00') DESC,
        u.username ASC
    ";
}

if (empty($whereClause)) {
    $whereClause = "WHERE u.role = 'user'";
} else {
    // If whereClause already has WHERE, replace it
    if (stripos($whereClause, 'WHERE') === 0) {
        $whereClause = str_ireplace('WHERE', 'WHERE u.role = \'user\' AND', $whereClause);
    } else {
        // If whereClause doesn't have WHERE, add it
        $whereClause = "WHERE u.role = 'user' AND " . $whereClause;
    }
}

// 6. Main SQL
$sql = "
  SELECT
    u.id,
    u.username,
    u.talent_category,
    u.profile_pic,
    COALESCE(ur.avg_rating, 0) AS avg_rating,
    COALESCE(ur.total_ratings, 0) AS total_ratings,
    MAX(r.uploaded_at) AS latest_upload
  FROM userdata u
  LEFT JOIN user_ratings ur
    ON u.id = ur.user_id
  LEFT JOIN resources r
    ON r.user_id = u.id
    AND r.status = 'approved'
  $whereClause
  GROUP BY u.id, u.username, u.talent_category, u.profile_pic, ur.avg_rating, ur.total_ratings
  $orderClause
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$allUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>
<link rel="stylesheet" href="styles/catalogue.css">

<main class="catalogue-container">
  <!-- Filter pills for categories and sorting -->
  <div class="filter-container">
    <div class="sort-pills">
      <a href="?category=<?= htmlspecialchars($selCategory) ?>&sort=recent" class="pill <?= $selSort === 'recent' ? 'active' : '' ?>">Recent</a>
      <a href="?category=<?= htmlspecialchars($selCategory) ?>&sort=top" class="pill <?= $selSort === 'top' ? 'active' : '' ?>">Top</a>
    </div>
    <div class="category-pills">
      <a href="?category=All&sort=<?= htmlspecialchars($selSort) ?>" class="pill <?= $selCategory === 'All' ? 'active' : '' ?>">All</a>
      <?php foreach ($validCats as $category): ?>
        <?php if ($category !== 'All'): ?>
          <a href="?category=<?= urlencode($category) ?>&sort=<?= htmlspecialchars($selSort) ?>" class="pill <?= $selCategory === $category ? 'active' : '' ?>"><?= htmlspecialchars($category) ?></a>
        <?php endif; ?>
      <?php endforeach; ?>
    </div>
  </div>

  <div class="cards-wrapper">
    <div class="grid">
      <?php if (empty($allUsers)): ?>
        <p class="no-results">No users found.</p>
      <?php endif; ?>

      <?php foreach ($allUsers as $u): ?>
        <a href="userprofile.php?user_id=<?= $u['id'] ?>" class="card user-card">
          <div class="card-media-wrapper">
            <?php $pic = $u['profile_pic'] ?: 'assets/contributor/icon.jpg'; ?>
            <div
              class="profile-pic"
              style="background-image: url('<?= htmlspecialchars($pic, ENT_QUOTES) ?>')"
              aria-label="Profile of <?= htmlspecialchars($u['username'], ENT_QUOTES) ?>"
            ></div>
          </div>
          <div class="meta">
            <h3><?= htmlspecialchars($u['username']) ?></h3>
            <p class="category-label">
              <?= htmlspecialchars($u['talent_category'] ?: 'Uncategorized') ?>
            </p>
            <div class="rating-display">
              <?= number_format((float)$u['avg_rating'], 1) ?>★ (<?= $u['total_ratings'] ?>)
            </div>
          </div>
        </a>
      <?php endforeach; ?>
    </div>
  </div>
</main>

<?php include 'footer.php'; ?>
