<?php
// Admin settings class
class AI_Recommender_Admin {
    
    private $options;
    
    public function __construct() {
        // Add admin menu
        add_action('admin_menu', array($this, 'add_admin_menu'));
        
        // Register settings
        add_action('admin_init', array($this, 'register_settings'));
    }
    
    // Add admin menu item
    public function add_admin_menu() {
        add_menu_page(
            'AI Product Recommender',
            'AI Recommender',
            'manage_options',
            'ai_recommender',
            array($this, 'render_settings_page'),
            'dashicons-format-chat',
            30
        );
    }
    
    // Register settings
    public function register_settings() {
        register_setting('ai_recommender_settings', 'ai_recommender_options');
        
        add_settings_section(
            'ai_recommender_general_section', 
            'General Settings', 
            array($this, 'render_general_section'), 
            'ai_recommender_settings'
        );
        
        add_settings_field(
            'gemini_api_key', 
            'Gemini API Key', 
            array($this, 'render_api_key_field'), 
            'ai_recommender_settings', 
            'ai_recommender_general_section'
        );
        
        add_settings_field(
            'chatbot_title', 
            'Chatbot Title', 
            array($this, 'render_chatbot_title_field'), 
            'ai_recommender_settings', 
            'ai_recommender_general_section',
            ['default' => 'AI Product Assistant']
        );
        
        add_settings_field(
            'welcome_message', 
            'Welcome Message', 
            array($this, 'render_welcome_message_field'), 
            'ai_recommender_settings', 
            'ai_recommender_general_section',
            ['default' => 'Hello! I\'m your AI product assistant. I can help you find products or answer questions about our store.']
        );
        
        add_settings_section(
            'ai_recommender_advanced_section', 
            'Advanced Settings', 
            array($this, 'render_advanced_section'), 
            'ai_recommender_settings'
        );
        
        add_settings_field(
            'temperature', 
            'AI Temperature', 
            array($this, 'render_temperature_field'), 
            'ai_recommender_settings', 
            'ai_recommender_advanced_section',
            ['default' => 0.7, 'min' => 0, 'max' => 1, 'step' => 0.1]
        );
        
        add_settings_field(
            'max_tokens', 
            'Max Response Tokens', 
            array($this, 'render_max_tokens_field'), 
            'ai_recommender_settings', 
            'ai_recommender_advanced_section',
            ['default' => 256, 'min' => 50, 'max' => 1024, 'step' => 1]
        );
    }
    
    // Render settings page
    public function render_settings_page() {
        $this->options = get_option('ai_recommender_options', array());
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('ai_recommender_settings');
                do_settings_sections('ai_recommender_settings');
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }
    
    // Render general section
    public function render_general_section() {
        echo '<p>Configure the basic settings for your AI Product Recommender</p>';
    }
    
    // Render advanced section
    public function render_advanced_section() {
        echo '<p>Advanced settings for the AI model behavior</p>';
    }
    
    // Render API key field
    public function render_api_key_field() {
        $value = isset($this->options['gemini_api_key']) ? $this->options['gemini_api_key'] : '';
        ?>
        <input type="password" id="gemini_api_key" name="ai_recommender_options[gemini_api_key]" value="<?php echo esc_attr($value); ?>" class="regular-text">
        <p class="description">Enter your Gemini API key from <a href="https://ai.google.dev/" target="_blank">Google AI Studio</a></p>
        <?php
    }
    
    // Render chatbot title field
    public function render_chatbot_title_field($args) {
        $value = isset($this->options['chatbot_title']) ? $this->options['chatbot_title'] : $args['default'];
        ?>
        <input type="text" id="chatbot_title" name="ai_recommender_options[chatbot_title]" value="<?php echo esc_attr($value); ?>" class="regular-text">
        <p class="description">Enter the title that appears at the top of the chatbot</p>
        <?php
    }
    
    // Render welcome message field
    public function render_welcome_message_field($args) {
        $value = isset($this->options['welcome_message']) ? $this->options['welcome_message'] : $args['default'];
        ?>
        <textarea id="welcome_message" name="ai_recommender_options[welcome_message]" rows="3" class="large-text"><?php echo esc_textarea($value); ?></textarea>
        <p class="description">Enter the welcome message that appears when the chatbot is first opened</p>
        <?php
    }
    
    // Render temperature field
    public function render_temperature_field($args) {
        $value = isset($this->options['temperature']) ? $this->options['temperature'] : $args['default'];
        ?>
        <input type="range" id="temperature" name="ai_recommender_options[temperature]" value="<?php echo esc_attr($value); ?>" min="<?php echo $args['min']; ?>" max="<?php echo $args['max']; ?>" step="<?php echo $args['step']; ?>">
        <span class="temperature-value"><?php echo esc_html($value); ?></span>
        <p class="description">Controls the randomness of the AI responses. Lower values make responses more deterministic.</p>
        <script>
            jQuery(document).ready(function($) {
                $('#temperature').on('input', function() {
                    $('.temperature-value').text($(this).val());
                });
            });
        </script>
        <?php
    }
    
    // Render max tokens field
    public function render_max_tokens_field($args) {
        $value = isset($this->options['max_tokens']) ? $this->options['max_tokens'] : $args['default'];
        ?>
        <input type="number" id="max_tokens" name="ai_recommender_options[max_tokens]" value="<?php echo esc_attr($value); ?>" min="<?php echo $args['min']; ?>" max="<?php echo $args['max']; ?>" step="<?php echo $args['step']; ?>">
        <p class="description">Maximum number of tokens (words) in the AI response</p>
        <?php
    }
}

// Gemini API integration class
class Gemini_API {
    
    private $api_key;
    private $temperature;
    private $max_tokens;
    
    public function __construct() {
        $options = get_option('ai_recommender_options', array());
        $this->api_key = isset($options['gemini_api_key']) ? $options['gemini_api_key'] : '';
        $this->temperature = isset($options['temperature']) ? $options['temperature'] : 0.7;
        $this->max_tokens = isset($options['max_tokens']) ? $options['max_tokens'] : 256;
    }
    
    // Chat completion with Gemini API
    public function generate_chat_response($message, $chat_history = array()) {
        if (empty($this->api_key)) {
            return array(
                'success' => false,
                'response' => 'API key not configured. Please set up the Gemini API key in the plugin settings.'
            );
        }
        
        // Get product information to include in the context
        $products_context = $this->get_products_context();
        
        $api_url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-pro:generateContent?key=' . $this->api_key;
        
        // Format chat history and current message
        $formatted_messages = array();
        
        // System prompt
        $formatted_messages[] = array(
            'role' => 'system', 
            'content' => 'You are an AI shopping assistant for a WooCommerce store. Help customers find products, answer questions about the store, and provide helpful recommendations. Here is information about some products in our store: ' . $products_context
        );
        
        // Add chat history
        foreach ($chat_history as $entry) {
            $formatted_messages[] = array(
                'role' => $entry['role'],
                'content' => $entry['content']
            );
        }
        
        // Add the current user message
        $formatted_messages[] = array(
            'role' => 'user',
            'content' => $message
        );
        
        $request_data = array(
            'contents' => $formatted_messages,
            'generationConfig' => array(
                'temperature' => (float)$this->temperature,
                'maxOutputTokens' => (int)$this->max_tokens
            )
        );
        
        $response = wp_remote_post($api_url, array(
            'headers' => array(
                'Content-Type' => 'application/json',
            ),
            'body' => json_encode($request_data),
            'timeout' => 30
        ));
        
        if (is_wp_error($response)) {
            return array(
                'success' => false,
                'response' => 'Error connecting to Gemini API: ' . $response->get_error_message()
            );
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (isset($data['error'])) {
            return array(
                'success' => false,
                'response' => 'Gemini API error: ' . $data['error']['message']
            );
        }
        
        // Check if the response has product recommendations
        $recommended_products = $this->extract_product_recommendations($data);
        
        return array(
            'success' => true,
            'response' => $data['candidates'][0]['content']['parts'][0]['text'],
            'products' => $recommended_products
        );
    }
    
    // Get product information to include in context
    private function get_products_context() {
        $products = wc_get_products(array(
            'limit' => 20,
            'status' => 'publish',
            'orderby' => 'date',
            'order' => 'DESC',
        ));
        
        $products_info = array();
        foreach ($products as $product) {
            $products_info[] = array(
                'id' => $product->get_id(),
                'name' => $product->get_name(),
                'price' => $product->get_price(),
                'url' => get_permalink($product->get_id()),
                'description' => $product->get_short_description(),
                'categories' => wp_get_post_terms($product->get_id(), 'product_cat', array('fields' => 'names'))
            );
        }
        
        return json_encode($products_info);
    }
    
    // Extract product recommendations from AI response
    private function extract_product_recommendations($data) {
        $text = $data['candidates'][0]['content']['parts'][0]['text'];
        
        // Look for product IDs in the response
        preg_match_all('/product_id:(\d+)/', $text, $matches);
        
        if (empty($matches[1])) {
            return array();
        }
        
        $product_ids = array_unique($matches[1]);
        $products = array();
        
        foreach ($product_ids as $product_id) {
            $product = wc_get_product($product_id);
            if ($product) {
                $products[] = array(
                    'id' => $product_id,
                    'name' => $product->get_name(),
                    'price' => $product->get_