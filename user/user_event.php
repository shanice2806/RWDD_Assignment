<?php
$pageTitle = "Events | ReLife Hub";
include __DIR__ . "/user_layout_top.php";
?>

<header>
  <h1>Events</h1>
  <p>Join eco activities and earn points</p>
</header>

<div class="search-bar">
  <input type="text" placeholder="Search events by title, location...">
  <button type="button">Search</button>
</div>

<div class="section-preview">
  <h2>Upcoming Events</h2>

  <div class="feed-card">
    <h3>Beach Cleanup @ Port Dickson</h3>
    <p><strong>Date:</strong> 25 Feb 2026 • <strong>Time:</strong> 9:00 AM</p>
    <p><strong>Location:</strong> Port Dickson</p>
    <p>Help clean the beach and learn waste sorting. (Design placeholder)</p>
    <a class="btn" href="event_detail.php">View Details</a>
    <a class="btn" href="#" style="margin-left:10px;">Join (Coming Soon)</a>
  </div>

  <div class="feed-card">
    <h3>Recycling Workshop (Campus)</h3>
    <p><strong>Date:</strong> 5 Mar 2026 • <strong>Time:</strong> 2:00 PM</p>
    <p><strong>Location:</strong> Main Hall</p>
    <p>Learn how to recycle correctly and earn badges. (Design placeholder)</p>
    <a class="btn" href="event_detail.php">View Details</a>
    <a class="btn" href="#" style="margin-left:10px;">Join (Coming Soon)</a>
  </div>
</div>

<div class="section-preview">
  <h2>My Registrations</h2>
  <table>
    <tr>
      <th>Event</th>
      <th>Date</th>
      <th>Status</th>
      <th>Action</th>
    </tr>
    <tr>
      <td>Beach Cleanup @ Port Dickson</td>
      <td>25 Feb 2026</td>
      <td>Registered</td>
      <td><a class="btn" href="#">Cancel (Coming Soon)</a></td>
    </tr>
  </table>
</div>

