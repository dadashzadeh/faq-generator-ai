<?php

if (!defined('ABSPATH')) {
    exit;
}

class FAQ_Gen_AI_Schema_Metabox {
    
    public function __construct() {
        add_action('add_meta_boxes', array($this, 'add_meta_box'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_metabox_scripts'));
        add_action('save_post', array($this, 'save_meta_box'), 10, 2);
        add_action('admin_notices', array($this, 'show_schema_status')); // جدید
    }

    
    /**
     * Show schema status in admin
     */
    public function show_schema_status() {
        global $post;
        
        if (!$post) {
            return;
        }
        
        $screen = get_current_screen();
        if (!$screen || !in_array($screen->id, array('post', 'page', 'product'))) {
            return;
        }
        
        $schema = get_post_meta($post->ID, '_faq_gen_ai_schema', true);
        
        if (empty($schema)) {
            return;
        }
        
        $schema_data = json_decode($schema, true);
        if (!$schema_data || !isset($schema_data['mainEntity'])) {
            return;
        }
        
        $count = count($schema_data['mainEntity']);
        $seo_enabled = (get_option('faq_gen_ai_seo_integration', '1') === '1');
        $has_seo = defined('RANK_MATH_VERSION') || defined('WPSEO_VERSION');
        
        echo '<div class="notice notice-info"><p>';
        echo '<strong>FAQ Schema:</strong> ';
        echo sprintf(__('%d FAQs ready to publish.', 'faq-generator-ai'), $count);
        
        if ($seo_enabled && $has_seo) {
            echo ' ' . __('Schema will be integrated with your SEO plugin.', 'faq-generator-ai');
        } else {
            echo ' ' . __('Schema will be added to page head.', 'faq-generator-ai');
        }
        
        echo '</p></div>';
    }
    
    /**
     * Add meta box
     */
    public function add_meta_box() {
        $supported_types = get_option('faq_gen_ai_supported_post_types', array('post', 'page', 'product'));
        
        foreach ($supported_types as $post_type) {
            add_meta_box(
                'faq_gen_ai_schema_metabox',
                '⚡ ' . __('FAQ Schema Manager', 'faq-generator-ai'),
                array($this, 'render_meta_box'),
                $post_type,
                'normal',
                'high'
            );
        }
    }
    
    /**
     * Enqueue scripts
     */
    public function enqueue_metabox_scripts($hook) {
        if (!in_array($hook, array('post.php', 'post-new.php'))) {
            return;
        }
        
        global $post;
        $supported_types = get_option('faq_gen_ai_supported_post_types', array('post', 'page', 'product'));
        
        if (!$post || !in_array(get_post_type($post), $supported_types)) {
            return;
        }
        
        wp_enqueue_script('jquery-ui-sortable');
        
        wp_enqueue_style(
            'faq-gen-ai-metabox',
            FAQ_GEN_AI_PLUGIN_URL . 'assets/css/metabox-styles.css',
            array(),
            FAQ_GEN_AI_VERSION
        );
        
        wp_enqueue_script(
            'faq-gen-ai-metabox',
            FAQ_GEN_AI_PLUGIN_URL . 'assets/js/metabox-script.js',
            array('jquery', 'jquery-ui-sortable'),
            FAQ_GEN_AI_VERSION,
            true
        );
        
        wp_localize_script('faq-gen-ai-metabox', 'faqSchemaMetabox', array(
            'post_id' => $post->ID,
            'strings' => array(
                'confirm_delete' => __('Delete this FAQ?', 'faq-generator-ai'),
                'confirm_delete_all' => __('Delete all FAQs?', 'faq-generator-ai'),
                'saved' => __('✓ Changes saved', 'faq-generator-ai'),
                'unsaved' => __('You have unsaved changes', 'faq-generator-ai'),
                'required_fields' => __('Question and answer are required', 'faq-generator-ai'),
            )
        ));
    }
    
    /**
     * Render meta box
     */
    public function render_meta_box($post) {
        wp_nonce_field('faq_schema_metabox_save', 'faq_schema_metabox_nonce');
        
        $schema = get_post_meta($post->ID, '_faq_gen_ai_schema', true);
        $has_schema = !empty($schema);
        
        $schema_data = null;
        $faq_items = array();
        if ($has_schema) {
            $schema_data = json_decode($schema, true);
            if ($schema_data && isset($schema_data['mainEntity'])) {
                $faq_items = $schema_data['mainEntity'];
            }
        }
        
        $seo_enabled = (get_option('faq_gen_ai_seo_integration', '1') === '1');
        $has_seo_plugin = (defined('RANK_MATH_VERSION') || defined('WPSEO_VERSION'));
        $seo_plugin_name = defined('RANK_MATH_VERSION') ? 'RankMath' : (defined('WPSEO_VERSION') ? 'Yoast SEO' : '');
        
        ?>
        <div class="faq-metabox-wrapper">
            
            <!-- Hidden field to store schema data -->
            <input type="hidden" id="faq-schema-data" name="faq_schema_data" value="<?php echo esc_attr($schema); ?>">
            
            <?php if ($has_schema && !empty($faq_items)): ?>
                
                <!-- Toolbar -->
                <div class="faq-toolbar">
                    <div class="faq-toolbar-left">
                        <span class="faq-count-badge">
                            <span class="dashicons dashicons-list-view"></span>
                            <strong id="faq-count-number"><?php echo count($faq_items); ?></strong> <?php _e('FAQs', 'faq-generator-ai'); ?>
                        </span>
                        <?php if ($seo_enabled && $has_seo_plugin): ?>
                        <span class="faq-seo-badge">
                            <span class="dashicons dashicons-admin-plugins"></span>
                            <?php echo esc_html($seo_plugin_name); ?>
                        </span>
                        <?php endif; ?>
                        <span class="faq-status-badge" style="display: none;">
                            <span class="dashicons dashicons-warning"></span>
                            <?php _e('Unsaved changes', 'faq-generator-ai'); ?>
                        </span>
                    </div>
                    <div class="faq-toolbar-right">
                        <button type="button" class="button faq-btn-add">
                            <span class="dashicons dashicons-plus-alt"></span>
                            <?php _e('Add New', 'faq-generator-ai'); ?>
                        </button>
                        <button type="button" class="button faq-btn-edit-json">
                            <span class="dashicons dashicons-editor-code"></span>
                            <?php _e('Edit JSON', 'faq-generator-ai'); ?>
                        </button>
                        <button type="button" class="button button-link-delete faq-btn-delete-all">
                            <span class="dashicons dashicons-trash"></span>
                            <?php _e('Delete All', 'faq-generator-ai'); ?>
                        </button>
                    </div>
                </div>
                
                <!-- FAQ Items List -->
                <div class="faq-items-container">
                    <div class="faq-items-list" id="faq-sortable-list">
                        <?php foreach ($faq_items as $index => $item): ?>
                            <?php $this->render_faq_item($item, $index); ?>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <!-- JSON Editor (Hidden) -->
                <div class="faq-json-editor" style="display: none;">
                    <div class="faq-json-header">
                        <h4><span class="dashicons dashicons-editor-code"></span> <?php _e('JSON Schema Editor', 'faq-generator-ai'); ?></h4>
                        <button type="button" class="button faq-json-close">
                            <span class="dashicons dashicons-no-alt"></span>
                        </button>
                    </div>
                    <textarea id="faq-json-textarea" class="faq-json-textarea"><?php echo esc_textarea($schema); ?></textarea>
                    <div class="faq-json-footer">
                        <button type="button" class="button button-primary faq-json-apply">
                            <span class="dashicons dashicons-yes"></span>
                            <?php _e('Apply Changes', 'faq-generator-ai'); ?>
                        </button>
                        <button type="button" class="button faq-json-close">
                            <?php _e('Cancel', 'faq-generator-ai'); ?>
                        </button>
                        <span class="faq-json-help">
                            <?php _e('Edit JSON and click "Apply" to update. Save post to persist changes.', 'faq-generator-ai'); ?>
                        </span>
                    </div>
                </div>
                
            <?php else: ?>
                
                <!-- Empty State -->
                <div class="faq-empty-state">
                    <div class="faq-empty-icon">
                        <span class="dashicons dashicons-editor-help"></span>
                    </div>
                    <h3><?php _e('No FAQs Yet', 'faq-generator-ai'); ?></h3>
                    <p><?php _e('Start by adding your first FAQ or use the "Generate FAQ" button in the editor.', 'faq-generator-ai'); ?></p>
                    <button type="button" class="button button-primary button-hero faq-btn-add">
                        <span class="dashicons dashicons-plus-alt"></span>
                        <?php _e('Add First FAQ', 'faq-generator-ai'); ?>
                    </button>
                </div>
                
            <?php endif; ?>
            
            <!-- Add/Edit Modal -->
            <div class="faq-modal" id="faq-modal" style="display: none;">
                <div class="faq-modal-backdrop"></div>
                <div class="faq-modal-dialog">
                    <div class="faq-modal-header">
                        <h3 id="faq-modal-title"><?php _e('Add New FAQ', 'faq-generator-ai'); ?></h3>
                        <button type="button" class="faq-modal-close">&times;</button>
                    </div>
                    <div class="faq-modal-body">
                        <div class="faq-form-group">
                            <label><?php _e('Question', 'faq-generator-ai'); ?> <span class="required">*</span></label>
                            <input type="text" 
                                   id="faq-modal-question" 
                                   class="widefat" 
                                   placeholder="<?php _e('Enter your question...', 'faq-generator-ai'); ?>">
                        </div>
                        <div class="faq-form-group">
                            <label><?php _e('Answer', 'faq-generator-ai'); ?> <span class="required">*</span></label>
                            <textarea id="faq-modal-answer" 
                                      class="widefat" 
                                      rows="6"
                                      placeholder="<?php _e('Enter your answer...', 'faq-generator-ai'); ?>"></textarea>
                        </div>
                        <input type="hidden" id="faq-modal-index" value="">
                    </div>
                    <div class="faq-modal-footer">
                        <button type="button" class="button button-primary button-large faq-modal-save">
                            <span class="dashicons dashicons-saved"></span>
                            <span id="faq-modal-save-text"><?php _e('Add FAQ', 'faq-generator-ai'); ?></span>
                        </button>
                        <button type="button" class="button button-large faq-modal-close">
                            <?php _e('Cancel', 'faq-generator-ai'); ?>
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Status Messages -->
            <div class="faq-message" style="display: none;"></div>
            
        </div>
        <?php
    }
    
    /**
     * Render single FAQ item
     */
    private function render_faq_item($item, $index) {
        $question = isset($item['name']) ? $item['name'] : '';
        $answer = isset($item['acceptedAnswer']['text']) ? $item['acceptedAnswer']['text'] : '';
        ?>
        <div class="faq-item-card" data-index="<?php echo $index; ?>" data-question="<?php echo esc_attr($question); ?>" data-answer="<?php echo esc_attr($answer); ?>">
            <div class="faq-item-drag">
                <span class="dashicons dashicons-menu"></span>
            </div>
            <div class="faq-item-number"><?php echo ($index + 1); ?></div>
            <div class="faq-item-content">
                <div class="faq-item-question">
                    <strong><?php echo esc_html($question); ?></strong>
                </div>
                <div class="faq-item-answer">
                    <?php echo esc_html(wp_trim_words($answer, 20)); ?>
                </div>
            </div>
            <div class="faq-item-actions">
                <button type="button" class="faq-item-btn faq-item-edit" title="<?php _e('Edit', 'faq-generator-ai'); ?>">
                    <span class="dashicons dashicons-edit"></span>
                </button>
                <button type="button" class="faq-item-btn faq-item-delete" title="<?php _e('Delete', 'faq-generator-ai'); ?>">
                    <span class="dashicons dashicons-trash"></span>
                </button>
            </div>
        </div>
        <?php
    }
    
    /**
     * Save meta box data when post is saved
     */
    public function save_meta_box($post_id, $post) {
        // Debug start
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('[FAQ Metabox] Save started for post ' . $post_id);
        }
        
        // Security checks
        if (!isset($_POST['faq_schema_metabox_nonce']) || 
            !wp_verify_nonce($_POST['faq_schema_metabox_nonce'], 'faq_schema_metabox_save')) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('[FAQ Metabox] Nonce check failed');
            }
            return;
        }
        
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('[FAQ Metabox] Skipping autosave');
            }
            return;
        }
        
        if (!current_user_can('edit_post', $post_id)) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('[FAQ Metabox] User cannot edit post');
            }
            return;
        }
        
        // Check if schema data is set
        if (isset($_POST['faq_schema_data'])) {
            $schema_data = wp_unslash($_POST['faq_schema_data']);
            
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('[FAQ Metabox] Schema data received: ' . strlen($schema_data) . ' bytes');
            }
            
            if (empty($schema_data)) {
                // *** CHANGED: Don't delete if empty, preserve existing ***
                $existing = get_post_meta($post_id, '_faq_gen_ai_schema', true);
                if (!empty($existing)) {
                    if (defined('WP_DEBUG') && WP_DEBUG) {
                        error_log('[FAQ Metabox] Schema data empty but existing schema found - preserving');
                    }
                    // Don't delete - preserve existing schema
                    return;
                }
                
                // Only delete if explicitly empty and no existing schema
                delete_post_meta($post_id, '_faq_gen_ai_schema');
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log('[FAQ Metabox] Schema deleted (no existing)');
                }
            } else {
                // Validate JSON
                $decoded = json_decode($schema_data, true);
                if (json_last_error() === JSON_ERROR_NONE && 
                    isset($decoded['@type']) && 
                    $decoded['@type'] === 'FAQPage') {
                    
                    // Save valid schema
                    $saved = update_post_meta($post_id, '_faq_gen_ai_schema', $schema_data);
                    
                    if (defined('WP_DEBUG') && WP_DEBUG) {
                        error_log('[FAQ Metabox] Schema saved: ' . ($saved ? 'success' : 'failed'));
                        
                        // Verify immediately
                        wp_cache_delete($post_id, 'post_meta');
                        $verify = get_post_meta($post_id, '_faq_gen_ai_schema', true);
                        error_log('[FAQ Metabox] Verification: ' . (!empty($verify) ? 'found' : 'NOT FOUND'));
                    }
                } else {
                    if (defined('WP_DEBUG') && WP_DEBUG) {
                        error_log('[FAQ Metabox] Invalid JSON: ' . json_last_error_msg());
                    }
                }
            }
        } else {
            // *** NEW: POST data not set - preserve existing ***
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('[FAQ Metabox] No schema data in POST - preserving existing');
            }
        }
    }
    
}
