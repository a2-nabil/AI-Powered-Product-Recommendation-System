<?php
// Admin settings class
class AI_Recommender_Admin {
    
    private $options;
    
    public function __construct() {
        // Add admin menu
        add_action('admin_menu', array($this, 'add_admin_menu'));
        
        // Register settings
        add_action('admin_init', array($this, 'register_settings'));
        
        // Add settings link to plugins page
        add_filter('plugin_action_links_' . plugin_basename(AI_RECOMMENDER_PATH . 'ai-product-recommender.php'), array($this, 'add_settings_link'));
    }
    
    // Add settings link to plugins page
    public function add_settings_link($links) {
        $settings_link = '<a href="admin.php?page=ai_recommender">' . __('Settings', 'ai-recommender') . '</a>';
        array_unshift($links, $settings_link);
        return $links;
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