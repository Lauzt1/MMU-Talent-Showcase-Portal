<?php
// resource_sharing.php
include 'header.php';
require_once 'admin/config.php';

// session_start() already in header.php

// Only allow logged-in users
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $allowed = [
        'image/jpeg','image/png','image/gif',
        'video/mp4','video/quicktime','video/webm'
    ];

    // Validate title
    $title = trim($_POST['title'] ?? '');
    if ($title === '' || strlen($title) > 255) {
        $msg = '<div class="error-msg">Please enter a title (max 255 chars).</div>';
    }
    // Check file upload
    elseif (isset($_FILES['resource']) && $_FILES['resource']['error'] === UPLOAD_ERR_OK) {
        $tmp  = $_FILES['resource']['tmp_name'];
        $mime = mime_content_type($tmp);
        $size = $_FILES['resource']['size'];

        if (in_array($mime, $allowed) && $size <= 50 * 1024 * 1024) {
            // Ensure the directory exists
            $uploadDir = __DIR__ . '/uploads/resources';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            $ext      = pathinfo($_FILES['resource']['name'], PATHINFO_EXTENSION);
            $safeName = uniqid('res_', true) . ".$ext";
            $destFull = $uploadDir . '/' . $safeName;
            $filePath = 'uploads/resources/' . $safeName;  // relative URL

            if (move_uploaded_file($tmp, $destFull)) {
                $desc = trim($_POST['description'] ?? null);
                $stmt = $pdo->prepare("
                    INSERT INTO resources
                      (user_id, file_name, file_path, mime_type, title, description)
                    VALUES (?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $_SESSION['user_id'],
                    $_FILES['resource']['name'],
                    $filePath,
                    $mime,
                    $title,
                    $desc
                ]);
                $msg = '<div class="success-msg">Upload successful & sent for review.</div>';
            } else {
                $msg = '<div class="error-msg">Could not move uploaded file.</div>';
            }
        } else {
            $msg = '<div class="error-msg">Invalid file type or file too large (max 50 MB).</div>';
        }
    }
}

// Fetch this user’s uploads
$stmt = $pdo->prepare("
    SELECT * FROM resources
     WHERE user_id = ?
     ORDER BY uploaded_at DESC
");
$stmt->execute([$_SESSION['user_id']]);
$uploads = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<link rel="stylesheet" href="styles/resourcesharing.css">

<main class="container">
  <div class="content">
    <section id="upload-area">
      <h1>Share Your Work</h1>
      <form action="" method="post" enctype="multipart/form-data">
        <div class="form-group">
          <label for="title">Title</label>
          <input
            type="text" id="title" name="title"
            required maxlength="255"
            placeholder="Enter a short title">
        </div>

        <div class="form-group">
          <label for="description">Description</label>
          <textarea
            id="description" name="description"
            rows="4" placeholder="Optional: add details about your work"></textarea>
        </div>

        <div class="form-group">
          <label for="resource">Select File</label>
          <input
            type="file" id="resource" name="resource"
            accept=".jpg,.jpeg,.png,.gif,.mp4,.mov,.webm"
            required>
        </div>

        <button class="upload-btn" type="submit">Upload</button>
      </form>

      <?= $msg ?>
    </section>

    <?php if ($uploads): ?>
    <section id="my-uploads">
      <h2 style="margin-top:40px">My Uploads</h2>
      <div class="grid">
        <?php foreach ($uploads as $u): ?>
        <div class="card">
          <?php if (str_starts_with($u['mime_type'], 'image')): ?>
            <img src="<?= htmlspecialchars($u['file_path']) ?>" alt="">
          <?php else: ?>
            <video src="<?= htmlspecialchars($u['file_path']) ?>" muted></video>
          <?php endif; ?>

          <div class="meta">
            <h3><?= htmlspecialchars($u['title']) ?></h3>
            <?php if ($u['description']): ?>
            <p>
              <?= htmlspecialchars(
                    strlen($u['description']) > 80
                      ? substr($u['description'], 0, 80) . '…'
                      : $u['description']
                  ) ?>
            </p>
            <?php endif; ?>
            <small>Status: <?= htmlspecialchars($u['status']) ?></small>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </section>
    <?php endif; ?>
  </div>
</main>

<?php include 'footer.php'; ?>
