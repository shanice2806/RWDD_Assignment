<?php
session_start();
include "../connect.php";
$page_title = "Edit User";
include "admin_header.php";


if (!isset($_GET['id'])) {
    header("Location: admin_manage_user.php");
    exit;
}

$user_id = $_GET['id'];

$stmt = $conn->prepare("
    SELECT user_id, name, email, role, eco_points, badges,
           created_at, account_status, last_login, profile_image
    FROM users
    WHERE user_id = ?
");
$stmt->bind_param("s", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("User not found.");
}

$user = $result->fetch_assoc();


$success = "";
$error   = "";


if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $role           = $_POST['role'];
    $account_status = $_POST['account_status'];

    $update = $conn->prepare("
        UPDATE users
        SET role = ?, account_status = ?
        WHERE user_id = ?
    ");

    $update->bind_param("sss", $role, $account_status, $user_id);

    if ($update->execute()) {
        $success = "User updated successfully.";
        $user['role'] = $role;
        $user['account_status'] = $account_status;

    } else {
        $error = "Failed to update user.";
    }
}
?>

<div class="main-content">
<main class="dashboard">


    <div class="page-title-bar">
        <a href="admin_manage_user.php?id=<?= urlencode($user_id) ?>" class="icon-btn back-btn">↩</a>
        <h2><?= htmlspecialchars($user_id) ?></h2>
    </div>


    <?php if (!empty($success)): ?>
        <div class="alert-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <?php if (!empty($error)): ?>
        <div class="alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>


    <form method="POST" class="section-preview" style="max-width:700px; margin:auto;">


        <div style="text-align:center; margin-bottom:20px;">
            <?php
            $profileImage = !empty($user['profile_image'])
                ? "../images/profile/" . htmlspecialchars($user['profile_image'])
                : "../images/profile/default.jpeg";
            ?>
            <div class="profile-avatar-large">
                <img src="<?= $profileImage ?>" alt="Profile">
            </div>
        </div>

        <div class="form-group">
            <label>User ID</label>
            <input type="text" value="<?= htmlspecialchars($user['user_id']) ?>" readonly>
        </div>

        <div class="form-group">
            <label>Name</label>
            <input type="text" value="<?= htmlspecialchars($user['name']) ?>" readonly>
        </div>

        <div class="form-group">
            <label>Email</label>
            <input type="text" value="<?= htmlspecialchars($user['email']) ?>" readonly>
        </div>

        <div class="form-group">
            <label>Eco Points</label>
            <input type="text" value="<?= htmlspecialchars($user['eco_points']) ?>" readonly>
        </div>

        <div class="form-group">
            <label>Badges</label>
            <input type="text" value="<?= htmlspecialchars($user['badges']) ?>" readonly>
        </div>

        <div class="form-group">
            <label>Created At</label>
            <input type="text" value="<?= htmlspecialchars($user['created_at']) ?>" readonly>
        </div>

        <div class="form-group">
            <label>Last Login</label>
            <input type="text" value="<?= htmlspecialchars($user['last_login']) ?>" readonly>
        </div>

        <div class="form-group">
            <label>Role</label>
            <select name="role">
                <option value="Admin" <?= $user['role'] === 'Admin' ? 'selected' : '' ?>>Admin</option>
                <option value="User" <?= $user['role'] === 'User' ? 'selected' : '' ?>>User</option>
                <option value="Event Organizer" <?= $user['role'] === 'Event Organizer' ? 'selected' : '' ?>>
                    Event Organizer
                </option>
            </select>
        </div>

        <div class="form-group">
            <label>Account Status</label>
            <select name="account_status">
                <option value="Activated" <?= $user['account_status'] === 'Activated' ? 'selected' : '' ?>>
                    Activated
                </option>
                <option value="Deactivated" <?= $user['account_status'] === 'Deactivated' ? 'selected' : '' ?>>
                    Deactivated
                </option>
            </select>
        </div>

        <div style="display:flex; justify-content:center; gap:20px; margin-top:25px;">
            <button type="submit" class="btn save-btn">Edit</button>
        </div>

    </form>
</main>
</div>

<?php include "admin_footer.php"; ?>
