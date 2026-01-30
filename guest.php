<?php
require_once "connect.php";

function h($str){
  return htmlspecialchars((string)$str, ENT_QUOTES, "UTF-8");
}

$ann = [];
$annSql = "
SELECT announcement_id, title, message, created_at
FROM announcements
WHERE is_active = 1
ORDER BY created_at DESC
LIMIT 3
";
$annRes = mysqli_query($conn, $annSql);
if ($annRes) {
  while ($row = mysqli_fetch_assoc($annRes)) $ann[] = $row;
}

/* ===== Events preview (upcoming) =====
   ⚠️ 如果你字段名不同，在这里改就行
*/
$events = [];
$evtSql = "
SELECT event_id, event_title, event_date_time, event_location
FROM events
WHERE event_status = 'Approved'
AND event_date_time >= NOW()
ORDER BY event_date_time ASC
LIMIT 3
";
$evtRes = mysqli_query($conn, $evtSql);
if ($evtRes) {
  while ($row = mysqli_fetch_assoc($evtRes)) $events[] = $row;
}

/* ===== Community posts preview (public) =====
   这里 JOIN users 取发帖者名字
*/
$posts = [];
$postSql = "
SELECT p.post_id, p.title, p.body, p.like_count, p.comment_count, p.post_created_at,
       u.name AS author_name
FROM posts p
JOIN users u ON p.user_id = u.user_id
WHERE p.post_status = 'Public'
ORDER BY p.post_created_at DESC
LIMIT 3
";
$postRes = mysqli_query($conn, $postSql);
if ($postRes) {
  while ($row = mysqli_fetch_assoc($postRes)) $posts[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>ReLife Hub | Guest</title>

  <link rel="stylesheet" href="css/style.css">
  <style>
    .nav-right {
      margin-left: auto;
      display: flex;
      gap: 1rem;
      align-items: center;
    }
    .nav-container {
      width: 100%;
      display: flex;
      justify-content: center;
      gap: 2rem;
      align-items: center;
      flex-wrap: wrap;
    }
    .hero {
      background: var(--color-section);
      border: 1px solid var(--color-border);
      border-radius: 8px;
      margin: 2rem;
      padding: 2rem;
      text-align: center;
    }
    .hero h2 { color: var(--color-primary); margin-top: 0; }
    .hero p { color: var(--color-muted); max-width: 850px; margin: 0.5rem auto 1.25rem; }

    .grid-2 {
      display: grid;
      grid-template-columns: repeat(2, minmax(0, 1fr));
      gap: 1.5rem;
      margin: 0 2rem 2rem;
    }

    .small-note { font-size: 0.9rem; color: var(--color-muted); }

    .mini-card{
      border: 1px solid var(--color-border);
      background: #fff;
      border-radius: 10px;
      padding: 12px;
      margin: 10px 0;
    }

    @media (max-width: 900px) {
      nav { justify-content: flex-start; overflow-x: auto; }
      .nav-container { justify-content: flex-start; gap: 1rem; }
      .grid-2 { grid-template-columns: 1fr; margin: 0 1rem 1.5rem; }
      .hero, .section-preview { margin: 1rem; }
    }
  </style>
</head>
<body>

  <header>
    <h1>ReLife Hub</h1>
    <p>Eco • Modern • Sustainable</p>
  </header>

  <nav>
    <div class="nav-container">
      <a href="#home">Home</a>
      <a href="#ann">Announcements</a>
      <a href="#events">Events</a>
      <a href="#rewards">Rewards</a>
      <a href="#community">Community</a>
      <a href="#about">About</a>

      <div class="nav-right">
        <a class="btn" href="login/login.php">Login</a>
        <a class="btn" href="login/register.php">Register</a>
      </div>
    </div>
  </nav>


  <div class="hero">
    <h2>Welcome, Guest 👋</h2>
    <p>
      ReLife Hub is a campus sustainability platform for recycling, upcycling, and eco-events.
      You can explore a preview as a guest, but you need an account to submit recycling logs, earn Eco-Points, and redeem rewards.
    </p>
    <a class="btn" href="login/login.php">Sign In to Start</a>
    <p class="small-note">Tip: Admin / Organizer dashboards will be available after login based on your role.</p>
  </div>

  <div class="section-preview" id="ann" style="margin: 0 2rem 2rem;">
    <h2>Announcements Preview</h2>
    <p>Public announcements (guest can view).</p>

    <?php if (count($ann) === 0): ?>
      <div class="alert-warning">No announcements right now.</div>
    <?php else: ?>
      <?php foreach ($ann as $a): ?>
        <div class="mini-card">
          <div style="font-weight:700;"><?= h($a["title"]); ?></div>
          <div style="color:var(--color-muted); font-size:12px; margin:6px 0;">
            <?= $a["created_at"] ? date("d M Y, h:i A", strtotime($a["created_at"])) : ""; ?>
          </div>
          <div><?= nl2br(h($a["message"])); ?></div>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>

    <div class="alert-success" style="margin-top:10px;">
      ✅ Login to receive event notifications and personalized updates.
    </div>
  </div>

  <div class="grid-2">

    <!-- Community Preview -->
    <div class="section-preview" id="community">
      <h2>Community Preview</h2>
      <p>Latest public posts — guest can view only.</p>

      <?php if (count($posts) === 0): ?>
        <div class="alert-warning">No public posts yet.</div>
      <?php else: ?>
        <?php foreach ($posts as $p): ?>
          <div class="feed-card" style="margin: 1rem 0;">
            <h3><?= h($p["author_name"]); ?></h3>
            <div style="font-weight:700; margin-bottom:6px;"><?= h($p["title"]); ?></div>
            <p>
              <?php
                $body = (string)($p["body"] ?? "");
                $preview = mb_substr($body, 0, 120);
                if (mb_strlen($body) > 120) $preview .= "...";
                echo h($preview);
              ?>
            </p>
            <small>
              Likes: <?= (int)$p["like_count"]; ?> |
              Comments: <?= (int)$p["comment_count"]; ?> |
              <?= $p["post_created_at"] ? date("d M Y", strtotime($p["post_created_at"])) : ""; ?>
            </small>
            <div style="margin-top:10px;">
              <a class="btn" href="login/login.php">Login to view details</a>
            </div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>

      <div class="alert-warning" style="margin-top: 1rem;">
        ⚠️ Guest mode: Posting and commenting requires login.
      </div>
    </div>

    <!-- Events Preview -->
    <div class="section-preview" id="events">
      <h2>Upcoming Events (Preview)</h2>

      <?php if (count($events) === 0): ?>
        <div class="alert-warning">No upcoming events right now.</div>
      <?php else: ?>
        <table>
          <tr>
            <th>Event</th>
            <th>Date</th>
            <th>Location</th>
          </tr>
          <?php foreach ($events as $e): ?>
            <tr>
              <td><?= h($e["event_title"]); ?></td>
              <td><?= $e["event_date_time"] ? date("Y-m-d H:i", strtotime($e["event_date_time"])) : ""; ?></td>
              <td><?= h($e["event_location"]); ?></td>
            </tr>
          <?php endforeach; ?>
        </table>
      <?php endif; ?>

      <div class="alert-success" style="margin-top:10px;">
        ✅ Login to register and earn Eco-Points for attendance.
      </div>

      <div style="margin-top:10px;">
        <a class="btn" href="login/login.php">Login to register</a>
      </div>
    </div>

  </div>

  <!-- Rewards Preview (still ok to be static) -->
  <div class="section-preview" id="rewards">
    <h2>Rewards Preview</h2>
    <p>These rewards can be redeemed using Eco-Points (login required).</p>

    <table>
      <tr>
        <th>Reward</th>
        <th>Points Needed</th>
        <th>Status</th>
      </tr>
      <tr>
        <td>Reusable Bottle</td>
        <td>300</td>
        <td>Available</td>
      </tr>
      <tr>
        <td>Eco Tote Bag</td>
        <td>200</td>
        <td>Available</td>
      </tr>
      <tr>
        <td>RM10 Voucher</td>
        <td>500</td>
        <td>Limited</td>
      </tr>
    </table>

    <button type="button" onclick="window.location.href='login/login.php'">Login to Redeem</button>
  </div>

  <div class="section-preview" id="about">
    <h2>About ReLife Hub</h2>
    <p>
      ReLife Hub encourages sustainable habits by combining recycling logs, community content,
      and an Eco-Points reward system. Students can record recyclables, learn DIY upcycling,
      and join eco-events to contribute to a greener campus.
    </p>
  </div>

  <footer>
    <p>&copy; 2026 ReLife Hub</p>
    <p><a href="#">Privacy Policy</a> | <a href="#">Terms of Use</a> | <a href="#">Contact Us</a></p>
  </footer>


</body>
</html>
