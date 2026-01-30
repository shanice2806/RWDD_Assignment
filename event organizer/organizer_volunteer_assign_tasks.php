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
$selected_event_id = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['assign_task'])) {
    $event_id = $_POST['event_id'] ?? '';
    $volunteer_id = $_POST['volunteer_id'] ?? '';
    $task = trim($_POST['task'] ?? '');
    $time_slots = $_POST['time_slots'] ?? [];
    
    if (empty($event_id)) {
        $error_message = "Please select an event.";
    } elseif (empty($volunteer_id)) {
        $error_message = "Please select a volunteer.";
    } elseif (empty($task)) {
        $error_message = "Please enter a task.";
    } elseif (empty($time_slots)) {
        $error_message = "Please select at least one time slot.";
    } else {
        // Convert array to comma-separated string
        $time_slot_str = implode(', ', $time_slots);
        
        // Update volunteer with assigned task and time slot
        $update_query = "UPDATE volunteers 
                        SET volunteer_task = '$task', time_slot = '$time_slot_str' 
                        WHERE volunteer_id = '$volunteer_id' AND event_id = '$event_id'";
        
        if ($conn->query($update_query)) {
            $success_message = "Task assigned successfully!";
            $selected_event_id = $event_id;
        } else {
            $error_message = "Error assigning task: " . $conn->error;
        }
    }
}

// Get event_id from POST or GET
if (isset($_POST['event_id'])) {
    $selected_event_id = $_POST['event_id'];
} elseif (isset($_GET['event_id'])) {
    $selected_event_id = $_GET['event_id'];
}

// Get all approved events for the organizer
$organizer_id = $_SESSION['user_id'];
$events_query = "SELECT event_id, event_title, event_date_time 
                 FROM events 
                 WHERE event_created_by = '$organizer_id' AND event_status = 'Approved' 
                 ORDER BY event_date_time DESC";
$events_result = $conn->query($events_query);

// Get volunteers for selected event
$volunteers = [];
if (!empty($selected_event_id)) {
    $volunteers_query = "SELECT v.volunteer_id, v.user_id, u.name, v.task_interested, v.volunteer_task, v.time_slot, v.notes
                         FROM volunteers v
                         JOIN users u ON v.user_id = u.user_id
                         WHERE v.event_id = '$selected_event_id'
                         ORDER BY u.name ASC";
    $volunteers_result = $conn->query($volunteers_query);
    if ($volunteers_result) {
        while ($vol = $volunteers_result->fetch_assoc()) {
            $volunteers[] = $vol;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assign Volunteer Task</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/organizer.css">
    <script>
        function loadVolunteers() {
            const form = document.getElementById('eventForm');
            form.submit();
        }
        
        function updateTaskField() {
            const volunteerSelect = document.getElementById('volunteer_id');
            const taskField = document.getElementById('task');
            const selectedOption = volunteerSelect.options[volunteerSelect.selectedIndex];
            const notesSection = document.getElementById('notes-section');
            const notesText = document.getElementById('notes-text');
            
            if (selectedOption.value) {
                const interests = selectedOption.getAttribute('data-interests');
                const currentTask = selectedOption.getAttribute('data-task');
                const notes = selectedOption.getAttribute('data-notes');
                
                // Update task field
                if (currentTask && currentTask !== '') {
                    taskField.value = currentTask;
                } else if (interests) {
                    taskField.placeholder = 'Interests: ' + interests;
                }
                
                // Show/hide notes section
                if (notes && notes.trim() !== '') {
                    notesText.textContent = notes;
                    notesSection.style.display = 'block';
                } else {
                    notesText.textContent = 'No notes available';
                    notesSection.style.display = 'none';
                }
            } else {
                notesSection.style.display = 'none';
            }
        }
    </script>
</head>
<body>
    <?php include 'organizer_header.php'; ?>
    
    <div class="dashboard-container">
        <?php include 'organizer_sidebar.php'; ?>
        
        <div class="main-content">
            <div class="back-btn-container">
                <a href="organizer_registration_attendance_hub.php" class="action-btn">← Back</a>
            </div>
            
            <div class="container container-narrow">
                <h1>Assign Volunteer Task</h1>
                
                <?php if ($success_message): ?>
                    <div class="alert alert-success"><?= $success_message ?></div>
                <?php endif; ?>
                
                <?php if ($error_message): ?>
                    <div class="alert alert-danger"><?= $error_message ?></div>
                <?php endif; ?>
                
                <!-- Event Selection Form -->
                <form method="POST" id="eventForm" class="announcement-form">
                    <div class="form-group">
                        <label class="form-label">Select Event: <span class="required">*</span></label>
                        <select name="event_id" class="form-control" required onchange="loadVolunteers()">
                            <option value="">-- Select Event --</option>
                            <?php if ($events_result && $events_result->num_rows > 0): ?>
                                <?php $events_result->data_seek(0); ?>
                                <?php while ($event = $events_result->fetch_assoc()): ?>
                                    <option value="<?= $event['event_id'] ?>" 
                                        <?= ($selected_event_id == $event['event_id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($event['event_title']) ?> - <?= date('d/m/Y', strtotime($event['event_date_time'])) ?>
                                    </option>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <option value="">No approved events found</option>
                            <?php endif; ?>
                        </select>
                    </div>
                </form>
                
                <?php if (!empty($volunteers)): ?>
                    <!-- Task Assignment Form -->
                    <form method="POST" class="announcement-form">
                        <input type="hidden" name="event_id" value="<?= $selected_event_id ?>">
                        
                        <div class="form-group" style="background: #f9f9f9; padding: 20px; border-radius: 8px; border: 1px solid #ddd;">
                            <h3 style="margin-top: 0; font-size: 16px; color: #333;">Task Assignment</h3>
                            
                            <div class="form-group">
                                <label class="form-label">Volunteer: <span class="required">*</span></label>
                                <select name="volunteer_id" id="volunteer_id" class="form-control" required onchange="updateTaskField()">
                                    <option value="">-- Select Volunteer --</option>
                                    <?php foreach ($volunteers as $vol): ?>
                                        <option value="<?= $vol['volunteer_id'] ?>" 
                                                data-interests="<?= htmlspecialchars($vol['task_interested'] ?? '') ?>"
                                                data-task="<?= htmlspecialchars($vol['volunteer_task'] ?? '') ?>"
                                                data-notes="<?= htmlspecialchars($vol['notes'] ?? '') ?>">
                                            <?= htmlspecialchars($vol['name']) ?> (<?= $vol['user_id'] ?>)
                                            <?php if ($vol['volunteer_task']): ?>
                                                - Current: <?= htmlspecialchars($vol['volunteer_task']) ?>
                                            <?php endif; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="form-group" id="notes-section" style="display: none;">
                                <label class="form-label">Volunteer Notes:</label>
                                <div id="volunteer-notes" class="alert">
                                    <span id="notes-text">No notes available</span>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Task: <span class="required">*</span></label>
                                <input type="text" name="task" id="task" class="form-control" required 
                                       placeholder="e.g., Registration Desk, Setup/Logistics">
                                <small class="form-text">Enter specific task to assign (based on their interests shown above)</small>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Time Slot(s): <span class="required">*</span></label>
                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-top: 10px;">
                                    <?php
                                    $time_slots = [
                                        '8AM-11AM',
                                        '9AM-12PM',
                                        '10AM-2PM',
                                        '12PM-3PM',
                                        '1PM-4PM',
                                        '2PM-5PM',
                                        '3PM-6PM',
                                        '4PM-7PM',
                                        '6PM-9PM',
                                        'Full Day'
                                    ];
                                    $selected_slots = $_POST['time_slots'] ?? [];
                                    foreach ($time_slots as $slot):
                                        $checked = in_array($slot, $selected_slots) ? 'checked' : '';
                                    ?>
                                        <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                                            <input type="checkbox" name="time_slots[]" value="<?= $slot ?>" <?= $checked ?>>
                                            <span style="font-size: 14px;"><?= $slot ?></span>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                                <small class="form-text">Select one or more time slots for this volunteer</small>
                                    </div>
                        </div>
                        
                        <div class="form-actions">
                            <button type="submit" name="assign_task" class="action-btn">Assign Task</button>
                        </div>
                    </form>
                <?php elseif (!empty($selected_event_id)): ?>
                    <div class="alert alert-error">No volunteers recruited for this event yet.</div>
                <?php endif; ?>
                
                <div class="flex gap-15 flex-center mt-20">
                    <a href="organizer_volunteer_recruit.php" class="action-btn">Recruit Volunteer</a>
                    <a href="organizer_volunteer_list.php" class="action-btn">View Volunteer List</a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
