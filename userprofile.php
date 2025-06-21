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

// 3. Handle rating submission (insert or update)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['resource_id'], $_POST['rating'])) {
    $resId         = (int) $_POST['resource_id'];
    $rating        = (int) $_POST['rating'];
    $currentUserId = $_SESSION['user_id'];

    // Check if this user has already rated this resource
    $checkStmt = $pdo->prepare("
        SELECT id
          FROM resource_ratings
         WHERE resource_id = ?
           AND user_id     = ?
    ");
    $checkStmt->execute([$resId, $currentUserId]);

    if ($checkStmt->rowCount() > 0) {
        // Update existing rating
        $upd = $pdo->prepare("
            UPDATE resource_ratings
               SET rating     = ?,
                   created_at = NOW()
             WHERE resource_id = ?
               AND user_id     = ?
        ");
        $upd->execute([$rating, $resId, $currentUserId]);
    } else {
        // Insert new rating
        $ins = $pdo->prepare("
            INSERT INTO resource_ratings (resource_id, user_id, rating)
            VALUES (?, ?, ?)
        ");
        $ins->execute([$resId, $currentUserId, $rating]);
    }

    // Redirect to avoid resubmission & refresh averages
    header("Location: userprofile.php?user_id={$profileId}");
    exit;
}

// 4. Fetch profile user
$stmt = $pdo->prepare("SELECT * FROM userdata WHERE id = ?");
$stmt->execute([$profileId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$user) {
    header('Location: catalogue.php?error=not_found');
    exit;
}

// 5. Prepare category pills
$currentCats = [];
if (!empty($user['talent_category'])) {
    $currentCats = explode(',', $user['talent_category']);
}
$colors = ['#667eea','#764ba2','#6b8e23','#ff6b6b','#48dbfb','#feca57','#ffa502','#1e90ff'];

// 6. Fetch resources with their average & total ratings
$resStmt = $pdo->prepare("
    SELECT
      r.*,
      COALESCE(ROUND(AVG(rr.rating), 1), 0) AS avg_rating,
      COUNT(rr.id)               AS total_ratings
    FROM resources r
    LEFT JOIN resource_ratings rr
      ON rr.resource_id = r.id
    WHERE r.user_id = ?
      AND r.status  = 'approved'
    GROUP BY r.id
    ORDER BY r.uploaded_at DESC
");
$resStmt->execute([$profileId]);
$resources = $resStmt->fetchAll(PDO::FETCH_ASSOC);

// Helper to get email
function getUserEmail($pdo, $userId) {
    $e = $pdo->prepare("SELECT email FROM userdata WHERE id = ?");
    $e->execute([$userId]);
    $row = $e->fetch(PDO::FETCH_ASSOC);
    return $row ? $row['email'] : null;
}
$userEmail = getUserEmail($pdo, $profileId);
?>
<link rel="stylesheet" href="styles/userprofile.css">
<style>
  /* Ensure rating area has breathing room */
  .modal-content { padding: 1rem; }
  .modal-rating { margin: 1rem 0; }
  .rating-input { display: flex; align-items: center; gap: 0.5rem; margin-top: 0.5rem; }
</style>

<main class="userprofile-container">
  <!-- PROFILE CARD -->
  <div class="profile-card">
    <div class="profile-header">
      <img src="<?= !empty($user['profile_pic']) && file_exists(__DIR__.'/'.$user['profile_pic'])
                   ? htmlspecialchars($user['profile_pic'])
                   : 'assets/contributor/icon.jpg' ?>"
           alt="Profile of <?= htmlspecialchars($user['username']) ?>"
           class="profile-pic">
      <h2><?= htmlspecialchars($user['username']) ?></h2>
      <?php if (!empty($user['bio'])): ?>
        <p class="bio"><?= nl2br(htmlspecialchars($user['bio'])) ?></p>
      <?php endif; ?>

      <?php if ($userEmail): ?>
        <p>Contact: <a href="mailto:<?= htmlspecialchars($userEmail) ?>">
          <?= htmlspecialchars($userEmail) ?></a></p>
      <?php endif; ?>

      <div class="category-pills">
        <?php foreach ($currentCats as $cat):
          $color = $colors[array_rand($colors)];
        ?>
          <span class="pill" style="background: <?= $color ?>;">
            <?= htmlspecialchars($cat) ?>
          </span>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

  <!-- RESOURCES GRID -->
  <div class="resources-card">
    <h3>Resources</h3>
    <?php if ($resources): ?>
      <div class="resources-grid">
        <?php foreach ($resources as $r):
          $descFull  = htmlspecialchars($r['description']);
          $descShort = strlen($r['description']) > 100
                     ? nl2br(htmlspecialchars(substr($r['description'],0,100))) . '…'
                     : nl2br($descFull);
        ?>
          <div class="resource-card clickable-card"
               data-id="<?= $r['id'] ?>"
               data-title="<?= htmlspecialchars($r['title']) ?>"
               data-category="<?= htmlspecialchars($r['category']) ?>"
               data-description="<?= $descFull ?>"
               data-uploaded="<?= date('Y-m-d H:i', strtotime($r['uploaded_at'])) ?>"
               data-filepath="<?= htmlspecialchars($r['file_path']) ?>"
               data-mimetype="<?= htmlspecialchars($r['mime_type']) ?>"
               data-avgrating="<?= $r['avg_rating'] ?>"
               data-totalratings="<?= $r['total_ratings'] ?>">
            
            <?php if (strpos($r['mime_type'], 'image/') === 0): ?>
              <img src="<?= $r['file_path'] ?>"
                   alt="<?= htmlspecialchars($r['title']) ?>"
                   class="resource-media">
            <?php elseif (strpos($r['mime_type'], 'video/') === 0): ?>
              <video src="<?= $r['file_path'] ?>"
                     controls class="resource-media"></video>
            <?php elseif (strpos($r['mime_type'], 'audio/') === 0): ?>
              <div class="resource-media-container">
                <img src="assets/misc/audioicon.png"
                     alt="Audio: <?= htmlspecialchars($r['title']) ?>"
                     class="resource-media icon">
              </div>
            <?php else: ?>
              <div class="resource-media-container">
                <img src="assets/misc/fileicon.png"
                     alt="File: <?= htmlspecialchars($r['title']) ?>"
                     class="resource-media icon">
              </div>
            <?php endif; ?>

            <div class="resource-info">
              <h4><?= htmlspecialchars($r['title']) ?></h4>
              <p class="resource-category"><?= htmlspecialchars($r['category']) ?></p>
              <p class="resource-description"><?= $descShort ?></p>
              <small class="resource-date">
                Uploaded: <?= date('Y-m-d H:i', strtotime($r['uploaded_at'])) ?>
              </small>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php else: ?>
      <p class="no-resources">No resources available.</p>
    <?php endif; ?>
  </div>
</main>

<!-- Modal for detailed view -->
<div id="portfolioModal" class="modal" style="display: none;">
  <div class="modal-content">
    <span class="close">&times;</span>
    <div id="modalBody"><!-- Populated via JS --></div>
  </div>
</div>

<script>
const profileId = <?= json_encode($profileId) ?>;

document.addEventListener('DOMContentLoaded', function() {
  const cards    = document.querySelectorAll('.clickable-card');
  const modal    = document.getElementById('portfolioModal');
  const modalBody= document.getElementById('modalBody');
  const closeBtn = modal.querySelector('.close');

  cards.forEach(card => card.addEventListener('click', function(e) {
    e.preventDefault();
    const data = {
      id:           this.dataset.id,
      title:        this.dataset.title,
      category:     this.dataset.category,
      description:  this.dataset.description,
      uploaded:     this.dataset.uploaded,
      filepath:     this.dataset.filepath,
      mimetype:     this.dataset.mimetype,
      avgRating:    this.dataset.avgrating,
      totalRatings: this.dataset.totalratings
    };
    showModal(data);
  }));

  closeBtn.addEventListener('click', closeModal);
  window.addEventListener('click', e => { if (e.target === modal) closeModal(); });
  document.addEventListener('keyup', e => { if (e.key === 'Escape') closeModal(); });

  function showModal(data) {
    let mediaHtml = '';
    if (data.mimetype.startsWith('image/')) {
      mediaHtml = `<img class="modal-media" src="${data.filepath}" alt="${data.title}">`;
    } else if (data.mimetype.startsWith('video/')) {
      mediaHtml = `
        <video class="modal-media" controls>
          <source src="${data.filepath}" type="${data.mimetype}">
        </video>`;
    } else if (data.mimetype.startsWith('audio/')) {
      mediaHtml = `
        <div class="modal-audio-container">
          <img src="assets/misc/audioicon.png" class="modal-audio-icon">
          <audio class="modal-media" controls>
            <source src="${data.filepath}" type="${data.mimetype}">
          </audio>
        </div>`;
    } else {
      mediaHtml = `
        <div class="modal-file-container">
          <img src="assets/misc/fileicon.png" class="modal-file-icon">
          <p><a href="${data.filepath}" target="_blank" class="modal-file-link">
            View/Download File
          </a></p>
        </div>`;
    }

    modalBody.innerHTML = `
      ${mediaHtml}
      <div class="modal-info">
        <h2>${data.title}</h2>
        <span class="modal-category">${data.category}</span>
        <p class="modal-description">${data.description.replace(/\n/g,'<br>')}</p>
        <p class="modal-date">Uploaded: ${data.uploaded}</p>
      </div>
      <div class="modal-rating">
        <p>Average Rating: <strong>${data.avgRating}</strong>
           (${data.totalRatings} ratings)</p>
        <div class="rating-input">
          <label for="ratingSelect">Your Rating:</label>
          <select id="ratingSelect">
            <option value="1">1</option><option value="2">2</option>
            <option value="3">3</option><option value="4">4</option>
            <option value="5">5</option>
          </select>
          <button id="submitRating">Submit</button>
        </div>
      </div>
    `;
    document.getElementById('submitRating').addEventListener('click', function() {
      const rating = document.getElementById('ratingSelect').value;
      const form   = document.createElement('form');
      form.method  = 'POST';
      form.action  = `userprofile.php?user_id=${profileId}`;
      form.innerHTML = 
        `<input type="hidden" name="resource_id" value="${data.id}">` +
        `<input type="hidden" name="rating"      value="${rating}">`;
      document.body.append(form);
      form.submit();
    });

    modal.style.display = 'block';
    document.body.style.overflow = 'hidden';
  }

  function closeModal() {
    modal.style.display = 'none';
    document.body.style.overflow = 'auto';
  }
});
</script>

<?php include 'footer.php'; ?>
