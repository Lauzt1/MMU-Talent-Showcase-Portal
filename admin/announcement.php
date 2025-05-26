<?php
include('header.php');
include('config.php');

// Handle form submissions
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                $title = $_POST['title'] ?? '';
                $content = $_POST['content'] ?? '';
                
                if (!empty($title) && !empty($content)) {
                    try {
                        $stmt = $pdo->prepare("INSERT INTO announcements (title, content) VALUES (?, ?)");
                        $stmt->execute([$title, $content]);
                        $message = "Announcement added successfully!";
                        $message_type = "success";
                    } catch(PDOException $e) {
                        $message = "Error adding announcement: " . $e->getMessage();
                        $message_type = "error";
                    }
                } else {
                    $message = "Please fill in all fields.";
                    $message_type = "error";
                }
                break;
                
            case 'edit':
                $id = $_POST['id'] ?? 0;
                $title = $_POST['title'] ?? '';
                $content = $_POST['content'] ?? '';
                
                if ($id > 0 && !empty($title) && !empty($content)) {
                    try {
                        $stmt = $pdo->prepare("UPDATE announcements SET title = ?, content = ? WHERE id = ?");
                        $stmt->execute([$title, $content, $id]);
                        $message = "Announcement updated successfully!";
                        $message_type = "success";
                    } catch(PDOException $e) {
                        $message = "Error updating announcement: " . $e->getMessage();
                        $message_type = "error";
                    }
                }
                break;
                
            case 'remove':
                $id = $_POST['id'] ?? 0;
                if ($id > 0) {
                    try {
                        $stmt = $pdo->prepare("DELETE FROM announcements WHERE id = ?");
                        $stmt->execute([$id]);
                        $message = "Announcement removed successfully!";
                        $message_type = "success";
                    } catch(PDOException $e) {
                        $message = "Error removing announcement: " . $e->getMessage();
                        $message_type = "error";
                    }
                }
                break;
        }
    }
}

// Fetch announcements from database
include 'config.php';
try {
    $stmt = $pdo->query("SELECT * FROM announcements ORDER BY created_date DESC");
    $announcements = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $announcements = [];
    $message = "Error fetching announcements: " . $e->getMessage();
    $message_type = "error";
}
?>

<link rel="stylesheet" href="../styles/announcement.css">
<main class="admin-main">
    <div class="admin-container">
        <h1>Announcement Management</h1>
        
        <?php if ($message): ?>
            <div class="message <?php echo $message_type; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        
        <div class="admin-layout">
            <!-- Left Sidebar -->
            <div class="admin-sidebar">
                <button class="action-btn active" onclick="showSection('add')">Add</button>
                <button class="action-btn" onclick="showSection('edit')">Edit</button>
                <button class="action-btn" onclick="showSection('remove')">Remove</button>
            </div>
            
            <!-- Main Content Area -->
            <div class="admin-content">
                <!-- Add Section -->
                <div id="add-section" class="content-section active">
                    <form method="POST" class="admin-form">
                        <input type="hidden" name="action" value="add">
                        
                        <div class="form-group">
                            <input type="text" name="title" placeholder="Announcement Title" required>
                        </div>
                        
                        <div class="form-group">
                            <textarea name="content" placeholder="Announcement Content" rows="6" required></textarea>
                        </div>
                        
                        <button type="submit" class="confirm-btn">Confirm</button>
                    </form>
                </div>
                
                <!-- Edit Section -->
                <div id="edit-section" class="content-section">
                    <div class="announcement-list">
                        <?php foreach ($announcements as $announcement): ?>
                            <div class="announcement-item" onclick="editAnnouncement(<?php echo $announcement['id']; ?>, '<?php echo htmlspecialchars($announcement['title']); ?>', '<?php echo htmlspecialchars($announcement['content']); ?>')">
                                <h3><?php echo htmlspecialchars($announcement['title']); ?></h3>
                                <p><?php echo htmlspecialchars($announcement['content']); ?></p>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <form method="POST" class="admin-form" id="edit-form" style="display: none;">
                        <input type="hidden" name="action" value="edit">
                        <input type="hidden" name="id" id="edit-id">
                        
                        <div class="form-group">
                            <input type="text" name="title" id="edit-title" placeholder="Announcement Title" required>
                        </div>
                        
                        <div class="form-group">
                            <textarea name="content" id="edit-content" placeholder="Announcement Content" rows="6" required></textarea>
                        </div>
                        
                        <button type="submit" class="confirm-btn">Confirm</button>
                        <button type="button" class="cancel-btn" onclick="cancelEdit()">Cancel</button>
                    </form>
                </div>
                
                <!-- Remove Section -->
                <div id="remove-section" class="content-section">
                    <div class="announcement-list">
                        <?php foreach ($announcements as $announcement): ?>
                            <div class="announcement-item removable" onclick="selectForRemoval(<?php echo $announcement['id']; ?>, '<?php echo htmlspecialchars($announcement['title']); ?>')">
                                <h3><?php echo htmlspecialchars($announcement['title']); ?></h3>
                                <p><?php echo htmlspecialchars($announcement['content']); ?></p>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <form method="POST" class="admin-form" id="remove-form" style="display: none;">
                        <input type="hidden" name="action" value="remove">
                        <input type="hidden" name="id" id="remove-id">
                        
                        <div class="remove-confirmation">
                            <p>Are you sure you want to remove "<span id="remove-title"></span>"?</p>
                        </div>
                        
                        <button type="submit" class="confirm-btn">Confirm</button>
                        <button type="button" class="cancel-btn" onclick="cancelRemove()">Cancel</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</main>

<script>
function showSection(section) {
    // Hide all sections
    const sections = document.querySelectorAll('.content-section');
    sections.forEach(s => s.classList.remove('active'));
    
    // Remove active class from all buttons
    const buttons = document.querySelectorAll('.action-btn');
    buttons.forEach(b => b.classList.remove('active'));
    
    // Show selected section
    document.getElementById(section + '-section').classList.add('active');
    event.target.classList.add('active');
    
    // Hide forms when switching sections
    document.getElementById('edit-form').style.display = 'none';
    document.getElementById('remove-form').style.display = 'none';
}

function editAnnouncement(id, title, content) {
    document.getElementById('edit-id').value = id;
    document.getElementById('edit-title').value = title;
    document.getElementById('edit-content').value = content;
    document.getElementById('edit-form').style.display = 'block';
}

function cancelEdit() {
    document.getElementById('edit-form').style.display = 'none';
}

function selectForRemoval(id, title) {
    document.getElementById('remove-id').value = id;
    document.getElementById('remove-title').textContent = title;
    document.getElementById('remove-form').style.display = 'block';
}

function cancelRemove() {
    document.getElementById('remove-form').style.display = 'none';
}

// Auto-hide messages
document.addEventListener('DOMContentLoaded', function() {
    const message = document.querySelector('.message');
    if (message) {
        setTimeout(function() {
            message.style.opacity = '0';
            setTimeout(function() {
                message.remove();
            }, 300);
        }, 3000);
    }
});
</script>

<?php include 'footer.php'; ?>