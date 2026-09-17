<div class="modal-overlay field-modal-overlay" id="editFieldModal" aria-hidden="true">
    <div class="modal-content field-modal" role="dialog" aria-modal="true" aria-labelledby="editFieldModalTitle">
        <div class="field-modal-header">
            <h3 id="editFieldModalTitle">Edit Field</h3>
            <button type="button" class="field-modal-close close-edit-field-modal" aria-label="Close" title="Close">
                <i class="ri-close-line" aria-hidden="true"></i>
            </button>
        </div>

        <form class="field-modal-form" id="editFieldForm" method="POST" action="/fields">
            <input type="hidden" name="action" value="edit_field">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="field_id" id="editFieldId" value="">

            <div class="field-form-field">
                <label for="editFieldName">Field name <span class="required" aria-hidden="true">*</span></label>
                <input id="editFieldName" type="text" name="name" maxlength="255" autocomplete="off" required>
            </div>

            <div class="field-form-row">
                <div class="field-form-field">
                    <label for="editFieldLocation">Location</label>
                    <input id="editFieldLocation" type="text" name="location" maxlength="255" autocomplete="address-level2">
                </div>
                <div class="field-form-field">
                    <label for="editFieldArea">Total area <span class="field-unit">ha</span></label>
                    <input id="editFieldArea" type="number" name="area" min="0" step="0.01" inputmode="decimal">
                </div>
            </div>

            <div class="field-modal-footer">
                <button type="button" class="field-btn-secondary close-edit-field-modal">Cancel</button>
                <button type="submit" class="field-btn-primary"><i class="ri-save-line" aria-hidden="true"></i> Save Changes</button>
            </div>
        </form>
    </div>
</div>
