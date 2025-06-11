<?php
// admin/pages/add_edit_post.php
// Ensure BASE_URL is properly defined with domain
if (!defined('BASE_URL')) {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
    $domain = $_SERVER['HTTP_HOST'];
    define('BASE_URL', $protocol . $domain . '/');
}
$admin_base_url = BASE_URL . 'admin/';

// Debug URL construction
error_log("BASE_URL: " . BASE_URL);
error_log("Admin Base URL: " . $admin_base_url);
global $conn; 

$is_editing = false;
$post_id = null;
$post_title = '';
$post_slug = '';
$post_content = '';
$post_category_id = null;
$post_status = 'published';
$post_featured_image = '';
$post_meta_description = '';
$post_meta_keywords = '';
$post_excerpt = '';


$is_editing = isset($_GET['id']) && !empty($_GET['id']);
$post_id = $is_editing ? (int)$_GET['id'] : null;

// Validate post ID if editing
if ($is_editing && $post_id <= 0) {
    $_SESSION['flash_message'] = "Invalid post ID.";
    $_SESSION['flash_message_type'] = "error";
    header('Location: ' . BASE_URL . 'admin/index.php?admin_page=posts');
    exit;
}

// Set form action target
$form_action_target = $is_editing ? 'edit_post_process.php' : 'add_post_process.php';

// Debug form setup
error_log("Form setup:");
error_log("- Is Editing: " . ($is_editing ? 'true' : 'false'));
error_log("- Post ID: " . ($post_id ?? 'null'));
error_log("- Form Target: " . $form_action_target);

if ($is_editing) {
    // Added new fields to select
    $stmt = $conn->prepare("SELECT title, slug, content, category_id, status, featured_image, meta_description, meta_keywords, excerpt FROM posts WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $post_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows === 1) {
            $post = $result->fetch_assoc();
            $post_title = $post['title'];
            $post_slug = $post['slug'];
            $post_content = $post['content']; 
            $post_category_id = $post['category_id'];
            $post_status = $post['status'];
            $post_featured_image = $post['featured_image'];
            $post_meta_description = $post['meta_description'] ?? '';
            $post_meta_keywords = $post['meta_keywords'] ?? '';
            $post_excerpt = $post['excerpt'] ?? '';
        } else {
            $_SESSION['flash_message'] = "Post not found.";
            $_SESSION['flash_message_type'] = "error";
            header('Location: ' . BASE_URL . 'admin/index.php?admin_page=posts');
            exit;
        }
        $stmt->close();
    } else {
        error_log("DB Error fetching post for edit: " . $conn->error);
        $_SESSION['flash_message'] = "Database error fetching post details.";
        $_SESSION['flash_message_type'] = "error";
        header('Location: ' . BASE_URL . 'admin/index.php?admin_page=posts');
        exit;
    }
}
// Construct form action URL
$form_action = rtrim(BASE_URL, '/') . '/admin/actions/edit_post_process.php';

// Debug form details
error_log("=== Form Construction ===");
error_log("BASE_URL: " . BASE_URL);
error_log("Form Action: " . $form_action);
error_log("Is Editing: " . ($is_editing ? 'true' : 'false'));
error_log("Post ID: " . ($post_id ?? 'null'));
error_log("Current URL: " . $_SERVER['REQUEST_URI']);
error_log("Submit Value: " . ($is_editing ? 'update' : 'create'));
error_log("=== End Form Details ===");

$categories_sql = "SELECT id, name FROM categories ORDER BY name ASC";
$categories_result = $conn->query($categories_sql);
$categories = ($categories_result && $categories_result->num_rows > 0) ? $categories_result->fetch_all(MYSQLI_ASSOC) : [];

$form_data = $_SESSION['form_data'] ?? [];
unset($_SESSION['form_data']);

$preview_link = null;
// Generate preview link for published posts with a slug
if ($is_editing && $post_status === 'published' && !empty($post_slug)) {
    $preview_link = rtrim(BASE_URL, '/') . '/' . urlencode($post_slug);
}
// For drafts, we'll implement a different preview mechanism later if needed
// For now, a simple "Save Draft" and then view it via a special admin preview or by temporarily publishing.

?>
<div class="container mx-auto px-4 py-8">
    <div class="flex flex-wrap justify-between items-center mb-6 gap-4">
        <h2 class="text-2xl font-semibold text-gray-800"><?php echo $is_editing ? 'Edit Post' : 'Add New Post'; ?></h2>
        <div class="flex items-center space-x-3">
            <?php if ($preview_link): ?>
                <a href="<?php echo $preview_link; ?>" target="_blank" class="bg-blue-500 hover:bg-blue-600 text-white font-medium py-2 px-4 rounded-lg shadow-sm transition-colors flex items-center text-sm">
                    <i data-lucide="eye" class="w-4 h-4 mr-2"></i>View Published Post
                </a>
            <?php endif; ?>
            <?php if ($is_editing && $post_status === 'draft' && $post_id): ?>
                 <a href="<?php echo BASE_URL; ?>index.php?page=post&preview_id=<?php echo $post_id; ?>&token=<?php echo generate_preview_token($post_id); ?>" target="_blank" class="bg-yellow-500 hover:bg-yellow-600 text-white font-medium py-2 px-4 rounded-lg shadow-sm transition-colors flex items-center text-sm">
                    <i data-lucide="file-search" class="w-4 h-4 mr-2"></i>Preview Draft
                </a>
            <?php endif; ?>

            <a href="<?php echo $admin_base_url; ?>index.php?admin_page=posts" class="text-admin-primary hover:underline text-sm flex items-center">
                <i data-lucide="arrow-left" class="w-4 h-4 mr-1"></i>Back to All Posts
            </a>
        </div>
    </div>

    <?php if (isset($_SESSION['form_error'])): ?>
        <div class="mb-4 p-4 bg-red-100 text-red-700 border border-red-300 rounded-md" role="alert">
            <p class="font-bold">Please correct the following errors:</p>
            <ul class="list-disc list-inside">
            <?php 
                if(is_array($_SESSION['form_error'])){
                    foreach($_SESSION['form_error'] as $err) { echo "<li>" . esc_html($err) . "</li>"; }
                } else {
                    echo "<li>" . esc_html($_SESSION['form_error']) . "</li>";
                }
                unset($_SESSION['form_error']); 
            ?>
            </ul>
        </div>
    <?php endif; ?>
    
    <?php 
    // Construct the form action URL
    $form_action = rtrim(BASE_URL, '/') . '/admin/actions/' . ($is_editing ? 'edit_post_process.php' : 'add_post_process.php');
    
    // Debug form submission details
    error_log("=== Form Construction Details ===");
    error_log("Form Action URL: " . $form_action);
    error_log("Is Editing: " . ($is_editing ? 'true' : 'false'));
    error_log("Post ID: " . ($post_id ?? 'null'));
    error_log("=== End Form Details ===");
    ?>
    <form action="<?php echo esc_html($form_action); ?>" method="POST" enctype="multipart/form-data" class="bg-white p-6 md:p-8 rounded-lg shadow-lg space-y-6">
        <?php echo generate_csrf_input(); ?>
        <?php if ($is_editing): ?>
            <input type="hidden" name="post_id" value="<?php echo (int)$post_id; ?>">
        <?php endif; ?>
        
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2 space-y-6">
                <div>
                    <label for="post_title" class="block text-sm font-medium text-gray-700 mb-1">Title <span class="text-red-500">*</span></label>
                    <input type="text" name="post_title" id="post_title" value="<?php echo esc_html($form_data['post_title'] ?? $post_title); ?>" required 
                           class="mt-1 block w-full px-3 py-2.5 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-admin-primary focus:border-admin-primary sm:text-sm"
                           oninput="document.getElementById('post_slug').value = slugify(this.value);">
                </div>

                <div>
                    <label for="post_slug" class="block text-sm font-medium text-gray-700 mb-1">Slug (URL-friendly)</label>
                    <input type="text" name="post_slug" id="post_slug" value="<?php echo esc_html($form_data['post_slug'] ?? $post_slug); ?>" 
                           class="mt-1 block w-full px-3 py-2.5 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-admin-primary focus:border-admin-primary sm:text-sm bg-gray-50"
                           placeholder="auto-generated-from-title">
                    <p class="text-xs text-gray-500 mt-1">If left blank, this will be auto-generated from the title. Must be unique.</p>
                </div>

                <div>
                    <label for="post_content_editor" class="block text-sm font-medium text-gray-700 mb-1">Content <span class="text-red-500">*</span></label>
                    <textarea name="post_content" id="post_content_editor" rows="25" 
                              class="mt-1 block w-full px-3 py-2.5 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-admin-primary focus:border-admin-primary sm:text-sm"><?php echo esc_html($form_data['post_content'] ?? $post_content); ?></textarea>
                </div>

                <div>
                    <label for="post_excerpt" class="block text-sm font-medium text-gray-700 mb-1">Excerpt (Summary)</label>
                    <textarea name="post_excerpt" id="post_excerpt" rows="4"
                              class="mt-1 block w-full px-3 py-2.5 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-admin-primary focus:border-admin-primary sm:text-sm"
                              placeholder="A short summary of the post for listings and SEO. If blank, one will be auto-generated."><?php echo esc_html($form_data['post_excerpt'] ?? $post_excerpt); ?></textarea>
                </div>
            </div>

            <div class="lg:col-span-1 space-y-6">
                <div class="bg-gray-50 p-4 rounded-md shadow-sm border">
                    <h3 class="text-lg font-medium text-gray-900 mb-3 border-b pb-2">Publish</h3>
                    <div class="space-y-4">
                        <div>
                            <label for="post_status" class="block text-sm font-medium text-gray-700 mb-1">Status <span class="text-red-500">*</span></label>
                            <select name="post_status" id="post_status" required class="mt-1 block w-full px-3 py-2.5 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-admin-primary focus:border-admin-primary sm:text-sm">
                                <option value="published" <?php echo (($form_data['post_status'] ?? $post_status) === 'published' ? 'selected' : ''); ?>>Published</option>
                                <option value="draft" <?php echo (($form_data['post_status'] ?? $post_status) === 'draft' ? 'selected' : ''); ?>>Draft</option>
                            </select>
                        </div>
                        <div class="pt-3">
                             <button type="submit" name="submit_post" value="1" class="w-full bg-admin-primary hover:bg-opacity-90 text-white font-medium py-2.5 px-5 rounded-lg shadow-md transition-colors flex items-center justify-center">
                                <i data-lucide="<?php echo $is_editing ? 'save' : 'plus-circle'; ?>" class="w-5 h-5 mr-2"></i>
                                <?php echo $is_editing ? 'Update Post' : 'Publish Post'; ?>
                            </button>
                        </div>
                    </div>
                </div>
                
                <div class="bg-gray-50 p-4 rounded-md shadow-sm border">
                    <h3 class="text-lg font-medium text-gray-900 mb-3 border-b pb-2">Category</h3>
                     <div>
                        <label for="post_category_id" class="sr-only">Category</label>
                        <select name="post_category_id" id="post_category_id" class="mt-1 block w-full px-3 py-2.5 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-admin-primary focus:border-admin-primary sm:text-sm">
                            <option value="">-- Uncategorized --</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo $category['id']; ?>" <?php echo (($form_data['post_category_id'] ?? $post_category_id) == $category['id'] ? 'selected' : ''); ?>>
                                    <?php echo esc_html($category['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                         <p class="text-xs text-gray-500 mt-1">Manage categories <a href="<?php echo $admin_base_url; ?>index.php?admin_page=categories" class="text-admin-primary hover:underline">here</a>.</p>
                    </div>
                </div>

                <div class="bg-gray-50 p-4 rounded-md shadow-sm border">
                     <h3 class="text-lg font-medium text-gray-900 mb-3 border-b pb-2">Featured Image</h3>
                    <div>
                        <label for="featured_image" class="sr-only">Featured Image</label>
<div class="space-y-3">
    <input type="file" name="featured_image" id="featured_image" accept="image/jpeg, image/png, image/gif, image/webp"
           class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-admin-primary file:text-white hover:file:bg-opacity-90 file:cursor-pointer">
    
    <!-- Image Preview Container -->
    <div id="imagePreviewContainer" class="hidden mt-3 space-y-2">
        <p class="text-xs text-gray-600 mb-1">Image preview:</p>
        <img id="imagePreview" src="#" alt="Image preview" class="max-h-48 rounded-lg shadow-sm">
        <button type="button" id="removeNewImage" class="text-xs text-red-600 hover:text-red-700 flex items-center">
            <i data-lucide="x" class="w-4 h-4 mr-1"></i> Remove selected image
        </button>
    </div>

    <?php if ($is_editing && !empty($post_featured_image)): ?>
        <div id="currentImageContainer" class="mt-3 space-y-2">
            <p class="text-xs text-gray-600 mb-1">Current image:</p>
            <img src="<?php echo BASE_URL . 'uploads/' . esc_html($post_featured_image); ?>" 
                 alt="Current featured image" 
                 class="max-h-48 rounded-lg shadow-sm" 
                 onError="this.parentElement.style.display='none';">
            <label class="mt-2 inline-flex items-center text-xs text-gray-600">
                <input type="checkbox" name="remove_featured_image" id="removeFeaturedImage" value="1" 
                       class="h-4 w-4 text-admin-primary border-gray-300 rounded focus:ring-admin-primary mr-1"> 
                Remove current image
            </label>
        </div>
    <?php endif; ?>
    <p class="text-xs text-gray-500">Max file size: 2MB. Supported formats: JPG, PNG, GIF, WEBP.</p>
</div>
                    </div>
                </div>
                
                <div class="bg-gray-50 p-4 rounded-md shadow-sm border">
                    <h3 class="text-lg font-medium text-gray-900 mb-3 border-b pb-2">SEO Settings</h3>
                    <div class="space-y-4">
                        <div>
                            <label for="post_meta_description" class="block text-sm font-medium text-gray-700 mb-1">Meta Description</label>
                            <textarea name="post_meta_description" id="post_meta_description" rows="3" maxlength="160"
                                      class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-admin-primary focus:border-admin-primary sm:text-sm"
                                      placeholder="Concise summary for search engines (approx. 155-160 chars)"><?php echo esc_html($form_data['post_meta_description'] ?? $post_meta_description); ?></textarea>
                            <p class="text-xs text-gray-500 mt-1"><span id="meta_desc_char_count">0</span>/160 characters</p>
                        </div>
                        <div>
                            <label for="post_meta_keywords" class="block text-sm font-medium text-gray-700 mb-1">Meta Keywords</label>
                            <input type="text" name="post_meta_keywords" id="post_meta_keywords" value="<?php echo esc_html($form_data['post_meta_keywords'] ?? $post_meta_keywords); ?>"
                                   class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-admin-primary focus:border-admin-primary sm:text-sm"
                                   placeholder="e.g., keyword1, keyword2, keyword3">
                            <p class="text-xs text-gray-500 mt-1">Comma-separated keywords.</p>
                        </div>
                    </div>
                </div>

            </div>
        </div>
        
        <div class="pt-5 border-t border-gray-200 mt-6">
            <div class="flex justify-end space-x-3">
                <a href="<?php echo $admin_base_url; ?>index.php?admin_page=posts" class="bg-gray-200 hover:bg-gray-300 text-gray-700 font-medium py-2.5 px-5 rounded-lg shadow-sm transition-colors">
                    Cancel
                </a>
                <button type="submit" name="submit_post" value="<?php echo $is_editing ? 'update' : 'create'; ?>" class="bg-admin-primary hover:bg-opacity-90 text-white font-medium py-2.5 px-5 rounded-lg shadow-md transition-colors flex items-center">
                    <i data-lucide="<?php echo $is_editing ? 'save' : 'plus-circle'; ?>" class="w-5 h-5 mr-2"></i>
                    <?php echo $is_editing ? 'Update Post' : 'Publish Post'; ?>
                </button>
                <?php 
                error_log("=== Form Submit Button ===");
                error_log("Is Editing: " . ($is_editing ? 'true' : 'false'));
                error_log("Submit Value: " . ($is_editing ? 'update' : 'create'));
                error_log("=== End Button Info ===");
                ?>
            </div>
        </div>
    </form>
</div>

<script>
function slugify(text) {
    if (typeof text !== 'string') return '';
    return text.toString().toLowerCase()
        .replace(/\s+/g, '-')
        .replace(/[^\w\-]+/g, '')
        .replace(/\-\-+/g, '-')
        .replace(/^-+/, '')
        .replace(/-+$/, '');
}

// Enhanced TinyMCE Configuration
if (typeof tinymce !== 'undefined') {
    tinymce.init({
        selector: '#post_content_editor',
        plugins: [
            'advlist', 'autolink', 'lists', 'link', 'image', 'charmap', 'preview',
            'anchor', 'searchreplace', 'visualblocks', 'code', 'fullscreen',
            'insertdatetime', 'media', 'table', 'help', 'wordcount', 'autoresize',
            'paste', 'emoticons', 'codesample'
        ],
        toolbar: [
            'undo redo | formatselect | bold italic underline strikethrough | alignleft aligncenter alignright alignjustify',
            'bullist numlist | outdent indent | link image media | forecolor backcolor emoticons | code preview fullscreen',
            'table | hr removeformat | subscript superscript | charmap | codesample'
        ],
        menubar: 'file edit view insert format tools table help',
        toolbar_mode: 'sliding',
        contextmenu: 'link image table',
        content_style: `
            body {
                font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen-Sans, Ubuntu, Cantarell, 'Helvetica Neue', sans-serif;
                font-size: 16px;
                line-height: 1.6;
                color: #1f2937;
                margin: 1rem;
            }
            p { margin: 0 0 1em; }
            table { border-collapse: collapse; }
            table td, table th { border: 1px solid #e5e7eb; padding: 0.5rem; }
        `,
        height: 600,
        min_height: 400,
        max_height: 800,
        autoresize_bottom_margin: 50,
        relative_urls: false,
        remove_script_host: false,
        convert_urls: true,
        browser_spellcheck: true,
        paste_data_images: true,
        image_advtab: true,
        link_context_toolbar: true,
        setup: function (editor) {
            // Auto-save functionality
            let timer;
            editor.on('change keyup', function() {
                clearTimeout(timer);
                timer = setTimeout(function() {
                    editor.save();
                    // TODO: Implement auto-save to server
                    console.log('Content auto-saved locally');
                }, 3000);
            });

            // Warn before leaving with unsaved changes
            window.onbeforeunload = function() {
                if (editor.isDirty()) {
                    return 'You have unsaved changes. Do you really want to leave?';
                }
            };
        }
    });
} else {
    console.warn("TinyMCE script not loaded. Rich text editor will not be available.");
}

// Image preview functionality
document.getElementById('featured_image')?.addEventListener('change', function(e) {
    const file = e.target.files[0];
    const previewContainer = document.getElementById('imagePreviewContainer');
    const preview = document.getElementById('imagePreview');
    const removeBtn = document.getElementById('removeNewImage');
    const currentImageContainer = document.getElementById('currentImageContainer');
    const removeFeaturedImage = document.getElementById('removeFeaturedImage');

    if (file) {
        // Check file size (2MB limit)
        if (file.size > 2 * 1024 * 1024) {
            alert('File is too large. Maximum size is 2MB.');
            this.value = '';
            return;
        }

        // Check file type
        if (!file.type.match('image/(jpeg|png|gif|webp)')) {
            alert('Invalid file type. Please select an image file (JPG, PNG, GIF, or WEBP).');
            this.value = '';
            return;
        }

        const reader = new FileReader();
        reader.onload = function(e) {
            preview.src = e.target.result;
            previewContainer.classList.remove('hidden');
            if (currentImageContainer) {
                currentImageContainer.classList.add('hidden');
                if (removeFeaturedImage) {
                    removeFeaturedImage.checked = true;
                }
            }
        };
        reader.readAsDataURL(file);
    } else {
        previewContainer.classList.add('hidden');
        if (currentImageContainer) {
            currentImageContainer.classList.remove('hidden');
            if (removeFeaturedImage) {
                removeFeaturedImage.checked = false;
            }
        }
    }
});

document.getElementById('removeNewImage')?.addEventListener('click', function() {
    const fileInput = document.getElementById('featured_image');
    const previewContainer = document.getElementById('imagePreviewContainer');
    const currentImageContainer = document.getElementById('currentImageContainer');
    const removeFeaturedImage = document.getElementById('removeFeaturedImage');

    fileInput.value = '';
    previewContainer.classList.add('hidden');
    if (currentImageContainer) {
        currentImageContainer.classList.remove('hidden');
        if (removeFeaturedImage) {
            removeFeaturedImage.checked = false;
        }
    }
});

const metaDescTextarea = document.getElementById('post_meta_description');
const metaDescCharCount = document.getElementById('meta_desc_char_count');
if(metaDescTextarea && metaDescCharCount) {
    function updateMetaDescCount() {
        metaDescCharCount.textContent = metaDescTextarea.value.length;
    }
    metaDescTextarea.addEventListener('input', updateMetaDescCount);
    updateMetaDescCount(); 
}

if (typeof lucide !== 'undefined') { lucide.createIcons(); }
</script>
