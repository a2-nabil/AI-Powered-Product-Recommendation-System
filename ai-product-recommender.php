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
class AI_Product_Recommender
{

    // Store instances of our classes
    private $admin;
    private $gemini_api;

    public function __construct()
    {
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
    public function is_woocommerce_active()
    {
        return in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')));
    }

    // Initialize plugin components
    public function init()
    {
        // Register REST API endpoints
        add_action('rest_api_init', array($this, 'register_api_endpoints'));
    }

    // Load necessary scripts and styles
    public function enqueue_scripts()
    {
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
        $chatbot_title = isset($options['chatbot_title']) ? $options['chatbot_title'] : 'AI Personal Assistant';
        $welcome_message = isset($options['welcome_message']) ? $options['welcome_message'] : 'Hello! I\'m your AI assistant. I can help you find products or answer questions about our store.';

        wp_localize_script('ai-recommender-script', 'aiRecommender', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'rest_url' => rest_url('ai-recommender/v1/'),
            'nonce' => wp_create_nonce('wp_rest'),
            'chatbot_title' => $chatbot_title,
            'welcome_message' => $welcome_message
        ));
    }

    // Register REST API endpoints
    public function register_api_endpoints()
    {
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
    public function process_chat_message($request)
    {
        $params = $request->get_params();
        $message = sanitize_text_field($params['message']);
        $chat_history = isset($params['chat_history']) ? $params['chat_history'] : array();

        // Use Gemini API to generate response
        return $this->gemini_api->generate_chat_response($message, $chat_history);
    }

    // Process image search requests
    public function process_image_search($request)
    {
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
    public function render_chatbot_container()
    {
        $options = get_option('ai_recommender_options', array());
        $chatbot_title = isset($options['chatbot_title']) ? $options['chatbot_title'] : 'AI Product Assistant';
        ?>
        <div class="nxtChat-toggle">
            <div id="nxt_open" class="nxtChat-toggle-icon">
                <svg width="50px" height="50px" viewBox="0 0 2 2" xmlns="http://www.w3.org/2000/svg">
                    <g fill="none" fill-rule="evenodd">
                        <path cx="16" cy="16" r="16" fill="#4a6cf7" d="M2 1A1 1 0 0 1 1 2A1 1 0 0 1 0 1A1 1 0 0 1 2 1z" />
                        <path fill="#FFF"
                            d="M1.018 1.458a0.719 0.719 0 0 0 0.13 -0.021 0.356 0.356 0 0 0 0.163 0.011 0.063 0.063 0 0 1 0.006 -0.001c0.019 0 0.045 0.011 0.082 0.035v-0.039a0.038 0.038 0 0 1 0.019 -0.033q0.024 -0.014 0.045 -0.031c0.054 -0.046 0.085 -0.107 0.085 -0.171q0 -0.033 -0.01 -0.063 0.025 -0.046 0.039 -0.096A0.287 0.287 0 0 1 1.625 1.207c0 0.088 -0.041 0.17 -0.112 0.23a0.375 0.375 0 0 1 -0.037 0.028v0.091c0 0.031 -0.036 0.05 -0.062 0.031a0.938 0.938 0 0 0 -0.075 -0.051 0.188 0.188 0 0 0 -0.023 -0.012q-0.032 0.005 -0.065 0.005c-0.088 0 -0.17 -0.026 -0.234 -0.071zm-0.467 -0.183C0.439 1.181 0.375 1.052 0.375 0.914c0 -0.282 0.266 -0.507 0.591 -0.507s0.591 0.225 0.591 0.507c0 0.282 -0.266 0.508 -0.591 0.508q-0.055 0 -0.108 -0.008c-0.015 0.004 -0.076 0.04 -0.165 0.104 -0.032 0.023 -0.077 0.001 -0.077 -0.038v-0.156a0.563 0.563 0 0 1 -0.065 -0.048m0.309 0.042q0.004 0 0.008 0.001c0.032 0.005 0.065 0.008 0.098 0.008 0.275 0 0.494 -0.186 0.494 -0.412 0 -0.226 -0.22 -0.412 -0.494 -0.412 -0.274 0 -0.494 0.186 -0.494 0.412 0 0.109 0.051 0.212 0.142 0.289 0.023 0.019 0.048 0.037 0.075 0.052 0.015 0.009 0.024 0.024 0.024 0.042v0.09c0.07 -0.047 0.116 -0.069 0.147 -0.069m-0.146 -0.307c-0.043 0 -0.077 -0.034 -0.077 -0.077 0 -0.042 0.035 -0.077 0.077 -0.077s0.077 0.034 0.077 0.077 -0.035 0.077 -0.077 0.077m0.252 0c-0.043 0 -0.077 -0.034 -0.077 -0.077 0 -0.042 0.035 -0.077 0.077 -0.077s0.077 0.034 0.077 0.077 -0.035 0.077 -0.077 0.077m0.252 0c-0.043 0 -0.077 -0.034 -0.077 -0.077 0 -0.042 0.035 -0.077 0.077 -0.077s0.077 0.034 0.077 0.077 -0.035 0.077 -0.077 0.077" />
                    </g>
                </svg>
            </div>
        </div>
        <div id="ai-recommender-chatbot" class="ai-recommender-container ai-recommender-minimized">
            <div class="ai-recommender-header">
                <h3><?php echo esc_html($chatbot_title); ?></h3>
                <button id="nxt_close" class="ai-recommender-toggle">
                    <svg fill="#fff" width="30px" height="30px" viewBox="0 0 0.9 0.9" xmlns="http://www.w3.org/2000/svg">
                        <path
                            d="M0.768 0.132A0.45 0.45 0 0 0 0.45 0a0.45 0.45 0 0 0 -0.318 0.768A0.45 0.45 0 0 0 0.45 0.9 0.45 0.45 0 0 0 0.768 0.132M0.71 0.711a0.375 0.375 0 0 1 -0.261 0.108 0.367 0.367 0 0 1 -0.367 -0.367 0.375 0.375 0 0 1 0.108 -0.261A0.375 0.375 0 0 1 0.45 0.083c0.204 0 0.367 0.165 0.367 0.367a0.375 0.375 0 0 1 -0.108 0.261" />
                        <path
                            d="M0.507 0.45 0.651 0.306A0.041 0.041 0 0 0 0.593 0.247L0.449 0.392 0.304 0.247a0.041 0.041 0 0 0 -0.057 0.058L0.392 0.45 0.247 0.594a0.041 0.041 0 1 0 0.058 0.058L0.45 0.509l0.144 0.144A0.041 0.041 0 1 0 0.652 0.594z" />
                    </svg>
                </button>
            </div>
            <div class="ai-recommender-body">
                <div class="ai-recommender-messages"></div>
            </div>
            <div class="ai-recommender-footer">
                <input type="text" class="ai-recommender-input" placeholder="Ask about anything...">
                <button class="ai-recommender-upload">
                    <svg width="20px" height="20px" viewBox="0 0 0.375 0.375" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path fill-rule="evenodd" clip-rule="evenodd"
                            d="M0.195 0.03a0.011 0.011 0 0 0 -0.016 0l-0.075 0.075a0.011 0.011 0 1 0 0.016 0.016L0.176 0.065V0.238a0.011 0.011 0 1 0 0.023 0V0.065l0.056 0.056a0.011 0.011 0 1 0 0.016 -0.016zM0.063 0.25a0.013 0.013 0 0 1 0.013 0.013V0.3c0 0.014 0.011 0.025 0.025 0.025h0.175A0.025 0.025 0 0 0 0.3 0.3v-0.038a0.013 0.013 0 1 1 0.025 0V0.3a0.05 0.05 0 0 1 -0.05 0.05H0.1A0.05 0.05 0 0 1 0.05 0.3v-0.038a0.013 0.013 0 0 1 0.013 -0.013"
                            fill="#000000" />
                    </svg></button>
                <button class="ai-recommender-send">
                    <svg fill="#fff" xmlns="http://www.w3.org/2000/svg" width="20px" height="20px" viewBox="0 0 1.3 1.3"
                        enable-background="new 0 0 52 52" xml:space="preserve">
                        <path
                            d="m0.053 1.113 0.11 -0.408h0.465c0.013 0 0.025 -0.013 0.025 -0.025v-0.05c0 -0.013 -0.013 -0.025 -0.025 -0.025H0.163l-0.107 -0.4C0.053 0.2 0.05 0.193 0.05 0.185c0 -0.017 0.017 -0.035 0.038 -0.033 0.005 0 0.007 0.003 0.013 0.003l1.125 0.463c0.015 0.005 0.025 0.02 0.025 0.035s-0.01 0.028 -0.023 0.033L0.1 1.16c-0.005 0.003 -0.01 0.003 -0.015 0.003 -0.02 -0.003 -0.035 -0.017 -0.035 -0.038 0 -0.005 0 -0.007 0.003 -0.013" />
                    </svg>
                </button>
            </div>
        </div>
        <?php
    }

    // Admin notice if WooCommerce is not active
    public function woocommerce_missing_notice()
    {
        ?>
        <div class="notice notice-error">
            <p><?php _e('AI Product Recommender requires WooCommerce to be installed and active.', 'ai-recommender'); ?></p>
        </div>
        <?php
    }
}

// Initialize the plugin
new AI_Product_Recommender();