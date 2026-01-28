<?php
session_start();
require_once "../connect.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: ../login/login.php");
    exit();
}
$user_id = $_SESSION["user_id"];

$userSql = "SELECT user_id, name, role, eco_points, profile_image
            FROM users WHERE user_id = ? LIMIT 1";
$userStmt = mysqli_prepare($conn, $userSql);
mysqli_stmt_bind_param($userStmt, "s", $user_id);
mysqli_stmt_execute($userStmt);
$userRes = mysqli_stmt_get_result($userStmt);
$user = mysqli_fetch_assoc($userRes) ?: ["name"=>"User","eco_points"=>0,"profile_image"=>""];
mysqli_stmt_close($userStmt);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Reward Function | ReLife Hub</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/user.css">
    <style>
        .reward-card { max-width: 900px; margin: 30px auto; background: #fff; border-radius: 12px; box-shadow: 0 6px 18px rgba(0,0,0,.08); padding: 25px; }
        .reward-header-flex { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .record-btn { background: #333; color: white; padding: 8px 20px; border-radius: 6px; text-decoration: none; font-weight: bold; }
        .points-balance-text { font-size: 20px; font-weight: 500; color: #444; }
        .points-req-box { border: 1px solid #ccc; padding: 5px 10px; border-radius: 5px; font-size: 13px; text-align: center; line-height: 1.2; }
        .reward-table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        .reward-table th { border: 1px solid #000; background-color: #f2f2f2; padding: 10px; text-align: center; font-weight: bold; color: #000000; }
        .reward-table td { border: 1px solid #000; padding: 12px; vertical-align: middle; }
        .item-row { display: flex; align-items: center; gap: 15px; }
        .item-icon { font-size: 24px; font-weight: bold; min-width: 40px; text-align: center; }
        .item-desc h4 { margin: 0; font-size: 16px; text-transform: lowercase; color: #000000; }
        .item-desc p { margin: 2px 0 0; font-size: 12px; color: #666; }

        /* THE ONLY EDIT: Changed background-color to match your theme */
        .btn-redeem { background-color: #26a69a; color: white; border: none; padding: 6px 15px; border-radius: 4px; cursor: pointer; font-weight: bold; }
        
        .out-stock { color: #888; font-weight: bold; }
        
        /* POPUP STYLES */
        .overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.6); display: flex; justify-content: center; align-items: center; z-index: 10000; }
        .popup-box { background: white; padding: 40px; border-radius: 10px; text-align: center; width: 400px; }
        .popup-btns { display: flex; justify-content: center; gap: 15px; margin-top: 25px; }
        .btn-pop { padding: 10px 25px; border-radius: 6px; border: none; font-weight: 600; cursor: pointer; color: white; }
        .btn-no-back { background-color: #4db6ac; }
        .btn-confirm-ok { background-color: #26a69a; }
        .hidden { display: none !important; }
        footer { text-align: center; padding: 20px; color: #888; font-size: 14px; }
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
                <div class="user-top-user"><span class="user-top-name"> Welcome! <?= htmlspecialchars($user['name']) ?> </span></div>
            </div>
            <div class="user-top-center"><h1 style="color: white; margin: 0;">Reward</h1></div>
            <div class="user-top-right">
                <span class="user-top-points">🪙 <?php echo (int)$user["eco_points"]; ?> points</span>
                <a href="user_dashboard.php" class="user-top-btn" title="Home">🏠</a>
                <a href="user_profile.php" class="user-top-btn" title="Profile">👤</a>
                <a href="../login/logout.php" class="user-top-btn logout" title="Logout">❌</a>
            </div>
        </div>

        <div class="reward-card">
            <div class="reward-header-flex">
                <div style="display: flex; align-items: center; gap: 20px;"><a href="reward_history.php" class="record-btn">Record</a><span class="points-balance-text">User point balance</span></div>
                <div class="points-req-box">Points<br>Required</div>
            </div>
            <table class="reward-table">
                <thead><tr><th style="width: 45%;">Redeem item</th><th style="width: 10%;">Stock</th><th style="width: 15%;">Date</th><th style="width: 30%;">Availability</th></tr></thead>
                <tbody>
                    <tr id="item-apspace">
                        <td><div class="item-row"><div class="item-icon">🪙</div><div class="item-desc"><h4>rm10 apspace</h4><p>100point needed<br>Use before 1/1/26<br>(only for platinum user)</p></div></div></td>
                        <td align="center">10</td><td align="center">12/12/25</td>
                        <td align="center"><button class="btn-redeem" onclick="handleRedeem('item-apspace', 'Rm10 apspace', 'success')">Redeem</button></td>
                    </tr>
                    <tr id="item-parking">
                        <td><div class="item-row"><div class="item-icon" style="color: red;">P</div><div class="item-desc"><h4>free parking(one week)</h4><p>500point needed<br>limited for this month(Use before 30/12/25)</p></div></div></td>
                        <td align="center">2</td><td align="center">5/12/25</td>
                        <td align="center"><button class="btn-redeem" onclick="handleRedeem('item-parking', 'Free parking', 'fail')">Redeem</button></td>
                    </tr>
                    <tr>
                        <td><div class="item-row"><div class="item-icon">🥪</div><div class="item-desc"><h4>Subway</h4><p>900point need</p></div></div></td>
                        <td align="center">0</td><td align="center">24/5/25</td><td align="center"><span class="out-stock">Out of Stock</span></td>
                    </tr>
                    <tr id="item-drink">
                        <td><div class="item-row"><div class="item-icon">☕</div><div class="item-desc"><h4>free cafeteria drink</h4><p>50point needed<br>Applicable for Coffee/Tea only</p></div></div></td>
                        <td align="center">50</td><td align="center">15/01/26</td>
                        <td align="center"><button class="btn-redeem" onclick="handleRedeem('item-drink', 'Free cafeteria drink', 'success')">Redeem</button></td>
                    </tr>
                    <tr id="item-print">
                        <td><div class="item-row"><div class="item-icon">🖨️</div><div class="item-desc"><h4>rm5 printing credit</h4><p>80point needed<br>For library black & white printing</p></div></div></td>
                        <td align="center">100</td><td align="center">01/02/26</td>
                        <td align="center"><button class="btn-redeem" onclick="handleRedeem('item-print', 'Rm5 printing credit', 'success')">Redeem</button></td>
                    </tr>
                </tbody>
            </table>
            <footer>© 2026 ReLife Hub</footer>
        </div>
    </div>
</div>

<div class="overlay hidden" id="popConfirm">
    <div class="popup-box">
        <p style="font-size: 18px; line-height: 1.5;">Confirm to redeem?<br><span id="itemNameText" style="font-weight:bold;"></span></p>
        <div class="popup-btns">
            <button class="btn-pop btn-no-back" onclick="hidePop('popConfirm')">No</button>
            <button class="btn-pop btn-confirm-ok" id="confirmActionBtn">Confirm</button>
        </div>
    </div>
</div>

<div class="overlay hidden" id="popSuccess">
    <div class="popup-box">
        <p style="font-size: 20px; font-weight:500;">Redeemed Successfully</p>
        <div class="popup-btns">
            <button class="btn-pop btn-no-back" id="successBackBtn">Back</button>
            <button class="btn-pop btn-confirm-ok" id="successOkBtn">Ok</button>
        </div>
    </div>
</div>

<div class="overlay hidden" id="popFail">
    <div class="popup-box">
        <p style="font-size: 18px; line-height: 1.5;">Unable to Redeem<br>*Not enough point*</p>
        <div class="popup-btns">
            <button class="btn-pop btn-no-back" onclick="hidePop('popFail')">Back</button>
            <button class="btn-pop btn-confirm-ok" onclick="hidePop('popFail')">Ok</button>
        </div>
    </div>
</div>

<script src="../user/user.js"></script>
<script>
    let currentItemId = '';

    function hidePop(id) { document.getElementById(id).classList.add('hidden'); }
    function showPop(id) { document.getElementById(id).classList.remove('hidden'); }

    function handleRedeem(rowId, name, outcome) {
        currentItemId = rowId;
        if (outcome === 'fail') {
            showPop('popFail');
        } else {
            document.getElementById('itemNameText').innerText = name;
            showPop('popConfirm');
            document.getElementById('confirmActionBtn').onclick = function() {
                hidePop('popConfirm');
                showPop('popSuccess');
            };
        }
    }

    function removeAndClose() {
        if(currentItemId) { document.getElementById(currentItemId).remove(); }
        hidePop('popSuccess');
    }

    document.getElementById('successBackBtn').onclick = removeAndClose;
    document.getElementById('successOkBtn').onclick = removeAndClose;
</script>
</body>
</html>