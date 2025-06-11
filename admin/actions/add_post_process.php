<?php
// admin/actions/edit_post_process.php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/hash.php';
$htmlPurifierPath = __DIR__ . '/../../libs/htmlpurifier/library/HTMLPurifier.auto.php';
$purifier = null;
if (file_exists($htmlPurifierPath)) {
    require_once $htmlPurifierPath;
    $purifier_config = HTMLPurifier_Config::createDefault();
    $purifier_config->set('HTML.Doctype', 'HTML 4.01 Transitional');
    $purifier_config->set('HTML.AllowedElements', [
        'p', 'br', 'b', 'strong', 'i', 'em', 'u', 's', 'strike', 'span',
        'ul', 'ol', 'li', 
        'a[href|title|target]', 
        'img[src|alt|title|width|height|style]',
        'h2', 'h3', 'h4', 'h5', 'h6',
        'blockquote', 'pre', 'code',
    ]);
    $purifier_config->set('HTML.TargetBlank', true);
    $purifier_config->set('AutoFormat.AutoParagraph', true);
    $purifier_config->set('AutoFormat.RemoveEmpty', true);
    $purifier = new HTMLPurifier($purifier_config);
} else {
     error_log("CRITICAL: HTMLPurifier library not found at: " . $htmlPurifierPath . ". Content will not be purified.");
}


defined('BASE_URL') or define('BASE_URL', '/');
$admin_base_url = BASE_URL . 'admin/';
$project_root = __DIR__ . '/../../'; 
$upload_path = $project_root . 'uploads/'; 


if (!isset($_SESSION['admin_user_id'])) {
    $_SESSION['flash_message'] = "You must be logged in to perform this action.";
    $_SESSION['flash_message_type'] = "error";
    header('Location: ' . $admin_base_url . 'index.php?admin_page=login');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_post']) && isset($_GET['id'])) {
    $post_id = (int)$_GET['id'];

    if (!isset($_POST['csrf_token']) || !validate_csrf_token($_POST['csrf_token'])) {
        $_SESSION['flash_message'] = "Invalid or missing CSRF token. Please try again.";
        $_SESSION['flash_message_type'] = "error";
        $_SESSION['form_data'] = $_POST;
        header('Location: ' . $admin_base_url . 'index.php?admin_page=edit_post&id=' . $post_id);
        exit;
    }

    $stmt_old = $conn->prepare("SELECT featured_image FROM posts WHERE id = ?");
    $old_featured_image = null;
    if ($stmt_old) {
        $stmt_old->bind_param("i", $post_id);
        $stmt_old->execute();
        $result_old = $stmt_old->get_result();
        if ($result_old->num_rows === 1) {
            $old_post_data = $result_old->fetch_assoc();
            $old_featured_image = $old_post_data['featured_image'];
        }
        $stmt_old->close();
    }

    $title = trim($_POST['post_title']);
    $slug = trim($_POST['post_slug']);
    $raw_content = $_POST['post_content'] ?? ''; 
    $category_id = !empty($_POST['post_category_id']) ? (int)$_POST['post_category_id'] : null;
    
    // **CRITICAL: Correctly get and validate status**
    $status_to_save = 'draft'; // Default to draft
    if (isset($_POST['post_status']) && in_array($_POST['post_status'], ['published', 'draft'])) {
        $status_to_save = $_POST['post_status'];
    }
    
    $remove_featured_image = isset($_POST['remove_featured_image']) && $_POST['remove_featured_image'] == '1';
    
    $meta_description = trim($_POST['post_meta_description'] ?? '');
    $meta_keywords = trim($_POST['post_meta_keywords'] ?? '');
    $excerpt = trim($_POST['post_excerpt'] ?? '');
    
    $errors = [];
    if (empty($title)) $errors[] = "Post title is required.";
    if (empty($raw_content) && strlen(strip_tags($raw_content)) < 2) {
        $errors[] = "Post content is required.";
    }
    if (strlen($meta_description) > 255) { $errors[] = "Meta Description should not exceed 255 characters."; }
    if (strlen($meta_keywords) > 255) { $errors[] = "Meta Keywords should not exceed 255 characters."; }


    if (empty($slug)) { $slug = slugify($title); } else { $slug = slugify($slug); }

    if (!empty($slug)) {
        $stmt_slug_check = $conn->prepare("SELECT id FROM posts WHERE slug = ? AND id != ?");
        if(!$stmt_slug_check) { $errors[] = "DB error preparing slug check: " . $conn->error; }
        else {
            $stmt_slug_check->bind_param("si", $slug, $post_id);
            $stmt_slug_check->execute();
            $stmt_slug_check->store_result();
            if ($stmt_slug_check->num_rows > 0) {
                $errors[] = "This slug ('" . esc_html($slug) . "') is already in use by another post.";
            }
            $stmt_slug_check->close();
        }
    } elseif(!empty($title)) {
        $errors[] = "Slug could not be generated, possibly because the title is empty or results in an empty slug.";
    }


    $featured_image_filename = $old_featured_image; 

    if ($remove_featured_image && !empty($old_featured_image)) {
        if (file_exists($upload_path . $old_featured_image)) {
            @unlink($upload_path . $old_featured_image);
        }
        $featured_image_filename = null; 
    }

    if (isset($_FILES['featured_image']) && $_FILES['featured_image']['error'] === UPLOAD_ERR_OK) {
        $upload_result = handle_file_upload($_FILES['featured_image'], $upload_path);
        if (is_array($upload_result)) {
            $errors = array_merge($errors, $upload_result);
        } else {
            if (!empty($old_featured_image) && $old_featured_image !== $upload_result && !$remove_featured_image) {
                if (file_exists($upload_path . $old_featured_image)) {
                    @unlink($upload_path . $old_featured_image);
                }
            }
            $featured_image_filename = $upload_result;
        }
    } elseif (isset($_FILES['featured_image']) && $_FILES['featured_image']['error'] !== UPLOAD_ERR_NO_FILE) {
         $errors[] = "Error uploading new featured image. Code: " . $_FILES['featured_image']['error'];
    }


    if (!empty($errors)) {
        $_SESSION['form_error'] = $errors;
        $_SESSION['form_data'] = $_POST;
        header('Location: ' . $admin_base_url . 'index.php?admin_page=edit_post&id=' . $post_id);
        exit;
    }

    $clean_content = $purifier ? $purifier->purify($raw_content) : esc_html($raw_content);
    
    if (empty($excerpt) && !empty($clean_content)) {
        $excerpt = generate_excerpt($clean_content, 155);
    }
    
    $category_id_to_save = $category_id === 0 ? null : $category_id;
    $featured_image_to_save = $featured_image_filename; 
    $meta_description_to_save = empty($meta_description) ? null : $meta_description;
    $meta_keywords_to_save = empty($meta_keywords) ? null : $meta_keywords;
    $excerpt_to_save = empty($excerpt) ? null : $excerpt;

    $sql = "UPDATE posts SET 
                title = ?, slug = ?, content = ?, category_id = ?, status = ?, 
                featured_image = ?, meta_description = ?, meta_keywords = ?, excerpt = ?, 
                updated_at = NOW() 
            WHERE id = ?";
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        // Types: title(s), slug(s), content(s), category_id(i), status(s), 
        // featured_image(s), meta_desc(s), meta_key(s), excerpt(s), id(i)
        $stmt->bind_param("sssisssssi",
            $title, $slug, $clean_content, $category_id_to_save, $status_to_save, 
            $featured_image_to_save, $meta_description_to_save, $meta_keywords_to_save, $excerpt_to_save, 
            $post_id
        );
        
        if ($stmt->execute()) {
            $_SESSION['flash_message'] = "Post updated successfully with status: '" . esc_html($status_to_save) . "'";
            $_SESSION['flash_message_type'] = "success";
            header('Location: ' . $admin_base_url . 'index.php?admin_page=posts');
            exit;
        } else {
            error_log("DB Error updating post: (" . $stmt->errno . ") " . $stmt->error);
            $_SESSION['flash_message'] = "Error updating post. DB Code: " . $stmt->errno . " - " . esc_html($stmt->error);
            $_SESSION['flash_message_type'] = "error";
        }
        $stmt->close();
    } else {
        error_log("DB Prepare Error updating post: " . $conn->error);
        $_SESSION['flash_message'] = "Database error preparing statement for post update: " . esc_html($conn->error);
        $_SESSION['flash_message_type'] = "error";
    }

    $_SESSION['form_data'] = $_POST; 
    header('Location: ' . $admin_base_url . 'index.php?admin_page=edit_post&id=' . $post_id);
    exit;

} else {
    $_SESSION['flash_message'] = "Invalid request or missing post ID.";
    $_SESSION['flash_message_type'] = "error";
    header('Location: ' . $admin_base_url . 'index.php?admin_page=posts');
    exit;
}

if (isset($conn) && $conn instanceof mysqli) {
    $conn->close();
}
?>
