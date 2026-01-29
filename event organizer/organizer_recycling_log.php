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
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_log'])) {
    $event_id = $_POST['event_id'] ?? '';
    $material_id = $_POST['material_id'] ?? '';
    $weight_kg = trim($_POST['weight_kg'] ?? '');
    $logged_by = trim($_POST['logged_by'] ?? '');
    $user_id_input = trim($_POST['user_id_input'] ?? '');
    $location = trim($_POST['location'] ?? '');
    
    if (empty($event_id)) {
        $error_message = "Please select an event.";
    } elseif (empty($material_id)) {
        $error_message = "Please select a material category.";
    } elseif (empty($weight_kg) || !is_numeric($weight_kg) || $weight_kg <= 0) {
        $error_message = "Please enter a valid weight (must be greater than 0).";
    } elseif (empty($logged_by)) {
        $error_message = "Please enter who logged this recyclable.";
    } elseif (empty($user_id_input)) {
        $error_message = "Please enter the User ID.";
    } else {
        // Check if user exists
        $user_check = "SELECT user_id FROM users WHERE user_id = '$user_id_input'";
        $user_result = $conn->query($user_check);
        
        if ($user_result && $user_result->num_rows > 0) {
            // Generate sequential log ID
            $last_id_query = "SELECT log_id FROM recycling_log ORDER BY log_id DESC LIMIT 1";
            $last_id_result = $conn->query($last_id_query);
            
            if ($last_id_result && $last_id_result->num_rows > 0) {
                $last_id = $last_id_result->fetch_assoc()['log_id'];
                $number = intval(substr($last_id, 3)) + 1;
                $log_id = 'rl_' . str_pad($number, 3, '0', STR_PAD_LEFT);
            } else {
                $log_id = 'rl_001';
            }
            
            // Calculate points based on material type (example: 10 points per kg)
            $points = intval($weight_kg * 10);
            
            // Handle event_id - use NULL if empty
            $event_id_sql = (empty($event_id) || $event_id == '') ? "NULL" : "'$event_id'";
            
            // Handle location
            $location_sql = empty($location) ? '' : $location;
            
            // Insert recycling log (photo_path is NULL for organizer entries)
            $insert_query = "INSERT INTO recycling_log 
                           (log_id, user_id, material_id, event_id, weight_kg, location, photo_path, status, points_awarded, is_flagged) 
                           VALUES ('$log_id', '$user_id_input', '$material_id', $event_id_sql, '$weight_kg', '$location_sql', NULL, 'VALID', $points, 0)";
            
            if ($conn->query($insert_query)) {
                // Generate unique transaction ID for eco_points_transactions
                $transaction_id = 'epr_' . str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT);
                
                while (true) {
                    $check_trans = "SELECT transaction_id FROM eco_points_transactions WHERE transaction_id = '$transaction_id'";
                    $check_trans_result = $conn->query($check_trans);
                    if ($check_trans_result->num_rows == 0) {
                        break;
                    }
                    $transaction_id = 'epr_' . str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT);
                }
                
                // Get material name for description
                $material_name_query = "SELECT materials_name FROM materials WHERE material_id = '$material_id'";
                $material_name_result = $conn->query($material_name_query);
                $material_name = $material_name_result->fetch_assoc()['materials_name'];
                
                // Create transaction record
                $description = "Points awarded for recycling $material_name materials.";
                $source_id = $log_id; // Reference to the recycling log
                
                $trans_query = "INSERT INTO eco_points_transactions 
                               (transaction_id, user_id, source_id, points_change, description) 
                               VALUES ('$transaction_id', '$user_id_input', '$source_id', $points, '$description')";
                
                if ($conn->query($trans_query)) {
                    // Update user's eco_points
                    $update_user_query = "UPDATE users SET eco_points = eco_points + $points WHERE user_id = '$user_id_input'";
                    
                    if ($conn->query($update_user_query)) {
                        $success_message = "Recyclable log submitted successfully! Points awarded: $points";
                        $selected_event_id = $event_id;
                    } else {
                        $error_message = "Log created but failed to update user points: " . $conn->error;
                    }
                } else {
                    $error_message = "Log created but failed to create transaction: " . $conn->error;
                }
            } else {
                $error_message = "Error submitting log to database: " . $conn->error;
            }
        } else {
            $error_message = "User ID not found in the system.";
        }
    }
}

// Fetch events created by this organizer
$events_query = "SELECT event_id, event_title, event_date_time FROM events WHERE event_created_by = '$user_id' ORDER BY event_date_time DESC";
$events_result = $conn->query($events_query);

// Fetch active materials
$materials_query = "SELECT material_id, materials_name FROM materials WHERE is_active = 1 ORDER BY materials_name ASC";
$materials_result = $conn->query($materials_query);

// Fetch recent logs for selected event
$recent_logs = [];
if (!empty($selected_event_id)) {
    $logs_query = "SELECT rl.log_id, rl.weight_kg, rl.submitted_at, m.materials_name, u.name 
                   FROM recycling_log rl
                   JOIN materials m ON rl.material_id = m.material_id
                   JOIN users u ON rl.user_id = u.user_id
                   WHERE rl.event_id = '$selected_event_id'
                   ORDER BY rl.submitted_at DESC
                   LIMIT 10";
    $logs_result = $conn->query($logs_query);
    
    if ($logs_result) {
        while ($row = $logs_result->fetch_assoc()) {
            $recent_logs[] = $row;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Recyclables Logging - Event Organizer</title>
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
      <h1>Recyclables Logging</h1>

      <?php if ($success_message): ?>
        <div class="alert alert-success"><?= $success_message ?></div>
      <?php endif; ?>
      
      <?php if ($error_message): ?>
        <div class="alert alert-danger"><?= $error_message ?></div>
      <?php endif; ?>

      <!-- Event Selection -->
      <form method="POST" action="" class="announcement-form">
        <div class="form-group">
          <label class="form-label">Select Event:</label>
          <select name="event_id" class="form-control" onchange="this.form.submit()">
            <option value="">-- Select Event --</option>
            <?php 
            if ($events_result && $events_result->num_rows > 0):
              $events_result->data_seek(0);
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

      <?php if (!empty($selected_event_id)): ?>
      <!-- Logging Form -->
      <form method="POST" action="" class="announcement-form">
        <input type="hidden" name="event_id" value="<?= $selected_event_id ?>">
        
        <div class="form-group announcement-form">
          <div class="form-group">
            <label class="form-label">Category: <span class="required">*</span></label>
            <select name="material_id" class="form-control" required>
              <option value="">-- Select Material --</option>
              <?php 
              if ($materials_result && $materials_result->num_rows > 0):
                $materials_result->data_seek(0);
                while ($material = $materials_result->fetch_assoc()):
              ?>
                <option value="<?= $material['material_id'] ?>">
                  <?= htmlspecialchars($material['materials_name']) ?>
                </option>
              <?php 
                endwhile;
              endif;
              ?>
            </select>
          </div>

          <div class="form-group">
            <label class="form-label">Weight (kg): <span class="required">*</span></label>
            <input type="number" name="weight_kg" class="form-control" 
                   step="0.01" min="0.01" placeholder="Enter weight in kg" required>
          </div>

          <div class="form-group">
            <label class="form-label">Log By: <span class="required">*</span></label>
            <input type="text" name="logged_by" class="form-control" 
                   placeholder="Name of person logging" value="<?= htmlspecialchars($_SESSION['name']) ?>" required>
          </div>

          <div class="form-group">
            <label class="form-label">User ID: <span class="required">*</span></label>
            <input type="text" name="user_id_input" class="form-control" 
                   placeholder="User ID who submitted recyclable" required>
            <small class="form-text">Enter the user ID of the person who submitted this recyclable</small>
          </div>

          <div class="form-group">
            <label class="form-label">Location (Optional):</label>
            <input type="text" name="location" class="form-control" 
                   placeholder="e.g., APU Bin 1, Library Bin">
          </div>
        </div>

        <div class="form-actions">
          <button type="submit" name="submit_log" class="action-btn">Submit Log</button>
        </div>
      </form>

      <?php if (count($recent_logs) > 0): ?>
      <!-- Recent Logs -->
      <div class="form-group">
        <h2>Recent Logs</h2>
        <div class="announcement-form">
          <?php foreach ($recent_logs as $log): ?>
          <div class="form-group">
            <strong><?= htmlspecialchars($log['materials_name']) ?></strong> 
            <span style="margin: 0 10px;"><?= number_format($log['weight_kg'], 2) ?> kg</span>
            <span class="form-text">By <?= htmlspecialchars($log['name']) ?></span>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
      <?php endif; ?>
      <?php endif; ?>
    </div>
  </div>

<?php include 'organizer_footer.php'; ?>
</body>
</html>
