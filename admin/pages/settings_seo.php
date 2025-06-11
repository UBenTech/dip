<div class="bg-white dark:bg-base-200 rounded-lg shadow p-6 max-w-xl">
    <h2 class="text-xl font-bold mb-4">SEO Settings</h2>
    <form method="post">
        <div class="mb-4">
            <label class="block mb-1 font-semibold">Site Title</label>
            <input type="text" name="seo_site_title" value="<?php echo esc_html($settings['seo_site_title'] ?? ''); ?>"
                class="input input-bordered w-full" required>
        </div>
        <div class="mb-4">
            <label class="block mb-1 font-semibold">Default Meta Description</label>
            <textarea name="seo_default_description" class="input input-bordered w-full" rows="3"><?php echo esc_html($settings['seo_default_description'] ?? ''); ?></textarea>
        </div>
        <div class="mb-4">
            <label class="block mb-1 font-semibold">Open Graph Image URL (optional)</label>
            <input type="url" name="seo_og_image" value="<?php echo esc_html($settings['seo_og_image'] ?? ''); ?>"
                class="input input-bordered w-full">
        </div>
        <button type="submit" class="bg-primary hover:bg-primary-hover text-white px-6 py-2 rounded font-semibold shadow transition-all">Save SEO Settings</button>
    </form>
</div>