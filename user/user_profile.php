<?php
session_start();

if (!isset($_SESSION['name'])) {
    $_SESSION['name'] = 'Tokoyaki';
    $_SESSION['email'] = 'tokoyaki@gmail.com';
}

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
body{
    font-family: Arial, sans-serif;
    background: #f5f5f5;
}

.profile-container{
    max-width: 420px;
    background: #fff;
    margin: 22px auto;
    padding: 20px;
    border-radius: 10px;
    text-align: center;
}

.profile-header{
    display: flex;
    justify-content: space-between;
    align-items: center;
    text-align: left;
}

.back-btn{
    font-size: 20px;
    text-decoration: none;
    color: #000;
    cursor: pointer;
}

.points{ font-size: 14px; }

.profile-image-section{
    text-align: center;
    margin: 14px 0;
}

.profile-image{
    width: 100px;
    height: 100px;
    margin: 0 auto;
    border-radius: 50%;
    background: #e0e0e0;
    display: flex;
    align-items: center;
    justify-content: center;
}

.profile-image img{
    width: 55px;
    height: 55px;
    opacity: 0.6;
}

.change-img{
    font-size: 12px;
    color: #666;
}

.profile-info{
    text-align: left;
}

.role-text {
    color: #888; 
    margin: 6px 0;
    font-size: 14px;
}

.profile-info p{
    margin: 6px 0;
    font-size: 14px;
}

.profile-info label{
    font-size: 13px;
    display: block;
    margin-top: 5px;
}

.profile-info input{
    width: 100%;
    padding: 4px 6px;
    margin-top: 2px;
    margin-bottom: 5px;
    box-sizing: border-box;
}

.status{ color: #aaa; }

.action-btn {
    width: 120px;
    padding: 8px;
    margin: 15px auto 0;
    cursor: pointer;
    display: block;
}

.hidden{ display: none; }

.popup{
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,0.5);
}

.popup-box{
    background: #fff;
    padding: 18px;
    width: 280px;
    margin: 200px auto;
    border-radius: 8px;
    text-align: center;
}

.popup-actions{
    display: flex;
    justify-content: space-between;
    gap: 20px;
    margin-top: 15px;
}

.popup-actions button{
    width: 100%;
    padding: 7px;
}
</style>
</head>

<body>

<div class="profile-container" id="viewProfile">
    <div class="profile-header">
        <a href="user_dashboard.php" class="back-btn">&larr;</a>
        <h3>User Profile</h3>
        <div class="points">🏆 User Point</div>
    </div>

    <div class="profile-image-section">
        <div class="profile-image">
            <img src="../images/user-icon.png">
        </div>
        <p class="change-img">Change Profile Image</p>
    </div>

    <div class="profile-info">
        <p class="role-text"><strong>User :</strong> role</p>
        <p><strong>Name :</strong> <?php echo $_SESSION['name']; ?></p>
        <p><strong>Email :</strong> <?php echo $_SESSION['email']; ?></p>
        <p><strong>Password :</strong> ********</p>
        <p class="status"><strong>Account Status :</strong> Active</p>
    </div>

    <button type="button" class="action-btn" onclick="openEdit()">Edit</button>
</div>

<div class="profile-container hidden" id="editProfile">
    <div class="profile-header">
        <span class="back-btn" onclick="closeEdit()">&larr;</span>
        <h3>Edit Profile</h3>
        <div class="points">🏆 User Point</div>
    </div>

    <div class="profile-image-section">
        <div class="profile-image">
            <img src="../images/user-icon.png">
        </div>
    </div>

    <form method="POST" id="editForm" class="profile-info">
        <p class="role-text"><strong>User :</strong> role</p>

        <label>Name :</label>
        <input type="text" name="name" value=""> 

        <label>Email :</label>
        <input type="email" name="email" value="">

        <label>Old Password :</label>
        <input type="password">

        <label>New Password :</label>
        <input type="password">

        <label>Confirm Password :</label>
        <input type="password">

        <p class="status"><strong>Account Status :</strong> Active</p>

        <input type="hidden" name="save_profile" value="1">
        <button type="button" class="action-btn" onclick="openConfirm()">Save Changes</button>
    </form>
</div>

<div class="popup hidden" id="confirmPopup">
    <div class="popup-box">
        <p>Confirm change?</p>
        <div class="popup-actions">
            <button onclick="closeConfirm()">No</button>
            <button onclick="submitForm()">Yes</button>
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