<?php
if (!isset($cropFiles) || !is_array($cropFiles)) {
    throw new RuntimeException('Edit sector modal requires $cropFiles.');
}
?>

<div
    class="modal-overlay sector-modal-overlay"
    id="editSectorModal"
    aria-hidden="true">
    <div
        class="modal-content sector-modal sector-modal--edit"
        role="dialog"
        aria-modal="true"
        aria-labelledby="editSectorModalTitle">
        <div class="sector-modal-header">
            <h3
                id="editSectorModalTitle"
                data-i18n="sectors.modal.edit.title">
                Edit Sector
            </h3>

            <button
                type="button"
                class="sector-modal-close close-edit-modal"
                aria-label="Close"
                title="Close"
                data-i18n-title="sectors.modal.common.close_title">
                <i class="ri-close-line" aria-hidden="true"></i>
            </button>
        </div>

        <form
            class="sector-modal-form"
            id="editSectorForm"
            method="POST"
            action=""
            enctype="multipart/form-data">
            <input
                type="hidden"
                name="action"
                value="edit_sector">

            <input
                type="hidden"
                name="csrf_token"
                value="<?= htmlspecialchars(
                            $_SESSION['csrf_token'] ?? '',
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>">

            <input
                type="hidden"
                name="sector_id"
                id="editSectorId"
                value="">

            <input
                type="hidden"
                name="sector_image"
                id="editSelectedImage"
                value="">

            <input
                type="hidden"
                name="image_mode"
                id="editImageMode"
                value="keep">

            <div class="sector-form-field">
                <label for="editSectorName">
                    <span data-i18n="sectors.modal.edit.sector_name">
                        Sector name
                    </span>
                    <span class="required" aria-hidden="true">*</span>
                </label>

                <input
                    id="editSectorName"
                    type="text"
                    name="sector_name"
                    class="standard-input"
                    maxlength="255"
                    autocomplete="off"
                    required>
            </div>

            <div class="sector-form-row sector-form-row--triple">
                <div class="sector-form-field">
                    <label for="editNodeId">
                        <span data-i18n="sectors.modal.edit.node_id">
                            ESP node ID
                        </span>
                        <span class="required" aria-hidden="true">*</span>
                    </label>

                    <input
                        id="editNodeId"
                        type="text"
                        name="node_id"
                        class="standard-input"
                        maxlength="50"
                        autocomplete="off"
                        autocapitalize="characters"
                        spellcheck="false"
                        required>
                </div>

                <div class="sector-form-field">
                    <label for="editMacAddress">
                        <span data-i18n="sectors.modal.edit.mac_address">
                            MAC address
                        </span>
                        <span class="required" aria-hidden="true">*</span>
                    </label>

                    <input
                        id="editMacAddress"
                        type="text"
                        name="mac_address"
                        class="standard-input"
                        placeholder="AA:BB:CC:DD:EE:FF"
                        data-i18n-placeholder="sectors.modal.edit.mac_address_placeholder"
                        maxlength="17"
                        inputmode="text"
                        autocomplete="off"
                        autocapitalize="characters"
                        spellcheck="false"
                        required>
                </div>

                <div class="sector-form-field">
                    <label for="editCropSelect">
                        <span data-i18n="sectors.modal.edit.crop">
                            Crop
                        </span>
                        <span class="required" aria-hidden="true">*</span>
                    </label>

                    <select
                        id="editCropSelect"
                        name="crop_type"
                        class="standard-input custom-select"
                        required>
                        <option
                            value=""
                            disabled
                            data-i18n="sectors.modal.edit.crop_placeholder">
                            Choose a crop
                        </option>

                        <?php foreach ($cropFiles as $crop): ?>
                            <?php
                            $cropValue = (string)$crop;
                            $cropLabel = ucwords(
                                str_replace('-', ' ', $cropValue)
                            );
                            ?>

                            <option
                                value="<?= htmlspecialchars(
                                            $cropValue,
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>">
                                <?= htmlspecialchars(
                                    $cropLabel,
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>
                            </option>
                        <?php endforeach; ?>

                        <option
                            value="other"
                            data-i18n="sectors.modal.edit.custom_crop_option">
                            Custom crop
                        </option>
                    </select>
                </div>
            </div>

            <div
                class="sector-form-field custom-crop-field"
                id="editCustomCropField"
                hidden>
                <label for="editCustomCrop">
                    <span data-i18n="sectors.modal.edit.custom_crop_name">
                        Custom crop name
                    </span>
                    <span class="required" aria-hidden="true">*</span>
                </label>

                <input
                    id="editCustomCrop"
                    type="text"
                    name="custom_crop"
                    class="standard-input"
                    placeholder="e.g. Dragon fruit"
                    data-i18n-placeholder="sectors.modal.edit.custom_crop_name_placeholder"
                    maxlength="100"
                    autocomplete="off"
                    disabled>
            </div>

            <section
                class="sector-image-picker"
                aria-labelledby="editSectorImageTitle">
                <div class="sector-image-picker-header">
                    <h4
                        id="editSectorImageTitle"
                        data-i18n="sectors.modal.edit.image_title">
                        Sector image
                    </h4>
                </div>

                <div
                    class="sector-image-tabs"
                    role="tablist"
                    aria-label="Sector image source"
                    data-i18n-aria-label="sectors.modal.common.image_source">
                    <button
                        type="button"
                        class="sector-image-tab is-active"
                        id="editDefaultImagesTab"
                        role="tab"
                        aria-selected="true"
                        aria-controls="editDefaultImagesPanel"
                        data-image-tab="default"
                        data-i18n="sectors.modal.edit.default_images">
                        Default images
                    </button>

                    <button
                        type="button"
                        class="sector-image-tab"
                        id="editUploadImageTab"
                        role="tab"
                        aria-selected="false"
                        aria-controls="editUploadImagePanel"
                        data-image-tab="upload"
                        data-i18n="sectors.modal.edit.upload_image">
                        Upload image
                    </button>
                </div>

                <div
                    class="sector-image-panel is-active"
                    id="editDefaultImagesPanel"
                    role="tabpanel"
                    aria-labelledby="editDefaultImagesTab"
                    data-image-panel="default">
                    <div
                        class="default-images-grid"
                        id="editImgGrid">
                        <?php foreach ($cropFiles as $crop): ?>
                            <?php
                            $cropValue = (string)$crop;
                            $cropFile = $cropValue . '.webp';
                            $cropLabel = ucwords(
                                str_replace('-', ' ', $cropValue)
                            );
                            ?>

                            <button
                                type="button"
                                class="grid-img-card"
                                data-image-file="<?= htmlspecialchars(
                                                        $cropFile,
                                                        ENT_QUOTES,
                                                        'UTF-8'
                                                    ) ?>"
                                data-crop-value="<?= htmlspecialchars(
                                                        $cropValue,
                                                        ENT_QUOTES,
                                                        'UTF-8'
                                                    ) ?>"
                                aria-pressed="false">
                                <img
                                    src="/assets/imgs/plants/<?= htmlspecialchars(
                                                                    $cropFile,
                                                                    ENT_QUOTES,
                                                                    'UTF-8'
                                                                ) ?>"
                                    alt="<?= htmlspecialchars(
                                                $cropLabel,
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ) ?>"
                                    loading="lazy">

                                <span>
                                    <?= htmlspecialchars(
                                        $cropLabel,
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </span>
                            </button>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div
                    class="sector-image-panel"
                    id="editUploadImagePanel"
                    role="tabpanel"
                    aria-labelledby="editUploadImageTab"
                    data-image-panel="upload"
                    hidden>
                    <div
                        class="sector-upload-dropzone"
                        id="editUploadDropzone"
                        role="button"
                        tabindex="0"
                        aria-describedby="editUploadHint">
                        <input
                            id="editSectorImageFile"
                            type="file"
                            name="sector_image_file"
                            accept="image/jpeg,image/png,image/webp"
                            hidden>

                        <div
                            class="sector-upload-empty"
                            id="editUploadEmpty">
                            <span
                                class="sector-upload-dropzone__icon"
                                aria-hidden="true">
                                <i class="ri-upload-cloud-2-line"></i>
                            </span>

                            <strong
                                data-i18n="sectors.modal.edit.upload_drop_title">
                                Drop an image here
                            </strong>

                            <span
                                data-i18n="sectors.modal.edit.upload_drop_or">
                                or click to browse
                            </span>

                            <small
                                id="editUploadHint"
                                data-i18n="sectors.modal.edit.upload_hint">
                                JPG, PNG or WebP · maximum 2 MB
                            </small>
                        </div>

                        <div
                            class="sector-upload-preview"
                            id="editUploadPreview"
                            hidden>
                            <div class="sector-upload-preview__image-wrap">
                                <img
                                    id="editUploadPreviewImage"
                                    src=""
                                    alt="">
                            </div>

                            <div class="sector-upload-preview__meta">
                                <strong id="editUploadFileName"></strong>

                                <span
                                    data-i18n="sectors.modal.edit.upload_ready">
                                    Image ready to replace the current image
                                </span>
                            </div>

                            <button
                                type="button"
                                class="sector-upload-preview__remove"
                                id="editRemoveUpload"
                                aria-label="Remove selected image"
                                title="Remove selected image"
                                data-i18n-title="sectors.modal.add.remove_image_title">
                                <i
                                    class="ri-close-line"
                                    aria-hidden="true"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </section>

            <div class="sector-modal-footer">
                <button
                    type="button"
                    class="btn-cancel-solid close-edit-modal"
                    data-i18n="sectors.modal.common.cancel">
                    Cancel
                </button>

                <button
                    type="submit"
                    class="btn-submit-solid">
                    <span data-i18n="sectors.modal.edit.submit">
                        Save Changes
                    </span>

                    <i
                        class="ri-check-line"
                        aria-hidden="true"></i>
                </button>
            </div>
        </form>
    </div>
</div>
