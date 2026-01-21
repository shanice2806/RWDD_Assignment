<?php
session_start();

// Simulated user data logic
if (!isset($_SESSION['name'])) {
    $_SESSION['name'] = 'Tokoyaki';
    $_SESSION['email'] = 'tokoyaki@gmail.com';
}

$user = [
    'user_id' => 'TP083586',
    'name' => $_SESSION['name'],
    'email' => $_SESSION['email'],
    'profile_image' => '' 
];

if (isset($_POST['save_profile'])) {
    $_SESSION['name'] = $_POST['name'];
    $_SESSION['email'] = $_POST['email'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>User Profile</title>

<style>
:root {
    --color-background: #ffffff;
    --color-section: #f7f9fa;
    --color-primary: #1e3a34;
    --color-secondary: #3ba99c;
    --color-text: #222222;
    --color-border: #e0e0e0;
    --color-input-bg: #e0e0e0; 
}

body {
    font-family: "Times New Roman", Times, serif;
    background: var(--color-background);
    color: var(--color-text);
    margin: 0;
    padding: 0;
}

/* Bigger container */
.profile-container {
    max-width: 700px; 
    background: #fff;
    margin: 40px auto;
    padding: 30px;
    border-radius: 10px;
    border: 1px solid var(--color-border);
    box-shadow: 0 4px 10px rgba(0,0,0,0.05);
}

/* Fixed Header: Title Center, Back Left, Points Right */
.profile-header {
    display: flex;
    justify-content: center;
    align-items: center; 
    margin-bottom: 20px;
    position: relative;
    width: 100%;
}

.back-btn {
    position: absolute;
    left: 0;
    font-size: 24px;
    text-decoration: none;
    color: var(--color-primary);
    cursor: pointer;
}

.profile-header h3 {
    margin: 0;
    color: var(--color-primary);
    font-size: 24px;
}

.points-label {
    position: absolute;
    right: 0;
    font-size: 14px;
    font-weight: bold;
}

/* Avatar Style */
.profile-avatar-large {
    width: 140px;
    height: 140px;
    background-color: #444; 
    border-radius: 50%;
    margin: 10px auto 20px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 70px; 
    color: white;
    overflow: hidden;
    border: 4px solid var(--color-secondary);
}

.profile-avatar-large img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.profile-info {
    text-align: left;
    max-width: 500px;
    margin: 0 auto;
}

.profile-info p {
    margin: 10px 0;
    font-size: 16px;
}

.role-text { color: #888; }

.profile-info label {
    font-size: 14px;
    display: block;
    margin-top: 12px;
    font-weight: bold;
    color: var(--color-primary);
}

/* Light Grey Boxes for Inputs */
.profile-info input {
    width: 100%;
    padding: 10px;
    margin-top: 5px;
    margin-bottom: 10px;
    border: 1px solid var(--color-border);
    border-radius: 4px;
    background-color: var(--color-input-bg); 
    box-sizing: border-box;
    font-family: "Times New Roman", Times, serif;
}

.action-btn {
    width: 160px;
    padding: 12px;
    margin: 25px auto 0;
    cursor: pointer;
    display: block;
    background-color: var(--color-secondary);
    color: white;
    border: none;
    border-radius: 6px;
    font-family: "Times New Roman", Times, serif;
    font-size: 16px;
    font-weight: bold;
}

.action-btn:hover { background-color: var(--color-primary); }

/* Hidden class with important to prevent flex overrides */
.hidden { display: none !important; }

/* Popup Styling */
.popup {
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,0.5);
    z-index: 999;
    display: flex; 
    align-items: center;
    justify-content: center;
}

.popup-box {
    background: #fff;
    padding: 30px;
    width: 300px;
    border-radius: 8px;
    text-align: center;
}

.popup-btns {
    display: flex; 
    justify-content: center; 
    gap: 15px; 
    margin-top: 20px;
}
</style>
</head>

<body>

<div class="profile-container" id="viewProfile">
    <div class="profile-header">
        <a href="user_dashboard.php" class="back-btn">&larr;</a>
        <h3>User Profile</h3>
        <div class="points-label">🏆 User Point</div>
    </div>

    <div class="profile-avatar-large">
        <?php if (!empty($user['profile_image'])): ?>
          <img src="../<?= htmlspecialchars($user['profile_image']) ?>" alt="Profile">
        <?php else: ?>
          👤
        <?php endif; ?>
    </div>

    <div class="profile-info">
        <p class="role-text"><strong>User :</strong> role</p>
        <p><strong>Name :</strong> <?php echo $_SESSION['name']; ?></p>
        <p><strong>Email :</strong> <?php echo $_SESSION['email']; ?></p>
        <p><strong>Password :</strong> ********</p>
        <p style="color: #aaa;"><strong>Account Status :</strong> Active</p>
    </div>

    <button type="button" class="action-btn" onclick="openEdit()">Edit Profile</button>
</div>

<div class="profile-container hidden" id="editProfile">
    <div class="profile-header">
        <span class="back-btn" onclick="closeEdit()">&larr;</span>
        <h3>Edit Profile</h3>
        <div class="points-label">🏆 User Point</div>
    </div>

    <div class="profile-avatar-large">
        <?php if (!empty($user['profile_image'])): ?>
          <img src="../<?= htmlspecialchars($user['profile_image']) ?>" alt="Profile">
        <?php else: ?>
          👤
        <?php endif; ?>
    </div>

    <form method="POST" id="editForm" class="profile-info">
        <p class="role-text"><strong>User :</strong> role</p>

        <label>Name :</label>
        <input type="text" name="name" placeholder="Enter new name">

        <label>Email :</label>
        <input type="email" name="email" placeholder="Enter new email">

        <label>Old Password :</label>
        <input type="password">

        <label>New Password :</label>
        <input type="password">

        <label>Confirm Password :</label>
        <input type="password">

        <input type="hidden" name="save_profile" value="1">
        <button type="button" class="action-btn" onclick="openConfirm()">Save Changes</button>
    </form>
</div>

<div class="popup hidden" id="confirmPopup">
    <div class="popup-box">
        <p>Confirm change?</p>
        <div class="popup-btns">
            <button type="button" class="action-btn" style="width: 80px; margin: 0;" onclick="submitForm()">Yes</button>
            <button type="button" class="action-btn" style="width: 80px; margin: 0; background: #6c757d;" onclick="closeConfirm()">No</button>
        </div>
    </div>
</div>

<script>
function openEdit(){
    document.getElementById("viewProfile").classList.add("hidden");
    document.getElementById("editProfile").classList.remove("hidden");
}
function closeEdit(){
    document.getElementById("editProfile").classList.add("hidden");
    document.getElementById("viewProfile").classList.remove("hidden");
}
function openConfirm(){
    document.getElementById("confirmPopup").classList.remove("hidden");
}
function closeConfirm(){
    document.getElementById("confirmPopup").classList.add("hidden");
}
function submitForm(){
    document.getElementById("editForm").submit();
}
</script>

</body>
</html>