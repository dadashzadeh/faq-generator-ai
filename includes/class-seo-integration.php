<?php

if (!defined('ABSPATH')) {
    exit;
}

class FAQ_Gen_AI_SEO_Integration {
    
    private $enabled = false;
    private $schema_output = false;
    
    public function __construct() {
        $this->enabled = (get_option('faq_gen_ai_seo_integration', '1') === '1');
        
        add_action('wp_head', array($this, 'output_faq_schema'), 99);
        add_action('wp_head', array($this, 'check_schema_output'), 999);
        
        if (!$this->enabled) {
            return;
        }
        
        if (defined('RANK_MATH_VERSION')) {
            add_filter('rank_math/json_ld', array($this, 'add_faq_to_rankmath'), 99, 2);
        }
        
        if (defined('WPSEO_VERSION')) {
            add_filter('wpseo_schema_graph_pieces', array($this, 'add_faq_to_yoast'), 11, 2);
            add_filter('wpseo_schema_graph', array($this, 'add_faq_to_yoast_graph'), 11, 2);
        }
    }
    
    private function is_supported_post_type() {
        global $post;
        
        if (!$post) {
            return false;
        }
        
        $supported_types = get_option('faq_gen_ai_supported_post_types', array('post', 'page', 'product'));
        return in_array(get_post_type($post), $supported_types);
    }
    
    private function get_faq_schema($post_id) {
        wp_cache_delete($post_id, 'post_meta');
        
        $faq_schema = get_post_meta($post_id, '_faq_gen_ai_schema', true);
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('[FAQ Schema Debug] Getting schema for post ' . $post_id);
            error_log('[FAQ Schema Debug] Schema found: ' . (!empty($faq_schema) ? 'yes' : 'no'));
        }
        
        if (empty($faq_schema)) {
            return false;
        }
        
        $schema_data = json_decode($faq_schema, true);
        
        if (!$schema_data || 
            !isset($schema_data['@type']) || 
            $schema_data['@type'] !== 'FAQPage' ||
            !isset($schema_data['mainEntity']) ||
            empty($schema_data['mainEntity'])) {
            
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('[FAQ Schema Debug] Schema validation failed');
            }
            return false;
        }
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('[FAQ Schema Debug] Schema validated - ' . count($schema_data['mainEntity']) . ' FAQs');
        }
        
        return $schema_data;
    }
    
    /**
     * ✅ NEW: Add post metadata to schema
     */
    private function enrich_schema_with_metadata($schema_data, $post_id) {
        $post = get_post($post_id);
        
        if (!$post) {
            return $schema_data;
        }
        
        // Add post title
        $schema_data['name'] = get_the_title($post_id);
        
        // Add URL
        $schema_data['url'] = get_permalink($post_id);
        
        // Add dates (in ISO 8601 format with timezone)
        $published = get_the_date('c', $post_id);
        $modified = get_the_modified_date('c', $post_id);
        
        if ($published) {
            $schema_data['datePublished'] = $published;
        }
        
        if ($modified) {
            $schema_data['dateModified'] = $modified;
        }
        
        // Move @context to end (like Parspack)
        if (isset($schema_data['@context'])) {
            $context = $schema_data['@context'];
            unset($schema_data['@context']);
            $schema_data['@context'] = $context;
        }
        
        return $schema_data;
    }
    
    public function output_faq_schema() {
        if (!is_singular()) {
            return;
        }
        
        global $post;
        
        if (!$post || !$this->is_supported_post_type()) {
            return;
        }
        
        $schema_data = $this->get_faq_schema($post->ID);
        
        if (!$schema_data) {
            return;
        }
        
        $seo_enabled = $this->enabled;
        $has_rankmath = defined('RANK_MATH_VERSION');
        $has_yoast = defined('WPSEO_VERSION');
        
        if ($seo_enabled && ($has_rankmath || $has_yoast)) {
            $this->stored_schema = $schema_data;
            $this->stored_post_id = $post->ID;
            
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('[FAQ Schema] SEO plugin detected');
            }
            return;
        }
        
        $this->output_schema_markup($schema_data, $post->ID);
    }
    
    public function check_schema_output() {
        if (!isset($this->stored_schema) || $this->schema_output) {
            return;
        }
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('[FAQ Schema] Using fallback output');
        }
        
        $this->output_schema_markup($this->stored_schema, $this->stored_post_id, true);
    }
    
    /**
     * ✅ UPDATED: Output with metadata
     */
    private function output_schema_markup($schema_data, $post_id, $is_fallback = false) {
        if ($this->schema_output) {
            return;
        }
        
        // Add context if not present
        if (!isset($schema_data['@context'])) {
            $schema_data['@context'] = 'https://schema.org';
        }
        
        // ✅ Enrich with metadata
        $schema_data = $this->enrich_schema_with_metadata($schema_data, $post_id);
        
        // Output schema
        $comment = $is_fallback ? 
            '<!-- FAQ Schema by FAQ Generator AI (Fallback) -->' : 
            '<!-- FAQ Schema by FAQ Generator AI -->';
            
        echo "\n" . $comment . "\n";
        echo '<script type="application/ld+json">';
        echo wp_json_encode($schema_data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
        echo '</script>';
        echo "\n";
        
        $this->schema_output = true;
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('[FAQ Schema] Schema output successful (' . count($schema_data['mainEntity']) . ' FAQs)');
        }
    }
    
    /**
     * ✅ UPDATED: RankMath integration with metadata
     */
    public function add_faq_to_rankmath($data, $jsonld) {
        global $post;
        
        if (!is_singular() || !$post || !$this->is_supported_post_type()) {
            return $data;
        }
        
        $schema_data = $this->get_faq_schema($post->ID);
        
        if (!$schema_data) {
            return $data;
        }
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('[RankMath FAQ] Adding FAQ schema to @graph');
        }
        
        if (!isset($data['@graph'])) {
            $data['@graph'] = array();
        }
        
        // Check if FAQ already exists
        $faq_exists = false;
        foreach ($data['@graph'] as $item) {
            if (isset($item['@type']) && $item['@type'] === 'FAQPage') {
                $faq_exists = true;
                break;
            }
        }
        
        if (!$faq_exists) {
            // ✅ Build enriched schema
            $faq_schema = array(
                '@type' => 'FAQPage',
                'name' => get_the_title($post->ID),
                'url' => get_permalink($post->ID),
                'mainEntity' => $schema_data['mainEntity']
            );
            
            // Add dates
            $published = get_the_date('c', $post->ID);
            $modified = get_the_modified_date('c', $post->ID);
            
            if ($published) {
                $faq_schema['datePublished'] = $published;
            }
            
            if ($modified) {
                $faq_schema['dateModified'] = $modified;
            }
            
            // Add to @graph
            $data['@graph'][] = $faq_schema;
            
            $this->schema_output = true;
            
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('[RankMath FAQ] Successfully added ' . count($schema_data['mainEntity']) . ' FAQs with metadata');
            }
        }
        
        return $data;
    }
    
    public function add_faq_to_yoast($pieces, $context) {
        global $post;
        
        if (!is_singular() || !$post || !$this->is_supported_post_type()) {
            return $pieces;
        }
        
        $schema_data = $this->get_faq_schema($post->ID);
        
        if ($schema_data) {
            // Enrich with metadata
            $schema_data = $this->enrich_schema_with_metadata($schema_data, $post->ID);
            
            if (class_exists('WPSEO_Graph_Piece')) {
                if (file_exists(FAQ_GEN_AI_PLUGIN_DIR . 'includes/class-yoast-faq-piece.php')) {
                    require_once FAQ_GEN_AI_PLUGIN_DIR . 'includes/class-yoast-faq-piece.php';
                    $pieces[] = new FAQ_Gen_AI_Yoast_FAQ_Piece($schema_data, $context);
                    $this->schema_output = true;
                }
            }
        }
        
        return $pieces;
    }
    
    public function add_faq_to_yoast_graph($data, $context) {
        global $post;
        
        if (!is_singular() || !$post || !$this->is_supported_post_type()) {
            return $data;
        }
        
        $schema_data = $this->get_faq_schema($post->ID);
        
        if ($schema_data) {
            $faq_exists = false;
            if (isset($data['@graph'])) {
                foreach ($data['@graph'] as $item) {
                    if (isset($item['@type']) && $item['@type'] === 'FAQPage') {
                        $faq_exists = true;
                        break;
                    }
                }
            }
            
            if (!$faq_exists) {
                // Enrich with metadata
                $schema_data = $this->enrich_schema_with_metadata($schema_data, $post->ID);
                
                if (!isset($data['@graph'])) {
                    $data['@graph'] = array();
                }
                
                $data['@graph'][] = $schema_data;
                $this->schema_output = true;
            }
        }
        
        return $data;
    }
    
    public function is_active() {
        return $this->enabled && (defined('RANK_MATH_VERSION') || defined('WPSEO_VERSION'));
    }
}
