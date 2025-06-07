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
        <?php for($i=0;$i<5;$i++): ?>
          <div class="placeholder"></div>
        <?php endfor; ?>
      </div>
    </section>

    <!-- Latest Portfolio Updates -->
    <section id="latest-uploads">
      <h2>Latest Portfolio Updates</h2>
      <div class="grid">
        // only changes the folowing section for latest portfolio updates
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
        ?>
            <div class="card">
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

                <?php if (!empty($u['description'])):
                  $desc = htmlspecialchars($u['description']);
                  if (strlen($u['description']) > 100) {
                    $desc = htmlspecialchars(substr($u['description'], 0, 100)) . '…';
                  }
                ?>
                  <p class="description"><?= nl2br($desc) ?></p>
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
