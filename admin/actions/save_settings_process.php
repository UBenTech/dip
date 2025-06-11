<?php
// admin/actions/save_settings_process.php
if (session_status() == PHP_SESSION_NONE) { session_start(); }
require_once '../../includes/functions.php'; // For esc_html, CSRF

defined('BASE_URL') or define('BASE_URL', '/');
$admin_base_url = BASE_URL . 'admin/';
$config_dir = __DIR__ . '/../../config/'; // Relative to this file's location
$settings_file_path = $config_dir . 'site_settings.json';

if (!isset($_SESSION['admin_user_id']) || $_SESSION['admin_role'] !== 'admin') { // Only admins can change settings
    $_SESSION['flash_message'] = "You do not have permission to perform this action.";
    $_SESSION['flash_message_type'] = "error";
    header('Location: ' . $admin_base_url . 'index.php?admin_page=dashboard');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_settings'])) {
    if (!isset($_POST['csrf_token']) || !validate_csrf_token($_POST['csrf_token'])) {
        $_SESSION['flash_message'] = "CSRF token mismatch. Action aborted.";
        $_SESSION['flash_message_type'] = "error";
        header('Location: ' . $admin_base_url . 'index.php?admin_page=settings');
        exit;
    }

    $settings_to_save = [];
    $errors = [];

    // Validate Site Name
    $site_name = trim($_POST['site_name']);
    if (empty($site_name)) {
        $errors[] = "Site Name cannot be empty.";
    } else {
        $settings_to_save['site_name'] = $site_name;
    }

    // Validate Site Tagline
    $site_tagline = trim($_POST['site_tagline']);
    if (empty($site_tagline)) {
        $errors[] = "Site Tagline cannot be empty.";
    } else {
        $settings_to_save['site_tagline'] = $site_tagline;
    }
    
    // Validate Contact Email
    $contact_email = trim($_POST['contact_email']);
    if (empty($contact_email)) {
        $errors[] = "Contact Email cannot be empty.";
    } elseif (!filter_var($contact_email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid Contact Email format.";
    } else {
        $settings_to_save['contact_email'] = $contact_email;
    }

    // Validate Posts Per Page
    $posts_per_page = filter_var($_POST['posts_per_page'], FILTER_VALIDATE_INT, ["options" => ["min_range" => 1, "max_range" => 50]]);
    if ($posts_per_page === false) {
        $errors[] = "Posts Per Page must be a number between 1 and 50.";
    } else {
        $settings_to_save['posts_per_page'] = $posts_per_page;
    }
    
    // Footer Copyright (optional, so no strict validation beyond trimming)
    $footer_copyright = trim($_POST['footer_copyright']);
    $settings_to_save['footer_copyright'] = $footer_copyright;


    if (!empty($errors)) {
        $_SESSION['form_error'] = $errors;
        $_SESSION['form_data'] = $_POST; // Preserve submitted data
        header('Location: ' . $admin_base_url . 'index.php?admin_page=settings');
        exit;
    }

    // Ensure config directory exists
    if (!is_dir($config_dir)) {
        if (!mkdir($config_dir, 0755, true)) {
            $_SESSION['flash_message'] = "Configuration directory could not be created. Please check permissions.";
            $_SESSION['flash_message_type'] = "error";
            header('Location: ' . $admin_base_url . 'index.php?admin_page=settings');
            exit;
        }
    }
    
    // Write to JSON file
    if (file_put_contents($settings_file_path, json_encode($settings_to_save, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES))) {
        $_SESSION['flash_message'] = "Settings saved successfully!";
        $_SESSION['flash_message_type'] = "success";

        // Clear any cached settings if you implement caching later
    } else {
        error_log("Failed to write settings to: " . $settings_file_path);
        $_SESSION['flash_message'] = "Error saving settings. Please check file permissions for the 'config' directory.";
        $_SESSION['flash_message_type'] = "error";
    }

    header('Location: ' . $admin_base_url . 'index.php?admin_page=settings');
    exit;

} else {
    header('Location: ' . $admin_base_url . 'index.php?admin_page=settings');
    exit;
}
?>
