<?php
/**
 * Plugin Name: FAQ Generator AI
 * Plugin URI: https://wordpress.org/plugins/faq-generator-ai/
 * Description: Generate FAQs using AI with TinyMCE integration. Compatible with RankMath.
 * Version: 1.2.0
 * Author: Mohammad Dadashzadeh
 * Author URI: https://dadashzadeh.org/
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Requires at least: 5.0
 * Tested up to: 6.8
 * Requires PHP: 7.4
 * Text Domain: faq-generator-ai
 * Domain Path: /languages
 */

if (!defined('ABSPATH')) {
    exit;
}

define('FAQ_GEN_AI_VERSION', '1.2.0');
define('FAQ_GEN_AI_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('FAQ_GEN_AI_PLUGIN_URL', plugin_dir_url(__FILE__));

// Include required files
require_once FAQ_GEN_AI_PLUGIN_DIR . 'includes/class-settings.php';
require_once FAQ_GEN_AI_PLUGIN_DIR . 'includes/class-api-handler.php';
require_once FAQ_GEN_AI_PLUGIN_DIR . 'includes/class-tinymce-button.php';
require_once FAQ_GEN_AI_PLUGIN_DIR . 'includes/class-seo-integration.php';
require_once FAQ_GEN_AI_PLUGIN_DIR . 'includes/class-schema-metabox.php';

class FAQ_Generator_AI {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_action('plugins_loaded', array($this, 'init'));
    }
    
    public function init() {
        // Initialize settings
        new FAQ_Gen_AI_Settings();
        
        // Initialize TinyMCE button
        new FAQ_Gen_AI_TinyMCE_Button();
        
        // Initialize SEO integration
        new FAQ_Gen_AI_SEO_Integration();
        
        // Initialize Schema Meta Box
        new FAQ_Gen_AI_Schema_Metabox();
        
        // Register AJAX handlers
        add_action('wp_ajax_faq_generate_ai', array($this, 'ajax_generate_faq'));
        
        // Add frontend styles
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_styles'));
        
        // ✅ NEW: Register shortcode
        add_shortcode('faq_display', array($this, 'render_faq_shortcode'));
    }
    
    /**
     * ✅ NEW: Render FAQ Shortcode
     * This reads from post_meta so changes in metabox are always reflected
     */
    public function render_faq_shortcode($atts) {
        global $post;
        
        // Parse attributes
        $atts = shortcode_atts(array(
            'post_id' => 0,
            'show_title' => 'auto', // auto, yes, no
        ), $atts, 'faq_display');
        
        // Get post ID
        $post_id = intval($atts['post_id']);
        if ($post_id === 0 && $post) {
            $post_id = $post->ID;
        }
        
        if (!$post_id) {
            return '<!-- FAQ Display: No post ID -->';
        }
        
        // Get schema from post meta
        $schema = get_post_meta($post_id, '_faq_gen_ai_schema', true);
        
        if (empty($schema)) {
            return '<!-- FAQ Display: No FAQ schema found -->';
        }
        
        $schema_data = json_decode($schema, true);
        
        if (!$schema_data || !isset($schema_data['mainEntity']) || empty($schema_data['mainEntity'])) {
            return '<!-- FAQ Display: Invalid or empty schema -->';
        }
        
        // Build HTML output
        $html = '';
        
        // Determine if we should show title
        $show_title = $atts['show_title'];
        if ($show_title === 'auto') {
            $show_title = (get_option('faq_gen_ai_show_title', '1') === '1') ? 'yes' : 'no';
        }
        
        // Add title
        if ($show_title === 'yes') {
            $is_rtl = is_rtl();
            $title = $is_rtl 
                ? get_option('faq_gen_ai_title_fa', 'سوالات متداول') 
                : get_option('faq_gen_ai_title_en', 'Frequently Asked Questions');
            $dir = $is_rtl ? 'rtl' : 'ltr';
            
            $html .= '<h2 class="faq-section-title" dir="' . esc_attr($dir) . '">' . esc_html($title) . '</h2>' . "\n";
        }
        
        // FAQ section
        $html .= '<div class="faq-section">' . "\n";
        
        foreach ($schema_data['mainEntity'] as $item) {
            $question = isset($item['name']) ? $item['name'] : '';
            $answer = isset($item['acceptedAnswer']['text']) ? $item['acceptedAnswer']['text'] : '';
            
            if (empty($question) || empty($answer)) {
                continue;
            }
            
            $html .= '<div class="faq-item">';
            $html .= '<h3 class="faq-question">' . esc_html($question) . '</h3>';
            $html .= '<div class="faq-answer"><p>' . esc_html($answer) . '</p></div>';
            $html .= '</div>' . "\n";
        }
        
        $html .= '</div>';
        
        return $html;
    }
    
    public function enqueue_frontend_styles() {
        // Enqueue styles
        wp_enqueue_style(
            'faq-gen-ai-frontend',
            FAQ_GEN_AI_PLUGIN_URL . 'assets/css/frontend-styles.css',
            array(),
            FAQ_GEN_AI_VERSION
        );
        
        // Enqueue Dashicons for arrow icon
        wp_enqueue_style('dashicons');
        
        // Enqueue JavaScript
        wp_enqueue_script(
            'faq-gen-ai-frontend',
            FAQ_GEN_AI_PLUGIN_URL . 'assets/js/frontend-script.js',
            array('jquery'),
            FAQ_GEN_AI_VERSION,
            true
        );
    }

    
    public function ajax_generate_faq() {
        // Enable error logging
        $debug_mode = defined('WP_DEBUG') && WP_DEBUG;
        
        if ($debug_mode) {
            error_log('=== FAQ Generator: AJAX Request Started ===');
        }
        
        // Verify nonce
        if (!check_ajax_referer('faq_gen_ai_nonce', 'nonce', false)) {
            if ($debug_mode) {
                error_log('FAQ Generator: Nonce verification failed');
            }
            wp_send_json_error('Security check failed. Please refresh the page.');
            return;
        }
        
        // Check user permissions
        if (!current_user_can('edit_posts')) {
            if ($debug_mode) {
                error_log('FAQ Generator: User does not have permission');
            }
            wp_send_json_error('You do not have permission to perform this action.');
            return;
        }
        
        // Get and validate inputs
        $prompt = isset($_POST['prompt']) ? sanitize_textarea_field($_POST['prompt']) : '';
        $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
        $output_format = isset($_POST['output_format']) ? sanitize_text_field($_POST['output_format']) : 'both';
        $faq_count = isset($_POST['faq_count']) ? intval($_POST['faq_count']) : intval(get_option('faq_gen_ai_default_count', 5));
        
        // ✅ NEW: Check if shortcode mode is enabled
        $use_shortcode = isset($_POST['use_shortcode']) ? ($_POST['use_shortcode'] === 'true' || $_POST['use_shortcode'] === '1') : true;
        
        if ($debug_mode) {
            error_log('FAQ Generator: Input prompt: ' . $prompt);
            error_log('FAQ Generator: Post ID: ' . $post_id);
            error_log('FAQ Generator: Format: ' . $output_format);
            error_log('FAQ Generator: Count: ' . $faq_count);
            error_log('FAQ Generator: Use Shortcode: ' . ($use_shortcode ? 'yes' : 'no'));
        }
        
        // Check if prompt is provided
        if (empty($prompt)) {
            wp_send_json_error('Prompt is required. Please enter a prompt.');
            return;
        }
        
        // Validate FAQ count
        $faq_count = max(3, min(10, $faq_count));
        
        // Replace shortcodes
        $original_prompt = $prompt;
        $prompt = $this->replace_shortcodes($prompt, $post_id);
        
        if ($debug_mode) {
            error_log('FAQ Generator: Prompt after shortcode replacement: ' . substr($prompt, 0, 200) . '...');
            error_log('FAQ Generator: Prompt length: ' . strlen($prompt));
        }
        
        // Check if prompt is valid after shortcode replacement
        if (empty(trim($prompt))) {
            wp_send_json_error('Prompt is empty after processing. The post might not have content for the shortcodes used.');
            return;
        }
        
        if (strlen(trim($prompt)) < 10) {
            wp_send_json_error('Prompt is too short (' . strlen($prompt) . ' characters). Please provide more content or use different shortcodes.');
            return;
        }
        
        // Generate FAQ
        try {
            $api_handler = new FAQ_Gen_AI_API_Handler();
            $result = $api_handler->generate_faq($prompt, $output_format, $post_id, $faq_count);
            
            if (is_wp_error($result)) {
                if ($debug_mode) {
                    error_log('FAQ Generator: Error - ' . $result->get_error_message());
                }
                wp_send_json_error($result->get_error_message());
                return;
            }
            
            // Check if content exists
            if (!isset($result['content']) || empty(trim($result['content']))) {
                if ($debug_mode) {
                    error_log('FAQ Generator: Result structure: ' . print_r($result, true));
                }
                wp_send_json_error('Generated content is empty. Please try again with a different prompt.');
                return;
            }
            
            // ✅ NEW: If shortcode mode, replace content with shortcode
            if ($use_shortcode) {
                $result['content'] = '[faq_display]';
                $result['use_shortcode'] = true;
            }
            
            if ($debug_mode) {
                error_log('FAQ Generator: Success! Content length: ' . strlen($result['content']));
            }
            
            wp_send_json_success($result);
            
        } catch (Exception $e) {
            if ($debug_mode) {
                error_log('FAQ Generator Exception: ' . $e->getMessage());
                error_log('FAQ Generator Exception Trace: ' . $e->getTraceAsString());
            }
            wp_send_json_error('An unexpected error occurred: ' . $e->getMessage());
        }
    }

    private function replace_shortcodes($prompt, $post_id) {
        $post = get_post($post_id);
        
        if (!$post) {
            error_log('FAQ Generator: Post not found for ID: ' . $post_id);
            return $prompt;
        }
        
        $debug_mode = defined('WP_DEBUG') && WP_DEBUG;
        
        // Replace [content]
        if (strpos($prompt, '[content]') !== false) {
            $content = strip_tags($post->post_content);
            if ($debug_mode) {
                error_log('FAQ Generator: Replacing [content] with ' . strlen($content) . ' characters');
            }
            $prompt = str_replace('[content]', $content, $prompt);
        }
        
        // Replace [title]
        if (strpos($prompt, '[title]') !== false) {
            if ($debug_mode) {
                error_log('FAQ Generator: Replacing [title] with: ' . $post->post_title);
            }
            $prompt = str_replace('[title]', $post->post_title, $prompt);
        }
        
        // Replace [excerpt]
        if (strpos($prompt, '[excerpt]') !== false) {
            $excerpt = !empty($post->post_excerpt) ? $post->post_excerpt : wp_trim_words($post->post_content, 55);
            if ($debug_mode) {
                error_log('FAQ Generator: Replacing [excerpt] with ' . strlen($excerpt) . ' characters');
            }
            $prompt = str_replace('[excerpt]', $excerpt, $prompt);
        }
        
        // Replace [author]
        if (strpos($prompt, '[author]') !== false) {
            $author = get_the_author_meta('display_name', $post->post_author);
            $prompt = str_replace('[author]', $author, $prompt);
        }
        
        // Replace [categories]
        if (strpos($prompt, '[categories]') !== false) {
            $categories = get_the_category($post_id);
            $cat_names = array();
            foreach ($categories as $cat) {
                $cat_names[] = $cat->name;
            }
            $prompt = str_replace('[categories]', implode(', ', $cat_names), $prompt);
        }
        
        // Replace [tags]
        if (strpos($prompt, '[tags]') !== false) {
            $tags = get_the_tags($post_id);
            $tag_names = array();
            if ($tags) {
                foreach ($tags as $tag) {
                    $tag_names[] = $tag->name;
                }
            }
            $prompt = str_replace('[tags]', implode(', ', $tag_names), $prompt);
        }
        
        // Get all custom fields
        $custom_fields = get_post_custom($post_id);
        foreach ($custom_fields as $key => $value) {
            // Skip WordPress internal fields
            if (substr($key, 0, 1) === '_') {
                continue;
            }
            $placeholder = '[' . $key . ']';
            if (strpos($prompt, $placeholder) !== false) {
                $prompt = str_replace($placeholder, implode(', ', $value), $prompt);
            }
        }
        
        return $prompt;
    }
}

// Initialize plugin
FAQ_Generator_AI::get_instance();
