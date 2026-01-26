<?php
session_start();
require_once "../connect.php";

$page_title = "Add Content Category";
include "admin_header.php";

/* =====================
   HANDLE FORM SUBMIT
   ===================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $content_name = trim($_POST['content_name']);
    $content_description = trim($_POST['content_description']);

    if ($content_name !== '') {

        /* Generate next content_category_id */
        $result = $conn->query("
            SELECT content_category_id
            FROM content_categories
            ORDER BY content_category_id DESC
            LIMIT 1
        ");

        if ($row = $result->fetch_assoc()) {
            $last_num = (int) substr($row['content_category_id'], 3);
            $new_num  = $last_num + 1;
        } else {
            $new_num = 1;
        }

        $content_category_id = 'cc_' . str_pad($new_num, 3, '0', STR_PAD_LEFT);

        /* Insert content category */
        $stmt = $conn->prepare("
            INSERT INTO content_categories
            (content_category_id, content_name, content_description, is_active)
            VALUES (?, ?, ?, 1)
        ");

        $stmt->bind_param(
            "sss",
            $content_category_id,
            $content_name,
            $content_description
        );

        if ($stmt->execute()) {
            header("Location: admin_system_settings.php?view=categories");
            exit();
        }
    }
}
?>

<main class="dashboard">
  <div class="main-content">

    <!-- PAGE TITLE BAR -->
    <div class="page-title-bar">
      <a href="admin_system_settings.php?view=categories"
         class="icon-btn back-btn">↩</a>

      <h2>Add Content Category</h2>
    </div>

    <!-- CENTERED FORM -->
    <div class="profile-container">

      <form method="POST"
            style="
              background:#e5e5e5;
              padding:30px;
              border-radius:8px;
            ">

        <div class="form-group">
          <label>Category Name</label>
          <input type="text"
                 name="content_name"
                 required>
        </div>

        <div class="form-group">
          <label>Category Description</label>
          <input type="text"
                 name="content_description">
        </div>

        <!-- ADD BUTTON -->
        <div style="text-align:center; margin-top:25px;">
          <button type="submit" class="action-btn">
            ADD
          </button>
        </div>

      </form>

    </div>

  </div>
</main>

<?php include "admin_footer.php"; ?>
