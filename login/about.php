<?php


session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>About ReLife Hub</title>
  <link rel="stylesheet" href="../style.css">
  <style>
    body{
    font-family:Arial,sans-serif;
    background:#f5f6f8;
    margin:0}

    .container{
    max-width:900px;
    margin:24px auto;
    padding:0 16px}

    .card{
    background:#fff;
    border-radius:12px;
    box-shadow:0 6px 18px rgba(0,0,0,.08);
    padding:20px;
    margin-bottom:16px}

    h1{margin:0 0 6px;
    font-size:26px}

    .subtitle{
    color:#6b7280;
    margin:0 0 12px}

    h2{
    margin:18px 0 8px;
    font-size:18px}

    p{
      color:#374151;
      line-height:1.6;
      margin:10px 0}

    ul{margin:8px 0 0 18px;
    color:#374151;
    line-height:1.6}

    .grid{display:grid;
    grid-template-columns:repeat(2,minmax(0,1fr));
    gap:12px}

    .mini{border:1px solid #e5e7eb;
    border-radius:12px;
    padding:14px;
    background:#fff}

    .tag{display:inline-block;
    padding:4px 10px;
    border-radius:999px;
    background:#eef2ff;
    color:#1f6feb;
    font-size:12px;
    font-weight:700}

    .btnrow{display:flex;
    gap:10px;
    flex-wrap:wrap;
    margin-top:10px}

    .btn{display:inline-block;
    padding:10px 12px;
    border-radius:10px;
    border:1px solid #e5e7eb;
    text-decoration:none;
    color:#111827;
    font-weight:700;
    background:#fff}

    .btn.primary{background:#1f6feb;
    border-color:#1f6feb;
    color:#fff}
    
    footer{padding:16px;text-align:center;color:#6b7280;font-size:13px}
    @media (max-width:768px){
      .grid{grid-template-columns:1fr}
    }
  </style>
</head>
<body>

  <div class="btn">
    <a href="login.php">Back to Login</a>
  </div>

  <div class="container">

    <div class="card">
      <span class="tag">About</span>
      <h1>ReLife Hub</h1>
      <p class="subtitle">
        A sustainability and community engagement platform that encourages recycling habits, event participation, and volunteerism.
      </p>

      <p>
        ReLife Hub is designed to support a greener campus/community by connecting users with approved eco-events,
        volunteer opportunities, and reward mechanisms. The platform provides a simple digital space where users can
        explore events, join activities, and track their contributions through eco points.
      </p>

      <div class="btnrow">
        <a class="btn primary" href="login.php">Sign In</a>
        <a class="btn" href="register.php">Create Account</a>
      </div>
    </div>

    <div class="card">
      <h2>Key Features</h2>
      <div class="grid">
        <div class="mini">
          <h3 style="margin:0 0 6px;">📅 Events</h3>
          <p style="margin:0;">
            Browse approved events, view details, and register to participate. Events are organized to promote environmental awareness and action.
          </p>
        </div>
        <div class="mini">
          <h3 style="margin:0 0 6px;">🤝 Volunteer Program</h3>
          <p style="margin:0;">
            Users can apply as volunteers for events. Organizers may later assign specific tasks based on event needs.
          </p>
        </div>
        <div class="mini">
          <h3 style="margin:0 0 6px;">🪙 Eco Points & Rewards</h3>
          <p style="margin:0;">
            Users earn eco points through participation and contributions. Points can support rewards, recognition, or leaderboard features.
          </p>
        </div>
        <div class="mini">
          <h3 style="margin:0 0 6px;">👥 Community</h3>
          <p style="margin:0;">
            Encourage social engagement through friend features, collaboration, and shared sustainability goals.
          </p>
        </div>
      </div>
    </div>

    <div class="card">
      <h2>User Roles</h2>
      <p>
        ReLife Hub supports multiple roles to ensure the system runs smoothly:
      </p>
      <ul>
        <li><strong>User:</strong> Joins events, applies as volunteer, earns points, and manages profile.</li>
        <li><strong>Event Organizer:</strong> Manages events, reviews volunteer applications, and assigns tasks when needed.</li>
        <li><strong>Admin:</strong> Oversees platform operations and user accounts (e.g., approvals, moderation, maintenance).</li>
      </ul>
    </div>

    <div class="card">
      <h2>How We Protect the Platform</h2>
      <ul>
        <li><strong>Login & Session Control:</strong> Access to dashboards requires authentication.</li>
        <li><strong>Role-based Access:</strong> Pages and actions are restricted based on user roles (User / Organizer / Admin).</li>
        <li><strong>Account Status:</strong> Deactivated accounts are blocked to maintain platform safety.</li>
      </ul>
      <p style="margin-top:10px;color:#6b7280;">
        Note: Specific implementations depend on your system requirements and database structure.
      </p>
    </div>

    <div class="card">
      <h2>Contact & Support</h2>
      <p>
        For assistance, please contact the system administrator or your event organizer.
        If you forget your password, use the “Forgot password?” function on the login page.
      </p>
    </div>

  </div>

  <footer>
    © 2026 ReLife Hub
  </footer>

</body>
</html>
