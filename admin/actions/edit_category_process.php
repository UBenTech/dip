<?php
// admin/actions/edit_category_process.php
if (session_status() == PHP_SESSION_NONE) { session_start(); }
require_once '../../includes/db.php';
require_once '../../includes/functions.php';

defined('BASE_URL') or define('BASE_URL', '/');
$admin_base_url = BASE_URL . 'admin/';

if (!isset($_SESSION['admin_user_id'])) {
    $_SESSION['flash_message'] = "Authentication required.";
    $_SESSION['flash_message_type'] = "error";
    header('Location: ' . $admin_base_url . 'index.php?admin_page=login');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_category']) && isset($_GET['id'])) {
    $category_id = (int)$_GET['id'];

    if (!isset($_POST['csrf_token']) || !validate_csrf_token($_POST['csrf_token'])) {
        $_SESSION['flash_message'] = "CSRF token mismatch. Action aborted.";
        $_SESSION['flash_message_type'] = "error";
        header('Location: ' . $admin_base_url . 'index.php?admin_page=edit_category&id=' . $category_id);
        exit;
    }

    $name = trim($_POST['name']);
    $slug = trim($_POST['slug']);
    $description = trim($_POST['description']);
    $errors = [];

    if (empty($name)) { $errors[] = "Category name is required."; }
    if (empty($slug)) { $slug = slugify($name); } else { $slug = slugify($slug); }

    // Check slug uniqueness (excluding current category)
    $stmt_check = $conn->prepare("SELECT id FROM categories WHERE slug = ? AND id != ?");
    $stmt_check->bind_param("si", $slug, $category_id);
    $stmt_check->execute();
    $stmt_check->store_result();
    if ($stmt_check->num_rows > 0) { $errors[] = "Category slug already exists. Choose a different name or slug."; }
    $stmt_check->close();

    if (!empty($errors)) {
        $_SESSION['form_error'] = $errors;
        $_SESSION['form_data'] = $_POST; // Preserve form data
        header('Location: ' . $admin_base_url . 'index.php?admin_page=edit_category&id=' . $category_id);
        exit;
    }

    $stmt_update = $conn->prepare("UPDATE categories SET name = ?, slug = ?, description = ? WHERE id = ?");
    if ($stmt_update) {
        $stmt_update->bind_param("sssi", $name, $slug, $description, $category_id);
        if ($stmt_update->execute()) {
            $_SESSION['flash_message'] = "Category updated successfully!";
            $_SESSION['flash_message_type'] = "success";
        } else {
            error_log("DB Error updating category: " . $stmt_update->error);
            $_SESSION['flash_message'] = "Error updating category: " . $stmt_update->error;
            $_SESSION['flash_message_type'] = "error";
        }
        $stmt_update->close();
    } else {
        error_log("DB Prepare Error updating category: " . $conn->error);
        $_SESSION['flash_message'] = "Database error preparing statement for category update.";
        $_SESSION['flash_message_type'] = "error";
    }
    header('Location: ' . $admin_base_url . 'index.php?admin_page=categories');
    exit;
} else {
    header('Location: ' . $admin_base_url . 'index.php?admin_page=categories');
    exit;
}
?>
