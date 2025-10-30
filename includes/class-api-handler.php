<?php

if (!defined('ABSPATH')) {
    exit;
}

class FAQ_Gen_AI_API_Handler {
    
    private const CACHE_GROUP = 'faq_gen_ai';
    private const CACHE_EXPIRATION = 3600;
    private const MAX_CONTENT_LENGTH = 2000;
    
    private $config = array();
    private $debug_mode = false;
    
    public function __construct() {
        $this->debug_mode = (defined('WP_DEBUG') && WP_DEBUG);
        $this->load_config();
    }
    
    private function load_config() {
        $this->config = array(
            'base_url'      => get_option('faq_gen_ai_base_url', 'https://api.openai.com/v1'),
            'model'         => get_option('faq_gen_ai_model', 'gpt-5-nano'),
            'temperature'   => floatval(get_option('faq_gen_ai_temperature', 0.5)),
            'api_key'       => get_option('faq_gen_ai_api_key'),
            'system_prompt' => get_option('faq_gen_ai_system_prompt', $this->get_default_system_prompt()),
            'max_tokens'    => 8000,
        );
    }
    
    private function debug_log($message, $data = null) {
        if (!$this->debug_mode) {
            return;
        }
        
        $log_message = '[FAQ Gen AI] ' . $message;
        if ($data !== null) {
            if (is_array($data) || is_object($data)) {
                $log_message .= ': ' . print_r($data, true);
            } else {
                $log_message .= ': ' . $data;
            }
        }
        error_log($log_message);
    }
    
    private function get_default_system_prompt() {
        return 'You are an FAQ generator. Output only in simple Markdown format. Be concise and clear.';
    }
    
    /**
     * Main method to generate FAQ
     */
    public function generate_faq($prompt, $output_format = 'both', $post_id = 0, $faq_count = 5) {
        $validation = $this->validate_inputs($prompt, $output_format, $faq_count);
        if (is_wp_error($validation)) {
            return $validation;
        }
        
        $prompt = $this->optimize_prompt_length($prompt);
        $this->debug_log('Optimized prompt length', strlen($prompt));
        
        $cache_key = $this->generate_cache_key($prompt, $output_format, $faq_count);
        $cached_result = $this->get_cached_result($cache_key);
        if ($cached_result !== false) {
            $this->debug_log('Using cached result');
            return $this->prepare_final_output($cached_result, $output_format, $post_id);
        }
        
        $messages = $this->build_markdown_messages($prompt, $faq_count);
        
        $api_response = $this->make_api_request($messages);
        if (is_wp_error($api_response)) {
            return $api_response;
        }
        
        $processed = $this->process_markdown_response($api_response, $output_format, $post_id, $faq_count);
        if (is_wp_error($processed)) {
            return $processed;
        }
        
        $this->cache_result($cache_key, $processed);
        
        return $processed;
    }
    
    private function validate_inputs($prompt, $output_format, $faq_count) {
        if (empty($this->config['api_key'])) {
            return new WP_Error('no_api_key', __('API key is not configured.', 'faq-generator-ai'));
        }
        
        if (empty($prompt) || strlen($prompt) < 10) {
            return new WP_Error('invalid_prompt', __('Prompt is too short.', 'faq-generator-ai'));
        }
        
        $valid_formats = array('both', 'html', 'schema');
        if (!in_array($output_format, $valid_formats)) {
            return new WP_Error('invalid_format', __('Invalid output format.', 'faq-generator-ai'));
        }
        
        if ($faq_count < 3 || $faq_count > 10) {
            return new WP_Error('invalid_count', __('FAQ count must be between 3 and 10.', 'faq-generator-ai'));
        }
        
        return true;
    }
    
    private function optimize_prompt_length($prompt) {
        if (strlen($prompt) > self::MAX_CONTENT_LENGTH) {
            $truncated = substr($prompt, 0, self::MAX_CONTENT_LENGTH);
            $last_period = strrpos($truncated, '.');
            if ($last_period !== false) {
                $truncated = substr($truncated, 0, $last_period + 1);
            }
            $prompt = $truncated;
        }
        
        return trim(preg_replace('/\s+/', ' ', $prompt));
    }
    
    /**
     * ✅ NEW: Build Markdown prompt messages
     */
    private function build_markdown_messages($user_prompt, $faq_count) {
        return array(
            array('role' => 'system', 'content' => $this->config['system_prompt']),
            array('role' => 'user', 'content' => $this->build_markdown_prompt($user_prompt, $faq_count))
        );
    }
    
    /**
     * ✅ NEW: Build simple Markdown prompt
     */
    private function build_markdown_prompt($user_prompt, $faq_count) {
        return "Create exactly {$faq_count} frequently asked questions about: {$user_prompt}

# OUTPUT FORMAT

Use simple Markdown. Each FAQ should be:

## Question text here?
Answer text here (2-3 sentences).

## Question text here?
Answer text here (2-3 sentences).

# EXAMPLE

## What is WordPress?
WordPress is a free and open-source content management system. It allows users to create websites and blogs easily without coding knowledge.

## How do I install plugins?
Go to Plugins → Add New in your WordPress dashboard. Search for the plugin you want and click Install, then Activate.

# REQUIREMENTS

1. Output EXACTLY {$faq_count} questions
2. Use ## for questions (heading level 2)
3. Keep answers short and clear (2-3 sentences max)
4. Use proper grammar and punctuation
5. If input is in Persian/English, output in that language
6. No extra formatting, no code blocks, just plain Markdown";
    }
    
    private function make_api_request($messages) {
        $this->debug_log('Making API request to', $this->config['base_url']);
        
        $request_body = array(
            'model'       => $this->config['model'],
            'messages'    => $messages,
            'temperature' => $this->config['temperature'],
            'max_tokens'  => $this->config['max_tokens'],
        );
        
        $this->debug_log('Request body', array(
            'model' => $request_body['model'],
            'temperature' => $request_body['temperature'],
            'max_tokens' => $request_body['max_tokens'],
            'message_count' => count($messages)
        ));
        
        $response = wp_remote_post($this->config['base_url'] . '/chat/completions', array(
            'headers'   => array(
                'Authorization' => 'Bearer ' . $this->config['api_key'],
                'Content-Type'  => 'application/json',
            ),
            'body'      => wp_json_encode($request_body),
            'timeout'   => 120,
            'sslverify' => false,
        ));
        
        if (is_wp_error($response)) {
            $this->debug_log('API request failed', $response->get_error_message());
            return new WP_Error('api_connection_error', $response->get_error_message());
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);
        
        $this->debug_log('API Response Code', $response_code);
        $this->debug_log('API Response (first 1000 chars)', substr($response_body, 0, 1000));
        
        if ($response_code !== 200) {
            $body = json_decode($response_body, true);
            $error = isset($body['error']['message']) ? $body['error']['message'] : 'Unknown error';
            $this->debug_log('API error', array('code' => $response_code, 'error' => $error));
            return new WP_Error('api_error', sprintf('API error (%d): %s', $response_code, $error));
        }
        
        return $response;
    }
    
    /**
     * ✅ NEW: Process Markdown response
     */
    private function process_markdown_response($response, $output_format, $post_id, $faq_count) {
        $body = json_decode(wp_remote_retrieve_body($response), true);
        
        $this->debug_log('Processing Markdown response');
        
        if (!isset($body['choices'][0]['message']['content'])) {
            $this->debug_log('Invalid response structure');
            return new WP_Error('invalid_response', __('Invalid API response structure.', 'faq-generator-ai'));
        }
        
        $markdown = trim($body['choices'][0]['message']['content']);
        
        $this->debug_log('Markdown content length', strlen($markdown));
        $this->debug_log('Markdown preview (first 500 chars)', substr($markdown, 0, 500));
        
        if (empty($markdown)) {
            return new WP_Error('empty_response', __('API returned empty content.', 'faq-generator-ai'));
        }
        
        // Parse Markdown to structured data
        $faqs = $this->parse_markdown_to_faqs($markdown);
        
        if (empty($faqs)) {
            $this->debug_log('No FAQs extracted from Markdown');
            return new WP_Error('parse_error', __('Could not parse FAQs from AI response. Please try again.', 'faq-generator-ai'));
        }
        
        $this->debug_log('Extracted FAQs', count($faqs));
        
        // Generate HTML and Schema from structured data
        return $this->build_output_from_faqs($faqs, $output_format, $post_id);
    }
    
    /**
     * ✅ NEW: Parse Markdown to FAQ array
     */
    private function parse_markdown_to_faqs($markdown) {
        $faqs = array();
        
        // Clean markdown
        $markdown = trim($markdown);
        $markdown = preg_replace('/```markdown\s*|\s*```/i', '', $markdown);
        $markdown = preg_replace('/```\s*|\s*```/i', '', $markdown);
        
        $this->debug_log('Cleaned markdown length', strlen($markdown));
        
        // Method 1: Parse ## headings followed by text
        // Pattern: ## Question\nAnswer\n
        if (preg_match_all('/^##\s+(.+?)$\n((?:(?!^##).)+)/m', $markdown, $matches, PREG_SET_ORDER)) {
            $this->debug_log('Method 1: Found FAQs with ## pattern', count($matches));
            
            foreach ($matches as $match) {
                $question = trim($match[1]);
                $answer = trim($match[2]);
                
                // Clean up answer
                $answer = preg_replace('/\n+/', ' ', $answer);
                $answer = trim($answer);
                
                if (!empty($question) && !empty($answer) && strlen($answer) > 10) {
                    $faqs[] = array(
                        'question' => $question,
                        'answer' => $answer
                    );
                }
            }
        }
        
        // Method 2: Try alternative pattern with numbered list
        // 1. **Question?**\n   Answer
        if (empty($faqs)) {
            $this->debug_log('Method 2: Trying numbered list pattern');
            
            if (preg_match_all('/^\d+\.\s+\*\*(.+?)\*\*\s*\n\s+(.+?)(?=\n\d+\.|\n*$)/ms', $markdown, $matches, PREG_SET_ORDER)) {
                $this->debug_log('Method 2: Found FAQs', count($matches));
                
                foreach ($matches as $match) {
                    $question = trim($match[1]);
                    $answer = trim($match[2]);
                    
                    $answer = preg_replace('/\n+/', ' ', $answer);
                    
                    if (!empty($question) && !empty($answer)) {
                        $faqs[] = array(
                            'question' => $question,
                            'answer' => $answer
                        );
                    }
                }
            }
        }
        
        // Method 3: Try Q: A: pattern
        if (empty($faqs)) {
            $this->debug_log('Method 3: Trying Q:/A: pattern');
            
            if (preg_match_all('/\*\*Q:\s*(.+?)\*\*\s*\n\s*A:\s*(.+?)(?=\n\*\*Q:|\n*$)/ms', $markdown, $matches, PREG_SET_ORDER)) {
                $this->debug_log('Method 3: Found FAQs', count($matches));
                
                foreach ($matches as $match) {
                    $question = trim($match[1]);
                    $answer = trim($match[2]);
                    
                    $answer = preg_replace('/\n+/', ' ', $answer);
                    
                    if (!empty($question) && !empty($answer)) {
                        $faqs[] = array(
                            'question' => $question,
                            'answer' => $answer
                        );
                    }
                }
            }
        }
        
        // Method 4: Last resort - find any bold text followed by normal text
        if (empty($faqs)) {
            $this->debug_log('Method 4: Trying bold text pattern');
            
            if (preg_match_all('/\*\*(.+?)\*\*\s*\n\s*(.+?)(?=\n\*\*|\n*$)/ms', $markdown, $matches, PREG_SET_ORDER)) {
                $this->debug_log('Method 4: Found potential FAQs', count($matches));
                
                foreach ($matches as $match) {
                    $question = trim($match[1]);
                    $answer = trim($match[2]);
                    
                    $answer = preg_replace('/\n+/', ' ', $answer);
                    
                    // Filter out non-FAQ content
                    if (!empty($question) && !empty($answer) && 
                        strlen($answer) > 20 && 
                        !preg_match('/^(example|output|format|requirement)/i', $question)) {
                        $faqs[] = array(
                            'question' => $question,
                            'answer' => $answer
                        );
                    }
                }
            }
        }
        
        if (empty($faqs)) {
            $this->debug_log('All parsing methods failed');
            $this->debug_log('Raw markdown sample', substr($markdown, 0, 500));
        } else {
            $this->debug_log('Successfully parsed FAQs', count($faqs));
            foreach ($faqs as $i => $faq) {
                $this->debug_log("FAQ {$i} Q", substr($faq['question'], 0, 100));
                $this->debug_log("FAQ {$i} A", substr($faq['answer'], 0, 100));
            }
        }
        
        return $faqs;
    }
    
    /**
     * ✅ NEW: Build HTML and Schema from FAQ array
     */
    private function build_output_from_faqs($faqs, $output_format, $post_id) {
        // Build HTML
        $html = $this->build_html_from_faqs($faqs);
        
        // Build Schema
        $schema = $this->build_schema_from_faqs($faqs);
        
        // Save schema to post meta
        if ($post_id > 0 && !empty($schema)) {
            $saved = update_post_meta($post_id, '_faq_gen_ai_schema', $schema);
            
            // Clear cache
            wp_cache_delete($post_id, 'post_meta');
            clean_post_cache($post_id);
            
            $this->debug_log('Schema saved to post meta', $saved ? 'success' : 'failed');
            
            // Verify
            $verify = get_post_meta($post_id, '_faq_gen_ai_schema', true);
            $this->debug_log('Schema verification', !empty($verify) ? 'found' : 'NOT FOUND');
        }
        
        // Add title
        $title = $this->get_faq_title();
        $html = $title . $html;
        
        // Build final output based on format
        $seo_enabled = (get_option('faq_gen_ai_seo_integration', '1') === '1');
        $has_seo_plugin = (defined('RANK_MATH_VERSION') || defined('WPSEO_VERSION'));
        
        switch ($output_format) {
            case 'both':
                if ($seo_enabled && $has_seo_plugin) {
                    $final_content = $html . "\n\n<!-- FAQ Schema integrated with SEO plugin -->";
                } else {
                    $final_content = $html . "\n\n<!-- FAQ Schema -->\n<script type=\"application/ld+json\">\n" . $schema . "\n</script>";
                }
                
                return array(
                    'html'    => $html,
                    'schema'  => $schema,
                    'content' => $final_content,
                    'format'  => 'both',
                    'seo_integrated' => ($seo_enabled && $has_seo_plugin)
                );
                
            case 'html':
                return array(
                    'html'    => $html,
                    'schema'  => '',
                    'content' => $html,
                    'format'  => 'html'
                );
                
            case 'schema':
                return array(
                    'html'    => '',
                    'schema'  => $schema,
                    'content' => "<script type=\"application/ld+json\">\n" . $schema . "\n</script>",
                    'format'  => 'schema'
                );
        }
    }
    
    /**
     * ✅ NEW: Build HTML from FAQ array
     */
    private function build_html_from_faqs($faqs) {
        $html = '<div class="faq-section">' . "\n";
        
        foreach ($faqs as $faq) {
            $question = esc_html($faq['question']);
            $answer = esc_html($faq['answer']);
            
            $html .= '<div class="faq-item">';
            $html .= '<h3 class="faq-question">' . $question . '</h3>';
            $html .= '<div class="faq-answer"><p>' . $answer . '</p></div>';
            $html .= '</div>' . "\n";
        }
        
        $html .= '</div>';
        
        return $html;
    }
    
    /**
     * ✅ NEW: Build Schema from FAQ array
     */
    private function build_schema_from_faqs($faqs) {
        $main_entity = array();
        
        foreach ($faqs as $faq) {
            $main_entity[] = array(
                '@type' => 'Question',
                'name' => $faq['question'],
                'acceptedAnswer' => array(
                    '@type' => 'Answer',
                    'text' => $faq['answer']
                )
            );
        }
        
        $schema_data = array(
            '@context' => 'https://schema.org',
            '@type' => 'FAQPage',
            'mainEntity' => $main_entity
        );
        
        return wp_json_encode($schema_data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
    
    private function get_faq_title() {
        if (get_option('faq_gen_ai_show_title', '1') !== '1') {
            return '';
        }
        
        $locale = get_locale();
        $is_rtl = is_rtl();
        $is_persian = (strpos($locale, 'fa') === 0 || $is_rtl);
        
        if ($is_persian) {
            $title = get_option('faq_gen_ai_title_fa', 'سوالات متداول');
            $dir = 'rtl';
        } else {
            $title = get_option('faq_gen_ai_title_en', 'Frequently Asked Questions');
            $dir = 'ltr';
        }
        
        return '<h2 class="faq-section-title" dir="' . esc_attr($dir) . '">' . esc_html($title) . '</h2>' . "\n";
    }
    
    private function prepare_final_output($result, $output_format, $post_id) {
        if (($output_format === 'html' || $output_format === 'both') && 
            !empty($result['html']) && 
            strpos($result['html'], 'faq-section-title') === false) {
            
            $title = $this->get_faq_title();
            $result['html'] = $title . $result['html'];
            
            if ($output_format === 'both') {
                $seo_enabled = (get_option('faq_gen_ai_seo_integration', '1') === '1');
                $has_seo_plugin = (defined('RANK_MATH_VERSION') || defined('WPSEO_VERSION'));
                
                if ($seo_enabled && $has_seo_plugin) {
                    $result['content'] = $result['html'] . "\n\n<!-- FAQ Schema integrated with SEO plugin -->";
                } else {
                    $result['content'] = $result['html'] . "\n\n<script type=\"application/ld+json\">\n" . $result['schema'] . "\n</script>";
                }
            } else {
                $result['content'] = $result['html'];
            }
        }
        
        return $result;
    }
    
    private function generate_cache_key($prompt, $output_format, $faq_count) {
        return 'faq_' . md5($prompt . $output_format . $faq_count . $this->config['model']);
    }
    
    private function get_cached_result($cache_key) {
        return wp_cache_get($cache_key, self::CACHE_GROUP);
    }
    
    private function cache_result($cache_key, $result) {
        wp_cache_set($cache_key, $result, self::CACHE_GROUP, self::CACHE_EXPIRATION);
    }
    
    public function clear_cache($post_id = 0) {
        if ($post_id > 0) {
            delete_post_meta($post_id, '_faq_gen_ai_schema');
        }
        wp_cache_flush();
    }
}
