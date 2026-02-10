<?php
// Start session - keeps user logged in
session_start();

require_once('../connect.php');

// ========================================
//Check if user is allowed here
// ========================================

if (!isset($_SESSION["user_id"]) || !in_array(strtolower($_SESSION["role"]), ["event organizer","event_organizer","organizer"])) {
    header("Location: ../login/login.php");
    exit();
}


$user_id = $_SESSION['user_id'];


$error_message = '';
$success_message = '';

// ========================================
// Check if form was submitted
// ========================================
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    

    $title = trim($_POST['title']);           // Event title
    $event_type = $_POST['event_type'];       // Event type (Workshop, Campaign, etc.)
    $date = $_POST['date'];                   // Event date
    $time = $_POST['time'];                   // Event time
    $location = trim($_POST['location']);     // Event location
    $description = trim($_POST['description']); // Event description
    $status = $_POST['status'] ?? 'draft';    

    $event_date_time = $date . ' ' . $time . ':00';
    
  
    if (empty($title) || empty($event_type) || empty($date) || empty($time) || empty($location) || empty($description)) {
        $error_message = "All fields are required.";
    } else {
        
        // ========================================
        //  Create the Event ID
        // ========================================
     
        $query = "SELECT event_id FROM events ORDER BY event_id DESC LIMIT 1";
        $result = mysqli_query($conn, $query);
        
        if (mysqli_num_rows($result) > 0) {
          
            $row = mysqli_fetch_assoc($result);
            $last_id = $row['event_id'];
            
           
            $number = intval(substr($last_id, 4));
            
           
            $number = $number + 1;
            
           
            $event_id = 'evt_' . str_pad($number, 3, '0', STR_PAD_LEFT);
        } else {
            
            $event_id = 'evt_001';
        }
        
        // ========================================
        //  Generate unique attendance code
        // ========================================
    
        
      
        $letters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
  
        $letter1 = $letters[rand(0, 25)];  // Pick random letter
        $letter2 = $letters[rand(0, 25)];  // Pick random letter
        $letter3 = $letters[rand(0, 25)];  // Pick random letter
        
     
        $number1 = rand(0, 9);
        $number2 = rand(0, 9);
        
      
        $attendance_code = $letter1 . $letter2 . $letter3 . $number1 . $number2;

        $insert_query = "INSERT INTO events (event_id, event_title, event_type, event_description, event_date_time, event_location, event_created_by, event_status, event_attendance_code, registration_status) 
                       VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'Closed')";
        
    
        $stmt = mysqli_prepare($conn, $insert_query);
        
 
        mysqli_stmt_bind_param($stmt, "sssssssss", $event_id, $title, $event_type, $description, $event_date_time, $location, $user_id, $status, $attendance_code);
        
        // Execute the query
        if (mysqli_stmt_execute($stmt)) {
            
    
            if (isset($_FILES['event_media']) && $_FILES['event_media']['error'] == UPLOAD_ERR_OK) {
        
                $file = $_FILES['event_media'];
                
             
                $upload_folder = '../images/events/';
                
             
                if (!file_exists($upload_folder)) {
                    mkdir($upload_folder, 0777, true);
                }
                
           
                $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                $allowed_types = array('jpg', 'jpeg', 'png', 'gif');
                
               
                if (in_array($file_extension, $allowed_types)) {
                    if ($file['size'] <= 5 * 1024 * 1024) {
                        
                        $new_filename = $event_id . '_poster.' . $file_extension;
                        $full_path = $upload_folder . $new_filename;
                        
                        
                        if (move_uploaded_file($file['tmp_name'], $full_path)) {
                            
                            // ========================================
                            //  Create Media ID
                            // ========================================
                        
                            $media_query = "SELECT event_media_id FROM event_media ORDER BY event_media_id DESC LIMIT 1";
                            $media_result = mysqli_query($conn, $media_query);
                            
                        
                            if (mysqli_num_rows($media_result) > 0) {
                                $media_row = mysqli_fetch_assoc($media_result);
                                $last_media_id = $media_row['event_media_id'];
                                
                              
                                $media_number = intval(substr($last_media_id, 4)) + 1;
                                $event_media_id = 'emd_' . str_pad($media_number, 3, '0', STR_PAD_LEFT);
                            } else {
                              
                                $event_media_id = 'emd_001';
                            }
                            
                            // ========================================
                            // Save file path to database
                            // ========================================
                            $media_insert = "INSERT INTO event_media (event_media_id, event_id, event_media_file_path, event_media_uploaded_by, event_media_is_hidden) 
                                           VALUES (?, ?, ?, ?, 0)";
                            
                            $media_stmt = mysqli_prepare($conn, $media_insert);
                            $file_path = 'images/events/' . $new_filename;
                            mysqli_stmt_bind_param($media_stmt, "ssss", $event_media_id, $event_id, $file_path, $user_id);
                            mysqli_stmt_execute($media_stmt);
                        } else {
                            $error_message = "Failed to upload file.";
                        }
                    } else {
                        $error_message = "File size exceeds 5MB limit.";
                    }
                } else {
                    $error_message = "Invalid file type. Only JPG, JPEG, PNG, and GIF files are allowed.";
                }
            }
            
            // ========================================
            // Show success message
            // ========================================
            if (empty($error_message)) {
                if ($status == 'pending') {
                    $success_message = "Event submitted for approval successfully!";
                } else {
                    $success_message = "Event saved as draft successfully!";
                }
                
                header("refresh:2;url=organizer_event_management.php");
            }
            
        } else {
            $error_message = "Failed to create event.";
        }
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Create Event - Organizer</title>
  <link rel="stylesheet" href="../css/style.css">
  <link rel="stylesheet" href="../css/organizer.css">
</head>
<body>
  <?php include 'organizer_header.php'; ?>

    <div class="main-content">
    <div class="back-btn-container">
      <a href="organizer_event_management.php" class="action-btn"> Back</a>
    </div>    <div class="create-event-container">
      <h1>Create Event Form</h1>
      
      <?php if (!empty($error_message)): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error_message); ?></div>
      <?php endif; ?>
      
      <?php if (!empty($success_message)): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success_message); ?></div>
      <?php endif; ?>
      
      <form method="POST" action="" enctype="multipart/form-data">
        <div class="form-group">
          <label for="title">Title:</label>
          <input type="text" id="title" name="title" class="form-control" required 
                 value="<?php echo isset($_POST['title']) ? htmlspecialchars($_POST['title']) : ''; ?>"
                 placeholder="Enter event title">
        </div>
        
        <div class="form-group">
          <label for="event_type">Event Type:</label>
          <select id="event_type" name="event_type" class="form-control" required>
            <option value="">-- Select Event Type --</option>
            <option value="Workshop" <?php echo (isset($_POST['event_type']) && $_POST['event_type'] == 'Workshop') ? 'selected' : ''; ?>>Workshop</option>
            <option value="Campaign" <?php echo (isset($_POST['event_type']) && $_POST['event_type'] == 'Campaign') ? 'selected' : ''; ?>>Campaign</option>
            <option value="Competition" <?php echo (isset($_POST['event_type']) && $_POST['event_type'] == 'Competition') ? 'selected' : ''; ?>>Competition</option>
            <option value="Volunteer Program" <?php echo (isset($_POST['event_type']) && $_POST['event_type'] == 'Volunteer Program') ? 'selected' : ''; ?>>Volunteer Program</option>
            <option value="Talk" <?php echo (isset($_POST['event_type']) && $_POST['event_type'] == 'Talk') ? 'selected' : ''; ?>>Talk</option>
            <option value="Recycling Drive" <?php echo (isset($_POST['event_type']) && $_POST['event_type'] == 'Recycling Drive') ? 'selected' : ''; ?>>Recycling Drive</option>
            <option value="Eco Fair" <?php echo (isset($_POST['event_type']) && $_POST['event_type'] == 'Eco Fair') ? 'selected' : ''; ?>>Eco Fair</option>
            <option value="Cleanup Day" <?php echo (isset($_POST['event_type']) && $_POST['event_type'] == 'Cleanup Day') ? 'selected' : ''; ?>>Cleanup Day</option>
            <option value="Repair & Reuse Fair" <?php echo (isset($_POST['event_type']) && $_POST['event_type'] == 'Repair & Reuse Fair') ? 'selected' : ''; ?>>Repair & Reuse Fair</option>
            <option value="Awareness Seminar" <?php echo (isset($_POST['event_type']) && $_POST['event_type'] == 'Awareness Seminar') ? 'selected' : ''; ?>>Awareness Seminar</option>
          </select>
        </div>
        
        <div class="form-group">
          <label for="date">Date:</label>
          <input type="date" id="date" name="date" class="form-control" required 
                 value="<?php echo isset($_POST['date']) ? htmlspecialchars($_POST['date']) : ''; ?>">
        </div>
        
        <div class="form-group">
          <label for="time">Time:</label>
          <input type="time" id="time" name="time" class="form-control" required 
                 value="<?php echo isset($_POST['time']) ? htmlspecialchars($_POST['time']) : ''; ?>">
        </div>
        
        <div class="form-group">
          <label for="location">Location:</label>
          <input type="text" id="location" name="location" class="form-control" required 
                 value="<?php echo isset($_POST['location']) ? htmlspecialchars($_POST['location']) : ''; ?>"
                 placeholder="Enter event location">
        </div>
        
        <div class="form-group">
          <label for="description">Description:</label>
          <textarea id="description" name="description" class="form-control" rows="5" required placeholder="Enter event description"><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; ?></textarea>
        </div>
        
        <div class="form-group">
          <label for="event_media">Upload Poster / Media:</label>
          <input type="file" id="event_media" name="event_media" class="form-control" accept="image/*">
          <small class="form-text">Allowed formats: JPG, JPEG, PNG, GIF. Max size: 5MB</small>
          
          <div class="image-preview-container" id="image-preview">
            <div class="placeholder-icon">🖼️</div>
          </div>
        </div>
        
        <div class="form-actions">
          <button type="submit" name="status" value="draft" class="action-btn">Save Draft</button>
          <button type="submit" name="status" value="pending" class="action-btn">Submit for Approval</button>
        </div>
      </form>
    </div>
  </div>
  
  <footer>
    © 2026 ReLife Hub
  </footer>


  <script>
  // Image preview functionality
  document.getElementById('event_media').addEventListener('change', function(e) {
      const file = e.target.files[0];
      const preview = document.getElementById('image-preview');
      
      if (file) {
          // Check file size
          if (file.size > 5 * 1024 * 1024) {
              alert('File size exceeds 5MB limit.');
              this.value = '';
              preview.innerHTML = '<div class="placeholder-icon">🖼️</div>';
              return;
          }
          
          const reader = new FileReader();
          reader.onload = function(e) {
              preview.innerHTML = '<img src="' + e.target.result + '" alt="Preview">';
          }
          reader.readAsDataURL(file);
      } else {
          preview.innerHTML = '<div class="placeholder-icon">🖼️</div>';
      }
  });
  </script>
</body>
</html>
