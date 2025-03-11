jQuery(document).ready(function($) {
    // Chatbot UI elements
    const $chatbot = $('#ai-recommender-chatbot');
    const $chatbotToggle = $('.ai-recommender-toggle');
    const $chatbotInput = $('.ai-recommender-input');
    const $chatbotSend = $('.ai-recommender-send');
    const $chatbotMessages = $('.ai-recommender-messages');
    const $chatbotUpload = $('.ai-recommender-upload');
    const $chatbotHeader = $('.ai-recommender-header h3');
    
    // Set chatbot title from settings
    if (aiRecommender.chatbot_title) {
        $chatbotHeader.text(aiRecommender.chatbot_title);
    }
    
    // Store chat history
    let chatHistory = [];
    
    // Toggle chatbot visibility
    $chatbotToggle.on('click', function() {
        $chatbot.toggleClass('ai-recommender-minimized');
    });
    
    // Send message when clicking send button
    $chatbotSend.on('click', function() {
        sendMessage();
    });
    
    // Send message when pressing Enter
    $chatbotInput.on('keypress', function(e) {
        if (e.which === 13) {
            sendMessage();
        }
    });
    
    // Handle image upload
    $chatbotUpload.on('click', function() {
        // Create a hidden file input
        const fileInput = $('<input type="file" accept="image/*" style="display:none">');
        $('body').append(fileInput);
        
        // Trigger click on the file input
        fileInput.trigger('click');
        
        // Handle file selection
        fileInput.on('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const imageData = e.target.result;
                    processImageSearch(imageData);
                };
                reader.readAsDataURL(file);
            }
            // Remove the file input
            fileInput.remove();
        });
    });
    
    // Function to send message to the AI
    function sendMessage() {
        const message = $chatbotInput.val().trim();
        if (message === '') return;
        
        // Add user message to chat
        addMessage('user', message);
        
        // Update chat history
        chatHistory.push({
            role: 'user',
            content: message
        });
        
        // Clear input
        $chatbotInput.val('');
        
        // Add thinking indicator
        const $thinking = $('<div class="ai-recommender-message ai-message"><div class="ai-recommender-thinking">Thinking...</div></div>');
        $chatbotMessages.append($thinking);
        $chatbotMessages.scrollTop($chatbotMessages[0].scrollHeight);
        
        // Send to API
        $.ajax({
            url: aiRecommender.rest_url + 'chat',
            method: 'POST',
            beforeSend: function(xhr) {
                xhr.setRequestHeader('X-WP-Nonce', aiRecommender.nonce);
            },
            data: {
                message: message,
                chat_history: chatHistory
            },
            success: function(response) {
                // Remove thinking indicator
                $thinking.remove();
                
                if (response.success) {
                    // Add AI response to chat history
                    chatHistory.push({
                        role: 'assistant',
                        content: response.response
                    });
                    
                    // Add AI response to chat
                    addMessage('ai', response.response);
                    
                    // If products were suggested, display them
                    if (response.products && response.products.length > 0) {
                        displayProducts(response.products);
                    }
                } else {
                    // Show error message
                    addMessage('ai', response.response);
                }
            },
            error: function() {
                // Remove thinking indicator
                $thinking.remove();
                
                // Show error message
                addMessage('ai', 'Sorry, I encountered an error. Please try again later.');
            }
        });
    }
    
    // Function to process image search
    function processImageSearch(imageData) {
        // Add user image to chat
        const $message = $('<div class="ai-recommender-message user-message"></div>');
        $message.append('<div class="ai-recommender-avatar">You</div>');
        $message.append('<div class="ai-recommender-image-upload"><img src="' + imageData + '" alt="Uploaded Image"></div>');
        $chatbotMessages.append($message);
        $chatbotMessages.scrollTop($chatbotMessages[0].scrollHeight);
        
        // Add thinking indicator
        const $thinking = $('<div class="ai-recommender-message ai-message"><div class="ai-recommender-thinking">Analyzing image...</div></div>');
        $chatbotMessages.append($thinking);
        $chatbotMessages.scrollTop($chatbotMessages[0].scrollHeight);
        
        // Send to API
        $.ajax({
            url: aiRecommender.rest_url + 'image-search',
            method: 'POST',
            beforeSend: function(xhr) {
                xhr.setRequestHeader('X-WP-Nonce', aiRecommender.nonce);
            },
            data: {
                image_data: imageData
            },
            success: function(response) {
                // Remove thinking indicator
                $thinking.remove();
                
                if (response.success && response.products && response.products.length > 0) {
                    // Add AI response
                    addMessage('ai', 'I found these products based on your image:');
                    
                    // Display products
                    displayProducts(response.products);
                } else {
                    // No products found
                    addMessage('ai', 'I couldn\'t find any matching products based on your image. Could you try a different image?');
                }
            },
            error: function() {
                // Remove thinking indicator
                $thinking.remove();
                
                // Show error message
                addMessage('ai', 'Sorry, I encountered an error processing your image. Please try again later.');
            }
        });
    }
    
    // Function to add a message to the chat
    function addMessage(sender, message) {
        const $message = $('<div class="ai-recommender-message"></div>');
        $message.addClass(sender === 'user' ? 'user-message' : 'ai-message');
        
        const avatarText = sender === 'user' ? 'You' : 'AI';
        $message.append('<div class="ai-recommender-avatar">' + avatarText + '</div>');
        $message.append('<div class="ai-recommender-text">' + message + '</div>');
        
        $chatbotMessages.append($message);
        $chatbotMessages.scrollTop($chatbotMessages[0].scrollHeight);
    }
    
    // Function to display product recommendations
    function displayProducts(products) {
        const $productsContainer = $('<div class="ai-recommender-products"></div>');
        
        products.forEach(product => {
            const $product = $('<div class="ai-recommender-product"></div>');
            $product.append('<img src="' + product.image + '" alt="' + product.name + '">');
            $product.append('<h4>' + product.name + '</h4>');
            $product.append('<div class="ai-recommender-price">' + product.price + '</div>');
            $product.append('<a href="' + product.url + '" class="ai-recommender-product-link">View Product</a>');
            
            $productsContainer.append($product);
        });
        
        const $message = $('<div class="ai-recommender-message ai-message"></div>');
        $message.append('<div class="ai-recommender-avatar">AI</div>');
        $message.append($productsContainer);
        
        $chatbotMessages.append($message);
        $chatbotMessages.scrollTop($chatbotMessages[0].scrollHeight);
    }
    
    // Initialize chatbot with a welcome message
    if (aiRecommender.welcome_message) {
        addMessage('ai', aiRecommender.welcome_message);
    } else {
        addMessage('ai', 'Hello! I\'m your AI product assistant. I can help you find products or answer questions about our store. You can also upload an image to find similar products.');
    }
});