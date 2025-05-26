<?php include 'header.php'; ?>
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
      // Mock FAQ data - replace with database later
      $faqs = [
        [
          'id' => 1,
          'question' => 'How do I create an account on the MMU Talent Showcase Portal?',
          'answer' => 'To create an account, click on the "Sign Up" button on the homepage. Fill in your student ID, email, and create a password. You will receive a verification email to activate your account.',
          'category' => 'account'
        ],
        [
          'id' => 2,
          'question' => 'How can I upload my portfolio?',
          'answer' => 'Once logged in, go to your profile page and click "Upload Portfolio". You can upload images, videos, documents, and add descriptions for each piece of work.',
          'category' => 'portfolio'
        ],
        [
          'id' => 3,
          'question' => 'What file formats are supported for portfolio uploads?',
          'answer' => 'We support common image formats (JPG, PNG, GIF), video formats (MP4, MOV, AVI), and document formats (PDF, DOC, DOCX). Maximum file size is 50MB per upload.',
          'category' => 'technical'
        ],
        [
          'id' => 4,
          'question' => 'How do I make my portfolio public?',
          'answer' => 'In your portfolio settings, you can toggle between "Private" and "Public" visibility. Public portfolios can be discovered by other users and visitors.',
          'category' => 'portfolio'
        ],
        [
          'id' => 5,
          'question' => 'Can I edit or delete my uploaded content?',
          'answer' => 'Yes, you can edit or delete any content you have uploaded. Go to your profile, select the content you want to modify, and use the edit or delete options.',
          'category' => 'general'
        ],
        [
          'id' => 6,
          'question' => 'How do I reset my password?',
          'answer' => 'Click on "Forgot Password" on the login page. Enter your email address, and you will receive a password reset link via email.',
          'category' => 'account'
        ],
        [
          'id' => 7,
          'question' => 'Why is my portfolio not showing up in search results?',
          'answer' => 'Make sure your portfolio is set to "Public" and that you have added relevant tags and descriptions. It may take a few minutes for new content to appear in search results.',
          'category' => 'technical'
        ],
        [
          'id' => 8,
          'question' => 'How can I contact other talented students?',
          'answer' => 'You can view public profiles and use the contact form on each profile page to send messages to other students. Respect privacy and use appropriate communication.',
          'category' => 'general'
        ]
      ];

      // Filter FAQs based on search
      $search = $_GET['search'] ?? '';
      $filtered_faqs = $faqs;
      
      if (!empty($search)) {
        $filtered_faqs = array_filter($faqs, function($faq) use ($search) {
          return stripos($faq['question'], $search) !== false || 
                 stripos($faq['answer'], $search) !== false;
        });
      }
      ?>

      <div class="faq-container">
        <?php if (empty($filtered_faqs)): ?>
          <div class="no-results">
            <p>No FAQs found matching your search.</p>
          </div>
        <?php else: ?>
          <?php foreach ($filtered_faqs as $faq): ?>
            <div class="faq-item" data-category="<?php echo $faq['category']; ?>">
              <div class="faq-question" onclick="toggleFAQ(<?php echo $faq['id']; ?>)">
                <h3><?php echo htmlspecialchars($faq['question']); ?></h3>
                <span class="faq-toggle">+</span>
              </div>
              <div class="faq-answer" id="faq-<?php echo $faq['id']; ?>">
                <p><?php echo htmlspecialchars($faq['answer']); ?></p>
                <span class="faq-category"><?php echo ucfirst($faq['category']); ?></span>
              </div>
            </div>
          <?php endforeach; ?>
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
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['message'])) {
      // In a real application, save to database
      echo '<div class="feedback-success">Thank you! We will get back to you soon.</div>';
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

<?php include 'footer.php'; ?>