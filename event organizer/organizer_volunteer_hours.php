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
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['log_hours'])) {
    $event_id = $_POST['event_id'] ?? '';
    $volunteer_id = $_POST['volunteer_id'] ?? '';
    $hours = trim($_POST['hours'] ?? '');
    $task_performed = trim($_POST['task_performed'] ?? '');
    
    if (empty($event_id)) {
        $error_message = "Please select an event.";
    } elseif (empty($volunteer_id)) {
        $error_message = "Please select a volunteer.";
    } elseif (empty($hours) || !is_numeric($hours) || $hours <= 0) {
        $error_message = "Please enter a valid number of hours.";
    } elseif (empty($task_performed)) {
        $error_message = "Please enter the task performed.";
    } else {
        // Get current hours
        $current_query = "SELECT volunteer_hours_logged FROM volunteers WHERE volunteer_id = '$volunteer_id'";
        $current_result = $conn->query($current_query);
        
        if ($current_result && $current_result->num_rows > 0) {
            $current_data = $current_result->fetch_assoc();
            $current_hours = floatval($current_data['volunteer_hours_logged']);
            $new_total = $current_hours + floatval($hours);
            
            // Update volunteer hours (add to existing)
            $update_query = "UPDATE volunteers 
                            SET volunteer_hours_logged = '$new_total', volunteer_task = '$task_performed' 
                            WHERE volunteer_id = '$volunteer_id' AND event_id = '$event_id'";
            
            if ($conn->query($update_query)) {
                $success_message = "Hours logged successfully! Total hours: " . number_format($new_total, 2);
                $selected_event_id = $event_id;
            } else {
                $error_message = "Error logging hours: " . $conn->error;
            }
        } else {
            $error_message = "Volunteer not found.";
        }
    }
}

// Fetch events created by this organizer
$events_query = "SELECT event_id, event_title, event_date_time FROM events WHERE event_created_by = '$user_id' ORDER BY event_date_time DESC";
$events_result = $conn->query($events_query);

// Fetch volunteers for selected event
$volunteers = [];
if (!empty($selected_event_id)) {
    $volunteers_query = "SELECT v.volunteer_id, v.volunteer_task, v.volunteer_hours_logged, u.name, u.user_id 
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

include 'organizer_header.php';
include 'organizer_sidebar.php';
?>

<div class="dashboard-container">
    <div class="main-content">
    <div class="back-btn-container">
      <button onclick="history.back()" class="action-btn">← Back</button>
    </div>
        <div class="container container-narrow">
            <h1 style="color: #1e3a34; margin-bottom: 10px;">Volunteer Hours</h1>
            <p style="color: #6c757d; margin-bottom: 30px;">Log volunteer hours for your events</p>
            <?php if ($success_message): ?>
                <div class="alert alert-success"><?= $success_message ?></div>
            <?php endif; ?>
            
            <?php if ($error_message): ?>
                <div class="alert alert-danger"><?= $error_message ?></div>
            <?php endif; ?>

            <!-- Event Selection Form -->
            <form method="POST" action="" class="announcement-form" style="margin-bottom: 30px;">
                <div class="form-group">
                    <label class="form-label">Select Event: <span class="required">*</span></label>
                    <select name="event_id" class="form-control" onchange="this.form.submit()" required>
                        <option value="">-- Select Event --</option>
                        <?php 
                        if ($events_result && $events_result->num_rows > 0):
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
                <!-- Log Hours Form -->
                <form method="POST" action="" class="announcement-form">
                    <input type="hidden" name="event_id" value="<?= $selected_event_id ?>">
                    
                    <div class="form-group" style="background: #f9f9f9; padding: 20px; border-radius: 8px; border: 1px solid #ddd;">
                        <div class="form-group">
                            <label class="form-label">Volunteer: <span class="required">*</span></label>
                            <select name="volunteer_id" class="form-control" id="volunteer_select" onchange="updateVolunteerInfo()" required>
                                <option value="">-- Select Volunteer --</option>
                                <?php foreach ($volunteers as $volunteer): ?>
                                    <option value="<?= $volunteer['volunteer_id'] ?>" 
                                            data-task="<?= htmlspecialchars($volunteer['volunteer_task'] ?? 'Not assigned') ?>"
                                            data-hours="<?= number_format($volunteer['volunteer_hours_logged'], 2) ?>">
                                        <?= htmlspecialchars($volunteer['name']) ?> (ID: <?= $volunteer['user_id'] ?>) - Current: <?= number_format($volunteer['volunteer_hours_logged'], 2) ?>h
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Hours Contributed: <span class="required">*</span></label>
                            <input type="number" name="hours" class="form-control" 
                                   step="0.5" min="0.5" placeholder="Enter hours (e.g., 2.5)" required>
                            <small class="form-text">Enter the number of hours worked in this session</small>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Task Performed: <span class="required">*</span></label>
                            <select name="task_performed" class="form-control" id="task_select" required>
                                <option value="">-- Select Task --</option>
                                <option value="Registration Desk">Registration Desk</option>
                                <option value="Setup/Logistics">Setup/Logistics</option>
                                <option value="Recyclable Collection Assistant">Recyclable Collection Assistant</option>
                                <option value="Sorting and Categorization">Sorting and Categorization</option>
                                <option value="Data Entry">Data Entry</option>
                                <option value="Material Handling and Transportation">Material Handling and Transportation</option>
                                <option value="Education and Outreach">Education and Outreach</option>
                                <option value="Photography/Videography">Photography/Videography</option>
                                <option value="Event Host/MC">Event Host/MC</option>
                                <option value="First Aid/Safety Officer">First Aid/Safety Officer</option>
                                <option value="Cleanup Crew">Cleanup Crew</option>
                                <option value="Guest Relations">Guest Relations</option>
                                <option value="Technical Support">Technical Support</option>
                            </select>
                            <small class="form-text" id="current_task_info" style="color: #666;"></small>
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="submit" name="log_hours" class="action-btn">Log Hours</button>
                    </div>
                </form>

                <div style="margin-top: 30px; display: flex; gap: 15px; justify-content: center;">
                    <a href="organizer_award_badges.php?event_id=<?= $selected_event_id ?>" class="action-btn">Award Badges</a>
                    <a href="organizer_volunteer_list.php?event_id=<?= $selected_event_id ?>" class="action-btn">View Volunteer List</a>
                </div>
            <?php else: ?>
                <div class="alert alert-info">Please select an event to log volunteer hours.</div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function updateVolunteerInfo() {
    const select = document.getElementById('volunteer_select');
    const taskInfo = document.getElementById('current_task_info');
    const taskSelect = document.getElementById('task_select');
    
    if (select.value) {
        const selectedOption = select.options[select.selectedIndex];
        const currentTask = selectedOption.getAttribute('data-task');
        const currentHours = selectedOption.getAttribute('data-hours');
        
        if (currentTask && currentTask !== 'Not assigned') {
            taskInfo.textContent = `Current task: ${currentTask} | Total hours logged: ${currentHours}h`;
            // Pre-select the current task
            taskSelect.value = currentTask;
        } else {
            taskInfo.textContent = `No task assigned yet | Total hours logged: ${currentHours}h`;
        }
    } else {
        taskInfo.textContent = '';
    }
}
</script>

<?php include 'organizer_footer.php'; ?>
