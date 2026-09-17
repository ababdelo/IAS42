<div class="modal-overlay field-modal-overlay" id="addFieldModal" aria-hidden="true">
    <div class="modal-content field-modal" role="dialog" aria-modal="true" aria-labelledby="addFieldModalTitle">
        <div class="field-modal-header">
            <h3 id="addFieldModalTitle">Add New Field</h3>
            <button type="button" class="field-modal-close close-field-modal" aria-label="Close" title="Close">
                <i class="ri-close-line" aria-hidden="true"></i>
            </button>
        </div>

        <form class="field-modal-form" id="addFieldForm" method="POST" action="/fields">
            <input type="hidden" name="action" value="add_field">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8') ?>">

            <p class="field-modal-description">Create a field to organize its sectors and connected devices.</p>

            <div class="field-form-field">
                <label for="addFieldName">Field name <span class="required" aria-hidden="true">*</span></label>
                <input id="addFieldName" type="text" name="name" placeholder="e.g. South Farm" maxlength="255" autocomplete="off" required>
            </div>

            <div class="field-form-row">
                <div class="field-form-field">
                    <label for="addFieldLocation">Location</label>
                    <input id="addFieldLocation" type="text" name="location" placeholder="e.g. Agadir" maxlength="255" autocomplete="address-level2">
                </div>
                <div class="field-form-field">
                    <label for="addFieldArea">Total area <span class="field-unit">ha</span></label>
                    <input id="addFieldArea" type="number" name="area" min="0" step="0.01" placeholder="0.00" inputmode="decimal">
                </div>
            </div>

            <div class="field-modal-footer">
                <button type="button" class="field-btn-secondary close-field-modal">Cancel</button>
                <button type="submit" class="field-btn-primary"><i class="ri-check-line" aria-hidden="true"></i> Save Field</button>
            </div>
        </form>
    </div>
</div>
