<?php

if (!defined('ABSPATH')) {
    exit;
}

class FAQ_Gen_AI_TinyMCE_Button {
    
    public function __construct() {
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_filter('mce_buttons', array($this, 'register_button'));
        add_filter('mce_external_plugins', array($this, 'add_plugin'));
    }
    
    public function enqueue_admin_scripts($hook) {
        if (!in_array($hook, array('post.php', 'post-new.php'))) {
            return;
        }
        
        // Check if current post type is supported
        global $post;
        $supported_types = get_option('faq_gen_ai_supported_post_types', array('post', 'page', 'product'));
        
        if ($post && !in_array(get_post_type($post), $supported_types)) {
            return;
        }
        
        wp_enqueue_style(
            'faq-gen-ai-admin',
            FAQ_GEN_AI_PLUGIN_URL . 'assets/css/admin-styles.css',
            array(),
            FAQ_GEN_AI_VERSION
        );
        
        wp_enqueue_script(
            'faq-gen-ai-admin',
            FAQ_GEN_AI_PLUGIN_URL . 'assets/js/admin-script.js',
            array('jquery'),
            FAQ_GEN_AI_VERSION,
            true
        );
        
        $seo_enabled = (get_option('faq_gen_ai_seo_integration', '1') === '1');
        $has_seo_plugin = (defined('RANK_MATH_VERSION') || defined('WPSEO_VERSION'));
        
        wp_localize_script('faq-gen-ai-admin', 'faqGenAI', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('faq_gen_ai_nonce'),
            'default_prompt' => get_option('faq_gen_ai_default_prompt', 'Generate FAQs based on this content: [content]'),
            'output_format' => get_option('faq_gen_ai_output_format', 'both'),
            'default_count' => intval(get_option('faq_gen_ai_default_count', 5)),
            'seo_integrated' => ($seo_enabled && $has_seo_plugin),
            'post_type' => get_post_type($post),
        ));
    }
    
    public function register_button($buttons) {
        // Check if current post type is supported
        global $post;
        $supported_types = get_option('faq_gen_ai_supported_post_types', array('post', 'page', 'product'));
        
        if ($post && in_array(get_post_type($post), $supported_types)) {
            array_push($buttons, 'faq_generator_ai');
        }
        
        return $buttons;
    }
    
    public function add_plugin($plugins) {
        global $post;
        $supported_types = get_option('faq_gen_ai_supported_post_types', array('post', 'page', 'product'));
        
        if ($post && in_array(get_post_type($post), $supported_types)) {
            $plugins['faq_generator_ai'] = FAQ_GEN_AI_PLUGIN_URL . 'assets/js/tinymce-plugin.js';
        }
        
        return $plugins;
    }
}
