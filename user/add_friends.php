<?php
session_start();
require_once "../connect.php"; // Using your required connection

// 1. Session and Role Security
if (!isset($_SESSION["user_id"])) {
    header("Location: ../login/login.php");
    exit();
}

$role = strtolower(trim($_SESSION["role"] ?? ""));
if ($role === "admin") {
    header("Location: ../admin/admin_dashboard.php");
    exit();
}

$user_id = $_SESSION["user_id"];

// 2. Fetch User Data (Eco-points etc. for the top bar)
$userSql = "SELECT user_id, name, eco_points FROM users WHERE user_id = ? LIMIT 1";
$userStmt = mysqli_prepare($conn, $userSql);
mysqli_stmt_bind_param($userStmt, "s", $user_id);
mysqli_stmt_execute($userStmt);
$userRes = mysqli_stmt_get_result($userStmt);
$user = mysqli_fetch_assoc($userRes);
mysqli_stmt_close($userStmt);

// 3. Original Chat Logic Data (Keeping your original arrays)
$friends = [
    ['name' => 'Max', 'msg' => 'Assignment almost done!', 'points' => '🏆', 'time' => '1h', 'unread' => true, 'history' => 'Hi Melissa! How is the assignment going?'],
    ['name' => 'candy', 'msg' => 'Thanks for the help!', 'points' => '🏆', 'time' => 'Yesterday', 'unread' => true, 'history' => 'Hey! Thanks again for helping me with the recycle task.'],
    ['name' => 'Peter', 'msg' => 'Hey, how are you?', 'points' => '🏆', 'time' => '2m', 'unread' => false, 'history' => 'Hello! Are you coming to the hub later?'],
    ['name' => 'dennis', 'msg' => 'See you tomorrow!', 'points' => '🏆', 'time' => '3h', 'unread' => false, 'history' => 'Got the files. See you tomorrow at college!'],
    ['name' => 'cavis', 'msg' => 'I sent the files.', 'points' => '🏆', 'time' => 'Jan 20', 'unread' => false, 'history' => 'Check your email, I just sent the documents over.'],
    ['name' => 'robin', 'msg' => 'Good job!', 'points' => '🏆', 'time' => 'Jan 19', 'unread' => false, 'history' => 'That was a great job on the community project.'],
    ['name' => 'szi ji pi', 'msg' => 'Check your points.', 'points' => '🏆', 'time' => 'Jan 18', 'unread' => false, 'history' => 'You earned 50 points today. Check it out!']
];

$requests = ['Roben', 'Dennis', 'Raymond', 'Mun'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat | ReLife Hub</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/user.css">
    <style>
        /* Keeping your original Chat & Pop-up styles */
        :root { --color-primary: #1e3a34; --color-secondary: #3ba99c; }
        .main-app { max-width: 1200px; margin: 20px auto; background: white; height: 80vh; position: relative; display: flex; flex-direction: column; border: 1px solid #ddd; border-radius: 12px; overflow: hidden; }
        .chat-title-row { padding: 20px 40px; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #eee; }
        #friendContainer { flex: 1; overflow-y: auto; padding: 0 40px; display: flex; flex-direction: column; }
        .friend-card { display: flex; align-items: center; padding: 15px; cursor: pointer; border-bottom: 1px solid #f2f2f2; order: 1; transition: background 0.2s; }
        .friend-card:hover { background: #f9f9f9; }
        .avatar { width: 50px; height: 50px; background: #333; border-radius: 50%; margin-right: 20px; display: flex; align-items: center; justify-content: center; color: white; position: relative; }
        .unread-dot { width: 12px; height: 12px; background: #ff4d4d; border-radius: 50%; position: absolute; right: 0; top: 0; border: 2px solid white; display: none; }
        .is-unread .unread-dot { display: block; }
        .is-unread { order: 0 !important; }
        
        /* Popup & DM Screen Styles */
        .overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.6); display: none; justify-content: center; align-items: center; z-index: 2500; }
        .popup { background: white; width: 850px; height: 500px; display: grid; grid-template-columns: 1fr 1fr; border-radius: 12px; position: relative; overflow: hidden; }
        .pop-sec { padding: 40px; display: flex; flex-direction: column; }
        .dm-screen { position: absolute; inset: 0; background: white; z-index: 1000; display: none; flex-direction: column; }
        .dm-user-bar { padding: 10px 40px; border-bottom: 1px solid #eee; display: flex; align-items: center; gap: 15px; }
        .dm-body { flex: 1; padding: 40px; overflow-y: auto; display: flex; flex-direction: column; gap: 10px; background: #fafafa; }
        .bubble { padding: 12px 18px; border-radius: 20px; max-width: 60%; }
        .bubble.them { background: #eee; align-self: flex-start; }
        .bubble.me { background: var(--color-primary); color: white; align-self: flex-end; }
        .dm-footer { padding: 20px 40px; border-top: 1px solid #eee; display: flex; gap: 10px; }
        .input-field { width: 100%; padding: 14px; background: #f0f0f0; border: 1px solid #ccc; border-radius: 4px; }
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
                    <span> Welcome! <?= htmlspecialchars($_SESSION['name'] ?? 'User') ?> </span>
                </div>
            </div>

            <div class="user-top-center">
                <input class="user-top-search" type="text" id="searchInp" placeholder="Search friends..." onkeyup="liveSearch()">
                <button class="user-top-search-btn" onclick="liveSearch()">Search</button>
            </div>

            <div class="user-top-right">
                <span class="user-top-points">
                    🪙 <?php echo (int)$user["eco_points"]; ?> points
                </span>
                <a href="user_dashboard.php" class="user-top-btn" title="Home">🏠</a>
                <a class="user-top-btn" href="javascript:void(0)" onclick="togglePop()" title="Add Friend">👥</a>
                <a href="../login/logout.php" class="user-top-btn logout" title="Logout">❌</a>
            </div>
        </div>

        <div class="main-app">
            <div class="chat-title-row">
                <h2>Chat</h2>
                <div style="display:flex; align-items:center; gap:20px;">
                    <span onclick="togglePop()" style="cursor:pointer; font-size:28px; color: var(--color-primary);">👤⁺</span>
                    <strong>🏆 User point</strong>
                </div>
            </div>

            <div class="friend-list" id="friendContainer">
                <?php foreach ($friends as $f): ?>
                <div class="friend-card <?= $f['unread'] ? 'is-unread' : '' ?>" 
                     onclick="openDM('<?= $f['name'] ?>', this, '<?= addslashes($f['history']) ?>')">
                    <div class="avatar">👤<div class="unread-dot"></div></div>
                    <div style="flex:1;">
                        <strong><?= $f['name'] ?> <?= $f['points'] ?></strong><br>
                        <small class="msg-text" style="color:#777;"><?= $f['msg'] ?></small>
                    </div>
                    <div class="time-text" style="color:#ccc; font-size:13px;"><?= $f['time'] ?></div>
                </div>
                <?php endforeach; ?>
            </div>

            <div class="dm-screen" id="dm">
                <div class="dm-user-bar">
                    <div style="cursor:pointer; font-size:26px;" onclick="closeDM()">⬅</div>
                    <div class="avatar" style="width:40px; height:40px; margin:0;">👤</div>
                    <strong id="dmName" style="font-size:20px;">Peter</strong>
                    <div style="margin-left:auto; display:flex; gap:20px; color:#666; font-size: 20px;">📞 📹 ⓘ</div>
                </div>
                <div class="dm-body" id="chatBody"></div>
                <div class="dm-footer">
                    <input type="text" id="msg" placeholder="Type a message..." style="flex:1; padding:15px; border-radius:30px; border:1px solid #ddd; outline:none;" onkeypress="if(event.key==='Enter')send()">
                    <button onclick="send()" style="background:var(--color-primary); color:white; border:none; padding:0 30px; border-radius:30px; cursor:pointer; font-weight: bold;">Send</button>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="overlay" id="pop">
    <div class="popup">
        <div style="position:absolute; right:20px; top:15px; cursor:pointer; font-size: 24px;" onclick="togglePop()">✖</div>
        <div class="pop-sec" style="border-right: 2px solid #f0f0f0;">
            <div style="color:var(--color-primary); font-size:22px; margin-bottom:20px; border-bottom:2px solid #3ba99c;">Friend Request</div>
            <div style="overflow-y:auto;">
                <?php foreach ($requests as $r): ?>
                <div id="row-<?= $r ?>" style="display:flex; justify-content:space-between; margin-bottom:18px; align-items:center; background: #f9f9f9; padding: 10px; border-radius: 8px;">
                    <span style="font-weight: bold;"><?= $r ?></span>
                    <div style="display: flex; gap: 10px;">
                        <button style="background:var(--color-primary); color:white; border:none; padding:8px 15px; border-radius:5px; cursor:pointer;" onclick="alert('Added!')">Add</button>
                        <button style="border:2px solid #ff4d4d; color: #ff4d4d; border-radius:50%; width:32px; height:32px; background:none; cursor:pointer;" onclick="decline('<?= $r ?>')">X</button>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="pop-sec">
            <div style="color:var(--color-primary); font-size:22px; margin-bottom:20px; border-bottom:2px solid #3ba99c;">Add friend</div>
            <input type="text" id="tpInput" class="input-field" placeholder="Example: TP083586">
            <button style="width:100%; padding:15px; background: #444; color:white; border:none; cursor:pointer; margin-top:20px; border-radius:5px; font-weight: bold;" onclick="alert('Sent!')">Send Request</button>
        </div>
    </div>
</div>

<script src="../user/user.js"></script>
<script>
    let activeCard = null;

    function liveSearch() {
        const val = document.getElementById('searchInp').value.toLowerCase();
        const cards = document.getElementsByClassName('friend-card');
        for (let card of cards) {
            card.style.display = card.innerText.toLowerCase().includes(val) ? "flex" : "none";
        }
    }

    function togglePop() {
        const p = document.getElementById('pop');
        p.style.display = (p.style.display === 'flex') ? 'none' : 'flex';
    }

    function decline(id) { document.getElementById('row-'+id).style.display = 'none'; }

    function openDM(n, el, history) { 
        document.getElementById('dmName').innerText = n; 
        document.getElementById('dm').style.display = 'flex'; 
        activeCard = el;
        el.classList.remove('is-unread');
        document.getElementById('chatBody').innerHTML = '<div class="bubble them">' + history + '</div>';
    }

    function closeDM() { document.getElementById('dm').style.display = 'none'; }

    function send() {
        const input = document.getElementById('msg');
        if(input.value.trim() !== "") {
            const d = document.createElement('div'); 
            d.className='bubble me'; 
            d.innerText=input.value;
            document.getElementById('chatBody').appendChild(d); 
            
            if(activeCard) {
                activeCard.querySelector('.msg-text').innerText = input.value;
                activeCard.querySelector('.time-text').innerText = '1s';
                activeCard.style.order = "-1";
            }

            input.value='';
            const body = document.getElementById('chatBody');
            body.scrollTop = body.scrollHeight;
        }
    }
</script>
</body>
</html>