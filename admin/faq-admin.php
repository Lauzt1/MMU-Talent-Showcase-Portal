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
                $question = $_POST['question'] ?? '';
                $answer = $_POST['answer'] ?? '';
                $category = $_POST['category'] ?? '';
                
                if (!empty($question) && !empty($answer) && !empty($category)) {
                    try {
                        $stmt = $pdo->prepare("INSERT INTO faqs (question, answer, category) VALUES (?, ?, ?)");
                        $stmt->execute([$question, $answer, $category]);
                        $message = "FAQ added successfully!";
                        $message_type = "success";
                    } catch(PDOException $e) {
                        $message = "Error adding FAQ: " . $e->getMessage();
                        $message_type = "error";
                    }
                } else {
                    $message = "Please fill in all fields.";
                    $message_type = "error";
                }
                break;
                
            case 'edit':
                $id = $_POST['id'] ?? 0;
                $question = $_POST['question'] ?? '';
                $answer = $_POST['answer'] ?? '';
                $category = $_POST['category'] ?? '';
                
                if ($id > 0 && !empty($question) && !empty($answer) && !empty($category)) {
                    try {
                        $stmt = $pdo->prepare("UPDATE faqs SET question = ?, answer = ?, category = ? WHERE id = ?");
                        $stmt->execute([$question, $answer, $category, $id]);
                        $message = "FAQ updated successfully!";
                        $message_type = "success";
                    } catch(PDOException $e) {
                        $message = "Error updating FAQ: " . $e->getMessage();
                        $message_type = "error";
                    }
                }
                break;
                
            case 'remove':
                $id = $_POST['id'] ?? 0;
                if ($id > 0) {
                    try {
                        $stmt = $pdo->prepare("DELETE FROM faqs WHERE id = ?");
                        $stmt->execute([$id]);
                        $message = "FAQ removed successfully!";
                        $message_type = "success";
                    } catch(PDOException $e) {
                        $message = "Error removing FAQ: " . $e->getMessage();
                        $message_type = "error";
                    }
                }
                break;
        }
    }
}

// Fetch FAQs from database
try {
    $stmt = $pdo->query("SELECT * FROM faqs ORDER BY created_date DESC");
    $faqs = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $faqs = [];
    $message = "Error fetching FAQs: " . $e->getMessage();
    $message_type = "error";
}
?>

<link rel="stylesheet" href="../styles/announcement.css">
<main class="admin-main">
    <div class="admin-container">
        <h1>FAQ Management</h1>
        
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
                            <input type="text" name="question" placeholder="FAQ Question" required>
                        </div>
                        
                        <div class="form-group">
                            <textarea name="answer" placeholder="FAQ Answer" rows="6" required></textarea>
                        </div>
                        
                        <div class="form-group">
                            <select name="category" required>
                                <option value="">Select Category</option>
                                <option value="general">General</option>
                                <option value="account">Account</option>
                                <option value="portfolio">Portfolio</option>
                                <option value="technical">Technical</option>
                            </select>
                        </div>
                        
                        <button type="submit" class="confirm-btn">Confirm</button>
                    </form>
                </div>
                
                <!-- Edit Section -->
                <div id="edit-section" class="content-section">
                    <div class="announcement-list">
                        <?php foreach ($faqs as $faq): ?>
                            <div class="announcement-item" onclick="editFAQ(<?php echo $faq['id']; ?>, '<?php echo htmlspecialchars($faq['question'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($faq['answer'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($faq['category']); ?>')">
                                <h3><?php echo htmlspecialchars($faq['question']); ?></h3>
                                <p><?php echo htmlspecialchars($faq['answer']); ?></p>
                                <span class="faq-category-badge"><?php echo ucfirst($faq['category']); ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <form method="POST" class="admin-form" id="edit-form" style="display: none;">
                        <input type="hidden" name="action" value="edit">
                        <input type="hidden" name="id" id="edit-id">
                        
                        <div class="form-group">
                            <input type="text" name="question" id="edit-question" placeholder="FAQ Question" required>
                        </div>
                        
                        <div class="form-group">
                            <textarea name="answer" id="edit-answer" placeholder="FAQ Answer" rows="6" required></textarea>
                        </div>
                        
                        <div class="form-group">
                            <select name="category" id="edit-category" required>
                                <option value="">Select Category</option>
                                <option value="general">General</option>
                                <option value="account">Account</option>
                                <option value="portfolio">Portfolio</option>
                                <option value="technical">Technical</option>
                            </select>
                        </div>
                        
                        <button type="submit" class="confirm-btn">Confirm</button>
                        <button type="button" class="cancel-btn" onclick="cancelEdit()">Cancel</button>
                    </form>
                </div>
                
                <!-- Remove Section -->
                <div id="remove-section" class="content-section">
                    <div class="announcement-list">
                        <?php foreach ($faqs as $faq): ?>
                            <div class="announcement-item removable" onclick="selectForRemoval(<?php echo $faq['id']; ?>, '<?php echo htmlspecialchars($faq['question'], ENT_QUOTES); ?>')">
                                <h3><?php echo htmlspecialchars($faq['question']); ?></h3>
                                <p><?php echo htmlspecialchars($faq['answer']); ?></p>
                                <span class="faq-category-badge"><?php echo ucfirst($faq['category']); ?></span>
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

function editFAQ(id, question, answer, category) {
    document.getElementById('edit-id').value = id;
    document.getElementById('edit-question').value = question;
    document.getElementById('edit-answer').value = answer;
    document.getElementById('edit-category').value = category;
    document.getElementById('edit-form').style.display = 'block';
}

function cancelEdit() {
    document.getElementById('edit-form').style.display = 'none';
}

function selectForRemoval(id, question) {
    document.getElementById('remove-id').value = id;
    document.getElementById('remove-title').textContent = question;
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

<style>
.faq-category-badge {
    display: inline-block;
    background: #667eea;
    color: white;
    padding: 5px 10px;
    border-radius: 12px;
    font-size: 0.8rem;
    font-weight: 500;
    margin-top: 10px;
}

.form-group select {
    width: 100%;
    padding: 1rem;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 1rem;
    transition: border-color 0.2s ease;
    background-color: white;
}

.form-group select:focus {
    outline: none;
    border-color: #007bff;
    box-shadow: 0 0 0 2px rgba(0, 123, 255, 0.25);
}
</style>

<?php include 'footer.php'; ?>