(() => {
  'use strict';

  const form = document.getElementById('profileForm');
  if (!form) return;

  const csrf = document.getElementById('profileCsrfToken')?.value || '';
  const status = document.getElementById('profileStatus');
  const saveButton = document.getElementById('profileSaveButton');
  const avatarModal = document.getElementById('avatarModal');
  const currentAvatar = document.getElementById('currentAvatar');
  const defaultAvatarInput = document.getElementById('defaultAvatarInput');
  const fileInput = document.getElementById('profileAvatarUpload');
  const dropArea = document.getElementById('avatarDropArea');
  const preview = document.getElementById('avatarUploadPreview');
  const previewImage = document.getElementById('avatarUploadPreviewImage');
  const generateButton = document.getElementById('generateProfilePassword');

  const tr = (key, fallback = key) => {
    if (typeof window.t !== 'function') return fallback;
    return window.t(key) || fallback;
  };

  const setStatus = (message, kind = '') => {
    if (!status) return;

    status.textContent = message;
    status.className = `profile-status${kind ? ` ${kind}` : ''}`;
  };

  const setLoading = (loading) => {
    if (!saveButton) return;

    saveButton.disabled = loading;
    saveButton.classList.toggle('is-loading', loading);

    const text = saveButton.querySelector('span');

    if (text) {
      text.textContent = loading
        ? tr('profile.saving', 'Saving…')
        : tr('profile.save', 'Save Changes');
    }
  };

  const syncFieldState = (field) => {
    if (!field) return;

    const input = field.querySelector('input');
    if (!input) return;

    field.classList.toggle('has-value', input.value.trim() !== '');
    field.classList.toggle('is-focused', document.activeElement === input);
  };

  document.querySelectorAll('.profile-field').forEach((field) => {
    const input = field.querySelector('input');
    if (!input) return;

    syncFieldState(field);

    input.addEventListener('focus', () => syncFieldState(field));
    input.addEventListener('blur', () => syncFieldState(field));
    input.addEventListener('input', () => syncFieldState(field));
  });

  document.querySelectorAll('.edit-field').forEach((button) => {
    button.addEventListener('click', () => {
      const input = document.getElementById(button.dataset.target || '');
      const icon = button.querySelector('i');

      if (!input) return;

      if (input.readOnly) {
        input.dataset.originalValue = input.value;
        input.readOnly = false;

        button.classList.add('is-editing');
        button.setAttribute(
          'aria-label',
          tr('profile.actions.cancel_edit', 'Cancel editing')
        );

        if (icon) {
          icon.classList.remove('ri-pencil-line');
          icon.classList.add('ri-close-line');
        }

        input.focus();
        input.select();
      } else {
        input.value = input.dataset.originalValue ?? input.value;
        input.readOnly = true;

        button.classList.remove('is-editing');
        button.setAttribute(
          'aria-label',
          tr('profile.actions.edit', 'Edit field')
        );

        if (icon) {
          icon.classList.remove('ri-close-line');
          icon.classList.add('ri-pencil-line');
        }

        syncFieldState(button.closest('.profile-field'));
      }
    });
  });

  document.querySelectorAll('.password-toggle').forEach((button) => {
    button.addEventListener('click', () => {
      const input = document.getElementById(button.dataset.target || '');
      const icon = button.querySelector('i');

      if (!input) return;

      const show = input.type === 'password';

      input.type = show ? 'text' : 'password';

      button.setAttribute('aria-pressed', String(show));
      button.setAttribute(
        'aria-label',
        show
          ? tr('profile.actions.hide_password', 'Hide password')
          : tr('profile.actions.show_password', 'Show password')
      );

      if (icon) {
        icon.classList.toggle('ri-eye-line', show);
        icon.classList.toggle('ri-eye-off-line', !show);
      }
    });
  });

  const secureRandomInt = (max) => {
    if (!window.crypto?.getRandomValues || max <= 0) {
      throw new Error('Secure random generator unavailable.');
    }

    const buffer = new Uint32Array(1);
    const range = 0x100000000;
    const limit = range - (range % max);

    do {
      window.crypto.getRandomValues(buffer);
    } while (buffer[0] >= limit);

    return buffer[0] % max;
  };

  const pick = (chars) => chars[secureRandomInt(chars.length)];

  const shuffle = (array) => {
    for (let index = array.length - 1; index > 0; index -= 1) {
      const j = secureRandomInt(index + 1);
      [array[index], array[j]] = [array[j], array[index]];
    }

    return array;
  };

  const generatePassword = (length = 16) => {
    const lower = 'abcdefghijklmnopqrstuvwxyz';
    const upper = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    const numbers = '0123456789';
    const symbols = '!@#$%^&*?-_';
    const alphaNum = lower + upper + numbers;

    const chars = [
      pick(lower),
      pick(upper),
      pick(numbers),
      pick(symbols),
      pick(symbols)
    ];

    while (chars.length < Math.max(12, length)) {
      chars.push(pick(alphaNum));
    }

    return shuffle(chars).join('');
  };

  generateButton?.addEventListener('click', () => {
    const input = document.getElementById('newPassword');
    const confirm = document.getElementById('confirmPassword');

    if (!input) return;

    input.value = generatePassword();
    input.type = 'text';
    input.dispatchEvent(new Event('input', { bubbles: true }));

    const toggle = document.querySelector(
      '.password-toggle[data-target="newPassword"]'
    );

    const icon = toggle?.querySelector('i');

    if (toggle) {
      toggle.setAttribute('aria-pressed', 'true');
      toggle.setAttribute(
        'aria-label',
        tr('profile.actions.hide_password', 'Hide password')
      );

      icon?.classList.remove('ri-eye-off-line');
      icon?.classList.add('ri-eye-line');
    }

    if (confirm) {
      confirm.value = input.value;
      confirm.dispatchEvent(new Event('input', { bubbles: true }));
      syncFieldState(confirm.closest('.profile-field'));
    }

    input.focus();
  });

  const openModal = () => {
    if (!avatarModal) return;

    avatarModal.hidden = false;
    document.body.classList.add('profile-modal-open');

    document.getElementById('closeAvatarModal')?.focus();
  };

  const closeModal = () => {
    if (!avatarModal) return;

    avatarModal.hidden = true;
    document.body.classList.remove('profile-modal-open');
  };

  document
    .getElementById('openAvatarModal')
    ?.addEventListener('click', openModal);

  document
    .getElementById('closeAvatarModal')
    ?.addEventListener('click', closeModal);

  avatarModal
    ?.querySelectorAll('[data-close-avatar-modal]')
    .forEach((element) => {
      element.addEventListener('click', closeModal);
    });

  document.addEventListener('keydown', (event) => {
    if (
      event.key === 'Escape' &&
      avatarModal &&
      !avatarModal.hidden
    ) {
      closeModal();
    }
  });

  const setTab = (name) => {
    document.querySelectorAll('[data-avatar-tab]').forEach((tab) => {
      const active = tab.dataset.avatarTab === name;

      tab.classList.toggle('is-active', active);
      tab.setAttribute('aria-selected', String(active));
    });

    document.querySelectorAll('[data-avatar-panel]').forEach((panel) => {
      const active = panel.dataset.avatarPanel === name;

      panel.classList.toggle('is-active', active);
      panel.hidden = !active;
    });
  };

  document.querySelectorAll('[data-avatar-tab]').forEach((tab) => {
    tab.addEventListener('click', () => {
      setTab(tab.dataset.avatarTab);
    });
  });

  const clearUpload = () => {
    if (fileInput) fileInput.value = '';

    if (preview) preview.hidden = true;

    if (previewImage) previewImage.src = '';

    document.querySelectorAll('.avatar-option').forEach((option) => {
      option.classList.remove('is-selected');
      option.setAttribute('aria-pressed', 'false');
    });
  };

  const applySelectedAvatar = (filename) => {
    if (!filename || !currentAvatar) return;

    currentAvatar.src =
      `/assets/imgs/avatars/default/${encodeURIComponent(filename)}`;

    currentAvatar.dataset.pendingType = 'default';
    currentAvatar.dataset.pendingValue = filename;

    if (defaultAvatarInput) {
      defaultAvatarInput.value = filename;
    }

    clearUpload();
    closeModal();
  };

  document.querySelectorAll('.avatar-option').forEach((option) => {
    option.addEventListener('click', () => {
      document.querySelectorAll('.avatar-option').forEach((item) => {
        const selected = item === option;

        item.classList.toggle('is-selected', selected);
        item.setAttribute('aria-pressed', String(selected));
      });

      applySelectedAvatar(option.dataset.avatarValue || '');
    });
  });

  const showUploadPreview = (file) => {
    if (!file) return;

    const validTypes = [
      'image/jpeg',
      'image/png',
      'image/webp'
    ];

    if (!validTypes.includes(file.type)) {
      setStatus(
        tr(
          'profile.errors.avatar_type',
          'Only JPG, PNG and WEBP images are supported.'
        ),
        'error'
      );
      return;
    }

    if (file.size > 2 * 1024 * 1024) {
      setStatus(
        tr(
          'profile.errors.avatar_too_large',
          'Image must be 2 MB or smaller.'
        ),
        'error'
      );
      return;
    }

    const reader = new FileReader();

    reader.onload = (event) => {
      const result = String(event.target?.result || '');

      if (previewImage) {
        previewImage.src = result;
      }

      if (preview) {
        preview.hidden = false;
      }

      if (defaultAvatarInput) {
        defaultAvatarInput.value = '';
      }

      document.querySelectorAll('.avatar-option').forEach((option) => {
        option.classList.remove('is-selected');
        option.setAttribute('aria-pressed', 'false');
      });

      if (currentAvatar) {
        currentAvatar.src = result;
        currentAvatar.dataset.pendingType = 'uploaded';
        currentAvatar.dataset.pendingValue = file.name;
      }

      closeModal();
      setStatus('');
    };

    reader.onerror = () => {
      setStatus(
        tr(
          'profile.errors.avatar_upload_failed',
          'Unable to preview this image.'
        ),
        'error'
      );
    };

    reader.readAsDataURL(file);
  };

  fileInput?.addEventListener('change', () => {
    showUploadPreview(fileInput.files?.[0]);
  });

  dropArea?.addEventListener('dragover', (event) => {
    event.preventDefault();
    dropArea.classList.add('is-dragging');
  });

  dropArea?.addEventListener('dragleave', () => {
    dropArea.classList.remove('is-dragging');
  });

  dropArea?.addEventListener('drop', (event) => {
    event.preventDefault();
    dropArea.classList.remove('is-dragging');

    const file = event.dataTransfer?.files?.[0];

    if (!file || !fileInput) return;

    try {
      const transfer = new DataTransfer();
      transfer.items.add(file);
      fileInput.files = transfer.files;
    } catch {
      return;
    }

    showUploadPreview(file);
  });

  document
    .getElementById('removeAvatarUpload')
    ?.addEventListener('click', () => {
      clearUpload();

      if (currentAvatar) {
        currentAvatar.src =
          currentAvatar.dataset.currentOriginal || currentAvatar.src;

        currentAvatar.dataset.pendingType = '';
        currentAvatar.dataset.pendingValue = '';
      }

      setStatus('');
    });

  const rememberOriginalAvatar = () => {
    if (
      currentAvatar &&
      !currentAvatar.dataset.currentOriginal
    ) {
      currentAvatar.dataset.currentOriginal = currentAvatar.src;
    }
  };

  rememberOriginalAvatar();

  form.addEventListener('submit', async (event) => {
    event.preventDefault();

    setStatus(tr('profile.saving', 'Saving…'));
    setLoading(true);

    const formData = new FormData(form);

    try {
      const response = await fetch('/api/profile', {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
          Accept: 'application/json',
          'X-CSRF-Token': csrf
        },
        body: formData
      });

      const result = await response.json().catch(() => ({}));

      if (!response.ok || result.status !== 'success') {
        throw new Error(
          tr(
            result.message_key || 'profile.errors.generic',
            'Unable to update your profile.'
          )
        );
      }

      setStatus(
        tr(
          result.message_key || 'profile.messages.saved',
          'Profile updated successfully.'
        ),
        result.changed === false ? 'warning' : 'success'
      );

      if (result.data) {
        const displayName =
          `${result.data.first_name || ''} ${result.data.last_name || ''}`.trim();

        document
          .querySelectorAll('.sidebar__user-info strong')
          .forEach((element) => {
            element.textContent =
              displayName || result.data.username || '';
          });

        document
          .querySelectorAll('.header__user img, .sidebar__user img')
          .forEach((img) => {
            if (result.data.avatar) {
              img.src = result.data.avatar;
            }
          });

        if (result.data.avatar && currentAvatar) {
          currentAvatar.src = result.data.avatar;
          currentAvatar.dataset.currentOriginal =
            result.data.avatar;

          currentAvatar.dataset.currentType =
            currentAvatar.dataset.pendingType ||
            currentAvatar.dataset.currentType;

          currentAvatar.dataset.currentValue =
            currentAvatar.dataset.pendingValue ||
            currentAvatar.dataset.currentValue;
        }

        document.querySelectorAll('.edit-field').forEach((button) => {
          const input = document.getElementById(
            button.dataset.target || ''
          );

          const icon = button.querySelector('i');

          if (!input) return;

          input.readOnly = true;

          button.classList.remove('is-editing');
          button.setAttribute(
            'aria-label',
            tr('profile.actions.edit', 'Edit field')
          );

          icon?.classList.remove('ri-close-line');
          icon?.classList.add('ri-pencil-line');

          syncFieldState(button.closest('.profile-field'));
        });
      }

      if (result.changed !== false) {
        form
          .querySelectorAll('input[type="password"]')
          .forEach((input) => {
            input.value = '';
            input.type = 'password';
            input.dispatchEvent(
              new Event('input', { bubbles: true })
            );
          });

        document.querySelectorAll('.password-toggle').forEach((button) => {
          button.setAttribute('aria-pressed', 'false');

          button.setAttribute(
            'aria-label',
            tr(
              'profile.actions.show_password',
              'Show password'
            )
          );

          const icon = button.querySelector('i');

          icon?.classList.remove('ri-eye-line');
          icon?.classList.add('ri-eye-off-line');
        });

        defaultAvatarInput.value = '';

        if (fileInput) {
          fileInput.value = '';
        }
      }
    } catch (error) {
      console.error('Profile update failed:', error);

      setStatus(
        error.message ||
          tr(
            'profile.errors.generic',
            'Unable to update your profile.'
          ),
        'error'
      );
    } finally {
      setLoading(false);
    }
  });

  document.addEventListener('languageChanged', () => {
    document.querySelectorAll('.edit-field').forEach((button) => {
      if (!button.classList.contains('is-editing')) {
        button.setAttribute(
          'aria-label',
          tr('profile.actions.edit', 'Edit field')
        );
      }
    });

    document.querySelectorAll('.password-toggle').forEach((button) => {
      const visible =
        button.getAttribute('aria-pressed') === 'true';

      button.setAttribute(
        'aria-label',
        visible
          ? tr('profile.actions.hide_password', 'Hide password')
          : tr(
              'profile.actions.show_password',
              'Show password'
            )
      );
    });
  });
})();
