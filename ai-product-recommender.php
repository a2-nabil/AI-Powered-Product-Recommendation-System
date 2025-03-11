<?php
/**
 * Plugin Name: AI-Powered Product Recommendation System
 * Description: AI chatbot for WooCommerce with Gemini integration
 * Version: 1.0.0
 * Author: Nabil
 * License: GPL v2 or later
 * Text Domain: ai-recommender
 * Requires WooCommerce: 4.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('AI_RECOMMENDER_PATH', plugin_dir_path(__FILE__));
define('AI_RECOMMENDER_URL', plugin_dir_url(__FILE__));
define('AI_RECOMMENDER_VERSION', '1.0.0');

// Include required files
require_once AI_RECOMMENDER_PATH . 'includes/class-admin-settings.php';
require_once AI_RECOMMENDER_PATH . 'includes/class-gemini-api.php';

// Main plugin class
class AI_Product_Recommender {
    
    // Store instances of our classes
    private $admin;
    private $gemini_api;
    
    public function __construct() {
        // Initialize admin settings
        $this->admin = new AI_Recommender_Admin();
        
        // Initialize Gemini API
        $this->gemini_api = new Gemini_API();
        
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
        wp_enqueue_style(
            'ai-recommender-styles', 
            AI_RECOMMENDER_URL . 'assets/css/style.css',
            array(),
            AI_RECOMMENDER_VERSION
        );
        
        wp_enqueue_script(
            'ai-recommender-script', 
            AI_RECOMMENDER_URL . 'assets/js/chatbot.js', 
            array('jquery'), 
            AI_RECOMMENDER_VERSION, 
            true
        );
        
        // Pass variables to JavaScript
        $options = get_option('ai_recommender_options', array());
        $chatbot_title = isset($options['chatbot_title']) ? $options['chatbot_title'] : 'AI Product Assistant';
        $welcome_message = isset($options['welcome_message']) ? $options['welcome_message'] : 'Hello! I\'m your AI product assistant. I can help you find products or answer questions about our store.';
        
        wp_localize_script('ai-recommender-script', 'aiRecommender', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'rest_url' => rest_url('ai-recommender/v1/'),
            'nonce' => wp_create_nonce('wp_rest'),
            'chatbot_title' => $chatbot_title,
            'welcome_message' => $welcome_message
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
        $chat_history = isset($params['chat_history']) ? $params['chat_history'] : array();
        
        // Use Gemini API to generate response
        return $this->gemini_api->generate_chat_response($message, $chat_history);
    }
    
    // Process image search requests
    public function process_image_search($request) {
        $params = $request->get_params();
        $image_data = $params['image_data'];
        
        // Decode base64 image data
        $image_data = str_replace('data:image/jpeg;base64,', '', $image_data);
        $image_data = str_replace('data:image/png;base64,', '', $image_data);
        $image_data = str_replace(' ', '+', $image_data);
        $image_data = base64_decode($image_data);
        
        // Save the image to a temporary file
        $upload_dir = wp_upload_dir();
        $temp_file = $upload_dir['path'] . '/' . uniqid() . '.jpg';
        file_put_contents($temp_file, $image_data);
        
        // TODO: Implement image recognition with Google Vision or TensorFlow
        // For now, perform a simple product search
        $products = wc_get_products(array(
            'limit' => 5,
            'status' => 'publish',
            'orderby' => 'rand'
        ));
        
        $product_data = array();
        foreach ($products as $product) {
            $product_data[] = array(
                'id' => $product->get_id(),
                'name' => $product->get_name(),
                'price' => $product->get_price_html(),
                'url' => get_permalink($product->get_id()),
                'image' => get_the_post_thumbnail_url($product->get_id(), 'thumbnail') ?: wc_placeholder_img_src()
            );
        }
        
        // Remove the temporary file
        unlink($temp_file);
        
        return new WP_REST_Response(array(
            'success' => true,
            'products' => $product_data
        ));
    }
    
    // Render the chatbot container in the footer
    public function render_chatbot_container() {
        $options = get_option('ai_recommender_options', array());
        $chatbot_title = isset($options['chatbot_title']) ? $options['chatbot_title'] : 'AI Product Assistant';
        ?>
        <div id="ai-recommender-chatbot" class="ai-recommender-container">
            <div class="ai-recommender-header">
                <h3><?php echo esc_html($chatbot_title); ?></h3>
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