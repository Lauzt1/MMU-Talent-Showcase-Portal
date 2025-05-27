<?php
// admin/talent.php — Admin panel for approving/rejecting resource uploads
include 'header.php';
require_once 'config.php';

// Redirect non‐admins
/*if (empty($_SESSION['is_admin'])) {
    header('Location: ../index.php');
    exit;
}*/

$message = '';
// Handle Approve/Reject actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'], $_POST['decision'])) {
    $id     = (int)$_POST['id'];
    $status = ($_POST['decision'] === 'approve') ? 'approved' : 'rejected';
    $upd    = $pdo->prepare("UPDATE resources SET status = ? WHERE id = ?");
    if ($upd->execute([$status, $id])) {
        $message = "<div class='success-msg'>Resource #{$id} marked {$status}.</div>";
    } else {
        $message = "<div class='error-msg'>Failed to update Resource #{$id}.</div>";
    }
}

// Fetch all pending resources
$stmt = $pdo->query("
  SELECT
    id,
    user_id,
    title,
    description,
    file_path,
    mime_type,
    status,
    uploaded_at
  FROM resources
  WHERE status = 'pending'
  ORDER BY uploaded_at DESC
");
$resources = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<link rel="stylesheet" href="../styles/talent.css">

<main class="admin-main">
  <h1>Pending Approvals</h1>
  <?= $message ?>

  <?php if (empty($resources)): ?>
    <p>No resources awaiting approval.</p>
  <?php else: ?>
    <ul class="resource-list">
      <?php foreach ($resources as $res): ?>
        <li>
          <div class="meta">
            <span><strong>ID:</strong> <?= $res['id'] ?></span>
            <span><strong>Title:</strong> <?= htmlspecialchars($res['title'], ENT_QUOTES) ?></span>
            <span><strong>Uploader User ID:</strong> <?= $res['user_id'] ?></span>
            <span><strong>Uploaded:</strong> <?= date('M j, Y', strtotime($res['uploaded_at'])) ?></span>
          </div>
          <div class="preview">
            <?php if (strpos($res['mime_type'], 'image') === 0): ?>
              <img src="../<?= htmlspecialchars($res['file_path'], ENT_QUOTES) ?>"
                   alt="<?= htmlspecialchars($res['title'], ENT_QUOTES) ?>">
            <?php else: ?>
              <video src="<?= htmlspecialchars($res['file_path'], ENT_QUOTES) ?>" muted></video>
            <?php endif; ?>
          </div>
          <div class="actions">
            <form method="post">
              <input type="hidden" name="id" value="<?= $res['id'] ?>">
              <button type="submit" name="decision" value="approve" class="approve-btn">Approve</button>
              <button type="submit" name="decision" value="reject"  class="reject-btn">Reject</button>
            </form>
          </div>
          <p class="description">
            <?= nl2br(htmlspecialchars($res['description'], ENT_QUOTES)) ?>
          </p>
        </li>
      <?php endforeach; ?>
    </ul>
  <?php endif; ?>
</main>

<?php include 'footer.php'; ?>
