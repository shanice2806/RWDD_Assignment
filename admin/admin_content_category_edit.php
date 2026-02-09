<?php
session_start();
require_once "../connect.php";

$page_title = "Edit Content Category";
include "admin_header.php";


if (!isset($_GET['id'])) {
    header("Location: admin_system_settings.php?view=categories");
    exit();
}

$category_id = $_GET['id'];


$stmt = $conn->prepare("
    SELECT content_category_id,
           content_name,
           content_description,
           is_active
    FROM content_categories
    WHERE content_category_id = ?
");
$stmt->bind_param("s", $category_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: admin_system_settings.php?view=categories");
    exit();
}

$category = $result->fetch_assoc();


$success = "";
$error   = "";


if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $content_name = trim($_POST['content_name']);
    $content_description = trim($_POST['content_description']);
    $status = $_POST['is_active'];

    if ($content_name !== '') {

        $stmt = $conn->prepare("
            UPDATE content_categories
            SET content_name = ?,
                content_description = ?,
                is_active = ?
            WHERE content_category_id = ?
        ");

        $stmt->bind_param(
            "ssis",
            $content_name,
            $content_description,
            $status,
            $category_id
        );

        if ($stmt->execute()) {
            $success = "Content category updated successfully.";
        } else {
            $error = "Failed to update content category.";
        }

    } else {
        $error = "Category name cannot be empty.";
    }
}
?>

<main class="dashboard">
  <div class="main-content">


    <div class="page-title-bar">
      <a href="admin_system_settings.php?view=categories"
         class="icon-btn back-btn">↩</a>
      <h2><?= htmlspecialchars($category["content_category_id"]) ?></h2>
    </div>

    <?php if (!empty($success)): ?>
      <div class="alert-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <?php if (!empty($error)): ?>
      <div class="alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>


    <div class="profile-container">

      <form method="POST"
            style="
              background:#e5e5e5;
              padding:30px;
              border-radius:8px;
            ">


        <div class="form-group">
          <label>Category ID</label>
          <input type="text"
                 value="<?= htmlspecialchars($category['content_category_id']) ?>"
                 readonly>
        </div>


        <div class="form-group">
          <label>Category Name</label>
          <input type="text"
                 name="content_name"
                 value="<?= htmlspecialchars($category['content_name']) ?>"
                 required>
        </div>


        <div class="form-group">
          <label>Category Description</label>
          <input type="text"
                 name="content_description"
                 value="<?= htmlspecialchars($category['content_description']) ?>">
        </div>


        <div class="form-group">
          <label>Status</label>
          <select name="is_active">
            <option value="1" <?= $category['is_active'] == 1 ? 'selected' : '' ?>>
              Active
            </option>
            <option value="0" <?= $category['is_active'] == 0 ? 'selected' : '' ?>>
              Inactive
            </option>
          </select>
        </div>


        <div style="text-align:center; margin-top:25px;">
          <button type="submit" class="action-btn">
            Edit
          </button>
        </div>

      </form>

    </div>

  </div>
</main>

<?php include "admin_footer.php"; ?>
