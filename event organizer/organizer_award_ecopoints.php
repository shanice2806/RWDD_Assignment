<?php
session_start();

// Check if user is an organizer
if (!isset($_SESSION["user_id"]) || !in_array(strtolower($_SESSION["role"]), ["event organizer","event_organizer","organizer"])) {
    header("Location: ../login/login.php");
    exit();
}

include '../connect.php';

$user_id = $_SESSION['user_id'];
$selected_event_id = $_POST['event_id'] ?? ($_GET['event_id'] ?? '');
$success_message = '';
$error_message = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['award_points'])) {
    $event_id = $_POST['event_id'] ?? '';
    $volunteer_ids = $_POST['volunteer_ids'] ?? [];
    $points = trim($_POST['points'] ?? '');
    $reason = trim($_POST['reason'] ?? '');
    
    if (empty($event_id)) {
        $error_message = "Please select an event.";
    } elseif (empty($volunteer_ids)) {
        $error_message = "Please select at least one volunteer.";
    } elseif (empty($points) || !is_numeric($points) || $points <= 0) {
        $error_message = "Please enter a valid number of eco-points (must be greater than 0).";
    } else {
        $points = intval($points);
        $reason_text = !empty($reason) ? $reason : "Points awarded for volunteering at event";
        
        // Get event details for transaction description
        $event_query = "SELECT event_title FROM events WHERE event_id = '$event_id'";
        $event_result = $conn->query($event_query);
        $event_title = '';
        if ($event_result && $event_result->num_rows > 0) {
            $event_title = $event_result->fetch_assoc()['event_title'];
        }
        
        $success_count = 0;
        $failed_count = 0;
        
        // Award points to each selected volunteer
        foreach ($volunteer_ids as $volunteer_id) {
            // Get volunteer's user_id
            $vol_query = "SELECT v.user_id, u.name 
                         FROM volunteers v 
                         JOIN users u ON v.user_id = u.user_id 
                         WHERE v.volunteer_id = '$volunteer_id' AND v.event_id = '$event_id'";
            $vol_result = $conn->query($vol_query);
            
            if ($vol_result && $vol_result->num_rows > 0) {
                $vol_data = $vol_result->fetch_assoc();
                $vol_user_id = $vol_data['user_id'];
                $vol_name = $vol_data['name'];
                
                // Generate unique transaction ID
                $transaction_id = 'ept_' . str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT);
                
                // Check if transaction ID exists
                while (true) {
                    $check_query = "SELECT transaction_id FROM eco_points_transactions WHERE transaction_id = '$transaction_id'";
                    $check_result = $conn->query($check_query);
                    if ($check_result->num_rows == 0) {
                        break;
                    }
                    $transaction_id = 'ept_' . str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT);
                }
                
                // Build source_id as event_id reference
                $source_id = 'evt_' . str_pad($event_id, 3, '0', STR_PAD_LEFT);
                
                // Create transaction description
                $description = $event_title ? "Eco-points awarded for volunteering at: $event_title. $reason_text" : "Eco-points awarded for volunteering. $reason_text";
                
                // Insert transaction record
                $trans_query = "INSERT INTO eco_points_transactions 
                               (transaction_id, user_id, source_id, points_change, description) 
                               VALUES ('$transaction_id', '$vol_user_id', '$source_id', $points, '$description')";
                
                if ($conn->query($trans_query)) {
                    // Update user's eco_points
                    $update_query = "UPDATE users 
                                    SET eco_points = eco_points + $points 
                                    WHERE user_id = '$vol_user_id'";
                    
                    if ($conn->query($update_query)) {
                        $success_count++;
                    } else {
                        $failed_count++;
                    }
                } else {
                    $failed_count++;
                }
            } else {
                $failed_count++;
            }
        }
        
        if ($success_count > 0) {
            $success_message = "Successfully awarded $points eco-points to $success_count volunteer(s)!";
        }
        if ($failed_count > 0) {
            $error_message = "Failed to award points to $failed_count volunteer(s).";
        }
        
        $selected_event_id = $event_id;
    }
}

// Fetch events created by this organizer
$events_query = "SELECT event_id, event_title, event_date_time FROM events WHERE event_created_by = '$user_id' ORDER BY event_date_time DESC";
$events_result = $conn->query($events_query);

// Fetch volunteers for selected event
$volunteers = [];
if (!empty($selected_event_id)) {
    $volunteers_query = "SELECT v.volunteer_id, v.volunteer_task, v.volunteer_hours_logged, u.name, u.user_id, u.eco_points 
                        FROM volunteers v 
                        JOIN users u ON v.user_id = u.user_id 
                        WHERE v.event_id = '$selected_event_id' 
                        ORDER BY u.name ASC";
    $volunteers_result = $conn->query($volunteers_query);
    
    if ($volunteers_result) {
        while ($row = $volunteers_result->fetch_assoc()) {
            $volunteers[] = $row;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Award Eco-Points - Event Organizer</title>
  <link rel="stylesheet" href="../css/style.css">
  <link rel="stylesheet" href="../css/organizer.css">
</head>
<body>
  <?php include 'organizer_header.php'; ?>

  <div class="main-content">
    <div class="back-btn-container">
      <button onclick="history.back()" class="action-btn">← Back</button>
    </div>
    <div class="container container-narrow">
      <h1>Award Volunteer Eco-Points</h1>
      <p class="form-text">Reward volunteers with eco-points for their contributions</p>

      <?php if ($success_message): ?>
        <div class="alert alert-success"><?= $success_message ?></div>
      <?php endif; ?>
      
      <?php if ($error_message): ?>
        <div class="alert alert-danger"><?= $error_message ?></div>
      <?php endif; ?>

      <!-- Event Selection Form -->
      <form method="POST" action="" class="announcement-form">
        <div class="form-group">
          <label class="form-label">Select Event: <span class="required">*</span></label>
          <select name="event_id" class="form-control" onchange="this.form.submit()" required>
            <option value="">-- Select Event --</option>
            <?php 
            if ($events_result && $events_result->num_rows > 0):
              $events_result->data_seek(0); // Reset pointer
              while ($event = $events_result->fetch_assoc()):
                $selected = ($event['event_id'] == $selected_event_id) ? 'selected' : '';
            ?>
              <option value="<?= $event['event_id'] ?>" <?= $selected ?>>
                <?= htmlspecialchars($event['event_title']) ?> - <?= date('M d, Y', strtotime($event['event_date_time'])) ?>
              </option>
            <?php 
              endwhile;
            endif;
            ?>
          </select>
        </div>
      </form>

      <?php if (!empty($selected_event_id) && count($volunteers) > 0): ?>
      <!-- Award Eco-Points Form -->
      <form method="POST" action="" class="announcement-form">
        <input type="hidden" name="event_id" value="<?= $selected_event_id ?>">
        
        <div class="form-group announcement-form">
          <div class="form-group">
            <label class="form-label">
              <input type="checkbox" id="select-all">
              Volunteer(s): <span class="required">*</span>
            </label>
            <div class="form-control" style="height: auto; max-height: 250px; overflow-y: auto;">
              <?php foreach ($volunteers as $volunteer): ?>
              <div class="form-group">
                <label class="form-label">
                  <input type="checkbox" name="volunteer_ids[]" value="<?= $volunteer['volunteer_id'] ?>" 
                         class="volunteer-checkbox">
                  <strong><?= htmlspecialchars($volunteer['name']) ?></strong>
                    <br>
                    <small class="form-text">
                      Current Points: <?= $volunteer['eco_points'] ?> | 
                      Hours Logged: <?= number_format($volunteer['volunteer_hours_logged'], 2) ?>h
                      <?php if ($volunteer['volunteer_task'] && $volunteer['volunteer_task'] != 'Not assigned'): ?>
                        | Task: <?= htmlspecialchars($volunteer['volunteer_task']) ?>
                      <?php endif; ?>
                    </small>
                </label>
              </div>
              <?php endforeach; ?>
            </div>
          </div>

          <div class="form-group">
            <label class="form-label">Eco-Points to Award: <span class="required">*</span></label>
            <input type="number" name="points" class="form-control" 
                   placeholder="Enter number of eco-points" 
                   min="1" step="1" required>
            <small class="form-text">
              Enter the number of eco-points to award to selected volunteers
            </small>
          </div>

          <div class="form-group">
            <label class="form-label">Reason (Optional):</label>
            <textarea name="reason" class="form-control" rows="3" 
                      placeholder="e.g., Exceptional participation, completing all tasks, etc."></textarea>
          </div>
        </div>

        <div class="form-actions">
          <button type="submit" name="award_points" class="action-btn">Award Eco-Points</button>
        </div>
      </form>

      <!-- Volunteer List -->
      <div class="form-group">
        <h2>Volunteer List for Selected Event</h2>
          <table class="participant-table">
            <thead>
              <tr>
                <th>Name</th>
                <th>Current Eco-Points</th>
                <th>Hours Logged</th>
                <th>Task</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($volunteers as $volunteer): ?>
              <tr>
                <td><?= htmlspecialchars($volunteer['name']) ?></td>
                <td><?= $volunteer['eco_points'] ?></td>
                <td><?= number_format($volunteer['volunteer_hours_logged'], 2) ?>h</td>
                <td><?= htmlspecialchars($volunteer['volunteer_task'] ?: 'Not assigned') ?></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
      <?php elseif (!empty($selected_event_id)): ?>
      <div class="alert alert-info">No volunteers found for this event. Please recruit volunteers first.</div>
      <?php endif; ?>
    </div>
  </div>

<script>
// Select All checkbox functionality
document.getElementById('select-all')?.addEventListener('change', function() {
    const checkboxes = document.querySelectorAll('.volunteer-checkbox');
    checkboxes.forEach(checkbox => {
        checkbox.checked = this.checked;
    });
});

// Update "Select All" if individual checkboxes change
document.querySelectorAll('.volunteer-checkbox').forEach(checkbox => {
    checkbox.addEventListener('change', function() {
        const allCheckboxes = document.querySelectorAll('.volunteer-checkbox');
        const checkedCheckboxes = document.querySelectorAll('.volunteer-checkbox:checked');
        const selectAllCheckbox = document.getElementById('select-all');
        
        if (selectAllCheckbox) {
            selectAllCheckbox.checked = allCheckboxes.length === checkedCheckboxes.length;
        }
    });
});
</script>

<?php include 'organizer_footer.php'; ?>
</body>
</html>
