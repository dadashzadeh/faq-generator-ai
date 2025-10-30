<?php
/**
 * Uninstall FAQ Generator AI
 *
 * @package FAQ_Generator_AI
 */

// If uninstall not called from WordPress, exit
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Delete plugin options
delete_option('faq_gen_ai_api_key');
delete_option('faq_gen_ai_base_url');
delete_option('faq_gen_ai_model');
delete_option('faq_gen_ai_temperature');
delete_option('faq_gen_ai_default_count');
delete_option('faq_gen_ai_output_format');
delete_option('faq_gen_ai_system_prompt');
delete_option('faq_gen_ai_default_prompt');
delete_option('faq_gen_ai_show_title');
delete_option('faq_gen_ai_title_en');
delete_option('faq_gen_ai_title_fa');
delete_option('faq_gen_ai_seo_integration');
delete_option('faq_gen_ai_supported_post_types');

// Delete all FAQ schema post meta
global $wpdb;

$wpdb->query(
    "DELETE FROM {$wpdb->postmeta} WHERE meta_key = '_faq_gen_ai_schema'"
);

// Clear cache
wp_cache_flush();

// Delete transients
$wpdb->query(
    "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_faq_gen_ai_%'"
);

$wpdb->query(
    "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_faq_gen_ai_%'"
);
