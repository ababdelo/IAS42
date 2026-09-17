(() => {
    'use strict';

    const t = (key, fallback) => {
        try {
            if (typeof window.t === 'function') {
                return window.t(key) || fallback;
            }
        } catch (_) { }

        return fallback;
    };

    const lockBody = () => {
        document.body.classList.add('modal-open');
    };

    const unlockBody = () => {
        if (!document.querySelector('.sector-modal-overlay[aria-hidden="false"]')) {
            document.body.classList.remove('modal-open');
        }
    };

    const setModalState = (modal, open) => {
        if (!modal) {
            return;
        }

        modal.setAttribute('aria-hidden', open ? 'false' : 'true');

        if (open) {
            lockBody();
        } else {
            unlockBody();
        }
    };

    const normalizeMac = (value) => {
        const hex = String(value || '')
            .replace(/[^0-9a-f]/gi, '')
            .slice(0, 12)
            .toUpperCase();

        return hex.match(/.{1,2}/g)?.join(':') ?? '';
    };

    const getCardData = (button) => {
        const card = button?.closest('.sector-card-square');

        if (!card) {
            return null;
        }

        return {
            card,
            id: card.dataset.sectorId || '',
            name: card.dataset.sectorName || '',
            crop: card.dataset.cropName || '',
            image: card.dataset.imagePath || '',
            nodeId: card.dataset.nodeId || '',
            mac: card.dataset.macAddress || ''
        };
    };

    const setupAddModal = () => {
        const modal = document.getElementById('addSectorModal');
        const form = document.getElementById('addSectorForm');
        const openButton = document.getElementById('openAddModalBtn');

        if (!modal || !form || !openButton) {
            return;
        }

        const cropSelect = document.getElementById('addCropSelect');
        const customCropField = document.getElementById('addCustomCropField');
        const customCropInput = document.getElementById('addCustomCrop');
        const macInput = document.getElementById('addMacAddress');
        const selectedImageInput = document.getElementById('addSelectedImage');
        const imageModeInput = document.getElementById('addImageMode');

        const imageTabs = [...modal.querySelectorAll('[data-image-tab]')];
        const imagePanels = [...modal.querySelectorAll('[data-image-panel]')];
        const imageCards = [
            ...modal.querySelectorAll('#addImgGrid .grid-img-card')
        ];

        const uploadDropzone = document.getElementById('addUploadDropzone');
        const uploadInput = document.getElementById('addSectorImageFile');
        const uploadEmpty = document.getElementById('addUploadEmpty');
        const uploadPreview = document.getElementById('addUploadPreview');
        const uploadPreviewImage = document.getElementById('addUploadPreviewImage');
        const uploadFileName = document.getElementById('addUploadFileName');
        const removeUploadButton = document.getElementById('addRemoveUpload');

        let previewUrl = null;

        const revokePreview = () => {
            if (previewUrl) {
                URL.revokeObjectURL(previewUrl);
                previewUrl = null;
            }
        };

        const clearUpload = () => {
            revokePreview();

            if (uploadInput) {
                uploadInput.value = '';
            }

            if (uploadPreview) {
                uploadPreview.hidden = true;
            }

            if (uploadEmpty) {
                uploadEmpty.hidden = false;
            }

            if (uploadPreviewImage) {
                uploadPreviewImage.removeAttribute('src');
            }

            if (uploadFileName) {
                uploadFileName.textContent = '';
            }
        };

        const clearDefaultSelection = () => {
            imageCards.forEach((card) => {
                card.classList.remove('selected');
                card.setAttribute('aria-pressed', 'false');
            });

            if (selectedImageInput) {
                selectedImageInput.value = '';
            }
        };

        const updateCustomCropVisibility = () => {
            const isCustom = cropSelect?.value === 'other';

            if (!customCropField || !customCropInput) {
                return;
            }

            customCropField.hidden = !isCustom;
            customCropInput.disabled = !isCustom;
            customCropInput.required = isCustom;

            if (!isCustom) {
                customCropInput.value = '';
            }
        };

        const setImageMode = (mode) => {
            const normalized = mode === 'upload' ? 'upload' : 'default';

            if (imageModeInput) {
                imageModeInput.value = normalized;
            }

            imageTabs.forEach((tab) => {
                const active = tab.dataset.imageTab === normalized;

                tab.classList.toggle('is-active', active);
                tab.setAttribute('aria-selected', active ? 'true' : 'false');
            });

            imagePanels.forEach((panel) => {
                const active = panel.dataset.imagePanel === normalized;

                panel.classList.toggle('is-active', active);
                panel.hidden = !active;
            });
        };

        const selectDefaultImage = (card) => {
            if (!card) {
                return;
            }

            clearDefaultSelection();

            card.classList.add('selected');
            card.setAttribute('aria-pressed', 'true');

            if (selectedImageInput) {
                selectedImageInput.value = card.dataset.imageFile || '';
            }

            if (cropSelect && card.dataset.cropValue) {
                cropSelect.value = card.dataset.cropValue;
                updateCustomCropVisibility();
            }
        };

        const handleUpload = (file) => {
            if (!file || !uploadInput) {
                return;
            }

            const allowedTypes = new Set([
                'image/jpeg',
                'image/png',
                'image/webp'
            ]);

            if (!allowedTypes.has(file.type)) {
                clearUpload();

                window.alert(
                    t(
                        'sectors.modal.add.upload_invalid_type',
                        'Only JPG, PNG, and WebP images are allowed.'
                    )
                );

                return;
            }

            if (file.size > 2 * 1024 * 1024) {
                clearUpload();

                window.alert(
                    t(
                        'sectors.modal.add.upload_too_large',
                        'The image must be 2 MB or smaller.'
                    )
                );

                return;
            }

            clearDefaultSelection();
            setImageMode('upload');

            revokePreview();

            previewUrl = URL.createObjectURL(file);

            if (uploadPreviewImage) {
                uploadPreviewImage.src = previewUrl;
            }

            if (uploadFileName) {
                uploadFileName.textContent = file.name;
            }

            if (uploadPreview) {
                uploadPreview.hidden = false;
            }

            if (uploadEmpty) {
                uploadEmpty.hidden = true;
            }

            if (cropSelect && cropSelect.value !== 'other') {
                cropSelect.value = 'other';
                updateCustomCropVisibility();
            }
        };

        const reset = () => {
            form.reset();
            clearDefaultSelection();
            clearUpload();
            setImageMode('default');
            updateCustomCropVisibility();
        };

        const open = () => {
            reset();
            setModalState(modal, true);

            window.setTimeout(() => {
                form.querySelector('input[name="sector_name"]')?.focus();
            }, 40);
        };

        const close = () => {
            setModalState(modal, false);
            reset();
        };

        openButton.addEventListener('click', open);

        modal.querySelectorAll('.close-modal').forEach((button) => {
            button.addEventListener('click', close);
        });

        modal.addEventListener('click', (event) => {
            if (event.target === modal) {
                close();
            }
        });

        imageTabs.forEach((tab) => {
            tab.addEventListener('click', () => {
                const mode = tab.dataset.imageTab;

                setImageMode(mode);

                if (mode === 'default') {
                    clearUpload();
                } else {
                    clearDefaultSelection();
                }
            });
        });

        cropSelect?.addEventListener('change', () => {
            updateCustomCropVisibility();

            if (cropSelect.value === 'other') {
                clearDefaultSelection();
                return;
            }

            const card = imageCards.find(
                (item) => item.dataset.cropValue === cropSelect.value
            );

            if (card) {
                clearUpload();
                selectDefaultImage(card);
                setImageMode('default');
            }
        });

        imageCards.forEach((card) => {
            card.addEventListener('click', () => {
                clearUpload();
                selectDefaultImage(card);
                setImageMode('default');
            });
        });

        macInput?.addEventListener('input', () => {
            macInput.value = normalizeMac(macInput.value);
        });

        uploadInput?.addEventListener('change', () => {
            handleUpload(uploadInput.files?.[0]);
        });

        removeUploadButton?.addEventListener('click', (event) => {
            event.preventDefault();
            event.stopPropagation();
            clearUpload();
        });

        ['dragenter', 'dragover'].forEach((eventName) => {
            uploadDropzone?.addEventListener(eventName, (event) => {
                event.preventDefault();
                event.stopPropagation();
                uploadDropzone.classList.add('is-dragover');
            });
        });

        ['dragleave', 'drop'].forEach((eventName) => {
            uploadDropzone?.addEventListener(eventName, (event) => {
                event.preventDefault();
                event.stopPropagation();
                uploadDropzone.classList.remove('is-dragover');
            });
        });

        uploadDropzone?.addEventListener('drop', (event) => {
            const file = event.dataTransfer?.files?.[0];

            if (!file || !uploadInput) {
                return;
            }

            try {
                const transfer = new DataTransfer();
                transfer.items.add(file);
                uploadInput.files = transfer.files;
            } catch (_) { }

            handleUpload(file);
        });

        uploadDropzone?.addEventListener('click', (event) => {
            if (event.target.closest('#addRemoveUpload')) {
                return;
            }

            uploadInput?.click();
        });

        uploadDropzone?.addEventListener('keydown', (event) => {
            if (event.key !== 'Enter' && event.key !== ' ') {
                return;
            }

            if (event.target.closest('#addRemoveUpload')) {
                return;
            }

            event.preventDefault();
            uploadInput?.click();
        });

        form.addEventListener('submit', () => {
            if (macInput) {
                macInput.value = normalizeMac(macInput.value);
            }
        });

        reset();

        window.addEventListener('beforeunload', revokePreview);
    };

    const setupEditModal = () => {
        const modal = document.getElementById('editSectorModal');
        const form = document.getElementById('editSectorForm');

        if (!modal || !form) {
            return;
        }

        const fieldId =
            new URLSearchParams(window.location.search).get('field') || '';

        const sectorId = document.getElementById('editSectorId');
        const sectorName = document.getElementById('editSectorName');
        const nodeId = document.getElementById('editNodeId');
        const mac = document.getElementById('editMacAddress');
        const cropSelect = document.getElementById('editCropSelect');
        const customCropField =
            document.getElementById('editCustomCropField');
        const customCrop = document.getElementById('editCustomCrop');

        const selectedImage =
            document.getElementById('editSelectedImage');
        const imageMode =
            document.getElementById('editImageMode');

        const tabs = [...modal.querySelectorAll('[data-image-tab]')];
        const panels = [...modal.querySelectorAll('[data-image-panel]')];
        const cards = [
            ...modal.querySelectorAll('#editImgGrid .grid-img-card')
        ];

        const uploadDropzone =
            document.getElementById('editUploadDropzone');
        const uploadInput =
            document.getElementById('editSectorImageFile');
        const uploadEmpty =
            document.getElementById('editUploadEmpty');
        const uploadPreview =
            document.getElementById('editUploadPreview');
        const uploadPreviewImage =
            document.getElementById('editUploadPreviewImage');
        const uploadFileName =
            document.getElementById('editUploadFileName');
        const removeUpload =
            document.getElementById('editRemoveUpload');

        const state = {
            originalNodeId: '',
            originalMac: '',
            originalImagePath: ''
        };

        let previewUrl = null;

        const revokePreview = () => {
            if (previewUrl) {
                URL.revokeObjectURL(previewUrl);
                previewUrl = null;
            }
        };

        const clearUpload = () => {
            revokePreview();

            if (uploadInput) {
                uploadInput.value = '';
            }

            if (uploadPreview) {
                uploadPreview.hidden = true;
            }

            if (uploadEmpty) {
                uploadEmpty.hidden = false;
            }

            if (uploadPreviewImage) {
                uploadPreviewImage.removeAttribute('src');
            }

            if (uploadFileName) {
                uploadFileName.textContent = '';
            }

            if (imageMode) {
                imageMode.value = 'keep';
            }
        };

        const clearCardSelection = () => {
            cards.forEach((card) => {
                card.classList.remove('selected');
                card.setAttribute('aria-pressed', 'false');
            });

            if (selectedImage) {
                selectedImage.value = '';
            }
        };

        const updateCustomCrop = () => {
            const isCustom = cropSelect?.value === 'other';

            if (!customCropField || !customCrop) {
                return;
            }

            customCropField.hidden = !isCustom;
            customCrop.disabled = !isCustom;
            customCrop.required = isCustom;

            if (!isCustom) {
                customCrop.value = '';
            }
        };

        const setMode = (mode) => {
            const normalized = mode === 'upload' ? 'upload' : 'default';

            tabs.forEach((tab) => {
                const active = tab.dataset.imageTab === normalized;

                tab.classList.toggle('is-active', active);
                tab.setAttribute('aria-selected', active ? 'true' : 'false');
            });

            panels.forEach((panel) => {
                const active =
                    panel.dataset.imagePanel === normalized;

                panel.classList.toggle('is-active', active);
                panel.hidden = !active;
            });
        };

        const selectCard = (card, changeCrop = true) => {
            if (!card) {
                return;
            }

            clearCardSelection();

            card.classList.add('selected');
            card.setAttribute('aria-pressed', 'true');

            if (selectedImage) {
                selectedImage.value =
                    card.dataset.imageFile || '';
            }

            if (imageMode) {
                imageMode.value = 'default';
            }

            if (
                changeCrop &&
                cropSelect &&
                card.dataset.cropValue
            ) {
                cropSelect.value = card.dataset.cropValue;
                updateCustomCrop();
            }
        };

        const setInitialImage = (imagePath, crop) => {
            clearCardSelection();

            if (imageMode) {
                imageMode.value = 'keep';
            }

            const currentImage = String(imagePath || '');

            const defaultFile =
                currentImage.startsWith('/assets/imgs/plants/') &&
                    !currentImage.includes('/custom/')
                    ? currentImage.split('/').pop()
                    : '';

            if (defaultFile) {
                const card = cards.find(
                    (item) =>
                        item.dataset.imageFile === defaultFile
                );

                if (card) {
                    selectCard(card, false);
                }
            }

            setMode('default');

            if (!cropSelect) {
                return;
            }

            const cropValue = String(crop || '');

            const option = [...cropSelect.options].find(
                (item) => item.value === cropValue
            );

            if (option) {
                cropSelect.value = cropValue;

                if (customCrop) {
                    customCrop.value = '';
                }
            } else {
                cropSelect.value = 'other';

                if (customCrop) {
                    customCrop.value = cropValue;
                }
            }

            updateCustomCrop();
        };

        const open = (data) => {
            state.originalNodeId = String(data?.nodeId || '')
                .trim()
                .toUpperCase();

            state.originalMac = normalizeMac(data?.mac || '');
            state.originalImagePath = String(data?.image || '');

            if (sectorId) {
                sectorId.value = String(data?.id || '');
            }

            if (sectorName) {
                sectorName.value = String(data?.name || '');
            }

            if (nodeId) {
                nodeId.value = state.originalNodeId;
            }

            if (mac) {
                mac.value = state.originalMac;
            }

            if (cropSelect) {
                cropSelect.value = '';
            }

            if (customCrop) {
                customCrop.value = '';
            }

            clearUpload();
            setInitialImage(
                state.originalImagePath,
                data?.crop || ''
            );

            form.action =
                `/sectors?field=${encodeURIComponent(fieldId)}`;

            setModalState(modal, true);

            window.setTimeout(() => {
                sectorName?.focus();
            }, 40);
        };

        const close = () => {
            setModalState(modal, false);

            form.reset();

            clearCardSelection();
            clearUpload();

            state.originalNodeId = '';
            state.originalMac = '';
            state.originalImagePath = '';

            setMode('default');
            updateCustomCrop();
        };

        document
            .querySelectorAll('[data-sector-action="edit"]')
            .forEach((button) => {
                button.addEventListener('click', () => {
                    const data = getCardData(button);

                    if (data) {
                        open(data);
                    }
                });
            });

        modal
            .querySelectorAll('.close-edit-modal')
            .forEach((button) => {
                button.addEventListener('click', close);
            });

        modal.addEventListener('click', (event) => {
            if (event.target === modal) {
                close();
            }
        });

        tabs.forEach((tab) => {
            tab.addEventListener('click', () => {
                const mode = tab.dataset.imageTab;

                setMode(mode);

                if (mode === 'upload') {
                    clearCardSelection();
                } else {
                    clearUpload();

                    if (imageMode) {
                        imageMode.value = 'keep';
                    }
                }
            });
        });

        cropSelect?.addEventListener('change', () => {
            updateCustomCrop();

            if (cropSelect.value === 'other') {
                clearCardSelection();
                return;
            }

            const card = cards.find(
                (item) =>
                    item.dataset.cropValue === cropSelect.value
            );

            if (card) {
                clearUpload();
                selectCard(card, false);
                setMode('default');
            }
        });

        cards.forEach((card) => {
            card.addEventListener('click', () => {
                clearUpload();
                selectCard(card, true);
                setMode('default');
            });
        });

        nodeId?.addEventListener('input', () => {
            nodeId.value = nodeId.value
                .toUpperCase()
                .replace(/[^A-Z0-9_-]/g, '')
                .slice(0, 50);
        });

        mac?.addEventListener('input', () => {
            mac.value = normalizeMac(mac.value);
        });

        const handleUpload = (file) => {
            if (!file || !uploadInput) {
                return;
            }

            const allowedTypes = new Set([
                'image/jpeg',
                'image/png',
                'image/webp'
            ]);

            if (!allowedTypes.has(file.type)) {
                clearUpload();

                window.alert(
                    t(
                        'sectors.modal.edit.upload_invalid_type',
                        'Only JPG, PNG, and WebP images are allowed.'
                    )
                );

                return;
            }

            if (file.size > 2 * 1024 * 1024) {
                clearUpload();

                window.alert(
                    t(
                        'sectors.modal.edit.upload_too_large',
                        'The image must be 2 MB or smaller.'
                    )
                );

                return;
            }

            clearCardSelection();
            setMode('upload');

            if (imageMode) {
                imageMode.value = 'upload';
            }

            revokePreview();

            previewUrl = URL.createObjectURL(file);

            if (uploadPreviewImage) {
                uploadPreviewImage.src = previewUrl;
            }

            if (uploadFileName) {
                uploadFileName.textContent = file.name;
            }

            if (uploadPreview) {
                uploadPreview.hidden = false;
            }

            if (uploadEmpty) {
                uploadEmpty.hidden = true;
            }
        };

        uploadInput?.addEventListener('change', () => {
            handleUpload(uploadInput.files?.[0]);
        });

        removeUpload?.addEventListener('click', (event) => {
            event.preventDefault();
            event.stopPropagation();
            clearUpload();
        });

        ['dragenter', 'dragover'].forEach((eventName) => {
            uploadDropzone?.addEventListener(eventName, (event) => {
                event.preventDefault();
                event.stopPropagation();
                uploadDropzone.classList.add('is-dragover');
            });
        });

        ['dragleave', 'drop'].forEach((eventName) => {
            uploadDropzone?.addEventListener(eventName, (event) => {
                event.preventDefault();
                event.stopPropagation();
                uploadDropzone.classList.remove('is-dragover');
            });
        });

        uploadDropzone?.addEventListener('drop', (event) => {
            const file = event.dataTransfer?.files?.[0];

            if (!file || !uploadInput) {
                return;
            }

            try {
                const transfer = new DataTransfer();
                transfer.items.add(file);
                uploadInput.files = transfer.files;
            } catch (_) { }

            handleUpload(file);
        });

        uploadDropzone?.addEventListener('click', (event) => {
            if (event.target.closest('#editRemoveUpload')) {
                return;
            }

            uploadInput?.click();
        });

        uploadDropzone?.addEventListener('keydown', (event) => {
            if (event.key !== 'Enter' && event.key !== ' ') {
                return;
            }

            if (event.target.closest('#editRemoveUpload')) {
                return;
            }

            event.preventDefault();
            uploadInput?.click();
        });

        form.addEventListener('submit', () => {
            if (mac) {
                mac.value = normalizeMac(mac.value);
            }

            if (nodeId) {
                nodeId.value =
                    nodeId.value.trim().toUpperCase();
            }
        });

        window.addEventListener('beforeunload', revokePreview);

        setMode('default');
        updateCustomCrop();
    };

    const setupDeleteModal = () => {
        const modal = document.getElementById('deleteSectorModal');
        const form = document.getElementById('deleteSectorForm');

        if (!modal || !form) {
            return;
        }

        const fieldId =
            new URLSearchParams(window.location.search).get('field') || '';

        const sectorId =
            document.getElementById('deleteSectorId');

        const sectorName =
            document.getElementById('deleteSectorName');

        const password =
            document.getElementById('deleteSectorPassword');

        const open = (data) => {
            if (sectorId) {
                sectorId.value = String(data?.id || '');
            }

            if (sectorName) {
                sectorName.textContent = String(data?.name || 'Sector');
            }

            if (password) {
                password.value = '';
                password.type = 'password';
            }

            const toggleButton =
                modal.querySelector('.toggle-password');

            const icon = toggleButton?.querySelector('i');

            if (icon) {
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }

            if (toggleButton) {
                toggleButton.setAttribute(
                    'aria-label',
                    'Show password'
                );

                toggleButton.setAttribute(
                    'title',
                    'Show password'
                );
            }

            form.action =
                `/sectors?field=${encodeURIComponent(fieldId)}`;

            setModalState(modal, true);

            window.setTimeout(() => {
                password?.focus();
            }, 40);
        };

        const close = () => {
            setModalState(modal, false);

            if (password) {
                password.value = '';
                password.type = 'password';
            }

            const toggleButton =
                modal.querySelector('.toggle-password');

            const icon = toggleButton?.querySelector('i');

            if (icon) {
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        };

        document
            .querySelectorAll('[data-sector-action="delete"]')
            .forEach((button) => {
                button.addEventListener('click', () => {
                    const data = getCardData(button);

                    if (data) {
                        open(data);
                    }
                });
            });

        modal
            .querySelectorAll('.close-delete-modal')
            .forEach((button) => {
                button.addEventListener('click', close);
            });

        modal.addEventListener('click', (event) => {
            if (event.target === modal) {
                close();
            }
        });

        modal
            .querySelectorAll('.toggle-password')
            .forEach((button) => {
                button.addEventListener('click', () => {
                    const targetId =
                        button.getAttribute('data-target');

                    if (!targetId) {
                        return;
                    }

                    const input =
                        document.getElementById(targetId);

                    if (!input) {
                        return;
                    }

                    const icon =
                        button.querySelector('i');

                    const isPassword =
                        input.type === 'password';

                    input.type =
                        isPassword ? 'text' : 'password';

                    if (icon) {
                        icon.classList.toggle(
                            'fa-eye',
                            !isPassword
                        );

                        icon.classList.toggle(
                            'fa-eye-slash',
                            isPassword
                        );
                    }

                    button.setAttribute(
                        'aria-label',
                        isPassword
                            ? 'Hide password'
                            : 'Show password'
                    );

                    button.setAttribute(
                        'title',
                        isPassword
                            ? 'Hide password'
                            : 'Show password'
                    );
                });
            });
    };

    document.addEventListener('keydown', (event) => {
        if (event.key !== 'Escape') {
            return;
        }

        const active = document.querySelector(
            '.sector-modal-overlay[aria-hidden="false"]'
        );

        if (!active) {
            return;
        }

        active.setAttribute('aria-hidden', 'true');
        unlockBody();
    });

    setupAddModal();
    setupEditModal();
    setupDeleteModal();
})();
