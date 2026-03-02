// Enhanced Chatbot with Smart Recommendations
// Conversation State Management

const chatbotToggle = document.getElementById('chatbotToggle');
const chatbotWindow = document.getElementById('chatbotWindow');
const chatbotClose = document.getElementById('chatbotClose');
const chatbotMessages = document.getElementById('chatbotMessages');
const chatbotInput = document.getElementById('chatbotInput');
const chatbotSend = document.getElementById('chatbotSend');

// Conversation state
let conversationState = {
    step: 'initial', // initial, location_asked, preference_asked, price_asked, dietary_asked, recommending
    location: null,
    preference: null,
    priceRange: null,
    dietary: null
};

// Toggle chatbot window
chatbotToggle.addEventListener('click', () => {
    chatbotWindow.classList.toggle('active');
});

chatbotClose.addEventListener('click', () => {
    chatbotWindow.classList.remove('active');
});

// Send message
function sendMessage() {
    const message = chatbotInput.value.trim();
    if (!message) return;

    // Add user message
    addMessage(message, 'user');
    chatbotInput.value = '';

    // Process message based on conversation state
    processMessage(message);
}

function processMessage(message) {
    showTypingIndicator();

    const messageLower = message.toLowerCase();

    // Step 1: Check if location is provided
    if (conversationState.step === 'initial' || conversationState.step === 'location_asked') {
        const localities = ['southern avenue', 'park street', 'salt lake', 'ballygunge', 'alipore', 'jadavpur', 'howrah', 'dum dum'];
        let foundLocality = null;

        for (const locality of localities) {
            if (messageLower.includes(locality)) {
                foundLocality = locality.split(' ').map(word => word.charAt(0).toUpperCase() + word.slice(1)).join(' ');
                break;
            }
        }

        if (foundLocality) {
            conversationState.location = foundLocality;
            conversationState.step = 'preference_asked';

            hideTypingIndicator();
            addMessage(`Great! I found restaurants in ${foundLocality}! 🎉\n\nWhat are you in the mood for today?`, 'bot');

            // Show preference options
            addPreferenceButtons();
            return;
        }
    }

    // Step 2: Check if preference is selected
    if (conversationState.step === 'preference_asked') {
        const preferences = {
            'spicy': ['spicy', 'hot', 'chili', 'masala'],
            'fast food': ['fast food', 'burger', 'pizza', 'fries', 'quick'],
            'drinks': ['drink', 'coffee', 'cold coffee', 'juice', 'shake', 'beverage', 'tea'],
            'chinese': ['chinese', 'noodles', 'fried rice', 'manchurian'],
            'bengali': ['bengali', 'fish', 'rice', 'traditional'],
            'dessert': ['dessert', 'sweet', 'ice cream', 'cake'],
            'italian': ['italian', 'pasta', 'pizza', 'lasagna'],
            'mexican': ['mexican', 'tacos', 'burrito', 'nachos'],
            'healthy': ['healthy', 'salad', 'soup', 'grilled'],
            'breakfast': ['breakfast', 'pancake', 'omelette', 'toast'],
            'snacks': ['snacks', 'samosa', 'pakora', 'finger food']
        };

        let foundPreference = null;
        for (const [pref, keywords] of Object.entries(preferences)) {
            if (keywords.some(keyword => messageLower.includes(keyword))) {
                foundPreference = pref;
                break;
            }
        }

        if (foundPreference) {
            conversationState.preference = foundPreference;
            conversationState.step = 'price_asked';

            hideTypingIndicator();
            addMessage(`Great choice! 😊\n\nWhat's your budget for this meal?`, 'bot');
            addPriceButtons();
            return;
        }
    }

    // Step 3: Check if price range is selected
    if (conversationState.step === 'price_asked') {
        const priceRanges = {
            'budget': ['budget', 'cheap', 'affordable', 'low'],
            'mid-range': ['mid', 'medium', 'moderate', 'average'],
            'premium': ['premium', 'expensive', 'luxury', 'high']
        };

        let foundPrice = null;
        for (const [range, keywords] of Object.entries(priceRanges)) {
            if (keywords.some(keyword => messageLower.includes(keyword))) {
                foundPrice = range;
                break;
            }
        }

        if (foundPrice) {
            conversationState.priceRange = foundPrice;
            conversationState.step = 'dietary_asked';

            hideTypingIndicator();
            addMessage(`Perfect! 👍\n\nAny dietary preferences?`, 'bot');
            addDietaryButtons();
            return;
        }
    }

    // Step 4: Check if dietary preference is selected
    if (conversationState.step === 'dietary_asked') {
        const dietary = {
            'veg': ['veg', 'vegetarian', 'veggie'],
            'non-veg': ['non-veg', 'non veg', 'meat', 'chicken', 'fish'],
            'both': ['both', 'any', 'all', 'no preference']
        };

        let foundDietary = null;
        for (const [diet, keywords] of Object.entries(dietary)) {
            if (keywords.some(keyword => messageLower.includes(keyword))) {
                foundDietary = diet;
                break;
            }
        }

        if (foundDietary) {
            conversationState.dietary = foundDietary;
            conversationState.step = 'recommending';

            // Get recommendations with all filters
            getRecommendations(
                conversationState.location,
                conversationState.preference,
                conversationState.priceRange,
                conversationState.dietary
            );
            return;
        }
    }

    // Fallback: Ask for location if not provided
    if (!conversationState.location) {
        hideTypingIndicator();
        conversationState.step = 'location_asked';
        addMessage("I'd love to help you find great food! 🍽️\n\nFirst, which area are you in?", 'bot');
        addSuggestions([
            'Southern Avenue',
            'Park Street',
            'Salt Lake',
            'Ballygunge'
        ]);
    } else {
        // General response
        hideTypingIndicator();
        addMessage("I'm here to help you find delicious food! Try telling me what you're craving.", 'bot');
    }
}

function addPreferenceButtons() {
    const messageDiv = document.createElement('div');
    messageDiv.className = 'chatbot-message bot';

    const buttonsDiv = document.createElement('div');
    buttonsDiv.className = 'preference-buttons';

    const preferences = [
        { emoji: '🌶️', text: 'Spicy Food', value: 'spicy' },
        { emoji: '🍔', text: 'Fast Food', value: 'fast food' },
        { emoji: '☕', text: 'Drinks & Coffee', value: 'drinks' },
        { emoji: '🍜', text: 'Chinese', value: 'chinese' },
        { emoji: '🍛', text: 'Bengali', value: 'bengali' },
        { emoji: '🍰', text: 'Desserts', value: 'dessert' },
        { emoji: '🍝', text: 'Italian', value: 'italian' },
        { emoji: '🌮', text: 'Mexican', value: 'mexican' },
        { emoji: '🥗', text: 'Healthy', value: 'healthy' },
        { emoji: '🍳', text: 'Breakfast', value: 'breakfast' },
        { emoji: '🍿', text: 'Snacks', value: 'snacks' }
    ];

    preferences.forEach(pref => {
        const button = document.createElement('button');
        button.className = 'preference-btn';
        button.innerHTML = `${pref.emoji} ${pref.text}`;
        button.onclick = () => {
            chatbotInput.value = pref.value;
            sendMessage();
        };
        buttonsDiv.appendChild(button);
    });

    messageDiv.appendChild(buttonsDiv);
    chatbotMessages.appendChild(messageDiv);
    chatbotMessages.scrollTop = chatbotMessages.scrollHeight;
}

function addPriceButtons() {
    const messageDiv = document.createElement('div');
    messageDiv.className = 'chatbot-message bot';

    const buttonsDiv = document.createElement('div');
    buttonsDiv.className = 'preference-buttons';
    buttonsDiv.style.gridTemplateColumns = 'repeat(3, 1fr)';

    const prices = [
        { emoji: '💰', text: 'Budget', value: 'budget', desc: 'Under ₹200' },
        { emoji: '💵', text: 'Mid-Range', value: 'mid-range', desc: '₹200-500' },
        { emoji: '💎', text: 'Premium', value: 'premium', desc: 'Above ₹500' }
    ];

    prices.forEach(price => {
        const button = document.createElement('button');
        button.className = 'preference-btn';
        button.innerHTML = `${price.emoji} ${price.text}<br><small style="font-size: 11px; opacity: 0.7;">${price.desc}</small>`;
        button.onclick = () => {
            chatbotInput.value = price.value;
            sendMessage();
        };
        buttonsDiv.appendChild(button);
    });

    messageDiv.appendChild(buttonsDiv);
    chatbotMessages.appendChild(messageDiv);
    chatbotMessages.scrollTop = chatbotMessages.scrollHeight;
}

function addDietaryButtons() {
    const messageDiv = document.createElement('div');
    messageDiv.className = 'chatbot-message bot';

    const buttonsDiv = document.createElement('div');
    buttonsDiv.className = 'preference-buttons';
    buttonsDiv.style.gridTemplateColumns = 'repeat(3, 1fr)';

    const dietary = [
        { emoji: '🥬', text: 'Vegetarian', value: 'veg' },
        { emoji: '🍗', text: 'Non-Veg', value: 'non-veg' },
        { emoji: '🍽️', text: 'Both', value: 'both' }
    ];

    dietary.forEach(diet => {
        const button = document.createElement('button');
        button.className = 'preference-btn';
        button.innerHTML = `${diet.emoji} ${diet.text}`;
        button.onclick = () => {
            chatbotInput.value = diet.value;
            sendMessage();
        };
        buttonsDiv.appendChild(button);
    });

    messageDiv.appendChild(buttonsDiv);
    chatbotMessages.appendChild(messageDiv);
    chatbotMessages.scrollTop = chatbotMessages.scrollHeight;
}

function getRecommendations(location, preference) {
    fetch('/zomato-tomato/backend/api/chatbot-handler.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=recommend&location=${encodeURIComponent(location)}&preference=${encodeURIComponent(preference)}`
    })
        .then(response => response.json())
        .then(data => {
            hideTypingIndicator();

            if (data.success && data.recommendations && data.recommendations.length > 0) {
                let responseText = `Perfect! Here are the best ${preference} options in ${location}:\n\n`;

                data.recommendations.forEach((rec, index) => {
                    responseText += `${index + 1}. 🍽️ ${rec.restaurant_name}\n`;
                    responseText += `   ⭐ ${rec.rating} • ${rec.cuisine_type}\n`;
                    if (rec.best_dishes && rec.best_dishes.length > 0) {
                        responseText += `   🌟 Must try: ${rec.best_dishes.join(', ')}\n`;
                    }
                    responseText += `\n`;
                });

                addMessage(responseText, 'bot');

                // Add action buttons
                addActionButtons(data.recommendations);
            } else {
                addMessage(`Sorry, I couldn't find any ${preference} options in ${location} right now. 😕\n\nWould you like to try a different area or food type?`, 'bot');
                resetConversation();
            }
        })
        .catch(error => {
            hideTypingIndicator();
            console.error('Error:', error);
            addMessage('Sorry, I encountered an error. Please try again.', 'bot');
        });
}

function addActionButtons(recommendations) {
    const messageDiv = document.createElement('div');
    messageDiv.className = 'chatbot-message bot';

    const actionsDiv = document.createElement('div');
    actionsDiv.className = 'action-buttons';

    if (recommendations.length > 0) {
        const viewBtn = document.createElement('a');
        viewBtn.href = `/zomato-tomato/frontend/pages/restaurant.php?id=${recommendations[0].restaurant_id}`;
        viewBtn.className = 'action-btn primary';
        viewBtn.textContent = '🍽️ View Menu';
        actionsDiv.appendChild(viewBtn);
    }

    const searchAgainBtn = document.createElement('button');
    searchAgainBtn.className = 'action-btn secondary';
    searchAgainBtn.textContent = '🔄 Search Again';
    searchAgainBtn.onclick = resetConversation;
    actionsDiv.appendChild(searchAgainBtn);

    messageDiv.appendChild(actionsDiv);
    chatbotMessages.appendChild(messageDiv);
    chatbotMessages.scrollTop = chatbotMessages.scrollHeight;
}

function resetConversation() {
    conversationState = {
        step: 'initial',
        location: null,
        preference: null
    };

    addMessage("Let's start fresh! Which area are you in?", 'bot');
    addSuggestions([
        'Southern Avenue',
        'Park Street',
        'Salt Lake',
        'Ballygunge'
    ]);
}

function addMessage(text, sender) {
    const messageDiv = document.createElement('div');
    messageDiv.className = `chatbot-message ${sender}`;

    const bubble = document.createElement('div');
    bubble.className = 'message-bubble';
    bubble.textContent = text;
    bubble.style.whiteSpace = 'pre-line';

    messageDiv.appendChild(bubble);
    chatbotMessages.appendChild(messageDiv);

    // Scroll to bottom
    chatbotMessages.scrollTop = chatbotMessages.scrollHeight;
}

function addSuggestions(suggestions) {
    const messageDiv = document.createElement('div');
    messageDiv.className = 'chatbot-message bot';

    const suggestionsDiv = document.createElement('div');
    suggestionsDiv.className = 'chatbot-suggestions';

    suggestions.forEach(suggestion => {
        const chip = document.createElement('div');
        chip.className = 'suggestion-chip';
        chip.textContent = suggestion;
        chip.onclick = () => {
            chatbotInput.value = suggestion;
            sendMessage();
        };
        suggestionsDiv.appendChild(chip);
    });

    messageDiv.appendChild(suggestionsDiv);
    chatbotMessages.appendChild(messageDiv);
    chatbotMessages.scrollTop = chatbotMessages.scrollHeight;
}

function showTypingIndicator() {
    const typingDiv = document.createElement('div');
    typingDiv.className = 'chatbot-message bot';
    typingDiv.id = 'typingIndicator';

    const indicator = document.createElement('div');
    indicator.className = 'typing-indicator';
    indicator.innerHTML = '<div class="typing-dot"></div><div class="typing-dot"></div><div class="typing-dot"></div>';

    typingDiv.appendChild(indicator);
    chatbotMessages.appendChild(typingDiv);
    chatbotMessages.scrollTop = chatbotMessages.scrollHeight;
}

function hideTypingIndicator() {
    const indicator = document.getElementById('typingIndicator');
    if (indicator) {
        indicator.remove();
    }
}

// Event listeners
chatbotSend.addEventListener('click', sendMessage);
chatbotInput.addEventListener('keypress', (e) => {
    if (e.key === 'Enter') {
        sendMessage();
    }
});

// Initial greeting
setTimeout(() => {
    addSuggestions([
        'Southern Avenue',
        'Park Street',
        'Salt Lake',
        'Help me find food'
    ]);
}, 500);
