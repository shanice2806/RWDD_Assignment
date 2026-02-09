<?php
session_start();
require_once "../connect.php"; 

if (!isset($_SESSION["user_id"])) {
    header("Location: ../login/login.php");
    exit();
}
$user_id = $_SESSION["user_id"];

function h($str) {
    return htmlspecialchars((string)$str, ENT_QUOTES, 'UTF-8');
}

// --- DATABASE ACTIONS ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $target = $_POST['target_id'];
        if ($_POST['action'] === 'accept') {
            mysqli_query($conn, "UPDATE user_friends SET status = 'accepted' WHERE user_id = '$target' AND friend_id = '$user_id'");
            $_SESSION['msg_alert'] = "Accepted a friend!";
        } elseif ($_POST['action'] === 'decline') {
            mysqli_query($conn, "DELETE FROM user_friends WHERE (user_id = '$target' AND friend_id = '$user_id') OR (user_id = '$user_id' AND friend_id = '$target') AND status = 'pending'");
            $_SESSION['msg_alert'] = "Declined a friend!";
        } elseif ($_POST['action'] === 'add') {
            $tp = mysqli_real_escape_string($conn, $_POST['tp_num']);
            $checkUser = mysqli_query($conn, "SELECT user_id FROM users WHERE user_id = '$tp'");
            if (mysqli_num_rows($checkUser) > 0) {
                $new_id = "f_" . substr(md5(time()), 0, 8);
                mysqli_query($conn, "INSERT INTO user_friends (friendship_id, user_id, friend_id, status) VALUES ('$new_id', '$user_id', '$tp', 'pending')");
                $_SESSION['msg_alert'] = "Friend request sent!";
            }
        }
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }
}

// 1. GET USER DATA
$userSql = "SELECT user_id, name, eco_points, profile_image FROM users WHERE user_id = ? LIMIT 1";
$userStmt = mysqli_prepare($conn, $userSql);
mysqli_stmt_bind_param($userStmt, "s", $user_id);
mysqli_stmt_execute($userStmt);
$user = mysqli_fetch_assoc(mysqli_stmt_get_result($userStmt));

// 2. Fetch Messages
$friends = [];
$msgSql = "SELECT m.*, u.name as sender_name FROM messages m JOIN users u ON m.sender_id = u.user_id ORDER BY m.sent_at DESC"; 
$msgRes = mysqli_query($conn, $msgSql);
while ($row = mysqli_fetch_assoc($msgRes)) {
    $friends[] = [
        'name' => $row['sender_name'],
        'msg' => $row['message_text'],
        'time' => date("H:i", strtotime($row['sent_at'])),
        'is_new' => ($row['sender_id'] !== $user_id), 
        'history' => $row['message_text']
    ];
}

// 3. Fetch Pending Requests
$requests = [];
$reqSql = "SELECT u.name, uf.user_id FROM user_friends uf 
           JOIN users u ON uf.user_id = u.user_id 
           WHERE uf.friend_id = '$user_id' AND uf.status = 'pending'";
$reqRes = mysqli_query($conn, $reqSql);
while ($rRow = mysqli_fetch_assoc($reqRes)) { $requests[] = $rRow; }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat | ReLife Hub</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/user.css">
</head>
    <style>
        :root { --color-primary: #1e3a34; --color-secondary: #3ba99c; --ig-blue: #0095f6; --color-red: #ff4d4d; --color-online: #2ecc71; }
        
        /* Layout Fix: Footer to bottom */
        .user-layout { display: flex; min-height: 100vh; width: 100%; }
        .user-content { flex: 1; display: flex; flex-direction: column; background: #f9f9f9; min-height: 100vh; }
        
        /* Main page made longer */
        .main-app { 
            width: 1200px; 
            margin: 40px auto; 
            background: white; 
            border-radius: 12px; 
            box-shadow: 0 4px 15px rgba(0,0,0,0.1); 
            display: flex; 
            flex-direction: column; 
            position: relative; 
            height: 850px; /* Made longer */
            overflow: hidden; 
        }
        
        .chat-title-row { padding: 15px 40px; background: var(--color-secondary); color: white; display: flex; justify-content: space-between; align-items: center; }
        .header-controls { display: flex; align-items: flex-end; gap: 30px; }
        
        .request-btn { background: #fff; color: var(--color-primary); border: none; padding: 8px 15px; border-radius: 6px; font-weight: bold; cursor: pointer; font-size: 14px; margin-bottom: 2px; }
        .request-btn:hover { background: #f2f2f2; color: var(--color-primary); }

        .point-box { background: white; color: black; padding: 4px 15px; border-radius: 6px; border: 1px solid #000; font-weight: bold; font-size: 15px; }

        .friend-list { background: #fff; flex: 1; overflow-y: auto; scrollbar-width: none; }
        .friend-card { display: flex; align-items: center; padding: 20px 40px; cursor: pointer; border-bottom: 1px solid #eee; transition: 0.2s; }
        .friend-card:hover { background: #f9f9f9; }
        
        .unread-dot { width: 10px; height: 10px; background: var(--ig-blue); border-radius: 50%; margin-left: 10px; display: inline-block; }
        
        .dm-screen { position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: #fff; z-index: 100; display: none; flex-direction: column; }
        .dm-user-bar { padding: 15px 30px; border-bottom: 1px solid #eee; display: flex; align-items: center; justify-content: space-between; }
        .dm-body { flex: 1; padding: 20px; overflow-y: auto; display: flex; flex-direction: column; gap: 10px; }
        .bubble { padding: 10px 15px; border-radius: 18px; max-width: 70%; font-size: 14px; word-wrap: break-word; }
        .bubble.me { background: var(--color-secondary); color: white; align-self: flex-end; }
        .bubble.them { background: #efefef; align-self: flex-start; }
        
        .dm-footer { padding: 15px 25px; display: flex; gap: 15px; border-top: 1px solid #eee; align-items: center; }
        .reply-input { flex: 1; padding: 10px; border: 1px solid #ddd; border-radius: 20px; outline: none; }
        .send-btn { background: none; color: var(--ig-blue); border: none; font-weight: bold; cursor: pointer; }

        .overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.6); display: none; justify-content: center; align-items: center; z-index: 2500; }
        .popup { background: white; width: 800px; height: 500px; display: grid; grid-template-columns: 1fr 1fr; border-radius: 12px; overflow: hidden; position: relative; }
        .action-btn { border: none; padding: 6px 12px; border-radius: 4px; color: white; font-weight: bold; cursor: pointer; font-size: 12px; }

        /* Footer stick to bottom */
        footer { margin-top: auto; padding: 20px 0; background: white; text-align: center; border-top: 1px solid #eee; }
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
                <div class="user-top-user">
                    <span class="user-top-name"><span> Welcome! <?= h($_SESSION['name'] ?? 'User') ?> </span>
                </div>
            </div>

            <form class="user-top-center" onsubmit="return false;">
                <input class="user-top-search" type="text" id="searchInp" placeholder="Search friends..." onkeyup="liveSearch()">
                <button class="user-top-search-btn" type="button">Search</button>
            </form>

            <div class="user-top-right">
                <a href="user_dashboard.php" class="user-top-btn" title="Home">🏠</a>
                <a class="user-top-btn" href="add_friends.php" title="Add Friend">👥</a>
                <a href="../login/logout.php" class="user-top-btn logout" title="Logout">❌</a>
            </div>
        </div>

        <div class="main-app">
            <div class="chat-title-row">
                <h2 style="margin:0; font-family: 'Times New Roman', serif;">Messages</h2>
                <div class="header-controls">
                    <button class="request-btn" onclick="togglePop()">Friend Request</button>
                    <div class="point-box">🪙 <?= number_format($user['eco_points']) ?> points</div>
                </div>
            </div>

            <div class="friend-list" id="friendContainer">
                <?php foreach ($friends as $f): ?>
                <div class="friend-card" onclick="openDM('<?= h($f['name']) ?>', this, '<?= h($f['history']) ?>')">
                    <div style="flex:1;">
                        <div style="display:flex; justify-content:space-between; align-items:center;">
                            <strong><?= h($f['name']) ?><?php if($f['is_new']): ?><span class="unread-dot"></span><?php endif; ?></strong>
                            <span class="card-time" style="color:#aaa; font-size:12px;"><?= $f['time'] ?></span>
                        </div>
                        <p class="card-msg" style="color:#666; margin-top: 5px; font-size: 14px;"><?= h($f['msg']) ?></p>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <div class="dm-screen" id="dm">
                <div class="dm-user-bar">
                    <div style="display:flex; align-items:center; gap:15px;">
                        <div style="cursor:pointer; font-size:24px;" onclick="closeDM()">⬅</div>
                        <span id="dmName" style="font-weight:bold; font-size: 18px;">-</span>
                    </div>
                    <div class="active-status" style="color: var(--color-online); font-size: 13px;">● Active now</div>
                </div>
                <div class="dm-body" id="chatBody"></div>
                <div class="dm-footer">
                    <input type="text" id="replyField" class="reply-input" placeholder="Message..." onkeypress="if(event.key==='Enter')sendReply()">
                    <button class="send-btn" onclick="sendReply()">Send</button>
                </div>
            </div>
        </div>
        <footer>© 2026 ReLife Hub</footer>
    </div>
</div>

<div class="overlay" id="pop">
    <div class="popup">
        <div style="position:absolute; right:20px; top:15px; cursor:pointer; font-size: 24px; z-index:10;" onclick="togglePop()">✖</div>
        <div style="padding:40px; border-right:1px solid #eee; overflow-y:auto;">
            <h3>Requests</h3>
            <?php if (empty($requests)): ?>
                <p style="color: #666; font-style: times new roman;">No pending friend requests.</p>
            <?php else: ?>
                <?php foreach ($requests as $r): ?>
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:10px; background:#f9f9f9; padding:10px; border-radius:8px;">
                    <strong><?= h($r['name']) ?></strong>
                    <div style="display:flex; gap:5px;">
                        <form method="POST"><input type="hidden" name="target_id" value="<?= $r['user_id'] ?>"><button name="action" value="accept" class="action-btn" style="background:var(--color-secondary);">Accept</button></form>
                        <form method="POST"><input type="hidden" name="target_id" value="<?= $r['user_id'] ?>"><button name="action" value="decline" class="action-btn" style="background:var(--color-red);">Decline</button></form>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        <div style="padding:40px;">
            <h3>Add Friend</h3>
            <p style="font-size: 13px; color: #666; margin-bottom: 10px;">Enter the user's TP number (e.g., TP012345) to send a request.</p>
            <form method="POST">
                <input type="text" name="tp_num" style="width:100%; box-sizing:border-box; padding:12px; border:1px solid #ddd; border-radius:5px; margin-bottom:15px;" placeholder="TP000000" required>
                <button type="submit" name="action" value="add" style="width:100%; padding:12px; background:var(--color-primary); color:white; border:none; border-radius:5px; font-weight:bold;">Send Request</button>
            </form>
        </div>
    </div>
</div>

<?php if (isset($_SESSION['msg_alert'])): ?>
    <script>alert("localhost: <?= $_SESSION['msg_alert'] ?>");</script>
    <?php unset($_SESSION['msg_alert']); ?>
<?php endif; ?>

<script src="../user/user.js"></script>
<script>
    function togglePop() { document.getElementById('pop').style.display = (document.getElementById('pop').style.display === 'flex') ? 'none' : 'flex'; }
    
    let currentActiveCard = null;
    let sentMessages = {}; 

    function openDM(name, el, history) { 
        currentActiveCard = el;
        document.getElementById('dmName').innerText = name; 
        document.getElementById('dm').style.display = 'flex'; 
        
        const dot = el.querySelector('.unread-dot');
        if(dot) dot.style.display = 'none';

        let content = '<div class="bubble them">' + history + '</div>';
        
        if(sentMessages[name]) {
            sentMessages[name].forEach(m => {
                content += '<div class="bubble me">' + m + '</div>';
            });
        }

        document.getElementById('chatBody').innerHTML = content;
        document.getElementById('chatBody').scrollTop = document.getElementById('chatBody').scrollHeight;
    }
    
    function closeDM() { document.getElementById('dm').style.display = 'none'; }
    
    function sendReply() {
        const input = document.getElementById('replyField');
        const message = input.value.trim();
        const friendName = document.getElementById('dmName').innerText;

        if(message !== "") {
            if(!sentMessages[friendName]) sentMessages[friendName] = [];
            sentMessages[friendName].push(message);

            const msgDiv = document.createElement('div'); 
            msgDiv.className = 'bubble me'; 
            msgDiv.innerText = message;
            document.getElementById('chatBody').appendChild(msgDiv);
            
            if(currentActiveCard) {
                currentActiveCard.querySelector('.card-msg').innerText = message;
                currentActiveCard.querySelector('.card-time').innerText = "Just now";
                document.getElementById('friendContainer').prepend(currentActiveCard);
            }
            input.value = ''; 
            document.getElementById('chatBody').scrollTop = document.getElementById('chatBody').scrollHeight;
        }
    }

    function liveSearch() {
        const val = document.getElementById('searchInp').value.toLowerCase();
        const cards = document.getElementsByClassName('friend-card');
        for (let card of cards) { card.style.display = card.innerText.toLowerCase().includes(val) ? "flex" : "none"; }
    }
</script>
</body>
</html>