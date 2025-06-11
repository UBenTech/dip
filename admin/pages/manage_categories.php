<?php
// Get all categories with post counts
$categories_sql = "
    SELECT c.*, COUNT(p.id) as post_count 
    FROM categories c 
    LEFT JOIN posts p ON c.id = p.category_id 
    GROUP BY c.id 
    ORDER BY c.name ASC
";
$categories_result = $conn->query($categories_sql);
$categories = ($categories_result && $categories_result->num_rows > 0) ? $categories_result->fetch_all(MYSQLI_ASSOC) : [];

// Get category for editing if ID is provided
$editing_category = null;
if (isset($_GET['edit']) && !empty($_GET['edit'])) {
    $edit_id = (int)$_GET['edit'];
    $edit_stmt = $conn->prepare("SELECT * FROM categories WHERE id = ?");
    $edit_stmt->bind_param("i", $edit_id);
    $edit_stmt->execute();
    $editing_category = $edit_stmt->get_result()->fetch_assoc();
}
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="md:grid md:grid-cols-3 md:gap-6">
        <!-- Category Form Section -->
        <div class="md:col-span-1">
            <div class="px-4 sm:px-0">
                <h3 class="text-lg font-medium leading-6 text-gray-900">
                    <?php echo $editing_category ? 'Edit Category' : 'Add New Category'; ?>
                </h3>
                <p class="mt-1 text-sm text-gray-600">
                    <?php echo $editing_category ? 'Update the category details below.' : 'Create a new category to organize your posts.'; ?>
                </p>
            </div>
        </div>

        <div class="mt-5 md:mt-0 md:col-span-2">
            <form action="<?php echo $admin_base_url; ?>actions/<?php echo $editing_category ? 'edit_category_process.php' : 'add_category_process.php'; ?>" method="POST">
                <?php echo generate_csrf_input(); ?>
                <?php if ($editing_category): ?>
                    <input type="hidden" name="category_id" value="<?php echo $editing_category['id']; ?>">
                <?php endif; ?>
                
                <div class="shadow sm:rounded-md sm:overflow-hidden">
                    <div class="px-4 py-5 bg-white space-y-6 sm:p-6">
                        <div>
                            <label for="category_name" class="block text-sm font-medium text-gray-700">
                                Category Name <span class="text-red-500">*</span>
                            </label>
                            <div class="mt-1">
                                <input type="text" name="name" id="category_name" required
                                       class="shadow-sm focus:ring-admin-primary focus:border-admin-primary block w-full sm:text-sm border-gray-300 rounded-md"
                                       value="<?php echo $editing_category ? esc_html($editing_category['name']) : ''; ?>"
                                       placeholder="Enter category name">
                            </div>
                        </div>

                        <div>
                            <label for="category_slug" class="block text-sm font-medium text-gray-700">
                                Slug
                            </label>
                            <div class="mt-1">
                                <input type="text" name="slug" id="category_slug"
                                       class="shadow-sm focus:ring-admin-primary focus:border-admin-primary block w-full sm:text-sm border-gray-300 rounded-md"
                                       value="<?php echo $editing_category ? esc_html($editing_category['slug']) : ''; ?>"
                                       placeholder="category-slug">
                                <p class="mt-1 text-sm text-gray-500">
                                    The "slug" is the URL-friendly version of the name. It should contain only lowercase letters, numbers, and hyphens.
                                </p>
                            </div>
                        </div>

                        <div>
                            <label for="category_description" class="block text-sm font-medium text-gray-700">
                                Description
                            </label>
                            <div class="mt-1">
                                <textarea id="category_description" name="description" rows="3"
                                          class="shadow-sm focus:ring-admin-primary focus:border-admin-primary block w-full sm:text-sm border-gray-300 rounded-md"
                                          placeholder="Brief description of the category"><?php echo $editing_category ? esc_html($editing_category['description']) : ''; ?></textarea>
                            </div>
                        </div>
                    </div>

                    <div class="px-4 py-3 bg-gray-50 text-right sm:px-6">
                        <?php if ($editing_category): ?>
                            <a href="<?php echo $admin_base_url; ?>index.php?admin_page=categories" 
                               class="inline-flex justify-center py-2 px-4 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-admin-primary mr-2">
                                Cancel
                            </a>
                        <?php endif; ?>
                        <button type="submit" name="submit_category" class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-admin-primary hover:bg-admin-primary/90 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-admin-primary">
                            <?php echo $editing_category ? 'Update Category' : 'Add Category'; ?>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="mt-8">
        <div class="sm:flex sm:items-center">
            <div class="sm:flex-auto">
                <h2 class="text-xl font-semibold text-gray-900">Categories</h2>
                <p class="mt-2 text-sm text-gray-700">
                    A list of all categories and their associated posts.
                </p>
            </div>
        </div>

        <div class="mt-6 bg-white shadow-sm rounded-lg border border-gray-200 overflow-hidden">
            <?php if (!empty($categories)): ?>
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Name
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Description
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Slug
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Posts
                            </th>
                            <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($categories as $category): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900">
                                        <?php echo esc_html($category['name']); ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm text-gray-500 truncate max-w-xs">
                                        <?php echo !empty($category['description']) ? esc_html($category['description']) : '—'; ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-500">
                                        <?php echo esc_html($category['slug']); ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?php if ($category['post_count'] > 0): ?>
                                        <a href="<?php echo $admin_base_url; ?>index.php?admin_page=posts&category=<?php echo $category['id']; ?>" 
                                           class="text-sm text-admin-primary hover:text-admin-primary/80">
                                            <?php echo $category['post_count']; ?> posts
                                        </a>
                                    <?php else: ?>
                                        <span class="text-sm text-gray-500">0 posts</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <div class="flex items-center justify-end space-x-3">
                                        <a href="<?php echo $admin_base_url; ?>index.php?admin_page=categories&edit=<?php echo $category['id']; ?>" 
                                           class="text-admin-primary hover:text-admin-primary/80"
                                           title="Edit">
                                            <i data-lucide="edit-2" class="w-5 h-5"></i>
                                        </a>
                                        <?php if ($category['post_count'] == 0): ?>
                                            <button onclick="confirmDeleteCategory(<?php echo $category['id']; ?>, '<?php echo esc_js($category['name']); ?>')" 
                                                    class="text-red-500 hover:text-red-600"
                                                    title="Delete">
                                                <i data-lucide="trash-2" class="w-5 h-5"></i>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="text-center py-12">
                    <i data-lucide="folder" class="mx-auto h-12 w-12 text-gray-400"></i>
                    <h3 class="mt-2 text-sm font-medium text-gray-900">No categories</h3>
                    <p class="mt-1 text-sm text-gray-500">
                        Get started by creating a new category.
                    </p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div id="deleteCategoryModal" class="hidden fixed z-10 inset-0 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
            <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                <div class="sm:flex sm:items-start">
                    <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-red-100 sm:mx-0 sm:h-10 sm:w-10">
                        <i data-lucide="alert-triangle" class="h-6 w-6 text-red-600"></i>
                    </div>
                    <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                        <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">
                            Delete Category
                        </h3>
                        <div class="mt-2">
                            <p class="text-sm text-gray-500" id="deleteModalMessage"></p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                <form id="deleteCategoryForm" method="POST" action="">
                    <?php echo generate_csrf_input(); ?>
                    <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 sm:ml-3 sm:w-auto sm:text-sm">
                        Delete
                    </button>
                </form>
                <button type="button" onclick="closeDeleteCategoryModal()" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-admin-primary sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                    Cancel
                </button>
            </div>
        </div>
    </div>
</div>

<script>
// Slug generator
document.getElementById('category_name')?.addEventListener('input', function() {
    const slugInput = document.getElementById('category_slug');
    if (!slugInput.value || slugInput.dataset.autoGenerated === 'true') {
        slugInput.value = this.value.toLowerCase()
            .replace(/\s+/g, '-')
            .replace(/[^\w\-]+/g, '')
            .replace(/\-\-+/g, '-')
            .replace(/^-+/, '')
            .replace(/-+$/, '');
        slugInput.dataset.autoGenerated = 'true';
    }
});

document.getElementById('category_slug')?.addEventListener('input', function() {
    this.dataset.autoGenerated = 'false';
});

// Delete category modal
function confirmDeleteCategory(categoryId, categoryName) {
    const modal = document.getElementById('deleteCategoryModal');
    const form = document.getElementById('deleteCategoryForm');
    const message = document.getElementById('deleteModalMessage');
    
    form.action = '<?php echo $admin_base_url; ?>actions/delete_category.php?id=' + categoryId;
    message.textContent = `Are you sure you want to delete the category "${categoryName}"? This action cannot be undone.`;
    
    modal.classList.remove('hidden');
}

function closeDeleteCategoryModal() {
    const modal = document.getElementById('deleteCategoryModal');
    modal.classList.add('hidden');
}

// Close modal when clicking outside
window.onclick = function(event) {
    const modal = document.getElementById('deleteCategoryModal');
    if (event.target == modal) {
        closeDeleteCategoryModal();
    }
}

// Initialize Lucide icons
if (typeof lucide !== 'undefined') {
    lucide.createIcons();
}
</script>
