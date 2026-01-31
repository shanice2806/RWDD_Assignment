<?php
session_start();
require_once "../connect.php"; 

if (!isset($_SESSION["user_id"])) {
    header("Location: ../login/login.php");
    exit();
}
$user_id = $_SESSION["user_id"];

// Handle Profile & Image Update Logic
if (isset($_POST['save_profile'])) {
    $newName = mysqli_real_escape_string($conn, $_POST['name']);
    $newEmail = mysqli_real_escape_string($conn, $_POST['email']);
    
    if (!empty($_FILES['profile_image']['name'])) {
        $targetDir = "../images/profile/";
        $fileName = time() . "_" . basename($_FILES["profile_image"]["name"]);
        $targetFilePath = $targetDir . $fileName;
        $dbFilePath = "images/profile/" . $fileName;

        if (move_uploaded_file($_FILES["profile_image"]["tmp_name"], $targetFilePath)) {
            $updateSql = "UPDATE users SET name = '$newName', email = '$newEmail', profile_image = '$dbFilePath' WHERE user_id = '$user_id'";
        }
    } else {
        $updateSql = "UPDATE users SET name = '$newName', email = '$newEmail' WHERE user_id = '$user_id'";
    }
    
    if (mysqli_query($conn, $updateSql)) {
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }
}

// Fetch user data
$userSql = "SELECT user_id, name, email, role, account_status, profile_image FROM users WHERE user_id = ? LIMIT 1";
$userStmt = mysqli_prepare($conn, $userSql);
mysqli_stmt_bind_param($userStmt, "s", $user_id);
mysqli_stmt_execute($userStmt);
$user = mysqli_fetch_assoc(mysqli_stmt_get_result($userStmt));

$profilePic = "../images/profile/default.jpeg"; 
if (!empty($user['profile_image'])) {
    $dbPath = "../" . $user['profile_image'];
    if (file_exists($dbPath)) { $profilePic = $dbPath; }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>User Profile | ReLife Hub</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/user.css">

    <style>
    :root {
        --color-background: #ffffff;
        --color-primary: #1e3a34;
        --color-secondary: #3ba99c;
        --color-text: #222222;
        --color-border: #e0e0e0;
        --color-input-bg: #f5f5f5; 
    }

    body { font-family: "Times New Roman", Times, serif; margin: 0; padding: 0; }
    .user-layout { display: flex; min-height: 100vh; width: 100%; }
    .user-content { flex: 1; display: flex; flex-direction: column; background: #f9f9f9; }

    .user-top-name { color: white !important; }

    .profile-container {
        width: 800px; 
        min-height: 900px; /* Increased height */
        background: #fff;
        margin: 50px auto;
        padding: 50px;
        border-radius: 12px;
        border: 1px solid var(--color-border);
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        box-sizing: border-box;
    }

    .profile-header { display: flex; justify-content: center; align-items: center; margin-bottom: 40px; position: relative; }
    .back-btn { position: absolute; left: 0; font-size: 24px; text-decoration: none; color: var(--color-primary); }
    .profile-header h3 { margin: 0; color: var(--color-primary); font-size: 32px; }

    .profile-avatar-large {
        width: 150px; height: 150px; border-radius: 50%;
        margin: 10px auto 0; display: flex; align-items: center; justify-content: center;
        overflow: hidden; border: 4px solid var(--color-secondary); background-color: #eee;
    }
    .profile-avatar-large img { width: 100%; height: 100%; object-fit: cover; display: block; }

    .change-img-text {
        display: block; text-align: center; font-size: 14px;
        color: #3ba99c; font-weight: bold; cursor: pointer;
        margin-top: 25px; 
        margin-bottom: 45px; text-decoration: underline;
    }

    .profile-info { text-align: left; max-width: 550px; margin: 0 auto; }
    
    /* Give space between rows */
    .profile-info p { 
        margin: 25px 0; /* More spacing between items */
        font-size: 18px; 
        border-bottom: 1px solid #f0f0f0; 
        padding-bottom: 12px; 
    }
    
    .status-active { color: #28a745; font-weight: bold; }

    .profile-info label { font-size: 15px; display: block; margin-top: 25px; font-weight: bold; color: var(--color-primary); }
    .profile-info input {
        width: 100%; padding: 12px; margin-top: 8px; margin-bottom: 10px;
        border: 1px solid var(--color-border); border-radius: 6px;
        background-color: var(--color-input-bg); box-sizing: border-box;
    }

    /* Move Edit button lower */
    .action-btn {
        width: 200px; padding: 14px; margin: 60px auto 20px; cursor: pointer; display: block;
        background-color: var(--color-secondary); color: white; border: none;
        border-radius: 6px; font-size: 17px; font-weight: bold;
    }

    .hidden { display: none !important; }

    .popup { position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 9999; display: flex; align-items: center; justify-content: center; }
    .popup-box { background: #fff; padding: 30px; width: 300px; border-radius: 10px; text-align: center; }
    .popup-btns { display: flex; justify-content: center; gap: 15px; margin-top: 20px; }
    
    /* Bigger and bold popup text */
    .popup-box p { font-size: 22px; font-weight: bold; margin-bottom: 10px; }
    </style>
</head>

<body class="sidebar-collapsed">
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<div class="user-layout">
    <?php include __DIR__ . "/user_sidebar.php"; ?>

    <div class="user-content">
        <div class="user-top-bar">
            <div class="user-top-left">
                <button class="sidebar-toggle" id="userSidebarToggle" type="button">☰</button>
                <span class="user-top-name">Welcome! <?= htmlspecialchars($user['name']) ?></span>
            </div>
            <div class="user-top-right">
                <a href="user_dashboard.php" class="user-top-btn">🏠</a>
                <a class="user-top-btn" href="add_friends.php">👥</a>
                <a href="../login/logout.php" class="user-top-btn logout">❌</a>
            </div>
        </div>

        <div class="profile-container" id="viewProfile">
            <div class="profile-header">
                <a href="user_dashboard.php" class="back-btn">&larr;</a>
                <h3>User Profile</h3>
            </div>
            <div class="profile-avatar-large">
                <img src="<?= htmlspecialchars($profilePic) ?>" alt="Profile Picture">
            </div>
            <span class="change-img-text" onclick="openEdit()">Change Profile Image</span>

            <div class="profile-info">
                <p><strong>Role :</strong> <?= htmlspecialchars($user['role']); ?></p>
                <p><strong>Name :</strong> <?= htmlspecialchars($user['name']); ?></p>
                <p><strong>Email :</strong> <?= htmlspecialchars($user['email']); ?></p>
                <p><strong>Password :</strong> ********</p>
                <p><strong>User ID :</strong> <?= htmlspecialchars($user['user_id']); ?></p>
                <p><strong>Account Status :</strong> <span class="status-active">Active</span></p>
            </div>
            <button type="button" class="action-btn" onclick="openEdit()">Edit Profile</button>
        </div>

        <div class="profile-container hidden" id="editProfile">
            <div class="profile-header">
                <span class="back-btn" onclick="closeEdit()">&larr;</span>
                <h3>Edit Profile</h3>
            </div>
            
            <form method="POST" id="editForm" enctype="multipart/form-data">
                <div class="profile-avatar-large">
                    <img src="<?= htmlspecialchars($profilePic) ?>" id="preview" alt="Profile Picture">
                </div>
                
                <label for="imgInput" class="change-img-text">Click here to upload new image</label>
                <input type="file" name="profile_image" id="imgInput" style="display:none;" onchange="previewImage(event)">

                <div class="profile-info">
                    <p><strong>Role :</strong> <?= htmlspecialchars($user['role']); ?></p>
                    <label>Name :</label>
                    <input type="text" name="name" value="<?= htmlspecialchars($user['name']); ?>">
                    <label>Email :</label>
                    <input type="email" name="email" value="<?= htmlspecialchars($user['email']); ?>">
                    <label>Old Password :</label> <input type="password">
                    <label>New Password :</label> <input type="password">
                    <label>Confirm Password :</label> <input type="password">
                    <p><strong>User ID :</strong> <?= htmlspecialchars($user['user_id']); ?></p>
                    
                    <input type="hidden" name="save_profile" value="1">
                    <button type="button" class="action-btn" onclick="openConfirm()">Save Changes</button>
                </div>
            </form>
        </div>

        <footer>© 2026 ReLife Hub</footer>
    </div>
</div>

<div class="popup hidden" id="confirmPopup">
    <div class="popup-box">
        <p>Confirm changes?</p>
        <div class="popup-btns">
            <button type="button" class="action-btn" style="width: 80px; background: #6c757d;" onclick="closeConfirm()">No</button>
            <button type="button" class="action-btn" style="width: 80px;" onclick="submitForm()">Yes</button>
        </div>
    </div>
</div>

<script src="../user/user.js"></script>
<script>
function openEdit(){ document.getElementById("viewProfile").classList.add("hidden"); document.getElementById("editProfile").classList.remove("hidden"); }
function closeEdit(){ document.getElementById("editProfile").classList.add("hidden"); document.getElementById("viewProfile").classList.remove("hidden"); }
function openConfirm(){ document.getElementById("confirmPopup").classList.remove("hidden"); }
function closeConfirm(){ document.getElementById("confirmPopup").classList.add("hidden"); }
function submitForm(){ document.getElementById("editForm").submit(); }

function previewImage(event) {
    var reader = new FileReader();
    reader.onload = function(){
        var output = document.getElementById('preview');
        output.src = reader.result;
    }
    reader.readAsDataURL(event.target.files[0]);
}
</script>
</body>
</html>