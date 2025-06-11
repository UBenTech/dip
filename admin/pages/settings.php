<?php
// admin/pages/settings.php
defined('BASE_URL') or define('BASE_URL', '/');
$admin_base_url = BASE_URL . 'admin/';
global $conn; // Not used directly here, but good to have if settings were in DB

// Define the path to the settings file
$settings_file_path = __DIR__ . '/../../config/site_settings.json'; // Relative to this file's location

// Default settings
$default_settings = [
    'site_name' => defined('SITE_NAME') ? SITE_NAME : 'dipug.com',
    'site_tagline' => defined('SITE_TAGLINE') ? SITE_TAGLINE : 'Digital Innovation and Programing',
    'posts_per_page' => 10,
    'contact_email' => 'info@dipug.com',
    'footer_copyright' => '&copy; ' . date("Y") . ' ' . (defined('SITE_NAME') ? SITE_NAME : 'dipug.com') . '. All Rights Reserved.',
];

// Load current settings
$current_settings = $default_settings;
if (file_exists($settings_file_path)) {
    $json_data = file_get_contents($settings_file_path);
    $loaded_settings = json_decode($json_data, true);
    if (is_array($loaded_settings)) {
        $current_settings = array_merge($default_settings, $loaded_settings);
    }
}

// Retrieve form data from session if validation failed
$form_data = $_SESSION['form_data'] ?? [];
unset($_SESSION['form_data']);

?>
<div class="container mx-auto px-4 py-8">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-semibold text-gray-800">Site Settings</h2>
    </div>

    <?php if (isset($_SESSION['flash_message'])): ?>
        <div class="mb-4 p-4 rounded-md <?php echo $_SESSION['flash_message_type'] === 'success' ? 'bg-green-100 text-green-700 border border-green-300' : 'bg-red-100 text-red-700 border border-red-300'; ?>" role="alert">
            <?php echo esc_html($_SESSION['flash_message']); ?>
            <?php unset($_SESSION['flash_message'], $_SESSION['flash_message_type']); ?>
        </div>
    <?php endif; ?>

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

    <form action="<?php echo $admin_base_url; ?>actions/save_settings_process.php" method="POST" class="bg-white p-6 md:p-8 rounded-lg shadow-lg space-y-6">
        <?php echo generate_csrf_input(); ?>
        
        <fieldset class="border border-gray-300 p-4 rounded-md">
            <legend class="text-lg font-medium text-gray-700 px-2">General Settings</legend>
            <div class="space-y-4 mt-2">
                <div>
                    <label for="site_name" class="block text-sm font-medium text-gray-700 mb-1">Site Name <span class="text-red-500">*</span></label>
                    <input type="text" name="site_name" id="site_name" value="<?php echo esc_html($form_data['site_name'] ?? $current_settings['site_name']); ?>" required 
                           class="mt-1 block w-full px-3 py-2.5 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-admin-primary focus:border-admin-primary sm:text-sm">
                </div>

                <div>
                    <label for="site_tagline" class="block text-sm font-medium text-gray-700 mb-1">Site Tagline <span class="text-red-500">*</span></label>
                    <input type="text" name="site_tagline" id="site_tagline" value="<?php echo esc_html($form_data['site_tagline'] ?? $current_settings['site_tagline']); ?>" required
                           class="mt-1 block w-full px-3 py-2.5 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-admin-primary focus:border-admin-primary sm:text-sm">
                </div>
                 <div>
                    <label for="contact_email" class="block text-sm font-medium text-gray-700 mb-1">Contact Email <span class="text-red-500">*</span></label>
                    <input type="email" name="contact_email" id="contact_email" value="<?php echo esc_html($form_data['contact_email'] ?? $current_settings['contact_email']); ?>" required
                           class="mt-1 block w-full px-3 py-2.5 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-admin-primary focus:border-admin-primary sm:text-sm">
                </div>
            </div>
        </fieldset>

        <fieldset class="border border-gray-300 p-4 rounded-md">
            <legend class="text-lg font-medium text-gray-700 px-2">Blog Settings</legend>
             <div class="space-y-4 mt-2">
                <div>
                    <label for="posts_per_page" class="block text-sm font-medium text-gray-700 mb-1">Posts Per Page (Public Blog) <span class="text-red-500">*</span></label>
                    <input type="number" name="posts_per_page" id="posts_per_page" value="<?php echo esc_html($form_data['posts_per_page'] ?? $current_settings['posts_per_page']); ?>" required min="1" max="50"
                           class="mt-1 block w-full px-3 py-2.5 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-admin-primary focus:border-admin-primary sm:text-sm">
                </div>
            </div>
        </fieldset>
        
        <fieldset class="border border-gray-300 p-4 rounded-md">
            <legend class="text-lg font-medium text-gray-700 px-2">Footer Settings</legend>
             <div class="space-y-4 mt-2">
                <div>
                    <label for="footer_copyright" class="block text-sm font-medium text-gray-700 mb-1">Footer Copyright Text</label>
                    <input type="text" name="footer_copyright" id="footer_copyright" value="<?php echo esc_html($form_data['footer_copyright'] ?? $current_settings['footer_copyright']); ?>"
                           class="mt-1 block w-full px-3 py-2.5 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-admin-primary focus:border-admin-primary sm:text-sm">
                     <p class="text-xs text-gray-500 mt-1">Use <code>{year}</code> to automatically insert the current year.</p>
                </div>
            </div>
        </fieldset>
        
        <div class="pt-5 border-t border-gray-200">
            <div class="flex justify-end">
                <button type="submit" name="save_settings" class="bg-admin-primary hover:bg-opacity-90 text-white font-medium py-2.5 px-6 rounded-lg shadow-md transition-colors flex items-center">
                    <i data-lucide="save" class="w-5 h-5 mr-2"></i>
                    Save Settings
                </button>
            </div>
        </div>
    </form>
</div>
<script>
if (typeof lucide !== 'undefined') { lucide.createIcons(); }
</script>
