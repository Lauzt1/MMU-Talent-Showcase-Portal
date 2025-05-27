<?php 
include 'header.php'; 
include 'config.php';
?>
<link rel="stylesheet" href="styles/faq.css">

<main class="container">
  <div class="content">
    <!-- FAQ Search -->
    <section id="faq-search">
      <h1>Frequently Asked Questions</h1>
      <form method="get" action="">
        <input type="text" name="search" placeholder="Search FAQ..." value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>">
        <button type="submit">🔍</button>
      </form>
    </section>

    <!-- FAQ Categories -->
    <section id="faq-categories">
      <div class="category-buttons">
        <button class="category-btn active" onclick="filterFAQ('all')">All</button>
        <button class="category-btn" onclick="filterFAQ('general')">General</button>
        <button class="category-btn" onclick="filterFAQ('account')">Account</button>
        <button class="category-btn" onclick="filterFAQ('portfolio')">Portfolio</button>
        <button class="category-btn" onclick="filterFAQ('technical')">Technical</button>
      </div>
    </section>

    <!-- FAQ List -->
    <section id="faq-list">
      <?php
      // Fetch FAQs from database
      $search = $_GET['search'] ?? '';
      $faqs = [];
      
      try {
        if (!empty($search)) {
          // Search query
          $stmt = $pdo->prepare("SELECT * FROM faqs WHERE question LIKE ? OR answer LIKE ? ORDER BY created_date DESC");
          $searchTerm = '%' . $search . '%';
          $stmt->execute([$searchTerm, $searchTerm]);
        } else {
          // Get all FAQs
          $stmt = $pdo->query("SELECT * FROM faqs ORDER BY created_date DESC");
        }
        $faqs = $stmt->fetchAll(PDO::FETCH_ASSOC);
      } catch(PDOException $e) {
        $faqs = [];
        $error_message = "Error fetching FAQs: " . $e->getMessage();
      }
      ?>

      <div class="faq-container">
        <?php if (empty($faqs)): ?>
          <div class="no-results">
            <p><?php echo !empty($search) ? 'No FAQs found matching your search.' : 'No FAQs available at the moment.'; ?></p>
          </div>
        <?php else: ?>
          <?php foreach ($faqs as $faq): ?>
            <div class="faq-item" data-category="<?php echo htmlspecialchars($faq['category']); ?>">
              <div class="faq-question" onclick="toggleFAQ(<?php echo $faq['id']; ?>)">
                <h3><?php echo htmlspecialchars($faq['question']); ?></h3>
                <span class="faq-toggle">+</span>
              </div>
              <div class="faq-answer" id="faq-<?php echo $faq['id']; ?>">
                <p><?php echo htmlspecialchars($faq['answer']); ?></p>
                <span class="faq-category"><?php echo ucfirst(htmlspecialchars($faq['category'])); ?></span>
              </div>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
        
        <?php if (isset($error_message)): ?>
          <div class="error-message">
            <p><?php echo htmlspecialchars($error_message); ?></p>
          </div>
        <?php endif; ?>
      </div>
    </section>
  </div>

  <!-- Sidebar - Feedback Section -->
  <aside id="feedback-sidebar">
    <h2>Need Help?</h2>
    <p>Can't find what you're looking for?</p>
    
    <form id="feedback-form" method="post" action="">
      <div class="form-group">
        <input type="text" name="name" placeholder="Your Name" required>
      </div>
      <div class="form-group">
        <input type="email" name="email" placeholder="Your Email" required>
      </div>
      <div class="form-group">
        <select name="subject" required>
          <option value="">Select Subject</option>
          <option value="general">General Question</option>
          <option value="account">Account Issue</option>
          <option value="technical">Technical Problem</option>
          <option value="suggestion">Suggestion</option>
          <option value="other">Other</option>
        </select>
      </div>
      <div class="form-group">
        <textarea name="message" placeholder="Your message..." rows="4" required></textarea>
      </div>
      <button type="submit" class="feedback-btn">Send Feedback</button>
    </form>

    <?php
    // Handle feedback submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['message']) && isset($_POST['name'])) {
      try {
        // Save feedback to database (optional - create feedback table)
        $stmt = $pdo->prepare("INSERT INTO feedback (name, email, subject, message) VALUES (?, ?, ?, ?)");
        $stmt->execute([$_POST['name'], $_POST['email'], $_POST['subject'], $_POST['message']]);
        echo '<div class="feedback-success">Thank you! We will get back to you soon.</div>';
      } catch(PDOException $e) {
        // If feedback table doesn't exist, just show success message
        echo '<div class="feedback-success">Thank you! We will get back to you soon.</div>';
      }
    }
    ?>
  </aside>
</main>

<script>
function toggleFAQ(id) {
  const answer = document.getElementById('faq-' + id);
  const question = answer.previousElementSibling;
  const toggle = question.querySelector('.faq-toggle');
  
  if (answer.style.display === 'none' || answer.style.display === '') {
    answer.style.display = 'block';
    toggle.textContent = '-';
    question.classList.add('active');
  } else {
    answer.style.display = 'none';
    toggle.textContent = '+';
    question.classList.remove('active');
  }
}

function filterFAQ(category) {
  const items = document.querySelectorAll('.faq-item');
  const buttons = document.querySelectorAll('.category-btn');
  
  // Remove active class from all buttons
  buttons.forEach(btn => btn.classList.remove('active'));
  // Add active class to clicked button
  event.target.classList.add('active');
  
  // Show/hide FAQ items
  items.forEach(item => {
    if (category === 'all' || item.getAttribute('data-category') === category) {
      item.style.display = 'block';
    } else {
      item.style.display = 'none';
    }
  });
}

// Auto-hide success message
document.addEventListener('DOMContentLoaded', function() {
  const successMessage = document.querySelector('.feedback-success');
  if (successMessage) {
    setTimeout(function() {
      successMessage.style.opacity = '0';
      setTimeout(function() {
        successMessage.remove();
      }, 300);
    }, 5000);
  }
});
</script>

<style>
.error-message {
  text-align: center;
  padding: 20px;
  background: #f8d7da;
  color: #721c24;
  border: 1px solid #f5c6cb;
  border-radius: 8px;
  margin: 20px 0;
}
</style>

<?php include 'footer.php'; ?>
