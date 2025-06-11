<?php
// admin/pages/add_edit_category.php
defined('BASE_URL') or define('BASE_URL', '/');
$admin_base_url = BASE_URL . 'admin/';
global $conn;

$is_editing = false;
$category_id = null;
$category_name = '';
$category_slug = '';
$category_description = '';

$form_action_target = 'add_category_process.php';

if (isset($_GET['id']) && !empty($_GET['id'])) {
    $is_editing = true;
    $category_id = (int)$_GET['id'];
    $form_action_target = 'edit_category_process.php?id=' . $category_id;

    $stmt = $conn->prepare("SELECT name, slug, description FROM categories WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $category_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows === 1) {
            $category = $result->fetch_assoc();
            $category_name = $category['name'];
            $category_slug = $category['slug'];
            $category_description = $category['description'];
        } else {
            $_SESSION['flash_message'] = "Category not found.";
            $_SESSION['flash_message_type'] = "error";
            header('Location: ' . $admin_base_url . 'index.php?admin_page=categories');
            exit;
        }
        $stmt->close();
    } else {
        error_log("DB Error fetching category for edit: " . $conn->error);
        $_SESSION['flash_message'] = "Database error fetching category details.";
        $_SESSION['flash_message_type'] = "error";
        header('Location: ' . $admin_base_url . 'index.php?admin_page=categories');
        exit;
    }
}
$form_action = $admin_base_url . 'actions/' . $form_action_target;

// Retrieve form data from session if validation failed
$form_data = $_SESSION['form_data'] ?? [];
unset($_SESSION['form_data']);
?>

<div class="container mx-auto px-4 py-8">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-semibold text-gray-800"><?php echo $is_editing ? 'Edit Category' : 'Add New Category'; ?></h2>
        <a href="<?php echo $admin_base_url; ?>index.php?admin_page=categories" class="text-admin-primary hover:underline text-sm flex items-center">
            <i data-lucide="arrow-left" class="w-4 h-4 mr-1"></i>Back to All Categories
        </a>
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

    <form action="<?php echo $form_action; ?>" method="POST" class="bg-white p-6 md:p-8 rounded-lg shadow-lg space-y-6">
        <?php echo generate_csrf_input(); ?>
        
        <div>
            <label for="category_name" class="block text-sm font-medium text-gray-700 mb-1">Category Name <span class="text-red-500">*</span></label>
            <input type="text" name="category_name" id="category_name" value="<?php echo esc_html($form_data['category_name'] ?? $category_name); ?>" required 
                   class="mt-1 block w-full px-3 py-2.5 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-admin-primary focus:border-admin-primary sm:text-sm"
                   oninput="document.getElementById('category_slug').value = slugify(this.value);">
        </div>

        <div>
            <label for="category_slug" class="block text-sm font-medium text-gray-700 mb-1">Slug (URL-friendly)</label>
            <input type="text" name="category_slug" id="category_slug" value="<?php echo esc_html($form_data['category_slug'] ?? $category_slug); ?>" 
                   class="mt-1 block w-full px-3 py-2.5 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-admin-primary focus:border-admin-primary sm:text-sm bg-gray-50"
                   placeholder="auto-generated-from-name">
            <p class="text-xs text-gray-500 mt-1">If left blank, this will be auto-generated. Must be unique.</p>
        </div>

        <div>
            <label for="category_description" class="block text-sm font-medium text-gray-700 mb-1">Description</label>
            <textarea name="category_description" id="category_description" rows="4"
                      class="mt-1 block w-full px-3 py-2.5 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-admin-primary focus:border-admin-primary sm:text-sm"><?php echo esc_html($form_data['category_description'] ?? $category_description); ?></textarea>
            <p class="text-xs text-gray-500 mt-1">Optional. A brief description of the category.</p>
        </div>
        
        <div class="pt-5 border-t border-gray-200">
            <div class="flex justify-end space-x-3">
                <a href="<?php echo $admin_base_url; ?>index.php?admin_page=categories" class="bg-gray-200 hover:bg-gray-300 text-gray-700 font-medium py-2.5 px-5 rounded-lg shadow-sm transition-colors">
                    Cancel
                </a>
                <button type="submit" name="submit_category" class="bg-admin-primary hover:bg-opacity-90 text-white font-medium py-2.5 px-5 rounded-lg shadow-md transition-colors flex items-center">
                    <i data-lucide="<?php echo $is_editing ? 'save' : 'plus-circle'; ?>" class="w-5 h-5 mr-2"></i>
                    <?php echo $is_editing ? 'Save Changes' : 'Add Category'; ?>
                </button>
            </div>
        </div>
    </form>
</div>
<script>
// Slugify function (can be in a global admin_script.js later)
function slugify(text) {
    if (typeof text !== 'string') return '';
    return text.toString().toLowerCase()
        .replace(/\s+/g, '-')
        .replace(/[^\w\-]+/g, '')
        .replace(/\-\-+/g, '-')
        .replace(/^-+/, '')
        .replace(/-+$/, '');
}
if (typeof lucide !== 'undefined') { lucide.createIcons(); }
</script>
