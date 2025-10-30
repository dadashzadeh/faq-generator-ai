<?php

if (!defined('ABSPATH')) {
    exit;
}

class FAQ_Gen_AI_Settings {
    
    public function __construct() {
        add_action('admin_menu', array($this, 'add_settings_page'));
        add_action('admin_init', array($this, 'register_settings'));
    }
    
    /**
     * Add settings page to WordPress admin
     */
    public function add_settings_page() {
        add_options_page(
            __('FAQ Generator AI Settings', 'faq-generator-ai'),
            __('FAQ Generator AI', 'faq-generator-ai'),
            'manage_options',
            'faq-gen-ai-settings',
            array($this, 'render_settings_page')
        );
    }
    
    /**
     * Register all settings with proper sanitization
     */
    public function register_settings() {
        // API Configuration Section
        add_settings_section(
            'faq_gen_ai_api_section',
            __('API Configuration', 'faq-generator-ai'),
            array($this, 'render_api_section'),
            'faq-gen-ai-settings'
        );
        
        // API Key
        register_setting(
            'faq_gen_ai_settings',
            'faq_gen_ai_api_key',
            array(
                'type' => 'string',
                'sanitize_callback' => array($this, 'sanitize_api_key'),
                'default' => ''
            )
        );
        
        add_settings_field(
            'faq_gen_ai_api_key',
            __('API Key', 'faq-generator-ai'),
            array($this, 'render_api_key_field'),
            'faq-gen-ai-settings',
            'faq_gen_ai_api_section'
        );
        
        // Base URL
        register_setting(
            'faq_gen_ai_settings',
            'faq_gen_ai_base_url',
            array(
                'type' => 'string',
                'sanitize_callback' => array($this, 'sanitize_url'),
                'default' => 'https://api.openai.com/v1'
            )
        );
        
        add_settings_field(
            'faq_gen_ai_base_url',
            __('API Base URL', 'faq-generator-ai'),
            array($this, 'render_base_url_field'),
            'faq-gen-ai-settings',
            'faq_gen_ai_api_section'
        );
        
        // Model Configuration Section
        add_settings_section(
            'faq_gen_ai_model_section',
            __('Model Configuration', 'faq-generator-ai'),
            array($this, 'render_model_section'),
            'faq-gen-ai-settings'
        );
        
        // Model
        register_setting(
            'faq_gen_ai_settings',
            'faq_gen_ai_model',
            array(
                'type' => 'string',
                'sanitize_callback' => array($this, 'sanitize_model'),
                'default' => 'gpt-3.5-turbo'
            )
        );
        
        add_settings_field(
            'faq_gen_ai_model',
            __('Model', 'faq-generator-ai'),
            array($this, 'render_model_field'),
            'faq-gen-ai-settings',
            'faq_gen_ai_model_section'
        );
        
        // Temperature
        register_setting(
            'faq_gen_ai_settings',
            'faq_gen_ai_temperature',
            array(
                'type' => 'number',
                'sanitize_callback' => array($this, 'sanitize_temperature'),
                'default' => 0.5
            )
        );
        
        add_settings_field(
            'faq_gen_ai_temperature',
            __('Temperature', 'faq-generator-ai'),
            array($this, 'render_temperature_field'),
            'faq-gen-ai-settings',
            'faq_gen_ai_model_section'
        );
        
        // Max Tokens
        register_setting(
            'faq_gen_ai_settings',
            'faq_gen_ai_max_tokens',
            array(
                'type' => 'integer',
                'sanitize_callback' => array($this, 'sanitize_max_tokens'),
                'default' => 8000
            )
        );
        
        add_settings_field(
            'faq_gen_ai_max_tokens',
            __('Max Tokens', 'faq-generator-ai'),
            array($this, 'render_max_tokens_field'),
            'faq-gen-ai-settings',
            'faq_gen_ai_model_section'
        );
        
        // Output Configuration Section
        add_settings_section(
            'faq_gen_ai_output_section',
            __('Output Configuration', 'faq-generator-ai'),
            array($this, 'render_output_section'),
            'faq-gen-ai-settings'
        );
        
        // Default Count
        register_setting(
            'faq_gen_ai_settings',
            'faq_gen_ai_default_count',
            array(
                'type' => 'integer',
                'sanitize_callback' => array($this, 'sanitize_count'),
                'default' => 5
            )
        );
        
        add_settings_field(
            'faq_gen_ai_default_count',
            __('Default FAQ Count', 'faq-generator-ai'),
            array($this, 'render_count_field'),
            'faq-gen-ai-settings',
            'faq_gen_ai_output_section'
        );
        
        // Output Format
        register_setting(
            'faq_gen_ai_settings',
            'faq_gen_ai_output_format',
            array(
                'type' => 'string',
                'sanitize_callback' => array($this, 'sanitize_output_format'),
                'default' => 'both'
            )
        );
        
        add_settings_field(
            'faq_gen_ai_output_format',
            __('Default Output Format', 'faq-generator-ai'),
            array($this, 'render_output_format_field'),
            'faq-gen-ai-settings',
            'faq_gen_ai_output_section'
        );
        
        // Show Title
        register_setting(
            'faq_gen_ai_settings',
            'faq_gen_ai_show_title',
            array(
                'type' => 'string',
                'sanitize_callback' => array($this, 'sanitize_checkbox'),
                'default' => '1'
            )
        );
        
        add_settings_field(
            'faq_gen_ai_show_title',
            __('Show FAQ Title', 'faq-generator-ai'),
            array($this, 'render_show_title_field'),
            'faq-gen-ai-settings',
            'faq_gen_ai_output_section'
        );
        
        // Prompt Configuration Section
        add_settings_section(
            'faq_gen_ai_prompt_section',
            __('Prompt Configuration', 'faq-generator-ai'),
            array($this, 'render_prompt_section'),
            'faq-gen-ai-settings'
        );
        
        // System Prompt
        register_setting(
            'faq_gen_ai_settings',
            'faq_gen_ai_system_prompt',
            array(
                'type' => 'string',
                'sanitize_callback' => array($this, 'sanitize_textarea'),
                'default' => 'You are an expert FAQ generator. Output only in simple Markdown format. Be concise and clear.'
            )
        );
        
        add_settings_field(
            'faq_gen_ai_system_prompt',
            __('System Prompt', 'faq-generator-ai'),
            array($this, 'render_system_prompt_field'),
            'faq-gen-ai-settings',
            'faq_gen_ai_prompt_section'
        );
        
        // Default Prompt
        register_setting(
            'faq_gen_ai_settings',
            'faq_gen_ai_default_prompt',
            array(
                'type' => 'string',
                'sanitize_callback' => array($this, 'sanitize_textarea'),
                'default' => 'Generate FAQs based on: [content]'
            )
        );
        
        add_settings_field(
            'faq_gen_ai_default_prompt',
            __('Default Prompt', 'faq-generator-ai'),
            array($this, 'render_default_prompt_field'),
            'faq-gen-ai-settings',
            'faq_gen_ai_prompt_section'
        );
        
        // SEO Integration Section
        add_settings_section(
            'faq_gen_ai_seo_section',
            __('SEO Integration', 'faq-generator-ai'),
            array($this, 'render_seo_section'),
            'faq-gen-ai-settings'
        );
        
        // SEO Integration Enable
        register_setting(
            'faq_gen_ai_settings',
            'faq_gen_ai_seo_integration',
            array(
                'type' => 'string',
                'sanitize_callback' => array($this, 'sanitize_checkbox'),
                'default' => '1'
            )
        );
        
        add_settings_field(
            'faq_gen_ai_seo_integration',
            __('Enable SEO Integration', 'faq-generator-ai'),
            array($this, 'render_seo_integration_field'),
            'faq-gen-ai-settings',
            'faq_gen_ai_seo_section'
        );
    }
    
    /**
     * Sanitization callbacks
     */
    
    public function sanitize_api_key($input) {
        return sanitize_text_field(trim($input));
    }
    
    public function sanitize_url($input) {
        $url = esc_url_raw(trim($input));
        // Ensure it's a valid URL
        if (empty($url) || !filter_var($url, FILTER_VALIDATE_URL)) {
            add_settings_error(
                'faq_gen_ai_base_url',
                'invalid_url',
                __('Invalid API Base URL. Please enter a valid URL.', 'faq-generator-ai'),
                'error'
            );
            return 'https://api.openai.com/v1';
        }
        return $url;
    }
    
    public function sanitize_model($input) {
        $allowed_models = array(
            'gpt-3.5-turbo',
            'gpt-4',
            'gpt-4-turbo',
            'gpt-4-turbo-preview',
            'gpt-4o',
            'gpt-4o-mini',
            'gpt-5-nano'
        );
        
        $input = sanitize_text_field(trim($input));
        
        // Allow custom models
        if (!in_array($input, $allowed_models) && !empty($input)) {
            // Custom model - just sanitize
            return preg_replace('/[^a-zA-Z0-9\-_.]/', '', $input);
        }
        
        return in_array($input, $allowed_models) ? $input : 'gpt-3.5-turbo';
    }
    
    public function sanitize_temperature($input) {
        $temp = floatval($input);
        
        if ($temp < 0 || $temp > 2) {
            add_settings_error(
                'faq_gen_ai_temperature',
                'invalid_temperature',
                __('Temperature must be between 0 and 2.', 'faq-generator-ai'),
                'error'
            );
            return 0.5;
        }
        
        return $temp;
    }
    
    public function sanitize_max_tokens($input) {
        $tokens = intval($input);
        
        if ($tokens < 100 || $tokens > 32000) {
            add_settings_error(
                'faq_gen_ai_max_tokens',
                'invalid_tokens',
                __('Max Tokens must be between 100 and 32000.', 'faq-generator-ai'),
                'error'
            );
            return 8000;
        }
        
        return $tokens;
    }
    
    public function sanitize_count($input) {
        $count = intval($input);
        
        if ($count < 3 || $count > 10) {
            add_settings_error(
                'faq_gen_ai_default_count',
                'invalid_count',
                __('FAQ count must be between 3 and 10.', 'faq-generator-ai'),
                'error'
            );
            return 5;
        }
        
        return $count;
    }
    
    public function sanitize_output_format($input) {
        $allowed_formats = array('both', 'html', 'schema');
        $input = sanitize_text_field($input);
        return in_array($input, $allowed_formats) ? $input : 'both';
    }
    
    public function sanitize_checkbox($input) {
        return ($input === '1' || $input === 1 || $input === true) ? '1' : '0';
    }
    
    public function sanitize_textarea($input) {
        return sanitize_textarea_field(trim($input));
    }
    
    /**
     * Section render callbacks
     */
    
    public function render_api_section() {
        echo '<p>' . esc_html__('Configure your AI API settings. You need an OpenAI API key or compatible API endpoint.', 'faq-generator-ai') . '</p>';
    }
    
    public function render_model_section() {
        echo '<p>' . esc_html__('Configure the AI model and generation parameters.', 'faq-generator-ai') . '</p>';
    }
    
    public function render_output_section() {
        echo '<p>' . esc_html__('Configure the default output settings for generated FAQs.', 'faq-generator-ai') . '</p>';
    }
    
    public function render_prompt_section() {
        echo '<p>' . esc_html__('Customize the prompts used for FAQ generation. Use shortcodes: [content], [title], [excerpt]', 'faq-generator-ai') . '</p>';
    }
    
    public function render_seo_section() {
        echo '<p>' . esc_html__('Configure integration with SEO plugins like RankMath and Yoast SEO.', 'faq-generator-ai') . '</p>';
    }
    
    /**
     * Field render callbacks
     */
    
    public function render_api_key_field() {
        $value = get_option('faq_gen_ai_api_key', '');
        $display_value = !empty($value) ? substr($value, 0, 10) . '...' : '';
        ?>
        <input type="password" 
               name="faq_gen_ai_api_key" 
               value="<?php echo esc_attr($value); ?>" 
               class="regular-text" 
               placeholder="sk-..." 
               autocomplete="off" />
        <?php if (!empty($value)): ?>
            <span class="description"><?php esc_html_e('Current key: ', 'faq-generator-ai'); echo esc_html($display_value); ?></span>
        <?php endif; ?>
        <p class="description">
            <?php 
            printf(
                /* translators: %s: OpenAI API keys URL */
                esc_html__('Get your API key from %s', 'faq-generator-ai'),
                '<a href="https://platform.openai.com/api-keys" target="_blank">OpenAI Platform</a>'
            );
            ?>
        </p>
        <?php
    }
    
    public function render_base_url_field() {
        $value = get_option('faq_gen_ai_base_url', 'https://api.openai.com/v1');
        ?>
        <input type="url" 
               name="faq_gen_ai_base_url" 
               value="<?php echo esc_attr($value); ?>" 
               class="regular-text" 
               placeholder="https://api.openai.com/v1" />
        <p class="description">
            <?php esc_html_e('Default: https://api.openai.com/v1 (Change only if using a custom endpoint)', 'faq-generator-ai'); ?>
        </p>
        <?php
    }
    
    public function render_model_field() {
        $value = get_option('faq_gen_ai_model', 'gpt-3.5-turbo');
        $models = array(
            'gpt-3.5-turbo' => 'GPT-3.5 Turbo (Fast, Economical)',
            'gpt-4' => 'GPT-4 (Powerful)',
            'gpt-4-turbo' => 'GPT-4 Turbo (Fast & Powerful)',
            'gpt-4-turbo-preview' => 'GPT-4 Turbo Preview',
            'gpt-4o' => 'GPT-4o (Latest)',
            'gpt-4o-mini' => 'GPT-4o Mini (Fast)',
            'gpt-5-nano' => 'GPT-5 Nano (Reasoning)',
        );
        ?>
        <select name="faq_gen_ai_model" class="regular-text">
            <?php foreach ($models as $model_value => $model_label): ?>
                <option value="<?php echo esc_attr($model_value); ?>" <?php selected($value, $model_value); ?>>
                    <?php echo esc_html($model_label); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <p class="description">
            <?php esc_html_e('Select the AI model. GPT-3.5 Turbo is recommended for most use cases.', 'faq-generator-ai'); ?>
        </p>
        <?php
    }
    
    public function render_temperature_field() {
        $value = get_option('faq_gen_ai_temperature', 0.5);
        ?>
        <input type="number" 
               name="faq_gen_ai_temperature" 
               value="<?php echo esc_attr($value); ?>" 
               min="0" 
               max="2" 
               step="0.1" 
               class="small-text" />
        <p class="description">
            <?php esc_html_e('Controls randomness. Lower values (0.2-0.5) for focused output, higher (0.7-1.0) for creative output.', 'faq-generator-ai'); ?>
        </p>
        <?php
    }
    
    public function render_max_tokens_field() {
        $value = get_option('faq_gen_ai_max_tokens', 8000);
        ?>
        <input type="number" 
               name="faq_gen_ai_max_tokens" 
               value="<?php echo esc_attr($value); ?>" 
               min="100" 
               max="32000" 
               step="100" 
               class="small-text" />
        <p class="description">
            <?php esc_html_e('Maximum tokens for generation. Increase if output is truncated. (100-32000)', 'faq-generator-ai'); ?>
        </p>
        <?php
    }
    
    public function render_count_field() {
        $value = get_option('faq_gen_ai_default_count', 5);
        ?>
        <input type="number" 
               name="faq_gen_ai_default_count" 
               value="<?php echo esc_attr($value); ?>" 
               min="3" 
               max="10" 
               step="1" 
               class="small-text" />
        <p class="description">
            <?php esc_html_e('Default number of FAQs to generate (3-10).', 'faq-generator-ai'); ?>
        </p>
        <?php
    }
    
    public function render_output_format_field() {
        $value = get_option('faq_gen_ai_output_format', 'both');
        $formats = array(
            'both' => __('HTML + Schema (Recommended)', 'faq-generator-ai'),
            'html' => __('HTML Only', 'faq-generator-ai'),
            'schema' => __('Schema Only', 'faq-generator-ai'),
        );
        ?>
        <select name="faq_gen_ai_output_format" class="regular-text">
            <?php foreach ($formats as $format_value => $format_label): ?>
                <option value="<?php echo esc_attr($format_value); ?>" <?php selected($value, $format_value); ?>>
                    <?php echo esc_html($format_label); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <p class="description">
            <?php esc_html_e('Choose the default output format. Both HTML and Schema is recommended for SEO.', 'faq-generator-ai'); ?>
        </p>
        <?php
    }
    
    public function render_show_title_field() {
        $value = get_option('faq_gen_ai_show_title', '1');
        ?>
        <label>
            <input type="checkbox" 
                   name="faq_gen_ai_show_title" 
                   value="1" 
                   <?php checked($value, '1'); ?> />
            <?php esc_html_e('Show "Frequently Asked Questions" title', 'faq-generator-ai'); ?>
        </label>
        <?php
    }
    
    public function render_system_prompt_field() {
        $value = get_option('faq_gen_ai_system_prompt', 'You are an expert FAQ generator. Output only in simple Markdown format. Be concise and clear.');
        ?>
        <textarea name="faq_gen_ai_system_prompt" 
                  rows="4" 
                  class="large-text"><?php echo esc_textarea($value); ?></textarea>
        <p class="description">
            <?php esc_html_e('System prompt that defines AI behavior. Advanced users only.', 'faq-generator-ai'); ?>
        </p>
        <?php
    }
    
    public function render_default_prompt_field() {
        $value = get_option('faq_gen_ai_default_prompt', 'Generate FAQs based on: [content]');
        ?>
        <textarea name="faq_gen_ai_default_prompt" 
                  rows="4" 
                  class="large-text"><?php echo esc_textarea($value); ?></textarea>
        <p class="description">
            <?php esc_html_e('Default prompt template. Available shortcodes: [content], [title], [excerpt]', 'faq-generator-ai'); ?>
        </p>
        <?php
    }
    
    public function render_seo_integration_field() {
        $value = get_option('faq_gen_ai_seo_integration', '1');
        $has_rankmath = defined('RANK_MATH_VERSION');
        $has_yoast = defined('WPSEO_VERSION');
        ?>
        <label>
            <input type="checkbox" 
                   name="faq_gen_ai_seo_integration" 
                   value="1" 
                   <?php checked($value, '1'); ?> />
            <?php esc_html_e('Enable automatic schema integration with SEO plugins', 'faq-generator-ai'); ?>
        </label>
        <p class="description">
            <?php
            if ($has_rankmath) {
                echo '<span style="color: green;">✓ ' . esc_html__('RankMath detected', 'faq-generator-ai') . '</span><br>';
            }
            if ($has_yoast) {
                echo '<span style="color: green;">✓ ' . esc_html__('Yoast SEO detected', 'faq-generator-ai') . '</span><br>';
            }
            if (!$has_rankmath && !$has_yoast) {
                esc_html_e('No SEO plugin detected. Schema will be output directly in page <head>.', 'faq-generator-ai');
            }
            ?>
        </p>
        <?php
    }
    
    /**
     * Render settings page
     */
    public function render_settings_page() {
        if (!current_user_can('manage_options')) {
            return;
        }
        
        // Handle form submission
        if (isset($_GET['settings-updated'])) {
            add_settings_error(
                'faq_gen_ai_messages',
                'faq_gen_ai_message',
                __('Settings saved successfully.', 'faq-generator-ai'),
                'success'
            );
        }
        
        settings_errors('faq_gen_ai_messages');
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            
            <form method="post" action="options.php">
                <?php
                settings_fields('faq_gen_ai_settings');
                do_settings_sections('faq-gen-ai-settings');
                submit_button(__('Save Settings', 'faq-generator-ai'));
                ?>
            </form>
            
            <hr>
            
            <h2><?php esc_html_e('Plugin Information', 'faq-generator-ai'); ?></h2>
            <table class="widefat">
                <tbody>
                    <tr>
                        <td><strong><?php esc_html_e('Version', 'faq-generator-ai'); ?>:</strong></td>
                        <td><?php echo esc_html(FAQ_GEN_AI_VERSION); ?></td>
                    </tr>
                    <tr>
                        <td><strong><?php esc_html_e('Documentation', 'faq-generator-ai'); ?>:</strong></td>
                        <td><a href="https://dadashzadeh.org/docs/faq-generator-ai/" target="_blank"><?php esc_html_e('View Documentation', 'faq-generator-ai'); ?></a></td>
                    </tr>
                    <tr>
                        <td><strong><?php esc_html_e('Support', 'faq-generator-ai'); ?>:</strong></td>
                        <td><a href="https://wordpress.org/support/plugin/faq-generator-ai/" target="_blank"><?php esc_html_e('Get Support', 'faq-generator-ai'); ?></a></td>
                    </tr>
                </tbody>
            </table>
        </div>
        <?php
    }
}
