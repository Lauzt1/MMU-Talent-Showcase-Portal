<?php
// feedback.php
include 'header.php';
require_once 'config.php';
?>

<link rel="stylesheet" href="../styles/feedback.css">
<main class="container">
  <section id="feedback">
    <h2>Users' Feedback</h2>
    <ul>
    <?php
    try {
        $stmt = $pdo->query(
            "SELECT name, email, subject, message, created_date
             FROM feedback
             ORDER BY created_date DESC"
        );
        $feedbacks = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($feedbacks)) {
            echo '<li>No feedback has been submitted yet.</li>';
        } else {
            foreach ($feedbacks as $fb) {
                ?>
                <li>
                  <h4><?php echo htmlspecialchars($fb['subject'], ENT_QUOTES); ?></h4>
                  <p><?php
                    $snippet = substr($fb['message'], 0, 100);
                    echo htmlspecialchars($snippet, ENT_QUOTES);
                    if (strlen($fb['message']) > 100) echo '…';
                  ?></p>
                  <small>
                    From <?php echo htmlspecialchars($fb['name'], ENT_QUOTES);
                    echo ' (' . htmlspecialchars($fb['email'], ENT_QUOTES) . ')'; ?>
                    on <?php echo date('M j, Y', strtotime($fb['created_date'])); ?>
                  </small>
                </li>
                <?php
            }
        }
    } catch (PDOException $e) {
        echo '<li>Error loading feedback.</li>';
    }
    ?>
    </ul>
  </section>
</main>

<?php include 'footer.php';
