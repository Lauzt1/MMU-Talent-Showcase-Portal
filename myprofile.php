<?php
// myprofile.php
session_start();
include 'header.php';
require_once 'admin/config.php';

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Fetch current user data
$stmt = $pdo->prepare("SELECT * FROM userdata WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$user) {
    session_destroy();
    header('Location: login.php?error=invalid_session');
    exit;
}

$msg = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Collect posted values
    $username        = trim($_POST['username'] ?? $user['username']);
    $email           = trim($_POST['email'] ?? $user['email']);
    $bio             = trim($_POST['bio'] ?? '');
    $talent_category = trim($_POST['talent_category'] ?? '');

    // Validation
    if (strlen($username) < 5) {
        $msg = '<div class="error-msg">Username must be at least 5 characters.</div>';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $msg = '<div class="error-msg">Please enter a valid email address.</div>';
    } else {
        // Handle profile picture upload & automatic square crop
        $newPicPath = $user['profile_pic'];
        if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] === UPLOAD_ERR_OK) {
            $tmpFile = $_FILES['profile_pic']['tmp_name'];
            $mime    = mime_content_type($tmpFile);
            $size    = $_FILES['profile_pic']['size'];
            $allowed = ['image/jpeg','image/png','image/gif'];

            if (!in_array($mime, $allowed)) {
                $msg = '<div class="error-msg">Allowed formats: JPG, PNG, GIF.</div>';
            } elseif ($size > 5 * 1024 * 1024) {
                $msg = '<div class="error-msg">Max file size is 5 MB.</div>';
            } else {
                $uploadDir = __DIR__ . '/assets/profile';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }
                $ext      = strtolower(pathinfo($_FILES['profile_pic']['name'], PATHINFO_EXTENSION));
                $safeName = 'pf_' . uniqid() . ".$ext";
                $destFull = $uploadDir . '/' . $safeName;
                $relative = 'assets/profile/' . $safeName;

                if (move_uploaded_file($tmpFile, $destFull)) {
                    // Crop to square using GD if available
                    if (function_exists('imagecreatefromstring') && function_exists('imagecopyresampled')) {
                        $imgData = file_get_contents($destFull);
                        $srcImg = imagecreatefromstring($imgData);
                        if ($srcImg) {
                            $width = imagesx($srcImg);
                            $height = imagesy($srcImg);
                            $minDim = min($width, $height);
                            $srcX = intval(($width - $minDim) / 2);
                            $srcY = intval(($height - $minDim) / 2);
                            $dstImg = imagecreatetruecolor($minDim, $minDim);
                            // Preserve transparency
                            if ($ext === 'png' || $ext === 'gif') {
                                imagecolortransparent($dstImg, imagecolorallocatealpha($dstImg, 0, 0, 0, 127));
                                imagealphablending($dstImg, false);
                                imagesavealpha($dstImg, true);
                            }
                            imagecopyresampled(
                                $dstImg,
                                $srcImg,
                                0, 0,
                                $srcX, $srcY,
                                $minDim, $minDim,
                                $minDim, $minDim
                            );
                            // Overwrite file with cropped version
                            switch ($ext) {
                                case 'jpg':
                                case 'jpeg':
                                    imagejpeg($dstImg, $destFull, 90);
                                    break;
                                case 'png':
                                    imagepng($dstImg, $destFull);
                                    break;
                                case 'gif':
                                    imagegif($dstImg, $destFull);
                                    break;
                            }
                            imagedestroy($srcImg);
                            imagedestroy($dstImg);
                        }
                    }
                    // Delete old pic if exists
                    if ($user['profile_pic'] && file_exists(__DIR__ . '/' . $user['profile_pic'])) {
                        unlink(__DIR__ . '/' . $user['profile_pic']);
                    }
                    $newPicPath = $relative;
                } else {
                    $msg = '<div class="error-msg">Failed to save uploaded picture.</div>';
                }
            }
        }

        // Update if no errors
        if ($msg === '') {
            try {
                $upd = $pdo->prepare("
                    UPDATE userdata
                      SET username = ?, email = ?, bio = ?, talent_category = ?, profile_pic = ?
                     WHERE id = ?
                ");
                $upd->execute([
                    $username,
                    $email,
                    $bio ?: null,
                    $talent_category ?: null,
                    $newPicPath,
                    $_SESSION['user_id']
                ]);
                $msg = '<div class="success-msg">Changes saved successfully.</div>';
                // Refresh user data
                $stmt->execute([$_SESSION['user_id']]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
            } catch (PDOException $e) {
                error_log("Profile update error: " . $e->getMessage());
                $msg = '<div class="error-msg">Database error. Please try again.</div>';
            }
        }
    }
}

// Determine which picture to show
if (!empty($user['profile_pic']) && file_exists(__DIR__ . '/' . $user['profile_pic'])) {
    $currentPic = $user['profile_pic'];
} else {
    $currentPic = 'assets/contributor/icon.jpg';
}

// Escape current values for display
$currentUsername = htmlspecialchars($user['username']);
$currentEmail    = htmlspecialchars($user['email']);
$currentBio      = htmlspecialchars($user['bio'] ?? '');
$currentCat      = htmlspecialchars($user['talent_category'] ?? '');
?>
<link rel="stylesheet" href="styles/myprofile.css">

<main class="profile-container">
  <form action="" method="post" enctype="multipart/form-data" class="profile-form" id="profile-form">
    <!-- Left Column: Picture + Display Name & Bio -->
    <div class="profile-left">
      <div class="picture-wrapper">
        <img src="<?= htmlspecialchars($currentPic) ?>" alt="Profile of <?= $currentUsername ?>" id="profile-img">
        <div class="picture-overlay" id="picture-overlay">
          <img src="assets/misc/edit.png" alt="Edit" class="overlay-icon">
        </div>
        <input type="file" id="profile_pic" name="profile_pic" accept=".jpg,.jpeg,.png,.gif" style="display:none;">
      </div>
      <div class="display-info">
        <h2 id="display-username"><?= $currentUsername ?></h2>
        <p id="display-bio"><?= $currentBio ?></p>
      </div>
    </div>

    <!-- Right Column: Editable Fields -->
    <div class="profile-right">
      <!-- Username Field -->
      <div class="field-group">
        <label for="username">Username</label>
        <div class="field-input">
          <input type="text" id="username" name="username"
                 value="<?= $currentUsername ?>" disabled minlength="5">
          <img src="assets/misc/edit.png" alt="Edit" class="edit-btn" data-target="username" data-display="display-username">
        </div>
      </div>

      <!-- Email Field -->
      <div class="field-group">
        <label for="email">Email</label>
        <div class="field-input">
          <input type="email" id="email" name="email"
                 value="<?= $currentEmail ?>" disabled>
          <img src="assets/misc/edit.png" alt="Edit" class="edit-btn" data-target="email">
        </div>
      </div>

      <!-- Bio Field -->
      <div class="field-group">
        <label for="bio">Bio</label>
        <div class="field-input">
          <textarea id="bio" name="bio" rows="3" disabled><?= $currentBio ?></textarea>
          <img src="assets/misc/edit.png" alt="Edit" class="edit-btn" data-target="bio" data-display="display-bio">
        </div>
      </div>

      <!-- Talent Category Field -->
      <div class="field-group">
        <label for="talent_category">Talent Category</label>
        <div class="field-input">
          <input type="text" id="talent_category" name="talent_category"
                 value="<?= $currentCat ?>" disabled>
          <img src="assets/misc/edit.png" alt="Edit" class="edit-btn" data-target="talent_category">
        </div>
      </div>

      <!-- Action Buttons -->
      <div class="action-buttons">
        <button type="button" class="cancel-btn" id="cancel-btn">Cancel</button>
        <button type="submit" class="save-btn">Save Changes</button>
      </div>

      <?= $msg ?>
    </div>
  </form>
</main>

<script>
  // Enable individual fields on edit-icon click, and update display name/bio live
  document.querySelectorAll('.edit-btn').forEach(btn => {
    btn.addEventListener('click', () => {
      const targetId = btn.getAttribute('data-target');
      const field = document.getElementById(targetId);
      field.disabled = false;
      field.focus();
      const displayId = btn.getAttribute('data-display');
      if (displayId) {
        field.addEventListener('input', () => {
          document.getElementById(displayId).textContent = field.value;
        });
      }
    });
  });

  // Profile picture overlay click
  document.getElementById('picture-overlay').addEventListener('click', () => {
    document.getElementById('profile_pic').click();
  });

  // Preview new profile picture on selection
  document.getElementById('profile_pic').addEventListener('change', function() {
    if (this.files && this.files[0]) {
      const reader = new FileReader();
      reader.onload = e => {
        document.getElementById('profile-img').src = e.target.result;
      };
      reader.readAsDataURL(this.files[0]);
    }
  });

  // Cancel button reloads page, discarding unsaved edits
  document.getElementById('cancel-btn').addEventListener('click', () => {
    window.location.reload();
  });
</script>
