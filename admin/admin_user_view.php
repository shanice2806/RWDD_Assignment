<?php
include "../connect.php";
$page_title = "View User";
include "admin_header.php";

/* Validate ID */
if (!isset($_GET['id'])) {
    die("User ID not provided.");
}

$user_id = $_GET['id'];

/* Fetch user */
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


$profileImage = !empty($user['profile_image'])
    ? $user['profile_image']
    : 'default.jpeg';
?>

<main class="dashboard">


    <div class="page-title-bar">
        <a href="admin_manage_user.php" class="icon-btn back-btn">↩</a>
        <h2><?= htmlspecialchars($user['user_id']) ?></h2>
    </div>


    <div class="section-preview" style="max-width:700px; margin:auto;">

   
        <div style="text-align:center; margin-bottom:20px;">
            <img src="../images/profile/<?= htmlspecialchars($profileImage) ?>"
                 alt="Profile Photo"
                 style="
                    width:120px;
                    height:120px;
                    border-radius:50%;
                    object-fit:cover;
                 ">
        </div>


        <div style="text-align:center; margin-bottom:20px;">
            <a href="admin_user_edit.php?id=<?= urlencode($user['user_id']) ?>" class="btn">
                Edit Profile
            </a>
        </div>


        <div class="form-group">
            <label>Name</label>
            <input type="text" value="<?= htmlspecialchars($user['name']) ?>" readonly>
        </div>

        <div class="form-group">
            <label>User ID</label>
            <input type="text" value="<?= htmlspecialchars($user['user_id']) ?>" readonly>
        </div>

        <div class="form-group">
            <label>Email</label>
            <input type="text" value="<?= htmlspecialchars($user['email']) ?>" readonly>
        </div>

        <div class="form-group">
            <label>Role</label>
            <input type="text" value="<?= htmlspecialchars($user['role']) ?>" readonly>
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
            <label>Last Login</label>
            <input type="text" value="<?= htmlspecialchars($user['last_login']) ?>" readonly>
        </div>

        <div class="form-group">
            <label>Account Created On</label>
            <input type="text" value="<?= htmlspecialchars($user['created_at']) ?>" readonly>
        </div>

        <div class="form-group">
            <label>Account Status</label>
            <input type="text" value="<?= htmlspecialchars($user['account_status']) ?>" readonly>
        </div>


        <div style="text-align:center; margin-bottom:20px;">
            <a href="admin_points_transaction.php?id=<?= urlencode($user['user_id']) ?>"
               class="btn"
               style="background:#3ba99c; color:#fff;">
                View Eco Points Transaction
            </a>
        </div>

    </div>
</main>

<?php include "admin_footer.php"; ?>
