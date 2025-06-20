<?php include 'header.php'; ?>
<link rel="stylesheet" href="styles/index.css">

<main class="container">
  <div class="content">
    <!-- Hero/Search -->
    <section id="search-hero">
      <h1>Welcome to MMU Talent Showcase Portal</h1>
      <form action="catalogue.php" method="get">
        <input type="text" name="q" placeholder="Search talents..." required>
        <button type="submit">🔍</button>
      </form>
    </section>

    <!-- Featured Talents -->
    <section id="featured-talents">
      <h2>Featured Talents</h2>
      <div class="grid">
        <?php
        // --- Pull the top 4 talents by avg_rating & total_ratings ---
        include 'admin/config.php';
        try {
          $stmt = $pdo->prepare("
            SELECT
              u.id,
              u.username,
              u.profile_pic,
              COALESCE(ur.avg_rating, 0) AS avg_rating,
              COALESCE(ur.total_ratings, 0) AS total_ratings
            FROM userdata u
            LEFT JOIN user_ratings ur
              ON u.id = ur.user_id
            ORDER BY
              ur.avg_rating DESC,
              ur.total_ratings DESC
            LIMIT 4
          ");
          $stmt->execute();
          $featured = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
          $featured = [];
        }

        if (empty($featured)):
          // fallback placeholders
          for ($i = 0; $i < 4; $i++): ?>
            <div class="placeholder"></div>
          <?php endfor;
        else:
          foreach ($featured as $t):
            $pic   = $t['profile_pic'] ?: 'assets/contributor/icon.jpg';
            $name  = htmlspecialchars($t['username'], ENT_QUOTES);
            $avg   = number_format((float)$t['avg_rating'], 1);
            $total = (int)$t['total_ratings'];
        ?>
            <a href="userprofile.php?user_id=<?= $t['id'] ?>"
               class="card talent-card clickable-card">
              <img class="card-media"
                   src="<?= htmlspecialchars($pic, ENT_QUOTES) ?>"
                   alt="Profile of <?= $name ?>">
              <div class="meta">
                <h3><?= $name ?></h3>
                <div class="rating-display">
                  <?= $avg ?>★ (<?= $total ?>)
                </div>
              </div>
            </a>
        <?php
          endforeach;
        endif;
        ?>
      </div>
    </section>

<!-- Latest Portfolio Updates -->
<section id="latest-uploads">
  <h2>Latest Portfolio Updates</h2>
  <div class="grid">
    <?php
    // --- Pull the 4 most recently uploaded resources ---
    include 'admin/config.php';

    try {
      $stmt = $pdo->prepare("
        SELECT r.*
        FROM resources r
        ORDER BY r.uploaded_at DESC
        LIMIT 4
      ");
      $stmt->execute();
      $latestUploads = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
      // If something goes wrong, show 4 placeholders instead
      $latestUploads = [];
    }

    if (empty($latestUploads)):
      // If no uploads exist, fall back to placeholders
      for ($i = 0; $i < 4; $i++): ?>
        <div class="placeholder"></div>
      <?php endfor;
    else:
      foreach ($latestUploads as $u):
        // Determine which media/type to show
        $filePath  = htmlspecialchars($u['file_path']);
        $mimeType  = $u['mime_type'];
        $title     = htmlspecialchars($u['title']);
        $category  = htmlspecialchars($u['category']);
        $uploaded  = date('Y-m-d H:i', strtotime($u['uploaded_at']));
        $fullDesc  = htmlspecialchars($u['description']);
        $shortDesc = strlen($u['description']) > 100 ? htmlspecialchars(substr($u['description'], 0, 100)) . '…' : $fullDesc;
    ?>
        <div class="card clickable-card" 
             data-id="<?= $u['id'] ?>"
             data-title="<?= $title ?>"
             data-category="<?= $category ?>"
             data-description="<?= $fullDesc ?>"
             data-uploaded="<?= $uploaded ?>"
             data-filepath="<?= $filePath ?>"
             data-mimetype="<?= htmlspecialchars($mimeType) ?>">
          
          <!-- 1) Media (image / video / audio / file‐icon) -->
          <?php if (strpos($mimeType, 'image/') === 0): ?>
            <img class="card-media" src="<?= $filePath ?>" alt="<?= $title ?>">
                         
          <?php elseif (strpos($mimeType, 'video/') === 0): ?>
            <video class="card-media" controls>
              <source src="<?= $filePath ?>" type="<?= htmlspecialchars($mimeType) ?>">
              Your browser does not support the video tag.
            </video>
                         
          <?php elseif (strpos($mimeType, 'audio/') === 0): ?>
            <a href="<?= $filePath ?>" target="_blank">
              <img
                class="card-media icon"
                src="assets/misc/audioicon.png"
                alt="Audio: <?= $title ?>"
              >
            </a>
                         
          <?php elseif ($mimeType === 'application/pdf' || $mimeType === 'text/plain'): ?>
            <a href="<?= $filePath ?>" target="_blank">
              <img
                class="card-media icon"
                src="assets/misc/fileicon.png"
                alt="File: <?= $title ?>"
              >
            </a>
          <?php endif; ?>
           
          <!-- 2) Meta -->
          <div class="meta">
            <h3><?= $title ?></h3>
            <p class="category-label"><?= $category ?></p>
             
            <?php if (!empty($u['description'])): ?>
              <p class="description"><?= nl2br($shortDesc) ?></p>
            <?php endif; ?>
             
            <small class="upload-date">
              Uploaded: <?= $uploaded ?>
            </small>
          </div>
        </div>
    <?php
      endforeach;
    endif;
    ?>
  </div>
</section>

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
    card.addEventListener('click', function() {
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
  });

  // Close modal events
  closeBtn.addEventListener('click', closeModal);
  window.addEventListener('click', function(event) {
    if (event.target === modal) {
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
        <audio class="modal-media" controls style="width: 100%;">
          <source src="${data.filepath}" type="${data.mimetype}">
          Your browser does not support the audio tag.
        </audio>`;
    } else {
      mediaHtml = `<p><a href="${data.filepath}" target="_blank">View/Download File</a></p>`;
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
  </div>

  <!-- Sidebar -->
<aside id="announcements">
    <h2>Announcements</h2>
    <ul>
        <?php 
        include('admin/config.php');
        try {
            $stmt = $pdo->query("SELECT * FROM announcements ORDER BY created_date DESC LIMIT 5");
            $announcements = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($announcements as $announcement): 
        ?>
            <li>
                <h4><?php echo htmlspecialchars($announcement['title']); ?></h4>
                <p><?php echo htmlspecialchars(substr($announcement['content'], 0, 100)); ?>
                <?php if (strlen($announcement['content']) > 100) echo '...'; ?></p>
                <small><?php echo date('M j, Y', strtotime($announcement['created_date'])); ?></small>
            </li>
        <?php 
            endforeach;
        } catch(PDOException $e) {
            echo '<li>Error loading announcements</li>';
        }
        ?>
    </ul>
</aside>
  </main>

<?php include 'footer.php'; ?>
