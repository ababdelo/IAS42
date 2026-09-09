/**
 * IAS42 Floating Chatbot UI Controller
 */
(() => {
  'use strict';

  const widget = document.getElementById('chatbotWidget');
  const toggleBtn = document.getElementById('chatbotToggle');
  const minimizeBtn = document.getElementById('chatbotMinimize');
  const chatWindow = document.getElementById('chatbotWindow');
  const textarea = document.getElementById('chatbotInput');

  if (!widget || !toggleBtn || !chatWindow) return;

  // CRITICAL BUG FIX: 
  // Moves the widget to the top of the body in the DOM tree. 
  // This physically places it before the sidebar overlay, completely bypassing 
  // the Chrome/Safari bug that erases elements behind backdrop-filters.
  document.body.prepend(widget);

  const openChat = () => {
    widget.classList.add('is-open');
    toggleBtn.setAttribute('aria-expanded', 'true');
    chatWindow.setAttribute('aria-hidden', 'false');
    if (textarea) textarea.focus();
  };

  const closeChat = () => {
    widget.classList.remove('is-open');
    toggleBtn.setAttribute('aria-expanded', 'false');
    chatWindow.setAttribute('aria-hidden', 'true');
  };

  toggleBtn.addEventListener('click', () => {
    widget.classList.contains('is-open') ? closeChat() : openChat();
  });

  minimizeBtn?.addEventListener('click', closeChat);

  // Close on Escape key
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && widget.classList.contains('is-open')) {
      closeChat();
    }
  });

  // Auto-resize textarea height as user types
  if (textarea) {
    textarea.addEventListener('input', function () {
      this.style.height = 'auto';
      this.style.height = `${Math.min(this.scrollHeight, 80)}px`;
    });
  }
})();
