<?php
session_start();

// Check if user is an organizer
if (!isset($_SESSION["user_id"]) || !in_array(strtolower($_SESSION["role"]), ["event organizer","event_organizer","organizer"])) {
    header("Location: ../login/login.php");
    exit();
}

include '../connect.php';

$success_message = '';
$error_message = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_volunteer'])) {
    $event_id = $_POST['event_id'] ?? '';
    $volunteer_name = trim($_POST['volunteer_name'] ?? '');
    $user_id = trim($_POST['user_id'] ?? '');
    $role_interests = $_POST['role_interests'] ?? [];
    $notes = trim($_POST['notes'] ?? '');
    
    if (empty($event_id)) {
        $error_message = "Please select an event.";
    } elseif (empty($volunteer_name)) {
        $error_message = "Please enter volunteer name.";
    } elseif (empty($user_id)) {
        $error_message = "Please enter user ID.";
    } elseif (empty($role_interests)) {
        $error_message = "Please select at least one task interest.";
    } else {
        // Convert array to comma-separated string
        $task_interested_str = implode(', ', $role_interests);
        
        // Check if user exists and name matches user_id
        $user_query = "SELECT user_id, name FROM users WHERE user_id = '$user_id' AND name = '$volunteer_name' LIMIT 1";
        $user_result = $conn->query($user_query);
        
        if ($user_result && $user_result->num_rows > 0) {
            $user = $user_result->fetch_assoc();
            
            // Check if already a volunteer for this event
            $check_query = "SELECT * FROM volunteers WHERE event_id = '$event_id' AND user_id = '$user_id'";
            $check_result = $conn->query($check_query);
            
            if ($check_result && $check_result->num_rows > 0) {
                $error_message = "This user is already a volunteer for this event.";
            } else {
                // Insert volunteer with task_interested and notes
                $insert_query = "INSERT INTO volunteers (event_id, user_id, task_interested, notes, volunteer_hours_logged) 
                                VALUES ('$event_id', '$user_id', '$task_interested_str', '$notes', 0.00)";
                
                if ($conn->query($insert_query)) {
                    $success_message = "Volunteer '$volunteer_name' (ID: $user_id) recruited successfully!";
                } else {
                    $error_message = "Error recruiting volunteer: " . $conn->error;
                }
            }
        } else {
            $error_message = "User not found or name doesn't match User ID. Please verify both the name and User ID are correct.";
        }
    }
}

// Get all approved events for the organizer
$organizer_id = $_SESSION['user_id'];
$events_query = "SELECT event_id, event_title, event_date_time 
                 FROM events 
                 WHERE event_created_by = '$organizer_id' AND event_status = 'Approved' 
                 ORDER BY event_date_time DESC";
$events_result = $conn->query($events_query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recruit Volunteers</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/organizer.css">
</head>
<body>
    <?php include 'organizer_header.php'; ?>
    
    <div class="dashboard-container">
        <?php include 'organizer_sidebar.php'; ?>
        
        <div class="main-content">
    <div class="back-btn-container">
      <button onclick="history.back()" class="action-btn">← Back</button>
    </div>
            <div class="container container-narrow">
                <h1>Recruit Volunteers</h1>
                
                <?php if ($success_message): ?>
                    <div class="alert alert-success"><?= $success_message ?></div>
                <?php endif; ?>
                
                <?php if ($error_message): ?>
                    <div class="alert alert-danger"><?= $error_message ?></div>
                <?php endif; ?>
                
                <form method="POST" class="announcement-form">
                    <div class="form-group">
                        <label class="form-label">Select Event: <span class="required">*</span></label>
                        <select name="event_id" class="form-control" required>
                            <option value="">-- Select Event --</option>
                            <?php if ($events_result && $events_result->num_rows > 0): ?>
                                <?php while ($event = $events_result->fetch_assoc()): ?>
                                    <option value="<?= $event['event_id'] ?>" 
                                        <?= (isset($_POST['event_id']) && $_POST['event_id'] == $event['event_id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($event['event_title']) ?> - <?= date('d/m/Y', strtotime($event['event_date_time'])) ?>
                                    </option>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <option value="">No approved events found</option>
                            <?php endif; ?>
                        </select>
                    </div>
                    
                    <div class="form-group" style="background: #f9f9f9; padding: 20px; border-radius: 8px; border: 1px solid #ddd;">
                        <h3 style="margin-top: 0; font-size: 16px; color: #333;">Volunteer Information</h3>
                        
                        <div class="form-group">
                            <label class="form-label">Volunteer Name: <span class="required">*</span></label>
                            <input type="text" name="volunteer_name" class="form-control" required 
                                   placeholder="e.g., Adam Tan, Siti Aisyah"
                                   value="<?= isset($_POST['volunteer_name']) ? htmlspecialchars($_POST['volunteer_name']) : '' ?>">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">User ID: <span class="required">*</span></label>
                            <input type="text" name="user_id" class="form-control" required 
                                   placeholder="e.g., TP000002, TP000006"
                                   value="<?= isset($_POST['user_id']) ? htmlspecialchars($_POST['user_id']) : '' ?>">
                            <small class="form-text">Both name and User ID must match to ensure the correct person</small>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Task Interested (Select all that apply): <span class="required">*</span></label>
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-top: 10px;">
                                <?php
                                $tasks = [
                                    'Registration Desk',
                                    'Setup/Logistics',
                                    'Recyclable Collection Assistant',
                                    'Crowd Management',
                                    'Log Verification Assistant',
                                    'Information Booth Support',
                                    'Event Setup and Teardown',
                                    'Volunteer Coordination',
                                    'Waste Sorting Supervisor',
                                    'Eco-Points Assistance Desk',
                                    'Photography',
                                    'Social Media',
                                    'Other'
                                ];
                                $selected = $_POST['role_interests'] ?? [];
                                foreach ($tasks as $task):
                                    $checked = in_array($task, $selected) ? 'checked' : '';
                                ?>
                                    <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                                        <input type="checkbox" name="role_interests[]" value="<?= $task ?>" <?= $checked ?>>
                                        <span style="font-size: 14px;"><?= $task ?></span>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                            <small class="form-text">Select volunteer's task interests. Actual task will be assigned later.</small>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Additional Notes (Optional):</label>
                            <textarea name="notes" class="form-control" rows="3" 
                                      placeholder="e.g., Available weekends only, Has photography equipment, First time volunteer"><?= isset($_POST['notes']) ? htmlspecialchars($_POST['notes']) : '' ?></textarea>
                            <small class="form-text">Any special notes about this volunteer</small>
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" name="add_volunteer" class="action-btn">Add Volunteer</button>
                    </div>
                </form>
                
                <div class="text-center mt-20">
                    <a href="organizer_volunteer_list.php" class="action-btn">View Volunteer List</a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
