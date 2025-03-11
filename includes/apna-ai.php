<?php
// Plugin Name: AI-Powered Product Recommendation System for WooCommerce
// Description: AI chatbot for WooCommerce with Gemini integration
// Version: 1.0.0
// Author: Nabil
// License: GPL v2 or later

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Main plugin class
class AI_Product_Recommender {
    
    public function __construct() {
        // Check if WooCommerce is active
        if ($this->is_woocommerce_active()) {
            // Initialize the plugin
            add_action('init', array($this, 'init'));
            
            // Register scripts and styles
            add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
            
            // Add chatbot container to footer
            add_action('wp_footer', array($this, 'render_chatbot_container'));
        } else {
            // Display admin notice if WooCommerce is not active
            add_action('admin_notices', array($this, 'woocommerce_missing_notice'));
        }
    }
    
    // Check if WooCommerce is active
    public function is_woocommerce_active() {
        return in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')));
    }
    
    // Initialize plugin components
    public function init() {
        // Register REST API endpoints
        add_action('rest_api_init', array($this, 'register_api_endpoints'));
    }
    
    // Load necessary scripts and styles
    public function enqueue_scripts() {
        wp_enqueue_style('ai-recommender-styles', plugin_dir_url(__FILE__) . 'assets/css/style.css');
        wp_enqueue_script('ai-recommender-script', plugin_dir_url(__FILE__) . 'assets/js/chatbot.js', array('jquery'), '1.0.0', true);
        
        // Pass variables to JavaScript
        wp_localize_script('ai-recommender-script', 'aiRecommender', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'rest_url' => rest_url('ai-recommender/v1/'),
            'nonce' => wp_create_nonce('wp_rest')
        ));
    }
    
    // Register REST API endpoints
    public function register_api_endpoints() {
        register_rest_route('ai-recommender/v1', '/chat', array(
            'methods' => 'POST',
            'callback' => array($this, 'process_chat_message'),
            'permission_callback' => '__return_true'
        ));
        
        register_rest_route('ai-recommender/v1', '/image-search', array(
            'methods' => 'POST',
            'callback' => array($this, 'process_image_search'),
            'permission_callback' => '__return_true'
        ));
    }
    
    // Process chat messages and interact with Gemini API
    public function process_chat_message($request) {
        $params = $request->get_params();
        $message = sanitize_text_field($params['message']);
        
        // TODO: Implement Gemini API integration here
        
        // For now, return a mock response
        return new WP_REST_Response(array(
            'success' => true,
            'response' => 'This is a placeholder response. Gemini API integration coming soon.'
        ));
    }
    
    // Process image search requests
    public function process_image_search($request) {
        $params = $request->get_params();
        $image_data = $params['image_data'];
        
        // TODO: Implement image recognition with Google Vision or TensorFlow
        
        // For now, return a mock response
        return new WP_REST_Response(array(
            'success' => true,
            'products' => array(
                // Mock product data
                array('id' => 1, 'name' => 'Sample Product', 'url' => '#', 'image' => '#')
            )
        ));
    }
    
    // Render the chatbot container in the footer
    public function render_chatbot_container() {
        ?>
        <div id="ai-recommender-chatbot" class="ai-recommender-container">
            <div class="ai-recommender-header">
                <h3>AI Product Assistant</h3>
                <button class="ai-recommender-toggle">×</button>
            </div>
            <div class="ai-recommender-body">
                <div class="ai-recommender-messages"></div>
            </div>
            <div class="ai-recommender-footer">
                <input type="text" class="ai-recommender-input" placeholder="Ask about products...">
                <button class="ai-recommender-send">Send</button>
                <button class="ai-recommender-upload">📷</button>
            </div>
        </div>
        <?php
    }
    
    // Admin notice if WooCommerce is not active
    public function woocommerce_missing_notice() {
        ?>
        <div class="notice notice-error">
            <p><?php _e('AI Product Recommender requires WooCommerce to be installed and active.', 'ai-recommender'); ?></p>
        </div>
        <?php
    }
}

// Initialize the plugin
new AI_Product_Recommender();