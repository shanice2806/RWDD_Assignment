<?php
session_start();

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
    <title>Relife Hub Chat</title>
    <style>
        :root {
            --color-primary: #1e3a34; 
            --color-secondary: #3ba99c; 
            --color-menu-bg: #4fb9af; 
            --color-bg: #ffffff;
        }

        body { font-family: "Times New Roman", Times, serif; margin: 0; padding: 0; background: #f4f4f4; }

        .admin-header {
            background-color: var(--color-primary); color: white; padding: 10px 40px;
            display: flex; justify-content: space-between; align-items: center; height: 60px;
        }
        .header-left { display: flex; align-items: center; gap: 15px; }
        .menu-icon-box { background-color: var(--color-menu-bg); padding: 5px 10px; border-radius: 4px; cursor: pointer; }

        .search-area { display: flex; align-items: center; gap: 10px; }
        .search-bar { width: 350px; padding: 10px; border: none; border-radius: 4px; outline: none; }
        .search-btn { background: var(--color-secondary); border: none; padding: 10px 15px; color: white; cursor: pointer; border-radius: 4px; }

        .main-app {
            max-width: 1200px; margin: 0 auto; background: white; height: 85vh;
            position: relative; display: flex; flex-direction: column; border: 1px solid #ddd;
        }

        .chat-title-row { padding: 20px 40px; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #eee; }

        #friendContainer { flex: 1; overflow-y: auto; padding: 0 40px; display: flex; flex-direction: column; }
        .friend-card {
            display: flex; align-items: center; padding: 15px; cursor: pointer; 
            border-bottom: 1px solid #f2f2f2; order: 1; transition: background 0.2s;
        }
        .friend-card:hover { background: #f9f9f9; }
        .avatar { width: 50px; height: 50px; background: #333; border-radius: 50%; margin-right: 20px; display: flex; align-items: center; justify-content: center; color: white; position: relative; }
        
        .unread-dot { width: 12px; height: 12px; background: #ff4d4d; border-radius: 50%; position: absolute; right: 0; top: 0; border: 2px solid white; display: none; }
        .is-unread .unread-dot { display: block; }
        .is-unread { order: 0 !important; }

        .sidebar-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.5); display: none; z-index: 2000; }
        .sidebar { width: 300px; height: 100%; background: #d3d3d3; padding: 30px 20px; box-sizing: border-box; transform: translateX(-100%); transition: 0.3s; }
        .sidebar.active { transform: translateX(0); }
        .sidebar-item { padding: 15px 0; border-bottom: 1px solid #bbb; cursor: pointer; text-decoration: none; color: black; display: block; }
        .sidebar-header { font-weight: bold; margin-top: 20px; color: #555; font-size: 14px; text-transform: uppercase; }

        .overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.6); display: none; justify-content: center; align-items: center; z-index: 1500; }
        .popup { background: white; width: 850px; height: 500px; display: grid; grid-template-columns: 1fr 1fr; border-radius: 12px; position: relative; overflow: hidden; border: 2px solid var(--color-primary); }
        .pop-sec { padding: 40px; display: flex; flex-direction: column; }
        .pop-title { color: var(--color-primary); border-bottom: 2px solid var(--color-secondary); padding-bottom: 10px; margin-bottom: 20px; font-size: 22px; }
        .input-field { width: 100%; padding: 14px; background: #f0f0f0; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; font-size: 16px; }

        .dm-screen { position: absolute; inset: 0; background: white; z-index: 1000; display: none; flex-direction: column; }
        .dm-top-bar { background: var(--color-primary); color: white; padding: 10px 40px; display: flex; align-items: center; gap: 15px; }
        .dm-user-bar { padding: 10px 40px; border-bottom: 1px solid #eee; display: flex; align-items: center; gap: 15px; }
        .dm-body { flex: 1; padding: 40px; overflow-y: auto; display: flex; flex-direction: column; gap: 10px; background: #fafafa; }
        .bubble { padding: 12px 18px; border-radius: 20px; max-width: 60%; }
        .bubble.them { background: #eee; align-self: flex-start; }
        .bubble.me { background: var(--color-primary); color: white; align-self: flex-end; }
        .dm-footer { padding: 20px 40px; border-top: 1px solid #eee; display: flex; gap: 10px; }
    </style>
</head>
<body>

<div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()">
    <div class="sidebar" id="sidebar" onclick="event.stopPropagation()">
        <div style="text-align: right; cursor: pointer; font-size: 24px;" onclick="toggleSidebar()">⬅</div>
        <h3 style="margin-top:0; color: var(--color-primary);">Menu</h3>
        <a href="#" class="sidebar-item">💬 Chat</a>
        <a href="#" class="sidebar-item">📖 Tutorial</a>
        <a href="#" class="sidebar-item">👥 Community</a>
        <a href="#" class="sidebar-item">🏆 Reward</a>
        <div class="sidebar-header">Dashboard</div>
        <a href="#" class="sidebar-item">📋 Ranking</a>
        <div class="sidebar-header">Account</div>
        <a href="user_profile.php" class="sidebar-item">👤 Profile</a>
    </div>
</div>

<div class="admin-header">
    <div class="header-left">
        <div class="menu-icon-box" onclick="toggleSidebar()">☰</div>
        <span>Welcome Melissa!</span>
    </div>
    <div class="search-area">
        <a href="add_friends.php" style="color:white; text-decoration:none; font-size:22px; margin-right:5px;">⬅</a>
        <input type="text" class="search-bar" id="searchInp" placeholder="Search friends..." onkeyup="liveSearch()">
        <button class="search-btn" onclick="liveSearch()">🔍</button>
    </div>
    <div class="header-right">🏠 ⚙️</div>
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

    <div class="overlay" id="pop">
        <div class="popup">
            <div style="position:absolute; right:20px; top:15px; cursor:pointer; font-size: 24px;" onclick="togglePop()">✖</div>
            <div class="pop-sec" style="border-right: 2px solid #f0f0f0;">
                <div class="pop-title">Friend Request</div>
                <div class="pop-content-area" style="overflow-y:auto;">
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
                <div class="pop-title">Add friend</div>
                <div class="pop-content-area">
                    <input type="text" id="tpInput" class="input-field" placeholder="Example: TP083586">
                    <button style="width:100%; padding:15px; background: #444; color:white; border:none; cursor:pointer; margin-top:20px; border-radius:5px; font-weight: bold;" onclick="alert('Sent!')">Send Request</button>
                </div>
            </div>
        </div>
    </div>

    <div class="dm-screen" id="dm">
        <div class="dm-top-bar">
            <div class="menu-icon-box" onclick="toggleSidebar()">☰</div>
            <span>Welcome Melissa!</span>
        </div>
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

<script>
    let activeCard = null;

    function toggleSidebar() {
        const overlay = document.getElementById('sidebarOverlay');
        const bar = document.getElementById('sidebar');
        if(overlay.style.display === 'block') {
            bar.classList.remove('active');
            setTimeout(() => overlay.style.display = 'none', 300);
        } else {
            overlay.style.display = 'block';
            setTimeout(() => bar.classList.add('active'), 10);
        }
    }

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

        // Load history only if chat body is currently empty or switching users
        const chatBody = document.getElementById('chatBody');
        chatBody.innerHTML = '<div class="bubble them">' + history + '</div>';
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
                const all = document.querySelectorAll('.friend-card');
                all.forEach(c => { if(c !== activeCard && c.style.order === "-1") c.style.order = "1"; });
            }

            input.value='';
            const body = document.getElementById('chatBody');
            body.scrollTop = body.scrollHeight;
        }
    }
</script>
</body>
</html>