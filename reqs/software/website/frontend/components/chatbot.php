<!-- Floating Chatbot Widget -->
<div class="chatbot-widget" id="chatbotWidget">
    <!-- Floating Action Button -->
    <button type="button" class="chatbot-toggle" id="chatbotToggle" aria-label="Open AI Assistant" aria-expanded="false" aria-controls="chatbotWindow">
        <i class="ri-sparkling-2-line chatbot-toggle__icon-open"></i>
    </button>

    <!-- Chat Window Container -->
    <section class="chatbot-window" id="chatbotWindow" aria-hidden="true">
        <!-- Header -->
        <div class="chatbot-header">
            <div class="chatbot-header__info">
                <div class="chatbot-header__avatar">
                    <!-- Your custom bot avatar -->
                    <img src="/assets/imgs/avatars/bot.webp" alt="IAS42 Assistant">
                    <span class="chatbot-header__status-dot" aria-hidden="true"></span>
                </div>
                <div class="chatbot-header__details">
                    <h3 data-i18n="global.chatbot.title">IAS42 Assistant</h3>
                    <span class="chatbot-header__status-text" data-i18n="global.chatbot.status">Online</span>
                </div>
            </div>
            <div class="chatbot-header__actions">
                <button type="button" class="chatbot-header__btn" id="chatbotMinimize" aria-label="Minimize Chat">
                    <i class="ri-close-line"></i> <!-- Changed to close icon to match the new behavior -->
                </button>
            </div>
        </div>

        <!-- Chat Body / Conversation Log -->
        <div class="chatbot-body" id="chatbotBody">
            <!-- Bot Initial Greeting -->
            <div class="chatbot-message chatbot-message--bot">
                <div class="chatbot-message__bubble" data-i18n="global.chatbot.welcome">
                    Hello! I am your IAS42 agricultural assistant. How can I help you manage your crops and sensor data today?
                </div>
                <span class="chatbot-message__time">Just now</span>
            </div>
            <!-- Removed the .chatbot-suggestions block here -->
        </div>

        <!-- Chat Input Form -->
        <form class="chatbot-footer" id="chatbotForm" onsubmit="event.preventDefault();">
            <div class="chatbot-input-wrapper">
                <textarea 
                    id="chatbotInput" 
                    rows="1" 
                    placeholder="Ask about your crops, soil, sensors..." 
                    data-i18n-placeholder="global.chatbot.placeholder" 
                    aria-label="Chat input"
                ></textarea>
                <button type="submit" class="chatbot-send-btn" id="chatbotSendBtn" aria-label="Send message">
                    <i class="ri-send-plane-2-fill"></i>
                </button>
            </div>
        </form>
    </section>
</div>
