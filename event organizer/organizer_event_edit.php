<?php
// ========================================
// STEP 1: Start session and check if user is logged in
// ========================================
session_start();

// Only event organizers can access this page
if (!isset($_SESSION["user_id"]) || !in_array(strtolower($_SESSION["role"]), ["event organizer","event_organizer","organizer"])) {
    header("Location: ../login/login.php");
    exit();
}

// Connect to database
include '../connect.php';

// Get current user's ID
$user_id = $_SESSION['user_id'];

// ========================================
// STEP 2: Get event ID from URL
// ========================================
if (!isset($_GET['id'])) {
    // No event ID provided, redirect back to event list
    header("Location: organizer_event_view.php");
    exit();
}

$event_id = $_GET['id'];

// ========================================
// STEP 3: Check if user is allowed to edit this event
// ========================================
// User can edit if they created the event OR they are an active cohost
$check_query = "SELECT e.* FROM events e
                LEFT JOIN co_host ch ON e.event_id = ch.event_id
                WHERE e.event_id = '$event_id' 
                AND (e.event_created_by = '$user_id' OR (ch.user_id = '$user_id' AND ch.cohost_status = 'active'))";
$check_result = $conn->query($check_query);

if ($check_result->num_rows == 0) {
    // User doesn't have permission to edit this event
    echo "<script>alert('You do not have permission to edit this event.'); window.location.href='organizer_event_view.php';</script>";
    exit();
}

// Get event data
$event = $check_result->fetch_assoc();

// ========================================
// STEP 4: Handle form submissions
// ========================================
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    // Check which section is being updated
    if (isset($_POST['update_description'])) {
        // Update description
        $description = $_POST['event_description'];
        $update_query = "UPDATE events SET event_description = '$description' WHERE event_id = '$event_id'";
        $conn->query($update_query);
        echo "<script>alert('Description updated successfully!'); window.location.href='organizer_event_edit.php?id=$event_id';</script>";
    }
    
    else if (isset($_POST['update_datetime'])) {
        // Update date and time
        $datetime = $_POST['event_date_time'];
        $update_query = "UPDATE events SET event_date_time = '$datetime' WHERE event_id = '$event_id'";
        $conn->query($update_query);
        echo "<script>alert('Date & Time updated successfully!'); window.location.href='organizer_event_edit.php?id=$event_id';</script>";
    }
    
    else if (isset($_POST['update_location'])) {
        // Update location
        $location = $_POST['event_location'];
        $update_query = "UPDATE events SET event_location = '$location' WHERE event_id = '$event_id'";
        $conn->query($update_query);
        echo "<script>alert('Location updated successfully!'); window.location.href='organizer_event_edit.php?id=$event_id';</script>";
    }
    
    else if (isset($_POST['update_type'])) {
        // Update event type
        $event_type = $_POST['event_type'];
        $update_query = "UPDATE events SET event_type = '$event_type' WHERE event_id = '$event_id'";
        $conn->query($update_query);
        echo "<script>alert('Event Type updated successfully!'); window.location.href='organizer_event_edit.php?id=$event_id';</script>";
    }
    
    else if (isset($_POST['update_poster'])) {
        // Update poster - handle file upload
        if (isset($_FILES['event_poster']) && $_FILES['event_poster']['error'] == 0) {
            // Check file type
            $allowed = ['jpg', 'jpeg', 'png', 'gif'];
            $filename = $_FILES['event_poster']['name'];
            $filetype = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            
            if (in_array($filetype, $allowed)) {
                // Check file size (max 5MB)
                if ($_FILES['event_poster']['size'] <= 5242880) {
                    // Create upload folder if it doesn't exist
                    $upload_dir = "../uploads/events/";
                    if (!file_exists($upload_dir)) {
                        mkdir($upload_dir, 0777, true);
                    }
                    
                    // Create unique filename
                    $new_filename = uniqid() . '_' . $filename;
                    $upload_path = $upload_dir . $new_filename;
                    
                    // Move uploaded file
                    if (move_uploaded_file($_FILES['event_poster']['tmp_name'], $upload_path)) {
                        // Delete old poster from event_media if exists
                        $delete_query = "DELETE FROM event_media WHERE event_id = '$event_id'";
                        $conn->query($delete_query);
                        
                        // Generate new media ID
                        $media_query = "SELECT event_media_id FROM event_media ORDER BY event_media_id DESC LIMIT 1";
                        $media_result = $conn->query($media_query);
                        
                        if ($media_result->num_rows > 0) {
                            $last_id = $media_result->fetch_assoc()['event_media_id'];
                            $number = intval(substr($last_id, 4)) + 1;
                            $new_media_id = 'emd_' . str_pad($number, 3, '0', STR_PAD_LEFT);
                        } else {
                            $new_media_id = 'emd_001';
                        }
                        
                        // Insert new poster
                        $file_path = "uploads/events/" . $new_filename;
                        $insert_query = "INSERT INTO event_media (event_media_id, event_id, event_media_file_path, event_media_uploaded_by, event_media_is_hidden) 
                                        VALUES ('$new_media_id', '$event_id', '$file_path', '$user_id', 0)";
                        $conn->query($insert_query);
                        
                        echo "<script>alert('Poster updated successfully!'); window.location.href='organizer_event_edit.php?id=$event_id';</script>";
                    } else {
                        echo "<script>alert('Failed to upload file.');</script>";
                    }
                } else {
                    echo "<script>alert('File size too large. Maximum 5MB allowed.');</script>";
                }
            } else {
                echo "<script>alert('Invalid file type. Only JPG, JPEG, PNG, and GIF allowed.');</script>";
            }
        }
    }
}

// Get current poster
$poster_query = "SELECT event_media_file_path FROM event_media WHERE event_id = '$event_id' LIMIT 1";
$poster_result = $conn->query($poster_query);
$current_poster = $poster_result->num_rows > 0 ? $poster_result->fetch_assoc()['event_media_file_path'] : '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Event - <?php echo htmlspecialchars($event['event_title']); ?></title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/organizer.css">
</head>
<body>
    <?php include 'organizer_header.php'; ?>
    
    <div class="dashboard-container">
        <?php include 'organizer_sidebar.php'; ?>
        
        <div class="main-content">
            <!-- Page title -->
            <div class="page-header">
                <h1>Edit Event</h1>
                <p>Event: <strong><?php echo htmlspecialchars($event['event_title']); ?></strong></p>
            </div>
            
            <!-- Edit event container -->
            <div class="edit-event-container">
                <h2>Please select the section you want to edit:</h2>
                
                <!-- Section buttons -->
                <div class="edit-sections">
                    
                    <!-- Description Section -->
                    <button class="section-btn" onclick="toggleSection('description')">Description</button>
                    <div id="description-section" class="edit-section" style="display: none;">
                        <form method="POST">
                            <textarea name="event_description" rows="6" required><?php echo htmlspecialchars($event['event_description']); ?></textarea>
                            <div class="button-group">
                                <button type="button" class="btn-cancel" onclick="toggleSection('description')">Cancel</button>
                                <button type="submit" name="update_description" class="btn-save">Save Changes</button>
                            </div>
                        </form>
                    </div>
                    
                    <!-- Date & Time Section -->
                    <button class="section-btn" onclick="toggleSection('datetime')">Date & Time</button>
                    <div id="datetime-section" class="edit-section" style="display: none;">
                        <form method="POST">
                            <input type="datetime-local" name="event_date_time" value="<?php echo date('Y-m-d\TH:i', strtotime($event['event_date_time'])); ?>" required>
                            <div class="button-group">
                                <button type="button" class="btn-cancel" onclick="toggleSection('datetime')">Cancel</button>
                                <button type="submit" name="update_datetime" class="btn-save">Save Changes</button>
                            </div>
                        </form>
                    </div>
                    
                    <!-- Location Section -->
                    <button class="section-btn" onclick="toggleSection('location')">Location</button>
                    <div id="location-section" class="edit-section" style="display: none;">
                        <form method="POST">
                            <input type="text" name="event_location" value="<?php echo htmlspecialchars($event['event_location']); ?>" required>
                            <div class="button-group">
                                <button type="button" class="btn-cancel" onclick="toggleSection('location')">Cancel</button>
                                <button type="submit" name="update_location" class="btn-save">Save Changes</button>
                            </div>
                        </form>
                    </div>
                    
                    <!-- Event Type Section -->
                    <button class="section-btn" onclick="toggleSection('eventtype')">Event Type</button>
                    <div id="eventtype-section" class="edit-section" style="display: none;">
                        <form method="POST">
                            <select name="event_type" required>
                                <option value="Workshop" <?php echo ($event['event_type'] == 'Workshop') ? 'selected' : ''; ?>>Workshop</option>
                                <option value="Seminar" <?php echo ($event['event_type'] == 'Seminar') ? 'selected' : ''; ?>>Seminar</option>
                                <option value="Clean-up Drive" <?php echo ($event['event_type'] == 'Clean-up Drive') ? 'selected' : ''; ?>>Clean-up Drive</option>
                                <option value="Recycling Event" <?php echo ($event['event_type'] == 'Recycling Event') ? 'selected' : ''; ?>>Recycling Event</option>
                                <option value="Tree Planting" <?php echo ($event['event_type'] == 'Tree Planting') ? 'selected' : ''; ?>>Tree Planting</option>
                                <option value="Awareness Campaign" <?php echo ($event['event_type'] == 'Awareness Campaign') ? 'selected' : ''; ?>>Awareness Campaign</option>
                                <option value="Competition" <?php echo ($event['event_type'] == 'Competition') ? 'selected' : ''; ?>>Competition</option>
                                <option value="Exhibition" <?php echo ($event['event_type'] == 'Exhibition') ? 'selected' : ''; ?>>Exhibition</option>
                                <option value="Fundraiser" <?php echo ($event['event_type'] == 'Fundraiser') ? 'selected' : ''; ?>>Fundraiser</option>
                                <option value="Community Gathering" <?php echo ($event['event_type'] == 'Community Gathering') ? 'selected' : ''; ?>>Community Gathering</option>
                            </select>
                            <div class="button-group">
                                <button type="button" class="btn-cancel" onclick="toggleSection('eventtype')">Cancel</button>
                                <button type="submit" name="update_type" class="btn-save">Save Changes</button>
                            </div>
                        </form>
                    </div>
                    
                    <!-- Poster Upload Section -->
                    <button class="section-btn" onclick="toggleSection('poster')">Poster Upload</button>
                    <div id="poster-section" class="edit-section" style="display: none;">
                        <form method="POST" enctype="multipart/form-data">
                            <?php if ($current_poster): ?>
                                <div class="current-poster">
                                    <p>Current Poster:</p>
                                    <img src="../<?php echo htmlspecialchars($current_poster); ?>" alt="Current Poster">
                                </div>
                            <?php endif; ?>
                            <div class="file-upload-area">
                                <p>Upload New Poster (JPG, JPEG, PNG, GIF - Max 5MB):</p>
                                <input type="file" name="event_poster" accept="image/*" required>
                            </div>
                            <div class="button-group">
                                <button type="button" class="btn-cancel" onclick="toggleSection('poster')">Cancel</button>
                                <button type="submit" name="update_poster" class="btn-save">Save Changes</button>
                            </div>
                        </form>
                    </div>
                    
                </div>
                
                <!-- Bottom buttons -->
                <div class="bottom-buttons">
                    <a href="organizer_event_view.php" class="btn-secondary">Back to Events</a>
                    <a href="organizer_event_detail.php?id=<?php echo $event_id; ?>" class="btn-primary">View Event Details</a>
                </div>
            </div>
            
        </div>
    </div>
    
    <script>
        // ========================================
        // JavaScript function to show/hide sections
        // ========================================
        function toggleSection(sectionName) {
            // Get the section element
            var section = document.getElementById(sectionName + '-section');
            
            // If section is hidden, show it. If visible, hide it.
            if (section.style.display === 'none') {
                // Hide all other sections first
                var allSections = document.querySelectorAll('.edit-section');
                for (var i = 0; i < allSections.length; i++) {
                    allSections[i].style.display = 'none';
                }
                
                // Show this section
                section.style.display = 'block';
            } else {
                // Hide this section
                section.style.display = 'none';
            }
        }
    </script>
</body>
</html>
