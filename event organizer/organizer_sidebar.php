<?php
$current_page = basename($_SERVER['PHP_SELF']);
?>

<aside class="organizer-sidebar" id="sidebar">
  <nav class="sidebar-menu">

    <!-- Switch Dashboard -->
    <a href="organizer_dashboard.php" 
       class="menu-item <?= ($current_page == 'organizer_dashboard.php') ? 'active' : '' ?>">
       📊 Switch to User Dashboard
    </a>

    <!-- Event Management -->
    <div class="menu-group">
      <button class="menu-toggle">📅 Event Management</button>
      <div class="submenu">
        <a href="create_event.php" class="<?= ($current_page == 'create_event.php') ? 'active' : '' ?>">Create Event</a>
        <a href="organizer_event_view.php" class="<?= ($current_page == 'organizer_event_view.php') ? 'active' : '' ?>">View All Events</a>
        <a href="edit_event.php" class="<?= ($current_page == 'edit_event.php') ? 'active' : '' ?>">Edit Events</a>
        <a href="archive_event.php" class="<?= ($current_page == 'archive_event.php') ? 'active' : '' ?>">Archive Events</a>
      </div>
    </div>

    <!-- Announcement & Recaps -->
    <div class="menu-group">
      <button class="menu-toggle">📢 Announcement & Recaps</button>
      <div class="submenu">
        <a href="view_announcements.php" class="<?= ($current_page == 'view_announcements.php') ? 'active' : '' ?>">View All Announcements</a>
        <a href="post_announcement.php" class="<?= ($current_page == 'post_announcement.php') ? 'active' : '' ?>">Post Announcement</a>
        <a href="send_notification.php" class="<?= ($current_page == 'send_notification.php') ? 'active' : '' ?>">Send Notification</a>
        <a href="post_recap.php" class="<?= ($current_page == 'post_recap.php') ? 'active' : '' ?>">Post Recap Summary</a>
      </div>
    </div>

    <!-- Registration & Attendance -->
    <div class="menu-group">
      <button class="menu-toggle">📝 Registration & Attendance</button>
      <div class="submenu">
        <a href="open_close_registration.php" class="<?= ($current_page == 'open_close_registration.php') ? 'active' : '' ?>">Open/Close Registration</a>
        <a href="organizer_registration_list.php" class="<?= ($current_page == 'organizer_registration_list.php') ? 'active' : '' ?>">View Registration List</a>
        <a href="generate_qr.php" class="<?= ($current_page == 'generate_qr.php') ? 'active' : '' ?>">Generate/View QR Code</a>
        <a href="manual_attendance.php" class="<?= ($current_page == 'manual_attendance.php') ? 'active' : '' ?>">Manual Attendance Marking</a>
        <a href="organizer_attendance_log.php" class="<?= ($current_page == 'organizer_attendance_log.php') ? 'active' : '' ?>">View Attendance List</a>
      </div>
    </div>

    <!-- Sustainability Tracking -->
    <div class="menu-group">
      <button class="menu-toggle">🌱 Sustainability Tracking</button>
      <div class="submenu">
        <a href="track_sustainability.php" class="<?= ($current_page == 'track_sustainability.php') ? 'active' : '' ?>">Track Progress</a>
        <a href="view_reports.php" class="<?= ($current_page == 'view_reports.php') ? 'active' : '' ?>">View Reports</a>
      </div>
    </div>

    <!-- Collaboration & Feedback -->
    <div class="menu-group">
      <button class="menu-toggle">💬 Collaboration & Feedback</button>
      <div class="submenu">
        <a href="submit_feedback.php" class="<?= ($current_page == 'submit_feedback.php') ? 'active' : '' ?>">Submit Feedback</a>
        <a href="view_feedback.php" class="<?= ($current_page == 'view_feedback.php') ? 'active' : '' ?>">View Feedback</a>
        <a href="collaborate.php" class="<?= ($current_page == 'collaborate.php') ? 'active' : '' ?>">Collaborate</a>
      </div>
    </div>

    <!-- Archive & Showcase -->
    <div class="menu-group">
      <button class="menu-toggle">📁 Archive & Showcase</button>
      <div class="submenu">
        <a href="archive_gallery.php" class="<?= ($current_page == 'archive_gallery.php') ? 'active' : '' ?>">Archive Gallery</a>
        <a href="showcase_projects.php" class="<?= ($current_page == 'showcase_projects.php') ? 'active' : '' ?>">Showcase Projects</a>
      </div>
    </div>

    <!-- Profile -->
    <a href="organizer_profile_view.php" 
       class="menu-item <?= ($current_page == 'organizer_profile_view.php') ? 'active' : '' ?>">
       👤 View Profile
    </a>

    <!-- Logout -->
    <a href="../login/logout.php" class="menu-item">⎋ Log Out</a>

    <footer class="sidebar-footer">© 2026 ReLife Hub</footer>
  </nav>
</aside>
