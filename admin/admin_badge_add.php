<?php
include "../connect.php";

/* =====================
   HANDLE ADD BADGE
   ===================== */
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $badge_name      = $_POST['badge_name'];
    $description     = $_POST['description'];
    $required_points = $_POST['required_points'];
    $is_active       = 1; // default active

    /* =====================
   IMAGE UPLOAD (DEBUG FRIENDLY)
   ===================== */
if (!isset($_FILES['badge_icon'])) {
    die("No file input received. Check your form name='badge_icon' and enctype.");
}

if ($_FILES['badge_icon']['error'] !== UPLOAD_ERR_OK) {
    die("Upload error code: " . $_FILES['badge_icon']['error']);
}

/* folder path (admin -> ../images/badges/) */
$upload_dir = realpath(__DIR__ . "/../images/badges");

if ($upload_dir === false) {
    die("Upload folder not found: " . __DIR__ . "/../images/badges");
}

$upload_dir .= DIRECTORY_SEPARATOR;

$tmp_name  = $_FILES['badge_icon']['tmp_name'];
$extension = strtolower(pathinfo($_FILES['badge_icon']['name'], PATHINFO_EXTENSION));

$allowed = ['png', 'jpg', 'jpeg', 'webp'];
if (!in_array($extension, $allowed)) {
    die("Only PNG, JPG, JPEG, WEBP allowed.");
}

/* optional: file size limit (2MB) */
if ($_FILES['badge_icon']['size'] > 2 * 1024 * 1024) {
    die("File too large. Max 2MB.");
}

$new_filename = uniqid("badge_") . "." . $extension;
$target_path  = $upload_dir . $new_filename;

/* check folder write permission */
if (!is_writable($upload_dir)) {
    die("Upload folder is not writable: " . $upload_dir);
}

/* move file */
if (!move_uploaded_file($tmp_name, $target_path)) {
    die("Failed to upload image. Target: " . $target_path);
}


    /* =====================
       INSERT INTO DATABASE
       ===================== */
    $badge_id = uniqid("b_");

    $stmt = $conn->prepare("
        INSERT INTO badges 
        (badge_id, badge_name, required_points, icon_path, description, is_active)
        VALUES (?, ?, ?, ?, ?, ?)
    ");

    $stmt->bind_param(
        "ssissi",
        $badge_id,
        $badge_name,
        $required_points,
        $new_filename,
        $description,
        $is_active
    );

    $stmt->execute();

    header("Location: admin_badge.php?added=1");
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Add Badge</title>
    <link rel="stylesheet" href="../css/admin.css">
    <style>
        /* Simple inline styles to match mockup */
        .upload-box {
            width: 180px;
            height: 120px;
            border: 2px dashed #bbb;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            margin-bottom: 10px;
        }

        .upload-box img {
            max-width: 100%;
            max-height: 100%;
            display: none;
        }

        .upload-box span {
            font-size: 28px;
            color: #888;
        }

        input[type="file"] {
            display: none;
        }

        .form-card {
            width: 420px;
            margin: auto;
        }

        .form-card input,
        .form-card textarea {
            width: 100%;
            margin-bottom: 12px;
        }

        .btn-add {
            padding: 8px 20px;
        }
    </style>
</head>
<body>

<h2>Add Badge</h2>

<form method="POST" enctype="multipart/form-data" class="form-card">

    <label>Badge Name</label>
    <input type="text" name="badge_name" required>

    <label>Badge Description</label>
    <textarea name="description" rows="3" required></textarea>

    <label>Points Required</label>
    <input type="number" name="required_points" required>

    <label>Badge Icon</label>

    <label class="upload-box">
        <span id="plusIcon">+</span>
        <img id="previewImg">
        <input type="file" name="badge_icon" accept="image/*" onchange="previewImage(event)" required>
    </label>

    <button type="submit" class="btn-add">ADD</button>

</form>

<script>
function previewImage(event) {
    const img = document.getElementById("previewImg");
    const plus = document.getElementById("plusIcon");

    img.src = URL.createObjectURL(event.target.files[0]);
    img.style.display = "block";
    plus.style.display = "none";
}
</script>

</body>
</html>
