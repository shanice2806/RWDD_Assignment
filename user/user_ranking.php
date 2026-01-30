<?php
session_start();
require_once "../connect.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: ../login/login.php");
    exit();
}
$user_id = $_SESSION["user_id"];

// 1. GET USER DATA
$userSql = "SELECT user_id, name, eco_points, profile_image FROM users WHERE user_id = ? LIMIT 1";
$userStmt = mysqli_prepare($conn, $userSql);
mysqli_stmt_bind_param($userStmt, "s", $user_id);
mysqli_stmt_execute($userStmt);
$user = mysqli_fetch_assoc(mysqli_stmt_get_result($userStmt));

// 4. FETCH RULES
$rulesQuery = mysqli_query($conn, "SELECT rule_key, description, points_per_kg FROM eco_points_rules WHERE is_active = 1 ORDER BY rule_id ASC");

// 进度条逻辑
$logCountQuery = mysqli_query($conn, "SELECT COUNT(*) as total FROM recycling_log WHERE user_id = '$user_id' AND status = 'VALID'");
$total_recycled = mysqli_fetch_assoc($logCountQuery)['total'] ?? 0;
$recycle_goal = 50; 
$progress_percent = min(($total_recycled / $recycle_goal) * 100, 100);

$allBadgesRes = mysqli_query($conn, "SELECT * FROM badges WHERE is_active = 1");

// User Achievements 按照 User ID 顺序排列
$userBadgesSql = "SELECT ub.user_id, b.icon_path, b.badge_id, ub.date_awarded 
                  FROM user_badges ub 
                  INNER JOIN badges b ON ub.badge_id = b.badge_id 
                  ORDER BY ub.user_id ASC";
$userBadgesRes = mysqli_query($conn, $userBadgesSql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Ranking | ReLife Hub</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/user.css">
    <style>
        .user-layout { display: flex; min-height: 100vh; width: 100%; }
        .user-content { flex: 1; display: flex; flex-direction: column; background: #f9f9f9; }
        .reward-card { max-width: 900px; margin: 40px auto; background: #fff; border-radius: 12px; box-shadow: 0 6px 18px rgba(0,0,0,.08); padding: 25px; width: 90%; }
        
        .reward-header-flex { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; position: relative; }
        .btn-toggle { background: #26a69a; color: white; padding: 8px 20px; border-radius: 6px; text-decoration: none; font-weight: bold; width: fit-content; border: none; cursor: pointer; }
        .ranking-title-font { font-family: "Times New Roman", Times, serif; font-size: 32px; font-weight: bold; color: #fff; text-align: center; }
        .leaderboard-title-font { font-family: "Times New Roman", Times, serif; font-size: 32px; font-weight: bold; color: #000; text-align: center; }

        .progress-text { font-size: 15px; font-weight: 600; color: #333; margin-bottom: 10px; }
        .progress-num { color: #26a69a; font-weight: 800; }
        .progress-track { background: #eee; height: 12px; border-radius: 10px; overflow: hidden; margin-bottom: 40px; border: 1px solid #ddd; }
        .progress-fill { background: #26a69a; height: 100%; transition: width 0.8s ease; }
        
        .section-title { font-size: 18px; font-weight: 700; color: #000; margin-bottom: 20px; display: block; border-bottom: 1px solid #000; padding-bottom: 5px; }
        .click-hint { color: red; font-size: 14px; font-weight: bold; margin-left: 10px; }

        .badges-container { display: flex; flex-direction: column; align-items: center; padding: 40px; background: rgba(38, 166, 154, 0.05); border: 2px solid #26a69a; border-radius: 20px; margin-bottom: 40px; gap: 30px; }
        .badge-row { display: flex; justify-content: center; gap: 50px; width: 100%; }
        .badge-box { background: #ffffff; padding: 20px; border-radius: 18px; box-shadow: 0 6px 15px rgba(0, 0, 0, 0.06); transition: all 0.3s ease; display: flex; flex-direction: column; justify-content: center; align-items: center; width: 160px; border: 2px solid transparent; }
        .badge-id-label { font-size: 13px; font-weight: bold; color: #26a69a; margin-bottom: 8px; }
        .badge-img { width: 120px; height: 120px; object-fit: contain; }

        @keyframes badgeShake {
            0% { transform: scale(1.1) rotate(0deg); }
            25% { transform: scale(1.2) rotate(5deg); }
            50% { transform: scale(1.2) rotate(-5deg); }
            75% { transform: scale(1.2) rotate(5deg); }
            100% { transform: scale(1) rotate(0deg); }
        }
        .shake-active { animation: badgeShake 0.6s ease; border: 3px solid #26a69a !important; }

        .reward-table { width: 100%; border-collapse: collapse; margin-top: 10px; margin-bottom: 30px; }
        .reward-table th { border: 1px solid #000; background-color: #26a69a; padding: 12px; text-align: center; font-weight: bold; color: #ffffff; text-transform: uppercase; font-size: 14px; }
        .reward-table td { border: 1px solid #000; padding: 12px; vertical-align: middle; color: #333; }
        
        .achievement-row { cursor: pointer; }
        .achievement-row:hover { background-color: #f0fdfa; }

        /* 修改点：更换 Highlight 颜色防止撞色 (#fff3cd 为浅黄色) */
        .row-me { background: #fff3cd !important; font-weight: 900; border: 2px solid #856404 !important; }
        .row-me td { color: #856404 !important; }

        .hidden-row { display: none; }
        .see-more { text-align: center; padding: 15px; color: #26a69a; font-weight: bold; cursor: pointer; text-decoration: underline; }

        .overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.6); display: flex; justify-content: center; align-items: center; z-index: 10000; }
        .popup-box { background: white; padding: 40px; border-radius: 10px; text-align: center; width: 600px; max-width: 95%; max-height: 80vh; overflow-y: auto; }
        .rule-item { display: flex; align-items: flex-start; margin-bottom: 8px; }
        .rule-number { min-width: 25px; font-weight: bold; text-align: left; }
        .rule-text { flex: 1; font-weight: bold; text-align: left; }
        .btn-ok { background: #333; color: white; width: 150px; padding: 12px 0; border-radius: 6px; font-weight: bold; cursor: pointer; border: none; margin-top: 10px; }
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
            <div class="user-top-center"><h1 class="ranking-title-font" style="margin: 0;">Ranking</h1></div>
            <div class="user-top-right">
                <a href="user_dashboard.php" class="user-top-btn">🏠</a>
                <a class="user-top-btn" href="add_friends.php">👥</a>
                <a href="../login/logout.php" class="user-top-btn logout">❌</a>
            </div>
        </div>

        <div class="reward-card">
            <div class="reward-header-flex">
                <div style="flex: 1;"><button class="btn-toggle" onclick="toggleModal(true)">Rules</button></div>
                <div class="leaderboard-title-font" style="flex: 2; text-align: center;">Leaderboard</div>
                <div style="flex: 1; text-align: right;">
                    <div style="font-size: 14px; font-weight: bold;">point balance :</div>
                    <div style="border: 1px solid #000; padding: 4px 10px; border-radius: 6px; font-weight: bold; background: #fff; display: inline-block;">
                        🪙 <?= number_format($user['eco_points']) ?> points
                    </div>
                </div>
            </div>

            <div class="progress-text">Total items recycled : <span class="progress-num"><?= $total_recycled ?> items</span></div>
            <div class="progress-track"><div class="progress-fill" style="width: <?= $progress_percent ?>%;"></div></div>

            <span class="section-title">Badges</span>
            <div class="badges-container">
                <?php 
                $badgesArr = [];
                while($b = mysqli_fetch_assoc($allBadgesRes)) { $badgesArr[] = $b; }
                echo '<div class="badge-row">';
                for($i = 0; $i < 3; $i++) {
                    if(isset($badgesArr[$i])) { 
                        echo '<div class="badge-box" id="badge-'.$badgesArr[$i]['badge_id'].'">';
                        echo '<span class="badge-id-label"><b>'.$badgesArr[$i]['badge_id'].'</b></span>';
                        echo '<img src="../images/badges/'.$badgesArr[$i]['icon_path'].'" class="badge-img">';
                        echo '</div>'; 
                    }
                }
                echo '</div><div class="badge-row">';
                for($i = 3; $i < 5; $i++) {
                    if(isset($badgesArr[$i])) { 
                        echo '<div class="badge-box" id="badge-'.$badgesArr[$i]['badge_id'].'">';
                        echo '<span class="badge-id-label"><b>'.$badgesArr[$i]['badge_id'].'</b></span>';
                        echo '<img src="../images/badges/'.$badgesArr[$i]['icon_path'].'" class="badge-img">';
                        echo '</div>'; 
                    }
                }
                echo '</div>';
                ?>
            </div>

            <span class="section-title">User Achievements <span class="click-hint">(click me ⬇️)</span></span>
            <table class="reward-table">
                <thead>
                    <tr>
                        <th style="width: 25%;">USER ID</th>
                        <th style="width: 35%;">USER BADGE ID</th> 
                        <th style="width: 40%;">DATE AWARDED</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($ub_row = mysqli_fetch_assoc($userBadgesRes)): 
                        $is_current_user = ($ub_row['user_id'] == $user_id);
                        $formatted_date = date("Y-m-d", strtotime($ub_row['date_awarded']));
                    ?>
                    <tr class="achievement-row <?= $is_current_user ? 'row-me' : '' ?>" onclick="highlightBadge('<?= $ub_row['badge_id'] ?>')">
                        <td align="center">
                            <?= htmlspecialchars($ub_row['user_id']) ?>
                            <?= $is_current_user ? ' <b>(Me)</b>' : '' ?>
                        </td>
                        <td align="center"><b><?= htmlspecialchars($ub_row['badge_id']) ?></b></td>
                        <td align="center"><?= $formatted_date ?></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>

            <span class="section-title">Recyclers Leaderboard 🏆</span>
            <table class="reward-table" id="rankTable">
                <thead><tr><th style="width: 20%;">Rank</th><th style="width: 50%;">Username</th><th style="width: 30%;">Total Points</th></tr></thead>
                <tbody>
                    <?php 
                    $rankSql = "SELECT user_id, name, eco_points FROM users ORDER BY eco_points DESC";
                    $rankResult = mysqli_query($conn, $rankSql);
                    $currentRank = 1;
                    while($row = mysqli_fetch_assoc($rankResult)):
                        $is_me = ($row['user_id'] == $user_id);
                        $hide_class = ($currentRank > 5) ? 'hidden-row' : '';
                        $starEmoji = ($currentRank <= 3) ? " ⭐" : "";
                    ?>
                    <tr class="<?= $hide_class ?> <?= $is_me ? 'row-me' : '' ?>">
                        <td align="center">#<?= $currentRank ?></td>
                        <td><?= htmlspecialchars($row['name']) . $starEmoji ?> <?= $is_me ? '<b>(Me)</b>' : '' ?></td>
                        <td align="center">🪙 <?= number_format($row['eco_points']) ?></td>
                    </tr>
                    <?php $currentRank++; endwhile; ?>
                </tbody>
            </table>
            <div class="see-more" id="seeBtn" onclick="toggleTable()">See more users..</div>
        </div>
        <footer>© 2026 ReLife Hub</footer>
    </div>
</div>

<div class="overlay hidden" id="rulesModal">
    <div class="popup-box">
        <h2 style="color: #26a69a; margin-bottom: 20px; border-bottom: 2px solid #eee; padding-bottom: 10px;">Eco Points Rules</h2>
        <div style="margin-bottom: 25px; line-height: 1.8; font-size: 14px;">
            <?php 
            mysqli_data_seek($rulesQuery, 0); 
            $count = 1;
            while($rule = mysqli_fetch_assoc($rulesQuery)) {
                $displayName = !empty($rule['description']) ? htmlspecialchars($rule['description']) : ucwords(str_replace('_', ' ', $rule['rule_key']));
                ?>
                <div class="rule-item">
                    <div class="rule-number"><?= $count ?>.&nbsp;</div>
                    <div class="rule-text"><?= $displayName ?><span style='color: #26a69a; float: right;'>+<?= $rule['points_per_kg'] ?> Pts</span></div>
                </div>
                <hr style='border: 0; border-top: 1px solid #f0f0f0; margin: 8px 0;'>
                <?php $count++; } ?>
        </div>
        <button class="btn-ok" onclick="toggleModal(false)">Got it !</button>
    </div>
</div>

<script>
    const sidebarToggle = document.getElementById('userSidebarToggle');
    const sidebar = document.getElementById('userSidebar');
    if (sidebarToggle && sidebar) {
        sidebarToggle.addEventListener('click', () => { sidebar.classList.toggle('collapsed'); });
    }

    function toggleModal(show) { document.getElementById('rulesModal').classList.toggle('hidden', !show); }
    
    function highlightBadge(badgeId) {
        document.querySelectorAll('.badge-box').forEach(el => el.classList.remove('shake-active'));
        const target = document.getElementById('badge-' + badgeId);
        if(target) {
            target.classList.add('shake-active');
            target.scrollIntoView({ behavior: 'smooth', block: 'center' });
            setTimeout(() => { target.classList.remove('shake-active'); }, 1000);
        }
    }
    
    let expanded = false;
    function toggleTable() {
        expanded = !expanded;
        document.querySelectorAll('.hidden-row').forEach(r => { r.style.display = expanded ? 'table-row' : 'none'; });
        document.getElementById('seeBtn').innerText = expanded ? 'See less..' : 'See more users..';
    }
</script>
</body>
</html>