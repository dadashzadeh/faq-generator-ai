<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Yoast SEO Graph Piece for FAQ Schema
 */
class FAQ_Gen_AI_Yoast_FAQ_Piece implements WPSEO_Graph_Piece {
    
    private $schema_data;
    private $context;
    
    public function __construct($schema_data, $context) {
        $this->schema_data = $schema_data;
        $this->context = $context;
    }
    
    public function is_needed() {
        return !empty($this->schema_data);
    }
    
    public function generate() {
        return $this->schema_data;
    }
}
