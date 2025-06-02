<?php
// resourcesharing.php
session_start();
include 'header.php';
require_once 'admin/config.php';

// Only allow logged-in users
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// VALIDATE USER EXISTS IN DATABASE
$userCheckStmt = $pdo->prepare("SELECT id FROM userdata WHERE id = ?");
$userCheckStmt->execute([$_SESSION['user_id']]);
if (!$userCheckStmt->fetch()) {
    session_destroy();
    header('Location: login.php?error=invalid_session');
    exit;
}

$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // ─── Allowed MIME types ──────────────────────────────────────────────
    $allowed = [
        'image/jpeg', 'image/png', 'image/gif',
        'video/mp4', 'video/quicktime', 'video/webm',
        'audio/mpeg', 'audio/wav', 'audio/ogg',
        'application/pdf', 'text/plain'
    ];

    // ─── Validate title ─────────────────────────────────────────────────
    $title = trim($_POST['title'] ?? '');
    if ($title === '' || strlen($title) > 255) {
        $msg = '<div class="error-msg">Please enter a title (max 255 chars).</div>';
    }
    // ─── Validate category (must be one of the five) ────────────────────
    elseif (empty($_POST['category']) || !in_array($_POST['category'], [
            'Art & Design',
            'Music & Audio',
            'Video & Film',
            'Writing & Documents',
            'others'
        ], true)) {
        $msg = '<div class="error-msg">Please select a valid category.</div>';
    }
    // ─── Check file upload ───────────────────────────────────────────────
    elseif (isset($_FILES['resource']) && $_FILES['resource']['error'] === UPLOAD_ERR_OK) {
        $tmp  = $_FILES['resource']['tmp_name'];
        $mime = mime_content_type($tmp);
        $size = $_FILES['resource']['size'];

        if (in_array($mime, $allowed) && $size <= 50 * 1024 * 1024) {
            // ─── Decide which subfolder to use ───────────────────────────
            $baseDir = __DIR__ . '/assets/uploads';
            if (!is_dir($baseDir)) {
                mkdir($baseDir, 0755, true);
            }

            if (strpos($mime, 'image/') === 0) {
                $categoryDir = $baseDir . '/images';
                $urlDir      = 'assets/uploads/images';
            }
            elseif (strpos($mime, 'video/') === 0) {
                $categoryDir = $baseDir . '/videos';
                $urlDir      = 'assets/uploads/videos';
            }
            elseif (strpos($mime, 'audio/') === 0) {
                $categoryDir = $baseDir . '/audio';
                $urlDir      = 'assets/uploads/audio';
            }
            else {
                // application/pdf or text/plain
                $categoryDir = $baseDir . '/files';
                $urlDir      = 'assets/uploads/files';
            }

            if (!is_dir($categoryDir)) {
                mkdir($categoryDir, 0755, true);
            }

            // Generate a safe, unique filename
            $ext      = pathinfo($_FILES['resource']['name'], PATHINFO_EXTENSION);
            $safeName = uniqid('res_', true) . ".$ext";
            $destFull = $categoryDir . '/' . $safeName;
            $filePath = $urlDir . '/' . $safeName;  // relative URL for DB

            if (move_uploaded_file($tmp, $destFull)) {
                $desc       = trim($_POST['description'] ?? null);
                $cat        = $_POST['category']; // single selected category
                try {
                    $stmt = $pdo->prepare("
                        INSERT INTO resources
                          (user_id, category, file_name, file_path, mime_type, title, description)
                        VALUES (?,       ?,        ?,         ?,         ?,         ?,     ?)
                    ");
                    $stmt->execute([
                        $_SESSION['user_id'],
                        $cat,
                        $_FILES['resource']['name'],
                        $filePath,
                        $mime,
                        $title,
                        $desc
                    ]);
                    $msg = '<div class="success-msg">Upload successful & sent for review.</div>';
                } catch (PDOException $e) {
                    error_log("Resource upload error: " . $e->getMessage());
                    $msg = '<div class="error-msg">Database error. Please try again or contact support.</div>';
                    if (file_exists($destFull)) {
                        unlink($destFull);
                    }
                }
            } else {
                $msg = '<div class="error-msg">Failed to move uploaded file.</div>';
            }
        } else {
            $msg = '<div class="error-msg">Invalid file type or file too large (max 50 MB).</div>';
        }
    } else {
        $msg = '<div class="error-msg">Please select a file to upload.</div>';
    }
}

// ─── Fetch this user's uploads ──────────────────────────────────────────────────
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
        <!-- Title Field -->
        <div class="form-group">
          <label for="title">Title</label>
          <input
            type="text" id="title" name="title"
            required maxlength="255"
            placeholder="Enter a short title">
        </div>

        <!-- NEW: Category Dropdown -->
        <div class="form-group">
          <label for="category">Category</label>
          <select id="category" name="category" required>
            <option value="" disabled selected>–– Select category ––</option>
            <option value="Art & Design">Art &amp; Design</option>
            <option value="Music & Audio">Music &amp; Audio</option>
            <option value="Video & Film">Video &amp; Film</option>
            <option value="Writing & Documents">Writing &amp; Documents</option>
            <option value="others">others</option>
          </select>
        </div>

        <!-- Description Field -->
        <div class="form-group">
          <label for="description">Description</label>
          <textarea
            id="description" name="description"
            rows="4" placeholder="Optional: add details about your work"></textarea>
        </div>

        <!-- File Picker -->
        <div class="form-group">
          <label for="resource">Select File</label>
          <input
            type="file" id="resource" name="resource"
            accept=".jpg,.jpeg,.png,.gif,.mp4,.mov,.webm,.mp3,.wav,.ogg,.pdf,.txt"
            required>
          <small class="note">
            Accepted formats: jpg, jpeg, png, gif, mp4, mov, webm, mp3, wav, ogg, pdf, txt.<br>
            Max file size: 50 MB.
          </small>
        </div>

        <!-- Upload Button -->
        <button class="upload-btn" type="submit">Upload</button>
      </form>

      <?= $msg ?>
    </section>

    <?php if ($uploads): ?>
    <section id="gallery">
      <h2>Your Uploaded Resources</h2>
      <div class="grid">
        <?php foreach ($uploads as $u): ?>
        <div class="card">
          <?php if (strpos($u['mime_type'], 'image/') === 0): ?>
            <img 
              class="card-media" 
              src="<?= htmlspecialchars($u['file_path']) ?>" 
              alt="<?= htmlspecialchars($u['title']) ?>">
          
          <?php elseif (strpos($u['mime_type'], 'video/') === 0): ?>
            <video 
              class="card-media" 
              controls
            >
              <source 
                src="<?= htmlspecialchars($u['file_path']) ?>" 
                type="<?= htmlspecialchars($u['mime_type']) ?>">
              Your browser does not support the video tag.
            </video>
          
          <?php elseif (strpos($u['mime_type'], 'audio/') === 0): ?>
            <a 
              href="<?= htmlspecialchars($u['file_path']) ?>" 
              target="_blank"
            >
              <img
                class="card-media icon"
                src="assets/misc/audioicon.png" 
                alt="Audio: <?= htmlspecialchars($u['title']) ?>">
            </a>
          
          <?php elseif ($u['mime_type'] === 'application/pdf' 
                    || $u['mime_type'] === 'text/plain'): ?>
            <a 
              href="<?= htmlspecialchars($u['file_path']) ?>" 
              target="_blank"
            >
              <img
                class="card-media icon"
                src="assets/misc/fileicon.png" 
                alt="File: <?= htmlspecialchars($u['title']) ?>">
            </a>
          
          <?php endif; ?>

          <div class="meta">
            <h3><?= htmlspecialchars($u['title']) ?></h3>
            <p class="category-label"><?= htmlspecialchars($u['category']) ?></p>
            <?php if ($u['description']): ?>
            <p>
              <?= nl2br(
                   strlen($u['description']) > 100
                     ? htmlspecialchars(substr($u['description'], 0, 100)) . '…'
                     : htmlspecialchars($u['description'])
                 ) ?>
            </p>
            <?php endif; ?>
            <small>Uploaded: <?= date('Y-m-d H:i', strtotime($u['uploaded_at'])) ?></small>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </section>
    <?php endif; ?>
  </div>
</main>

<?php include 'footer.php'; ?>
