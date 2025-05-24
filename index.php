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
      <?php for($i=0;$i<3;$i++): ?>
        <li><div class="placeholder small"></div></li>
      <?php endfor; ?>
    </ul>
  </aside>
</main>

<?php include 'footer.php'; ?>
