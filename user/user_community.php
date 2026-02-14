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

$role = strtolower(trim($_SESSION["role"] ?? ""));
if ($role === "admin") {
  header("Location: ../admin/admin_dashboard.php");
  exit();
}

$user_id = $_SESSION["user_id"];

function h($s){ return htmlspecialchars($s ?? "", ENT_QUOTES, "UTF-8"); }

function youtube_to_embed($url) {
  $url = trim($url ?? "");
  if ($url === "") return "";
  if (strpos($url, "youtube.com/embed/") !== false) return $url;

  $parts = parse_url($url);
  if (!empty($parts["query"])) {
    parse_str($parts["query"], $q);
    if (!empty($q["v"])) return "https://www.youtube.com/embed/" . $q["v"];
  }
  if (!empty($parts["host"]) && strpos($parts["host"], "youtu.be") !== false) {
    $vid = ltrim($parts["path"] ?? "", "/");
    if ($vid !== "") return "https://www.youtube.com/embed/" . $vid;
  }
  return $url;
}

if (!isset($_SESSION["liked_posts"])) {
  $_SESSION["liked_posts"] = []; 
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["toggle_like"])) {
  $post_id  = trim($_POST["post_id"] ?? "");
  $category = trim($_POST["category"] ?? "all");
  $pagePost = (int)($_POST["page"] ?? 1);
  if ($pagePost < 1) $pagePost = 1;

  if ($post_id !== "") {
    $alreadyLiked = !empty($_SESSION["liked_posts"][$post_id]);

    if ($alreadyLiked) {
      $sqlLike = "UPDATE posts
                  SET like_count = GREATEST(like_count - 1, 0)
                  WHERE post_id = ?
                  LIMIT 1";
      $st = mysqli_prepare($conn, $sqlLike);
      mysqli_stmt_bind_param($st, "s", $post_id);
      mysqli_stmt_execute($st);
      mysqli_stmt_close($st);

      unset($_SESSION["liked_posts"][$post_id]);
    } else {
      $sqlLike = "UPDATE posts
                  SET like_count = like_count + 1
                  WHERE post_id = ?
                  LIMIT 1";
      $st = mysqli_prepare($conn, $sqlLike);
      mysqli_stmt_bind_param($st, "s", $post_id);
      mysqli_stmt_execute($st);
      mysqli_stmt_close($st);

      $_SESSION["liked_posts"][$post_id] = true;
    }
  }

  header("Location: user_community.php?category=" . urlencode($category) . "&page=" . $pagePost);
  exit();
}

$userSql = "SELECT user_id, name, email, role, eco_points, badges, created_at, account_status, last_login, profile_image
            FROM users
            WHERE user_id = ?
            LIMIT 1";
$userStmt = mysqli_prepare($conn, $userSql);
mysqli_stmt_bind_param($userStmt, "s", $user_id);
mysqli_stmt_execute($userStmt);
$userRes = mysqli_stmt_get_result($userStmt);
$user = mysqli_fetch_assoc($userRes);
mysqli_stmt_close($userStmt);

if (!$user) {
  session_unset();
  session_destroy();
  header("Location: ../login/login.php");
  exit();
}

$selectedCat = trim($_GET["category"] ?? "all");

$page = (int)($_GET["page"] ?? 1);
if ($page < 1) $page = 1;

$pageSize = 1; 
$offset   = ($page - 1) * $pageSize;

$cats = [];
$catSql = "SELECT content_category_id, content_name
           FROM content_categories
           WHERE is_active = 1
           ORDER BY content_name ASC";
$catRes = mysqli_query($conn, $catSql);
if ($catRes) {
  while ($row = mysqli_fetch_assoc($catRes)) $cats[] = $row;
}

if ($selectedCat === "all") {
  $countSql = "SELECT COUNT(*) AS total FROM posts WHERE post_status='Public'";
  $countRes = mysqli_query($conn, $countSql);
  $totalRows = (int)(mysqli_fetch_assoc($countRes)["total"] ?? 0);
} else {
  $countSql = "SELECT COUNT(*) AS total FROM posts WHERE post_status='Public' AND content_category_id=?";
  $countStmt = mysqli_prepare($conn, $countSql);
  mysqli_stmt_bind_param($countStmt, "s", $selectedCat);
  mysqli_stmt_execute($countStmt);
  $countRes = mysqli_stmt_get_result($countStmt);
  $totalRows = (int)(mysqli_fetch_assoc($countRes)["total"] ?? 0);
  mysqli_stmt_close($countStmt);
}

$totalPages = ($totalRows > 0) ? (int)ceil($totalRows / $pageSize) : 1;
if ($page > $totalPages) {
  $page = $totalPages;
  $offset = ($page - 1) * $pageSize;
}

$hasPrev = $page > 1;
$hasNext = $page < $totalPages;

$sql = "
  SELECT p.post_id, p.title, p.body, p.difficulty_level, p.post_created_at,
         p.content_category_id,
         p.like_count,
         u.user_id AS author_id,
         u.name AS author_name,
         u.profile_image AS author_profile_image
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

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Community</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">

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
      color:#fff;
      cursor:pointer;
      font-size:16px;
      display:flex; align-items:center; justify-content:center;
      font-weight:900;
    }

    .wf-icon.liked{
      background:#d61f1f !important;
    }

    .wf-blank{
      height:500px;
      background:#fff;
      border-bottom:1px solid #eee;
      position:relative;
    }

    .wf-bottom{
      padding:14px;
      background:#fff;
    }

    .wf-author{
      display:flex;
      align-items:center;
      gap:10px;
      margin-bottom:10px;
      font-size:14px;
      color:#444;
    }
    .wf-author img{
      width:34px;
      height:34px;
      border-radius:50%;
      object-fit:cover;
      border:1px solid #ddd;
      background:#fff;
    }
    .wf-author-id{ font-weight:800; color:#111; }
    .wf-author-name{ color:#555; }

    .wf-title{
      font-weight:800;
      margin-bottom:10px;
      padding-bottom:10px;
      border-bottom:6px solid #222;
    }

    .wf-body{
      white-space:pre-wrap;
      line-height:1.5;
      color:#222;
    }

    .wf-difficulty{
      font-size:13px;
      color:#555;
      padding-top:10px;
      border-top:1px solid #eee;
      margin-top:12px;
      margin-bottom:12px;
    }

    .wf-section-title{ font-weight:800; margin: 8px 0; }

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
      color:#fff;
      cursor:pointer;
      font-weight:800;
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
      font-weight:800;
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
      font-weight:800;
    }

    .wf-show-video{
      position:absolute;
      left:12px;
      bottom:12px;
      z-index:3;
      padding:10px 12px;
      border-radius:12px;
      border:1px solid #fff;
      background:rgba(0,0,0,.55);
      color:#fff;
      font-weight:800;
      cursor:pointer;
    }

    @media (max-width:768px){
      .community-container{ max-width:100%; padding:18px 14px; }
      .wf-card{ border-radius:16px; margin:16px 0; }
      .wf-bottom{ padding:16px; }
      .wf-icon{ width:54px; height:54px; font-size:20px; }
      .wf-comments{ font-size:15px; max-height:none; }
      .wf-form-row input{ font-size:16px; padding:12px 14px; }
      .wf-form-row button{ font-size:16px; padding:12px 16px; }
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
          <span> Welcome!  <?= h($_SESSION['name'] ?? 'User') ?> </span>
        </div>
      </div>

      <div class="user-top-center">
      <h1 style="color: white;">Community</h1>
      </div>

      <div class="user-top-right">
        <span class="user-top-points">🪙 <?php echo (int)$user["eco_points"]; ?> points</span>
        <a href="user_dashboard.php" class="user-top-btn" title="Home">🏠</a>
        <a class="user-top-btn" href="add_friends.php" title="Add Friend">👥</a>
        <a href="../login/logout.php" class="user-top-btn logout" title="Logout" onclick="return confirm('Logout now?');">❌</a>
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
              <?php foreach ($cats as $cat): ?>
                <option value="<?php echo h($cat["content_category_id"]); ?>"
                  <?php echo ($selectedCat === $cat["content_category_id"]) ? "selected" : ""; ?>>
                  <?php echo h($cat["content_name"]); ?>
                </option>
              <?php endforeach; ?>
            </select>
          </form>
        </div>

        <?php if (!$post): ?>
          <p>No posts found.</p>
        <?php else: ?>
          <?php
            $pid = $post["post_id"];

            $authorImg = "../images/profile.jpeg";
            $rawAuthorProfile = trim($post["author_profile_image"] ?? "");
            if ($rawAuthorProfile !== "") {
              $try  = "../" . ltrim($rawAuthorProfile, "/");
              $disk = __DIR__ . "/../" . ltrim($rawAuthorProfile, "/");
              if (file_exists($disk)) $authorImg = $try;
            }

            $firstImage = "";
            $firstVideo = "";

            $mediaSql = "SELECT media_type, file_path, video_url
                         FROM post_media
                         WHERE post_id=?
                         ORDER BY order_number ASC, media_id ASC";
            $mStmt = mysqli_prepare($conn, $mediaSql);
            mysqli_stmt_bind_param($mStmt, "s", $pid);
            mysqli_stmt_execute($mStmt);
            $mRes = mysqli_stmt_get_result($mStmt);

            while ($m = mysqli_fetch_assoc($mRes)) {
              $t = strtolower(trim($m["media_type"] ?? ""));

              if ($t === "image" && $firstImage === "") {
                $firstImage = trim($m["file_path"] ?? "");
              }
              if ($t === "video" && $firstVideo === "") {
                $firstVideo = youtube_to_embed($m["video_url"] ?? "");
              }
              if ($firstImage !== "" && $firstVideo !== "") break;
            }
            mysqli_stmt_close($mStmt);

            $imgSrc = "";
            if ($firstImage !== "" && str_starts_with($firstImage, "images/")) {
              $imgSrc = "../" . $firstImage; 
            }

            $liked = !empty($_SESSION["liked_posts"][$pid]);
            $likeCount = (int)($post["like_count"] ?? 0);
          ?>

          <div class="wf-card">

            <div class="wf-actions">

              <form method="post" style="margin:0;">
                <input type="hidden" name="toggle_like" value="1">
                <input type="hidden" name="post_id" value="<?php echo h($pid); ?>">
                <input type="hidden" name="category" value="<?php echo h($selectedCat); ?>">
                <input type="hidden" name="page" value="<?php echo (int)$page; ?>">
                <button type="submit" class="wf-icon <?php echo $liked ? 'liked' : ''; ?>" title="Like">
                  ❤ <?php echo $likeCount; ?>
                </button>
              </form>

              <button type="button" class="wf-icon" title="Comment"
                      onclick="document.getElementById('comment-input-<?php echo h($pid); ?>')
                      .focus();">
                💬
              </button>

              <button type="button" class="wf-icon" title="Report"
                      onclick="document.getElementById('report-<?php echo h($pid); ?>')
                      .focus();">
                ⚠
              </button>
            </div>

            <div class="wf-blank wf-video">

              <?php if ($imgSrc !== ""): ?>
                <img id="img-<?php echo h($pid); ?>"
                     src="<?php echo h($imgSrc); ?>"
                     style="width:100%; height:500px; object-fit:cover; display:block;"
                     alt="Post media">
              <?php endif; ?>

              <?php if ($firstVideo !== ""): ?>
                <button type="button"
                        class="wf-show-video"
                        data-post="<?php echo h($pid); ?>"
                        data-video="<?php echo h($firstVideo); ?>">
                  Show Video
                </button>

                <iframe id="video-<?php echo h($pid); ?>"
                        width="100%" height="500"
                        src=""
                        title="Video"
                        frameborder="0"
                        allowfullscreen
                        style="display:none;"></iframe>
              <?php endif; ?>

            </div>

            <div class="wf-bottom">

              <div class="wf-author">
                <span class="wf-author-id"><?php echo h($post["author_id"]); ?></span>
                <span class="wf-author-name"><?php echo h($post["author_name"]); ?></span>
              </div>

              <div class="wf-title"><?php echo h($post["title"]); ?></div>

              <?php if (trim($post["body"] ?? "") !== ""): ?>
                <div class="wf-body"><?php echo h($post["body"]); ?></div>
              <?php endif; ?>

              <div class="wf-difficulty">
                Difficulty level : <?php echo h($post["difficulty_level"]); ?>
              </div>

              <?php
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

              <div class="wf-section-title" id="comment-<?php echo h($pid); ?>">Comments</div>
              <div class="wf-comments">
                <?php if (mysqli_num_rows($cres) == 0): ?>
                  <div class="wf-comment-item">No comments yet.</div>
                <?php else: ?>
                  <?php while ($c = mysqli_fetch_assoc($cres)): ?>
                    <div class="wf-comment-item">
                      <b><?php echo h($c["name"]); ?>:</b>
                      <?php echo h($c["comment_text"]); ?>
                    </div>
                  <?php endwhile; ?>
                <?php endif; ?>
              </div>

              <?php mysqli_stmt_close($cstmt); ?>

              <form class="wf-form-row" method="post" action="comment_add.php">
                <input type="hidden" name="post_id" value="<?php echo h($pid); ?>">
                <input type="hidden" name="category" value="<?php echo h($selectedCat); ?>">
                <input type="hidden" name="page" value="<?php echo (int)$page; ?>">
                <input id="comment-input-<?php echo h($pid); ?>" type="text" name="comment" placeholder="add a comment..." required>
                <button type="submit">Send</button>
              </form>

              <form class="wf-form-row" method="post" action="report_add.php">
                <input type="hidden" name="post_id" value="<?php echo h($pid); ?>">
                <input type="hidden" name="category" value="<?php echo h($selectedCat); ?>">
                <input type="hidden" name="page" value="<?php echo (int)$page; ?>">
                <input id="report-<?php echo h($pid); ?>" type="text" name="reason" placeholder="Report reason..." required>
                <button type="submit">Report</button>
              </form>

            </div>
          </div>

          <div class="community-pager">
            <?php $catParam = urlencode($selectedCat); ?>

            <?php if ($hasPrev): ?>
              <a class="pager-btn" href="?category=<?php echo $catParam; ?>&page=<?php echo $page-1; ?>">back</a>
            <?php else: ?>
              <span class="pager-btn disabled">back</span>
            <?php endif; ?>

            <span class="pager-info">Page <?php echo (int)$page; ?> / <?php echo (int)$totalPages; ?></span>

            <?php if ($hasNext): ?>
              <a class="pager-btn" href="?category=<?php echo $catParam; ?>&page=<?php echo $page+1; ?>">Next</a>
            <?php else: ?>
              <span class="pager-btn disabled">Next</span>
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

<script>
  document.addEventListener("click", function(e){
    const btn = e.target.closest(".wf-show-video");
    if (!btn) return;

    const pid = btn.getAttribute("data-post");
    const videoUrl = btn.getAttribute("data-video");

    const img = document.getElementById("img-" + pid);
    const iframe = document.getElementById("video-" + pid);

    if (!iframe) return;

    const isShowing = iframe.style.display !== "none";

    if (isShowing) {
      iframe.style.display = "none";
      iframe.src = "";
      if (img) img.style.display = "block";
      btn.textContent = "Show Video";
    } else {
      if (img) img.style.display = "none";
      iframe.style.display = "block";
      iframe.src = videoUrl;
      btn.textContent = "Show Photo";
    }
  });
</script>
</body>
</html>
