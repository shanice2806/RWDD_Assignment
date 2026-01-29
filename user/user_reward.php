<?php
session_start();
require_once "../connect.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: ../login/login.php");
    exit();
}
$user_id = $_SESSION["user_id"];

// --- 1. HANDLE REDEMPTION AJAX ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax_redeem'])) {
    $r_id = $_POST['reward_id'];
    $pts_required = (int)$_POST['points'];

    // Check current points first
    $checkQuery = mysqli_query($conn, "SELECT eco_points FROM users WHERE user_id = '$user_id'");
    $userData = mysqli_fetch_assoc($checkQuery);
    $current_balance = (int)$userData['eco_points'];

    mysqli_begin_transaction($conn);
    try {
        $r_count_res = mysqli_query($conn, "SELECT COUNT(*) as total FROM reward_redemptions");
        $r_count = mysqli_fetch_assoc($r_count_res)['total'] + 1;
        $new_rdp = "rdp_" . str_pad($r_count, 3, "0", STR_PAD_LEFT);

        $t_count_res = mysqli_query($conn, "SELECT COUNT(*) as total FROM eco_points_transactions");
        $t_count = mysqli_fetch_assoc($t_count_res)['total'] + 1;
        $new_ept = "ept_" . str_pad($t_count, 3, "0", STR_PAD_LEFT);

        if ($current_balance >= $pts_required) {
            // SUCCESSFUL PATH
            mysqli_query($conn, "INSERT INTO reward_redemptions (redemption_id, user_id, reward_id, redemption_date_time, status, points_spent) VALUES ('$new_rdp', '$user_id', '$r_id', NOW(), 'Approved', $pts_required)");
            mysqli_query($conn, "INSERT INTO eco_points_transactions (transaction_id, user_id, source_id, points_change, description) VALUES ('$new_ept', '$user_id', '$new_rdp', -$pts_required, 'Redeemed Reward Item')");
            mysqli_query($conn, "UPDATE users SET eco_points = eco_points - $pts_required WHERE user_id = '$user_id'");
            mysqli_query($conn, "UPDATE reward_catalog SET stock = stock - 1 WHERE reward_id = '$r_id'");
            mysqli_commit($conn);
            echo "success";
        } else {
            // INSUFFICIENT POINTS PATH (REJECTED)
            mysqli_query($conn, "INSERT INTO reward_redemptions (redemption_id, user_id, reward_id, redemption_date_time, status, points_spent) VALUES ('$new_rdp', '$user_id', '$r_id', NOW(), 'Rejected', 0)");
            mysqli_query($conn, "INSERT INTO eco_points_transactions (transaction_id, user_id, source_id, points_change, description) VALUES ('$new_ept', '$user_id', '$new_rdp', 0, 'Failed Redemption - Insufficient Points')");
            mysqli_commit($conn);
            echo "insufficient";
        }
        exit;
    } catch (Exception $e) {
        mysqli_rollback($conn);
        echo "error"; exit;
    }
}

// --- 2. GET USER DATA ---
$userSql = "SELECT user_id, name, eco_points FROM users WHERE user_id = ? LIMIT 1";
$userStmt = mysqli_prepare($conn, $userSql);
mysqli_stmt_bind_param($userStmt, "s", $user_id);
mysqli_stmt_execute($userStmt);
$user = mysqli_fetch_assoc(mysqli_stmt_get_result($userStmt));
$current_points = (int)$user['eco_points'];

$view = isset($_GET['view']) ? $_GET['view'] : 'catalog';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Reward | ReLife Hub</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/user.css">
    <style>
        .user-layout { display: flex; min-height: 100vh; width: 100%; }
        .user-content { flex: 1; display: flex; flex-direction: column; background: #f9f9f9; }
        .reward-card { max-width: 900px; margin: 40px auto; background: #fff; border-radius: 12px; box-shadow: 0 6px 18px rgba(0,0,0,.08); padding: 25px; width: 90%; }
        .reward-header-flex { display: grid; grid-template-columns: 1fr auto 1fr; align-items: center; margin-bottom: 20px; }
        .btn-toggle { background: #333; color: white; padding: 8px 20px; border-radius: 6px; text-decoration: none; font-weight: bold; width: fit-content; }
        .mid-label { font-size: 24px; font-weight: bold; color: #000; text-align: center; }
        .points-display-group { text-align: right; }
        .balance-label { font-size: 15px; color: #000; margin-bottom: 5px; }
        .balance-label b { font-weight: bold; }
        .points-box { border: 1px solid #000; padding: 6px 12px; border-radius: 6px; font-size: 16px; font-weight: bold; background: #fff; display: inline-block; }
        .reward-table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        .reward-table th { border: 1px solid #000; background-color: #f2f2f2; padding: 10px; text-align: center; font-weight: bold; color: #000; }
        .reward-table td { border: 1px solid #000; padding: 12px; vertical-align: middle; color: #333; }
        .item-row { display: flex; align-items: center; gap: 25px; }
        .reward-img { width: 65px; height: 65px; object-fit: contain; background: #fff; border-radius: 4px; border: 1px solid #ddd; }
        .btn-redeem { background-color: #26a69a; color: white; border: none; padding: 6px 15px; border-radius: 4px; cursor: pointer; font-weight: bold; }
        .btn-redeem:disabled { background-color: #ccc; cursor: not-allowed; }
        
        .overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.6); display: flex; justify-content: center; align-items: center; z-index: 10000; }
        .popup-box { background: white; padding: 40px; border-radius: 10px; text-align: center; width: 420px; font-family: inherit; }
        .pop-btn-container { display: flex; justify-content: center; gap: 20px; margin-top: 30px; }
        .pop-btn { width: 130px; padding: 12px 0; border-radius: 6px; font-weight: bold; font-size: 16px; cursor: pointer; border: none; font-family: inherit; transition: opacity 0.2s; }
        .btn-no { background: #1f5147; color: white; }
        .btn-confirm { background: #1f5147; color: white; }
        .btn-ok { background: #333; color: white; width: 150px; }
        .pop-btn:hover { opacity: 0.9; }
        .hidden { display: none !important; }
        footer { text-align: center; padding: 20px; color: #888; font-size: 14px; margin-top: auto; }
    </style>
</head>
<body class="sidebar-collapsed">
<div class="user-layout">
    <?php include "user_sidebar.php"; ?>
    <div class="user-content">
        <div class="user-top-bar">
            <div class="user-top-left">
                <button class="sidebar-toggle" id="userSidebarToggle">☰</button>
                <div class="user-top-user">Welcome! <?= htmlspecialchars($user['name']) ?></div>
            </div>
            <div class="user-top-center"><h1 style="color: white; margin: 0;">Reward</h1></div>
            <div class="user-top-right">
                <a class="user-top-btn" href="user_profile.php">👤</a>
                <a href="user_dashboard.php" class="user-top-btn">🏠</a>
                <a href="../login/logout.php" class="user-top-btn logout">❌</a>
            </div>
        </div>

        <div class="reward-card">
            <div class="reward-header-flex">
                <a href="user_reward.php?view=<?= ($view === 'record') ? 'catalog' : 'record' ?>" class="btn-toggle">
                    <?= ($view === 'record') ? 'Back' : 'Record' ?>
                </a>
                <div class="mid-label"><?= ($view === 'record') ? 'Reward redemptions' : 'Points Required' ?></div>
                <div class="points-display-group">
                    <div class="balance-label"><b>User point balance :</b></div>
                    <div class="points-box">🪙 <?= $current_points ?> points</div>
                </div>
            </div>

            <table class="reward-table">
                <?php if ($view === 'record'): ?>
                    <thead>
                        <tr>
                            <th style="width: 40%;">Redeem Item</th>
                            <th style="width: 20%;">Used Points</th>
                            <th style="width: 20%;">Status</th>
                            <th style="width: 20%;">Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $histRes = mysqli_query($conn, "SELECT r.*, c.reward_name, c.image_path FROM reward_redemptions r JOIN reward_catalog c ON r.reward_id = c.reward_id WHERE r.user_id = '$user_id' ORDER BY r.redemption_date_time DESC");
                        while($h = mysqli_fetch_assoc($histRes)): ?>
                        <tr>
                            <td>
                                <div class="item-row">
                                    <img src="..<?= $h['image_path'] ?>" class="reward-img">
                                    <b style="color: #000;"><?= htmlspecialchars($h['reward_name']) ?></b>
                                </div>
                            </td>
                            <td align="center" style="color: #d32f2f; font-weight: bold;">-<?= $h['points_spent'] ?> pts</td>
                            <td align="center" style="font-weight:bold; color:<?= $h['status'] === 'Rejected' ? '#f44336' : '#2e7d32' ?>;"><?= $h['status'] ?></td>
                            <td align="center"><?= date("d/m/Y", strtotime($h['redemption_date_time'])) ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                <?php else: ?>
                    <thead>
                        <tr>
                            <th style="width: 45%;">Redeem item</th>
                            <th style="width: 15%;">Points</th>
                            <th style="width: 15%;">Stock</th>
                            <th style="width: 25%;">Availability</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $catRes = mysqli_query($conn, "SELECT * FROM reward_catalog WHERE reward_id NOT IN (SELECT reward_id FROM reward_redemptions WHERE user_id = '$user_id' AND status = 'Approved') ORDER BY points_required ASC");
                        while($row = mysqli_fetch_assoc($catRes)): 
                        ?>
                        <tr>
                            <td>
                                <div class="item-row">
                                    <img src="..<?= $row['image_path'] ?>" class="reward-img">
                                    <div>
                                        <b style="color: #000;"><?= htmlspecialchars($row['reward_name']) ?></b><br>
                                        <small style="color: #666;"><?= htmlspecialchars($row['description']) ?></small>
                                    </div>
                                </div>
                            </td>
                            <td align="center"><?= $row['points_required'] ?> pts</td>
                            <td align="center"><?= $row['stock'] ?></td>
                            <td align="center">
                                <button class="btn-redeem" onclick="handleRedeem('<?= $row['reward_id'] ?>', '<?= addslashes($row['reward_name']) ?>', <?= $row['points_required'] ?>)" <?= $row['stock'] <= 0 ? 'disabled' : '' ?>>
                                    <?= $row['stock'] <= 0 ? 'Out of Stock' : 'Redeem' ?>
                                </button>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                <?php endif; ?>
            </table>
        </div>
        <footer>© 2026 ReLife Hub</footer>
    </div>
</div>

<div class="overlay hidden" id="popConfirm">
    <div class="popup-box">
        <p style="font-size: 1.2rem; color: #333;">Confirm to redeem?</p>
        <p id="itemNameText" style="font-weight:bold; color: #26a69a; margin-top: -10px; font-size: 1.2rem;"></p>
        <div class="pop-btn-container">
            <button class="pop-btn btn-no" onclick="document.getElementById('popConfirm').classList.add('hidden')">No</button>
            <button class="pop-btn btn-confirm" id="confirmActionBtn">Confirm</button>
        </div>
    </div>
</div>

<div class="overlay hidden" id="popSuccess">
    <div class="popup-box">
        <p style="font-size: 1.4rem; font-weight: bold; color: #26a69a;">Redeem Successful!</p>
        <div class="pop-btn-container">
            <button class="pop-btn btn-ok" onclick="location.reload()">OK</button>
        </div>
    </div>
</div>

<div class="overlay hidden" id="popFail">
    <div class="popup-box">
        <p style="font-size: 1.3rem; font-weight: bold; color: #f44336;">Redeem Unsuccessful</p>
        <p style="color: #333; margin-top: -5px;"><b>* not enough points *</b></p>
        <div class="pop-btn-container">
            <button class="pop-btn btn-no" onclick="location.reload()">Noted</button>
        </div>
    </div>
</div>

<script>
    let currentRewardId = '', currentPoints = 0;
    const toggleBtn = document.getElementById('userSidebarToggle');
    const sidebar = document.getElementById('userSidebar');
    if (toggleBtn && sidebar) { toggleBtn.addEventListener('click', () => { sidebar.classList.toggle('collapsed'); }); }

    function handleRedeem(id, name, pts) {
        currentRewardId = id; currentPoints = pts;
        document.getElementById('itemNameText').innerText = name;
        document.getElementById('popConfirm').classList.remove('hidden');
    }

    document.getElementById('confirmActionBtn').onclick = function() {
        let params = new URLSearchParams({ ajax_redeem: '1', reward_id: currentRewardId, points: currentPoints });
        fetch('user_reward.php', { method: 'POST', body: params })
        .then(res => res.text())
        .then(data => { 
            document.getElementById('popConfirm').classList.add('hidden');
            if(data.trim() === 'success') { 
                document.getElementById('popSuccess').classList.remove('hidden');
            } else if(data.trim() === 'insufficient') {
                document.getElementById('popFail').classList.remove('hidden');
            } else { 
                alert("Database Error!"); 
            } 
        });
    };
</script>
</body>
</html>