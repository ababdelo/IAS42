(() => {
  'use strict';

  // Field Modal Elements
  const fieldModal = document.getElementById('fieldModal');
  const openFieldBtn = document.getElementById('openFieldModalBtn');
  const closeFieldBtns = document.querySelectorAll('.close-field-modal');
  const fieldForm = document.getElementById('fieldForm');

  // Generic Open/Close
  const openModal = (modalEl) => modalEl?.setAttribute('aria-hidden', 'false');
  const closeModal = (modalEl, formEl) => {
    modalEl?.setAttribute('aria-hidden', 'true');
    formEl?.reset();
  };

  // Field Listeners
  openFieldBtn?.addEventListener('click', () => openModal(fieldModal));
  
  closeFieldBtns.forEach(btn => btn.addEventListener('click', () => closeModal(fieldModal, fieldForm)));
  
  fieldModal?.addEventListener('click', (e) => { 
    if (e.target === fieldModal) closeModal(fieldModal, fieldForm); 
  });
  
  fieldForm?.addEventListener('submit', (e) => {
    e.preventDefault();
    // Simulate API Call for MVP
    window.showNotification('success', window.t ? window.t('fields.messages.field_success') : 'Farm profile saved successfully.');
    closeModal(fieldModal, fieldForm);
  });

})();
