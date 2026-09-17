<div
    class="modal-overlay field-modal-overlay sector-modal-overlay"
    id="deleteFieldModal"
    aria-hidden="true">
    <div
        class="modal-content field-modal sector-modal sector-modal--delete"
        role="dialog"
        aria-modal="true"
        aria-labelledby="deleteFieldModalTitle">
        <button
            type="button"
            class="sector-delete-close close-delete-field-modal"
            aria-label="Close"
            title="Close">
            <i class="ri-close-line" aria-hidden="true"></i>
        </button>

        <form
            class="field-delete-form sector-delete-form"
            id="deleteFieldForm"
            method="POST"
            action="/fields">
            <input type="hidden" name="action" value="delete_field">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="field_id" id="deleteFieldId" value="">

            <div class="field-delete-dialog sector-delete-dialog">
                <h3
                    id="deleteFieldModalTitle"
                    class="field-delete-title sector-delete-title">
                    Delete <span id="deleteFieldName">this field</span>?
                </h3>

                <div class="field-danger-banner sector-danger-banner">
                    <div class="field-danger-banner__icon sector-danger-banner__icon" aria-hidden="true">
                        <i class="ri-alarm-warning-line"></i>
                    </div>
                    <p class="field-danger-banner__text sector-danger-banner__text">
                        <span class="field-delete-warning-title sector-delete-warning-title">This action is irreversible!</span>
                        Deleting this field permanently removes all sectors and connected nodes assigned to it.
                    </p>
                </div>

                <div class="field-delete-password sector-delete-password">
                    <label for="deleteFieldPassword">
                        Enter your password to confirm
                        <span class="required" aria-hidden="true">*</span>
                    </label>

                    <div class="field-password password-field">
                        <input
                            id="deleteFieldPassword"
                            type="password"
                            name="password"
                            class="standard-input"
                            autocomplete="current-password"
                            placeholder="Enter your password"
                            required>
                        <button
                            type="button"
                            class="field-password-toggle toggle-password"
                            data-target="deleteFieldPassword"
                            aria-label="Show password"
                            title="Show password">
                            <i class="fa-regular fa-eye" aria-hidden="true"></i>
                        </button>
                    </div>
                </div>
            </div>

            <div class="field-modal-footer field-delete-footer sector-modal-footer sector-delete-footer">
                <button type="button" class="btn-cancel-solid close-delete-field-modal">Cancel</button>
                <button type="submit" class="btn-delete-solid">
                    <i class="ri-delete-bin-line" aria-hidden="true"></i>
                    <span>Delete Field</span>
                </button>
            </div>
        </form>
    </div>
</div>
