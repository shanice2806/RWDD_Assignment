<?php
session_start();
require_once "../connect.php";

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (!isset($_SESSION["user_id"])) {
  header("Location: ../login/login.php");
  exit();
}

$user_id = $_SESSION["user_id"];

/* =========================
   1) 取当前登录用户资料（用于 topbar + sidebar）
========================= */
$userSql = "SELECT name, eco_points, profile_image FROM users WHERE user_id = ? LIMIT 1";
$uStmt = mysqli_prepare($conn, $userSql);
mysqli_stmt_bind_param($uStmt, "s", $user_id);
mysqli_stmt_execute($uStmt);
$uRes  = mysqli_stmt_get_result($uStmt);
$user  = mysqli_fetch_assoc($uRes) ?: ["name" => "User", "eco_points" => 0, "profile_image" => ""];
mysqli_stmt_close($uStmt);

$profileImg = trim($user["profile_image"] ?? "");
if ($profileImg === "") $profileImg = "../assets/default-avatar.png";

/* =========================
   2) 分类筛选 + 分页
========================= */
$selectedCat = trim($_GET["category"] ?? "all");

$page = (int)($_GET["page"] ?? 1);
if ($page < 1) $page = 1;

$pageSize = 1; // 你原本就是 1（一次一篇），我保留
$offset   = ($page - 1) * $pageSize;

/* 分类下拉 */
$catSql = "SELECT content_category_id, content_name
           FROM content_categories
           WHERE is_active = 1
           ORDER BY content_name ASC";
$catRes = mysqli_query($conn, $catSql);

/* =========================
   3) 先算总数（为了分页）
========================= */
$countSql = "SELECT COUNT(*) AS total FROM posts p WHERE p.post_status = 'Public'";
if ($selectedCat !== "all") $countSql .= " AND p.content_category_id = ?";

if ($selectedCat !== "all") {
  $countStmt = mysqli_prepare($conn, $countSql);
  mysqli_stmt_bind_param($countStmt, "s", $selectedCat);
  mysqli_stmt_execute($countStmt);
  $countRes = mysqli_stmt_get_result($countStmt);
  $totalRows = (int)(mysqli_fetch_assoc($countRes)["total"] ?? 0);
  mysqli_stmt_close($countStmt);
} else {
  $countRes = mysqli_query($conn, $countSql);
  $totalRows = (int)(mysqli_fetch_assoc($countRes)["total"] ?? 0);
}

$totalPages = ($totalRows > 0) ? (int)ceil($totalRows / $pageSize) : 1;
if ($page > $totalPages) {
  $page = $totalPages;
  $offset = ($page - 1) * $pageSize;
}

$hasPrev = $page > 1;
$hasNext = $page < $totalPages;

/* =========================
   4) 取当前页的 post（Public）
========================= */
$sql = "
  SELECT p.post_id, p.title, p.body, p.difficulty_level, p.post_created_at,
         p.content_category_id,
         u.name AS author_name
  FROM posts p
  JOIN users u ON u.user_id = p.user_id
  WHERE p.post_status = 'Public'
";

if ($selectedCat !== "all") $sql .= " AND p.content_category_id = ? ";
$sql .= " ORDER BY p.post_created_at DESC LIMIT ? OFFSET ? ";

$pStmt = mysqli_prepare($conn, $sql);

if ($selectedCat !== "all") {
  mysqli_stmt_bind_param($pStmt, "sii", $selectedCat, $pageSize, $offset);
} else {
  mysqli_stmt_bind_param($pStmt, "ii", $pageSize, $offset);
}

mysqli_stmt_execute($pStmt);
$pRes = mysqli_stmt_get_result($pStmt);
$post = mysqli_fetch_assoc($pRes);
mysqli_stmt_close($pStmt);

/* =========================
   5) 小工具：把 youtube watch 转成 embed（给 iframe 用）
========================= */
function youtube_to_embed($url) {
  $url = trim($url ?? "");
  if ($url === "") return "";

  // 已经是 embed
  if (strpos($url, "youtube.com/embed/") !== false) return $url;

  // watch?v=xxxx
  $parts = parse_url($url);
  if (!empty($parts["query"])) {
    parse_str($parts["query"], $q);
    if (!empty($q["v"])) {
      return "https://www.youtube.com/embed/" . $q["v"];
    }
  }

  if (!empty($parts["host"]) && strpos($parts["host"], "youtu.be") !== false) {
    $vid = ltrim($parts["path"] ?? "", "/");
    if ($vid !== "") return "https://www.youtube.com/embed/" . $vid;
  }

  return $url; 
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Community</title>

  <link rel="stylesheet" href="../css/style.css">
  <link rel="stylesheet" href="../css/user.css">

  <style>
    .community-wrap{
      background:#f5f7f8;
      min-height: 88vh;
    }
    .community-container{
      max-width:700px;
      margin:0 auto;
      padding:16px;
    }

    .community-tools{
      display:flex;
      align-items:center;
      justify-content:space-between;
      gap:12px;
      margin-bottom:10px;
    }

    .community-add{
      width:44px; height:44px;
      border-radius:10px;
      background:#111; color:#fff;
      text-decoration:none;
      display:flex; align-items:center;
      justify-content:center;
      font-size:26px;
    }
    .community-filter select{
      padding:10px 12px;
      border-radius:10px;
      border:1px solid #ccc;
      background:#fff;
      font-size:15px;
    }

    .wf-card{
      position:relative;
      background:#fff;
      border:1px solid #ddd;
      border-radius:14px;
      overflow:hidden;
      margin:18px 0;
    }

    .wf-actions{
      position:absolute;
      right:12px;
      top:300px;
      display:flex;
      flex-direction:column;
      gap:10px;
      z-index:2;
    }
    .wf-icon{
      width:55px; height:55px;
      border-radius:10px;
      border:1px solid #ffffff;
      background:#3f7a54;
      cursor:pointer;
      font-size:16px;
      display:flex; align-items:center; justify-content:center;
    }
    .wf-icon.liked{
      color:#b23a3a;
      border-color:#f0c7c7;
      background:#fff5f5;
    }

    .wf-blank{
      height:500px;
      background:#fff;
      border-bottom:1px solid #eee;
    }

    .wf-bottom{
      padding:14px;
      background:#fff;
    }
    .wf-author{
      display:flex; align-items:center; gap:8px;
      font-size:14px; color:#444;
      margin-bottom:6px;
    }
    .wf-usericon{
      width:24px; height:24px;
      border:1px solid #ddd;
      border-radius:50%;
      display:flex; align-items:center; justify-content:center;
      font-size:12px;
    }
    .wf-title{
      font-weight:700;
      margin-bottom:10px;
      padding-bottom:10px;
      border-bottom:6px solid #222;
    }
    .wf-difficulty{
      font-size:13px;
      color:#555;
      padding-top:8px;
      border-top:1px solid #eee;
      margin-bottom:12px;
    }

    .wf-section-title{ font-weight:700; margin: 6px 0; }
    .wf-comments{
      background:#fafafa;
      border:1px dashed #ddd;
      border-radius:10px;
      padding:10px;
      max-height:140px;
      overflow:auto;
      font-size:13px;
    }
    .wf-comment-item{ margin:6px 0; }

    .wf-form-row{
      display:flex;
      gap:10px;
      margin-top:10px;
    }
    .wf-form-row input{
      flex:1;
      padding:10px 12px;
      border:1px solid #3b6d4f;
      border-radius:10px;
      box-sizing:border-box;
    }
    .wf-form-row button{
      padding:10px 14px;
      border-radius:10px;
      border:1px solid #f5f5f5;
      background:#2d7b2e;
      cursor:pointer;
      font-weight:700;
    }

    .community-pager{
      display:flex;
      align-items:center;
      justify-content:space-between;
      gap:12px;
      margin:16px 0 6px;
    }
    .pager-btn{
      display:inline-flex;
      align-items:center;
      justify-content:center;
      padding:10px 14px;
      border-radius:12px;
      border:1px solid #ddd;
      background:#fff;
      text-decoration:none;
      font-weight:700;
      color:#111;
      min-width:110px;
    }
    .pager-btn.disabled{
      opacity:0.45;
      pointer-events:none;
    }
    .pager-info{
      font-size:14px;
      color:#555;
      font-weight:700;
    }

    @media (max-width:768px){
      .community-container{ max-width:100%; padding:18px 14px; }

      .wf-card{ border-radius:16px; margin:16px 0; }
      .wf-bottom{ padding:16px; }

      .wf-icon{ width:54px; height:54px; font-size:20px; }

      .wf-comments{ font-size:15px; max-height:none; }
      .wf-form-row input{
        font-size:16px;
        padding:12px 14px;
      }
      .wf-form-row button{
        font-size:16px;
        padding:12px 16px;
      }

      .pager-info{ font-size:15px; }
      .pager-btn{ font-size:16px; padding:12px 16px; min-width:120px; }
    }
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
          <span class="user-top-name">Welcome! <?= htmlspecialchars($_SESSION['name'] ?? 'User') ?></span>
        </div>
      </div>

      <div class="user-top-center">
        <h1 style="color:white;">Community</h1>
      </div>

      <div class="user-top-right">
        <span class="user-top-points">🪙 <?php echo (int)$user["eco_points"]; ?> points</span>
        <a href="user_dashboard.php" class="user-top-btn">🏠</a>
        <a href="friends_add.php" class="user-top-btn">👥</a>
        <a href="../login/logout.php" class="user-top-btn logout">❌</a>
      </div>
    </div>

    <div class="community-wrap">
      <div class="community-container">

        <div class="community-tools">
          <a class="community-add" href="user_tutorial_add.php" title="Add Post">+</a>

          <form method="get" class="community-filter">
            <input type="hidden" name="page" value="1">
            <select name="category" onchange="this.form.submit()">
              <option value="all" <?php echo ($selectedCat==="all") ? "selected" : ""; ?>>All Category</option>
              <?php if ($catRes): ?>
                <?php while ($cat = mysqli_fetch_assoc($catRes)): ?>
                  <option value="<?php echo htmlspecialchars($cat["content_category_id"]); ?>"
                    <?php echo ($selectedCat === $cat["content_category_id"]) ? "selected" : ""; ?>>
                    <?php echo htmlspecialchars($cat["content_name"]); ?>
                  </option>
                <?php endwhile; ?>
              <?php endif; ?>
            </select>
          </form>
        </div>

        <?php if (!$post): ?>
          <p>No posts found.</p>
        <?php else: ?>
          <?php
            $pid = $post["post_id"];

            /* ====== 取该 post 的媒体（优先 video，其次 image）====== */
            $mediaSql = "SELECT media_type, file_path, video_url
                         FROM post_media
                         WHERE post_id = ?
                         ORDER BY order_number ASC";
            $mStmt = mysqli_prepare($conn, $mediaSql);
            mysqli_stmt_bind_param($mStmt, "s", $pid);
            mysqli_stmt_execute($mStmt);
            $mRes = mysqli_stmt_get_result($mStmt);

            $firstVideo = "";
            $firstImage = "";

            while ($m = mysqli_fetch_assoc($mRes)) {
              if ($m["media_type"] === "video" && $firstVideo === "") $firstVideo = youtube_to_embed($m["video_url"]);
              if ($m["media_type"] === "image" && $firstImage === "") $firstImage = $m["file_path"];
            }
            mysqli_stmt_close($mStmt);
          ?>

          <div class="wf-card">

            <div class="wf-actions">
              <button type="button" class="wf-icon" title="Like"
                      onclick="this.classList.toggle('liked')">♡</button>

              <button type="button" class="wf-icon" title="Comment"
                      onclick="document.getElementById('comment-<?php echo htmlspecialchars($pid); ?>')
                      .scrollIntoView({behavior:'smooth'});">
                💬
              </button>

              <button type="button" class="wf-icon" title="Report"
                      onclick="document.getElementById('report-<?php echo htmlspecialchars($pid); ?>').focus();">
                ⚠
              </button>
            </div>

            <div class="wf-blank wf-video">
              <?php if ($firstVideo !== ""): ?>
                <iframe width="100%" height="500"
                  src="<?php echo htmlspecialchars($firstVideo); ?>"
                  title="Video" frameborder="0" allowfullscreen></iframe>

              <?php elseif ($firstImage !== ""): ?>
                <img src="<?php echo htmlspecialchars($firstImage); ?>"
                     style="width:100%; height:500px; object-fit:cover; display:block;"
                     alt="Post media">

              <?php else: ?>
                <!-- no media -->
              <?php endif; ?>
            </div>

            <div class="wf-bottom">
              <div class="wf-author">
                <span class="wf-usericon">👤</span>
                <span><?php echo htmlspecialchars($post["author_name"]); ?></span>
              </div>

              <div class="wf-title"><?php echo htmlspecialchars($post["title"]); ?></div>

              <div class="wf-difficulty">
                Difficulty level : <?php echo htmlspecialchars($post["difficulty_level"]); ?>
              </div>

              <?php if (trim($post["body"] ?? "") !== ""): ?>
                <div class="body"><?php echo htmlspecialchars($post["body"]); ?></div>
              <?php endif; ?>

              <?php
                /* ====== 取评论 ====== */
                $csql = "
                  SELECT c.comment_text, u.name
                  FROM community_comments c
                  JOIN users u ON u.user_id = c.user_id
                  WHERE c.post_id = ?
                  ORDER BY c.date_posted ASC
                ";
                $cstmt = mysqli_prepare($conn, $csql);
                mysqli_stmt_bind_param($cstmt, "s", $pid);
                mysqli_stmt_execute($cstmt);
                $cres = mysqli_stmt_get_result($cstmt);
              ?>

              <div class="wf-section-title" id="comment-<?php echo htmlspecialchars($pid); ?>">Comments</div>
              <div class="wf-comments">
                <?php if (mysqli_num_rows($cres) == 0): ?>
                  <div class="wf-comment-item">No comments yet.</div>
                <?php else: ?>
                  <?php while ($c = mysqli_fetch_assoc($cres)): ?>
                    <div class="wf-comment-item">
                      <b><?php echo htmlspecialchars($c["name"]); ?>:</b>
                      <?php echo htmlspecialchars($c["comment_text"]); ?>
                    </div>
                  <?php endwhile; ?>
                <?php endif; ?>
              </div>

              <?php mysqli_stmt_close($cstmt); ?>

              <!-- comment 表单：加回 category/page，这样提交后能回到当前页 -->
              <form class="wf-form-row" method="post" action="comment_add.php">
                <input type="hidden" name="post_id" value="<?php echo htmlspecialchars($pid); ?>">
                <input type="hidden" name="category" value="<?php echo htmlspecialchars($selectedCat); ?>">
                <input type="hidden" name="page" value="<?php echo (int)$page; ?>">
                <input type="text" name="comment" placeholder="add a comment..." required>
                <button type="submit">Send</button>
              </form>

              <!-- report 表单：加回 category/page -->
              <form class="wf-form-row" method="post" action="report_add.php">
                <input type="hidden" name="post_id" value="<?php echo htmlspecialchars($pid); ?>">
                <input type="hidden" name="category" value="<?php echo htmlspecialchars($selectedCat); ?>">
                <input type="hidden" name="page" value="<?php echo (int)$page; ?>">
                <input id="report-<?php echo htmlspecialchars($pid); ?>" type="text" name="reason" placeholder="Report reason..." required>
                <button type="submit">Report</button>
              </form>

            </div>
          </div>

          <div class="community-pager">
            <?php $catParam = urlencode($selectedCat); ?>

            <?php if ($hasPrev): ?>
              <a class="pager-btn" href="?category=<?php echo $catParam; ?>&page=<?php echo $page-1; ?>">◀</a>
            <?php else: ?>
              <span class="pager-btn disabled">◀</span>
            <?php endif; ?>

            <span class="pager-info">Page <?php echo (int)$page; ?> / <?php echo (int)$totalPages; ?></span>

            <?php if ($hasNext): ?>
              <a class="pager-btn" href="?category=<?php echo $catParam; ?>&page=<?php echo $page+1; ?>">Next ➡️</a>
            <?php else: ?>
              <span class="pager-btn disabled">Next ➡️</span>
            <?php endif; ?>
          </div>

        <?php endif; ?>

      </div>
    </div>

    <footer>
      <p>&copy; 2026 ReLife Hub</p>
    </footer>

  </div>
</div>

<script src="../user/user.js"></script>
</body>
</html>
