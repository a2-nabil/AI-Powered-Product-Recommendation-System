<?php
/**
 * Format the Gemini API response by converting product recommendations to attractive links
 * 
 * @param string $response_text The raw text response from Gemini API
 * @return string Formatted HTML output with styled product recommendations
 */
function format_gemini_response($response_text) {
    // Regular expression to find product recommendations in the text
    $pattern = '/\*\*\*(.*?):\*\* product_id:(\d+)/';
    
    // Replace each product mention with a styled link
    $formatted_response = preg_replace_callback(
        $pattern,
        function($matches) {
            $product_name = trim($matches[1]);
            $product_id = $matches[2];
            
            // Get the product URL (assuming you have a function for this)
            $product_url = get_permalink($product_id);
            
            // Create a styled button/link for the product
            return '<div class="recommended-product">
                <a href="' . esc_url($product_url) . '" class="product-link">
                    <strong>' . esc_html($product_name) . '</strong>
                    <span class="view-product">View Course</span>
                </a>
            </div>';
        },
        $response_text
    );
    
    // Additional formatting for paragraphs and general text flow
    $formatted_response = '<div class="ai-assistant-response">' . 
        nl2br($formatted_response) . 
    '</div>';
    
    return $formatted_response;
}

/**
 * Add necessary CSS styles to make the recommendations look good
 */
function add_gemini_response_styles() {
    ?>
    <style>
        .ai-assistant-response {
            font-size: 16px;
            line-height: 1.6;
            color: #333;
            margin-bottom: 20px;
        }
        
        .recommended-product {
            margin: 15px 0;
            background: #f7f9fc;
            border-radius: 8px;
            border-left: 4px solid #4285f4; /* Google blue */
            overflow: hidden;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        
        .recommended-product:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }
        
        .product-link {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px;
            text-decoration: none;
            color: #333;
        }
        
        .product-link strong {
            font-size: 16px;
            color: #1a73e8;
        }
        
        .view-product {
            background: #4285f4;
            color: white;
            padding: 6px 12px;
            border-radius: 4px;
            font-size: 14px;
            white-space: nowrap;
        }
    </style>
    <?php
}

/**
 * Function to implement the formatting in your existing code
 */
function display_gemini_response($response_data) {
    // Add the CSS styles
    add_gemini_response_styles();
    
    // Format the response text
    if ($response_data['success']) {
        $formatted_text = format_gemini_response($response_data['response']);
        echo $formatted_text;
    } else {
        echo '<div class="ai-error">' . esc_html($response_data['response']) . '</div>';
    }
}
