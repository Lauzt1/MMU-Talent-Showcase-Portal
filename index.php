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
        <?php for($i=0;$i<4;$i++): ?>
          <div class="placeholder"></div>
        <?php endfor; ?>
      </div>
    </section>
  </div>

  <!-- Sidebar -->
<aside id="announcements">
    <h2>Announcements</h2>
    <ul>
        <?php 
        include('config.php');
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
