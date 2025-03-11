<?php
// Gemini API integration class
class Gemini_API
{

    private $api_key;
    private $temperature;
    private $max_tokens;

    public function __construct()
    {
        $options = get_option('ai_recommender_options', array());
        $this->api_key = isset($options['gemini_api_key']) ? $options['gemini_api_key'] : '';
        $this->temperature = isset($options['temperature']) ? $options['temperature'] : 0.7;
        $this->max_tokens = isset($options['max_tokens']) ? $options['max_tokens'] : 256;
    }

    // Chat completion with Gemini API
    public function generate_chat_response($message, $chat_history = array())
    {
        if (empty($this->api_key)) {
            return array(
                'success' => false,
                'response' => 'API key not configured. Please set up the Gemini API key in the plugin settings.'
            );
        }

        // Get product information to include in the context
        $products_context = $this->get_products_context();

        // Updated API endpoint with the correct model name
        $api_url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-pro:generateContent?key=' . $this->api_key;

        // Format chat history and current message for Gemini API
        $formatted_messages = array();

        // Add chat history with correct format
        foreach ($chat_history as $entry) {
            $formatted_messages[] = array(
                'role' => $entry['role'],
                'parts' => array(
                    array('text' => $entry['content'])
                )
            );
        }

        // Add the current user message
        $formatted_messages[] = array(
            'role' => 'user',
            'parts' => array(
                array('text' => $message)
            )
        );

        // System instruction should be placed as a separate field in the request
        $request_data = array(
            'contents' => $formatted_messages,
            'systemInstruction' => array(
                'parts' => array(
                    array('text' => 'You are an AI shopping assistant for a WooCommerce store. Help customers find products, answer questions about the store, and provide helpful recommendations. When recommending a specific product, include the tag product_id:XXX where XXX is the product ID. Here is information about some products in our store: ' . $products_context)
                )
            ),
            'generationConfig' => array(
                'temperature' => (float) $this->temperature,
                'maxOutputTokens' => (int) $this->max_tokens
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
    private function get_products_context()
    {
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
    private function extract_product_recommendations($data)
    {
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
                    'price' => $product->get_price_html(),
                    'url' => get_permalink($product_id),
                    'image' => get_the_post_thumbnail_url($product_id, 'thumbnail') ?: wc_placeholder_img_src()
                );
            }
        }

        return $products;
    }
}