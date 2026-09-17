(() => {
    'use strict';

    const modalSelector = '.field-modal-overlay';
    const openModal = (modal) => {
        if (!modal) return;
        modal.setAttribute('aria-hidden', 'false');
        document.body.classList.add('modal-open');
    };

    const closeModal = (modal, form) => {
        if (!modal) return;
        modal.setAttribute('aria-hidden', 'true');
        form?.reset();

        if (!document.querySelector(`${modalSelector}[aria-hidden="false"]`)) {
            document.body.classList.remove('modal-open');
        }
    };

    const addModal = document.getElementById('addFieldModal');
    const editModal = document.getElementById('editFieldModal');
    const deleteModal = document.getElementById('deleteFieldModal');
    const addForm = document.getElementById('addFieldForm');
    const editForm = document.getElementById('editFieldForm');
    const deleteForm = document.getElementById('deleteFieldForm');

    document.getElementById('openFieldModalBtn')?.addEventListener('click', () => {
        openModal(addModal);
        document.getElementById('addFieldName')?.focus();
    });

    document.querySelectorAll('.close-field-modal').forEach((button) => {
        button.addEventListener('click', () => closeModal(addModal, addForm));
    });

    document.querySelectorAll('.close-edit-field-modal').forEach((button) => {
        button.addEventListener('click', () => closeModal(editModal, editForm));
    });

    document.querySelectorAll('.close-delete-field-modal').forEach((button) => {
        button.addEventListener('click', () => closeModal(deleteModal, deleteForm));
    });

    document.querySelectorAll(modalSelector).forEach((modal) => {
        modal.addEventListener('click', (event) => {
            if (event.target !== modal) return;

            if (modal === addModal) closeModal(addModal, addForm);
            if (modal === editModal) closeModal(editModal, editForm);
            if (modal === deleteModal) closeModal(deleteModal, deleteForm);
        });
    });

    document.querySelectorAll('[data-field-action="edit"]').forEach((button) => {
        button.addEventListener('click', () => {
            const card = button.closest('.farm-row-card');
            if (!card) return;

            document.getElementById('editFieldId').value = card.dataset.fieldId || '';
            document.getElementById('editFieldName').value = card.dataset.fieldName || '';
            document.getElementById('editFieldLocation').value = card.dataset.fieldLocation || '';
            document.getElementById('editFieldArea').value = card.dataset.fieldArea || '';
            openModal(editModal);
            document.getElementById('editFieldName')?.focus();
        });
    });

    document.querySelectorAll('[data-field-action="delete"]').forEach((button) => {
        button.addEventListener('click', () => {
            const card = button.closest('.farm-row-card');
            if (!card) return;

            document.getElementById('deleteFieldId').value = card.dataset.fieldId || '';
            document.getElementById('deleteFieldName').textContent = card.dataset.fieldName || 'this field';
            openModal(deleteModal);
            document.getElementById('deleteFieldPassword')?.focus();
        });
    });

    document.querySelectorAll('.toggle-password[data-target="deleteFieldPassword"]').forEach((button) => {
        button.addEventListener('click', () => {
            const input = document.getElementById(button.dataset.target);
            if (!input) return;

            const isPassword = input.type === 'password';
            input.type = isPassword ? 'text' : 'password';
            button.setAttribute('aria-label', isPassword ? 'Hide password' : 'Show password');
            button.setAttribute('title', isPassword ? 'Hide password' : 'Show password');
            button.querySelector('i')?.classList.toggle('fa-eye-slash', isPassword);
            button.querySelector('i')?.classList.toggle('fa-eye', !isPassword);
        });
    });

    document.addEventListener('keydown', (event) => {
        if (event.key !== 'Escape') return;

        if (addModal?.getAttribute('aria-hidden') === 'false') closeModal(addModal, addForm);
        if (editModal?.getAttribute('aria-hidden') === 'false') closeModal(editModal, editForm);
        if (deleteModal?.getAttribute('aria-hidden') === 'false') closeModal(deleteModal, deleteForm);
    });
})();
