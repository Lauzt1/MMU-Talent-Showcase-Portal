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

// 5. Function to get user email
function getUserEmail($pdo, $userId) {
    $stmt = $pdo->prepare("SELECT email FROM userdata WHERE id = ?");
    $stmt->execute([$userId]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result ? $result['email'] : null;
}

// 6. Fetch resources (newest first)
$resStmt = $pdo->prepare("
    SELECT * FROM resources 
    WHERE user_id = ? AND status = 'approved'
    ORDER BY uploaded_at DESC
");
$resStmt->execute([$profileId]);
$resources = $resStmt->fetchAll(PDO::FETCH_ASSOC);

// 7. Prepare category pills
$currentCats = [];
if (!empty($user['talent_category'])) {
    $currentCats = explode(',', $user['talent_category']);
}
$colors = ['#667eea','#764ba2','#6b8e23','#ff6b6b','#48dbfb','#feca57','#ffa502','#1e90ff'];

// Get user email
$userEmail = getUserEmail($pdo, $profileId);
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

      <!-- Email display section -->
      <?php if (!empty($userEmail)): ?>
        <div class="contact-info">
          <p class="find-me-here">Find me here: 
            <a href="mailto:<?= htmlspecialchars($userEmail) ?>" class="email-link">
              <?= htmlspecialchars($userEmail) ?>
            </a>
          </p>
        </div>
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
        <?php foreach ($resources as $r): 
          $filePath  = htmlspecialchars($r['file_path']);
          $mimeType  = $r['mime_type'];
          $title     = htmlspecialchars($r['title']);
          $category  = htmlspecialchars($r['category']);
          $uploaded  = date('Y-m-d H:i', strtotime($r['uploaded_at']));
          $fullDesc  = htmlspecialchars($r['description']);
          $shortDesc = strlen($r['description']) > 100 ? htmlspecialchars(substr($r['description'], 0, 100)) . '…' : $fullDesc;
        ?>
          <div class="resource-card clickable-card"
               data-id="<?= $r['id'] ?>"
               data-title="<?= $title ?>"
               data-category="<?= $category ?>"
               data-description="<?= $fullDesc ?>"
               data-uploaded="<?= $uploaded ?>"
               data-filepath="<?= $filePath ?>"
               data-mimetype="<?= htmlspecialchars($mimeType) ?>">
            
            <?php if (strpos($r['mime_type'], 'image/') === 0): ?>
              <img src="<?= htmlspecialchars($r['file_path']) ?>"
                   alt="<?= htmlspecialchars($r['title']) ?>"
                   class="resource-media">
            <?php elseif (strpos($r['mime_type'], 'video/') === 0): ?>
              <video src="<?= htmlspecialchars($r['file_path']) ?>"
                     controls
                     class="resource-media"></video>
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
              <p class="resource-category"><?= $category ?></p>
              <?php if (!empty($r['description'])): ?>
                <p class="resource-description"><?= nl2br($shortDesc) ?></p>
              <?php endif; ?>
              <small class="resource-date">
                Uploaded: <?= $uploaded ?>
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
    <div id="modalBody">
      <!-- Content will be populated by JavaScript -->
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
  const cards = document.querySelectorAll('.clickable-card');
  const modal = document.getElementById('portfolioModal');
  const modalBody = document.getElementById('modalBody');
  const closeBtn = document.querySelector('.close');

  // Add click event to each card
  cards.forEach(card => {
    card.addEventListener('click', function(e) {
      // Prevent default behavior for links and videos
      e.preventDefault();
      
      const data = {
        title: this.dataset.title,
        category: this.dataset.category,
        description: this.dataset.description,
        uploaded: this.dataset.uploaded,
        filepath: this.dataset.filepath,
        mimetype: this.dataset.mimetype
      };
      
      showModal(data);
    });
    
    // Add cursor pointer to indicate clickability
    card.style.cursor = 'pointer';
  });

  // Close modal events
  closeBtn.addEventListener('click', closeModal);
  window.addEventListener('click', function(event) {
    if (event.target === modal) {
      closeModal();
    }
  });

  // Close modal with Escape key
  document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape' && modal.style.display === 'block') {
      closeModal();
    }
  });

  function showModal(data) {
    let mediaHtml = '';
    
    // Generate media HTML based on type
    if (data.mimetype.startsWith('image/')) {
      mediaHtml = `<img class="modal-media" src="${data.filepath}" alt="${data.title}">`;
    } else if (data.mimetype.startsWith('video/')) {
      mediaHtml = `
        <video class="modal-media" controls>
          <source src="${data.filepath}" type="${data.mimetype}">
          Your browser does not support the video tag.
        </video>`;
    } else if (data.mimetype.startsWith('audio/')) {
      mediaHtml = `
        <div class="modal-audio-container">
          <img src="assets/misc/audioicon.png" alt="Audio file" class="modal-audio-icon">
          <audio class="modal-media" controls style="width: 100%; margin-top: 10px;">
            <source src="${data.filepath}" type="${data.mimetype}">
            Your browser does not support the audio tag.
          </audio>
        </div>`;
    } else {
      mediaHtml = `
        <div class="modal-file-container">
          <img src="assets/misc/fileicon.png" alt="File" class="modal-file-icon">
          <p><a href="${data.filepath}" target="_blank" class="modal-file-link">View/Download File</a></p>
        </div>`;
    }

    modalBody.innerHTML = `
      ${mediaHtml}
      <div class="modal-info">
        <h2>${data.title}</h2>
        <span class="modal-category">${data.category}</span>
        <p class="modal-description">${data.description.replace(/\n/g, '<br>')}</p>
        <p class="modal-date">Uploaded: ${data.uploaded}</p>
      </div>
    `;
    
    modal.style.display = 'block';
    document.body.style.overflow = 'hidden'; // Prevent background scrolling
  }

  function closeModal() {
    modal.style.display = 'none';
    document.body.style.overflow = 'auto'; // Restore scrolling
  }
});
</script>

<?php include 'footer.php'; ?>
